<?php

namespace App\Http\Controllers;

use App\Models\KelompokTani;
use App\Models\LogAktivitas;
use App\Models\Pengaturan;
use App\Models\Pinjaman;
use App\Services\PencairanPinjaman;
use App\Services\SetoranPinjaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PinjamanController extends Controller
{
    /**
     * Daftar dibuat per kelompok tani, bukan per transaksi: setiap kelompok
     * yang pernah meminjam tampil satu baris, termasuk yang sudah lunas.
     */
    public function index(Request $request): View
    {
        $status = $request->string('status')->trim()->value();
        $cari = $request->string('cari')->trim()->value();
        $perHalaman = $request->integer('per_halaman', 10) ?: 10;

        $daftar = KelompokTani::query()
            ->whereHas('pinjaman')
            ->with('pinjaman.pembayaran')
            ->when($cari, fn ($query) => $query->where('nama_kelompok', 'like', "%{$cari}%"))
            ->orderBy('nama_kelompok')
            ->get()
            ->when($status, fn ($daftar) => $daftar->filter(fn (KelompokTani $kelompok) => $kelompok->status_pinjaman === $status))
            ->values();

        $halaman = LengthAwarePaginator::resolveCurrentPage();

        return view('pinjaman.index', [
            'daftarKelompok' => (new LengthAwarePaginator(
                $daftar->forPage($halaman, $perHalaman)->values(),
                $daftar->count(),
                $perHalaman,
                $halaman,
                ['path' => $request->url(), 'query' => $request->query()],
            )),
            'totalPokok' => Pinjaman::sum('jumlah_pinjaman'),
            'totalSisaPokok' => Pinjaman::with('pembayaran')->where('status', '!=', Pinjaman::STATUS_LUNAS)
                ->get()->sum(fn (Pinjaman $pinjaman) => $pinjaman->sisa_pokok),
        ]);
    }

    /**
     * Detail pinjaman satu kelompok: semua pinjaman dan setorannya dalam
     * satu daftar transaksi.
     */
    public function kelompok(Request $request, KelompokTani $kelompok): View
    {
        $kelompok->load(['pinjaman' => fn ($query) => $query->with('pembayaran')->orderBy('tanggal_transaksi')->orderBy('id')]);
        $jenis = $request->string('jenis')->trim()->value();

        $pinjaman = $kelompok->pinjaman->map(fn (Pinjaman $pinjaman) => [
            'jenis' => 'pinjaman',
            'tanggal' => $pinjaman->tanggal_transaksi,
            'urutan' => $pinjaman->created_at,
            'kode' => $pinjaman->kode,
            'deskripsi' => $pinjaman->deskripsi,
            'jumlah' => (float) $pinjaman->jumlah_pinjaman,
            'pinjaman' => $pinjaman,
        ]);

        $setoran = $kelompok->pinjaman
            ->flatMap(fn (Pinjaman $pinjaman) => $pinjaman->pembayaran->each->setRelation('pinjaman', $pinjaman))
            ->groupBy(fn ($baris) => $baris->kode_setoran ?? $baris->kode)
            ->map(fn ($grup, $kode) => [
                'jenis' => 'setoran',
                'tanggal' => $grup->first()->tanggal,
                'urutan' => $grup->min('created_at'),
                'kode' => $kode,
                'deskripsi' => $grup->first()->deskripsi,
                'jumlah' => (float) $grup->sum('total_bayar'),
                'bunga' => (float) $grup->sum('bunga_nominal'),
                'pokok' => (float) $grup->sum('bayar_pokok'),
                'baris' => $grup->sortBy('id')->values(),
            ])
            ->values();

        $layanan = new SetoranPinjaman($kelompok);

        return view('pinjaman.kelompok', [
            'jenis' => $jenis,
            'kelompok' => $kelompok,
            'daftarTransaksi' => (match ($jenis) {
                'pinjaman' => $pinjaman,
                'setoran' => $setoran,
                default => $pinjaman->concat($setoran),
            })->sortByDesc(fn (array $baris) => $baris['tanggal']->format('Ymd').$baris['urutan']?->format('YmdHis'))->values(),
            'totalPinjaman' => (float) $kelompok->pinjaman->sum('jumlah_pinjaman'),
            'totalSisaPokok' => $kelompok->total_pinjaman,
            'totalBunga' => $layanan->totalBungaTerhutang(),
            'totalSetoran' => (float) $setoran->sum('jumlah'),
        ]);
    }

    public function create(): View
    {
        return view('pinjaman.create', $this->dataForm());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $pinjaman = DB::transaction(function () use ($data, $request) {
            // Kunci baris tunggal pengaturan sebelum membaca urutan pinjaman.
            $pengaturan = Pengaturan::lockForUpdate()->orderBy('id')->first() ?? Pengaturan::ambil();
            $kelompok = KelompokTani::with('pinjaman.pembayaran')->findOrFail($data['kelompok_tani_id']);

            if (! $kelompok->bisaMengajukanPinjaman()) {
                throw ValidationException::withMessages([
                    'kelompok_tani_id' => $kelompok->pesanTidakBisaMeminjam(),
                ]);
            }

            $persenTalangan = (float) $pengaturan->potongan_dana_talangan;
            $persenPotongan = (float) $pengaturan->potongan_operasional + $persenTalangan;
            $potongan = round((float) $data['jumlah_pinjaman'] * $persenPotongan / 100, 2);
            $pinjaman = Pinjaman::create([
                ...$data,
                'kode' => $this->kodeBerikutnya(),
                'tanggal_jatuh_tempo_awal' => $data['tanggal_jatuh_tempo'],
                'termin' => 1,
                'potongan_persen' => $persenPotongan,
                'potongan_talangan_persen' => $persenTalangan,
                'potongan_nominal' => $potongan,
                'jumlah_diterima' => (float) $data['jumlah_pinjaman'] - $potongan,
                'bunga_persen' => (float) $pengaturan->suku_bunga_pinjaman,
                'tunggakan_bunga' => 0,
                'status' => Pinjaman::STATUS_BELUM_BAYAR,
                'user_id' => $request->user()->id,
            ]);

            (new PencairanPinjaman($pinjaman))->sinkronkan($request->user());

            // Pinjaman bertanggal mundur bisa mengubah alokasi FIFO setoran lama.
            (new SetoranPinjaman($pinjaman->kelompokTani))->hitungUlang($request->user());

            return $pinjaman;
        }, 3);

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_INPUT,
            'Membuat pinjaman '.rupiah($pinjaman->jumlah_pinjaman).' untuk '.$pinjaman->kelompokTani->nama_kelompok,
            $pinjaman->kode,
        );

        return redirect()
            ->route('pinjaman.show', $pinjaman)
            ->with('sukses', 'Pinjaman berhasil dibuat. Kontribusi dana talangan '
                .rupiah(round((float) $pinjaman->jumlah_pinjaman * (float) $pinjaman->potongan_talangan_persen / 100, 2))
                .' ikut tercatat.');
    }

    public function show(Pinjaman $pinjaman): View
    {
        $pinjaman->load([
            'kelompokTani',
            'pembayaran' => fn ($query) => $query->latest('tanggal')->latest('id'),
            'danaTalangan',
        ]);

        return view('pinjaman.show', [
            'pinjaman' => $pinjaman,
            'bungaTerhutang' => $pinjaman->bungaTerhutang(),
            'bulanBerjalan' => $pinjaman->bulanBungaBerjalan(),
        ]);
    }

    public function faktur(Pinjaman $pinjaman): View
    {
        $pinjaman->load('kelompokTani');

        return view('pinjaman.faktur', [
            'pinjaman' => $pinjaman,
        ]);
    }

    public function edit(Pinjaman $pinjaman): View|RedirectResponse
    {
        if (! $pinjaman->bisaDiubah()) {
            return redirect()->route('pinjaman.show', $pinjaman)->with('gagal', $this->pesanTerkunci());
        }

        return view('pinjaman.edit', [...$this->dataForm(), 'pinjaman' => $pinjaman]);
    }

    public function update(Request $request, Pinjaman $pinjaman): RedirectResponse
    {
        if (! $pinjaman->bisaDiubah()) {
            return redirect()->route('pinjaman.show', $pinjaman)->with('gagal', $this->pesanTerkunci());
        }

        $data = $this->validasi($request);

        // Memindah pinjaman ke kelompok lain tunduk pada syarat yang sama
        // dengan mengajukan pinjaman baru di kelompok itu.
        if ((int) $data['kelompok_tani_id'] !== $pinjaman->kelompok_tani_id) {
            $kelompokBaru = KelompokTani::with('pinjaman.pembayaran')->findOrFail($data['kelompok_tani_id']);

            if (! $kelompokBaru->bisaMengajukanPinjaman()) {
                throw ValidationException::withMessages([
                    'kelompok_tani_id' => $kelompokBaru->pesanTidakBisaMeminjam(),
                ]);
            }
        }

        // Persentase tetap memakai snapshot saat pinjaman dibuat supaya nilai
        // yang sudah dibayar kelompok tidak berubah di belakang layar.
        $potongan = round((float) $data['jumlah_pinjaman'] * (float) $pinjaman->potongan_persen / 100, 2);
        $kelompokLama = $pinjaman->kelompokTani;

        DB::transaction(function () use ($pinjaman, $data, $potongan, $request, $kelompokLama) {
            // Belum ada setoran, jadi termin kembali ke awal dari jatuh tempo baru.
            $pinjaman->update([
                ...$data,
                'tanggal_jatuh_tempo_awal' => $data['tanggal_jatuh_tempo'],
                'termin' => 1,
                'potongan_nominal' => $potongan,
                'jumlah_diterima' => (float) $data['jumlah_pinjaman'] - $potongan,
                'diubah_oleh_id' => $request->user()->id,
            ]);

            (new PencairanPinjaman($pinjaman))->sinkronkan($request->user());
            $pinjaman->segarkanStatus();

            // Tanggal, nominal, atau kelompok yang berubah menggeser alokasi FIFO.
            (new SetoranPinjaman($pinjaman->load('kelompokTani')->kelompokTani))->hitungUlang($request->user());
            if ($kelompokLama->isNot($pinjaman->kelompokTani)) {
                (new SetoranPinjaman($kelompokLama))->hitungUlang($request->user());
            }
        });

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_SUNTING,
            'Mengubah data pinjaman '.$pinjaman->kelompokTani->nama_kelompok,
            $pinjaman->kode,
        );

        return redirect()
            ->route('pinjaman.show', $pinjaman)
            ->with('sukses', 'Data pinjaman berhasil diperbarui.');
    }

    public function destroy(Pinjaman $pinjaman): RedirectResponse
    {
        if (! $pinjaman->bisaDiubah()) {
            return back()->with('gagal', $this->pesanTerkunci());
        }

        $kode = $pinjaman->kode;
        $kelompok = $pinjaman->kelompokTani;
        $nama = $kelompok->nama_kelompok;

        DB::transaction(function () use ($pinjaman) {
            // Baris kas dan talangan turunan ikut dibersihkan.
            $pinjaman->transaksiKas()->delete();
            $pinjaman->danaTalangan()->delete();
            $pinjaman->delete();
        });

        LogAktivitas::catat(LogAktivitas::TINDAKAN_HAPUS, "Menghapus pinjaman {$nama}", $kode);

        return redirect()
            ->route($kelompok->pinjaman()->exists() ? 'pinjaman.kelompok' : 'pinjaman.index', $kelompok)
            ->with('sukses', 'Pinjaman berhasil dihapus beserta transaksi turunannya.');
    }

    private function pesanTerkunci(): string
    {
        return 'Pinjaman yang sudah punya transaksi pembayaran tidak bisa disunting atau dihapus. '
            .'Hapus dulu setorannya di riwayat pembayaran bila datanya keliru.';
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(): array
    {
        $pengaturan = Pengaturan::ambil();

        return [
            'daftarKelompok' => KelompokTani::with('pinjaman.pembayaran')->orderBy('nama_kelompok')->get(),
            'pengaturan' => $pengaturan,
            'persenPotongan' => (float) $pengaturan->potongan_operasional + (float) $pengaturan->potongan_dana_talangan,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        return $request->validate([
            'kelompok_tani_id' => ['required', Rule::exists('kelompok_tani', 'id')],
            'tanggal_transaksi' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['required', 'date', 'after_or_equal:tanggal_transaksi'],
            'jumlah_pinjaman' => ['required', 'integer', 'min:1'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ], attributes: [
            'kelompok_tani_id' => 'kelompok tani',
            'tanggal_transaksi' => 'tanggal transaksi',
            'tanggal_jatuh_tempo' => 'tanggal jatuh tempo',
            'jumlah_pinjaman' => 'jumlah pinjaman',
        ]);
    }

    private function kodeBerikutnya(): string
    {
        $awalan = 'LP-'.now()->year.'-';
        $terakhir = 0;

        foreach (Pinjaman::where('kode', 'like', $awalan.'%')->pluck('kode') as $kode) {
            $urutan = substr($kode, strlen($awalan));

            if (ctype_digit($urutan)) {
                $terakhir = max($terakhir, (int) $urutan);
            }
        }

        return $awalan.sprintf('%03d', $terakhir + 1);
    }
}
