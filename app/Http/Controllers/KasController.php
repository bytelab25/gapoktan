<?php

namespace App\Http\Controllers;

use App\Models\KategoriKas;
use App\Models\KelompokTani;
use App\Models\LogAktivitas;
use App\Models\Pengaturan;
use App\Models\TransaksiKas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KasController extends Controller
{
    public function index(Request $request): View
    {
        $pengaturan = Pengaturan::ambil();

        $transaksi = TransaksiKas::query()
            ->with(['kategori', 'kelompokTani'])
            ->when($request->date('dari'), fn ($query, $dari) => $query->whereDate('tanggal', '>=', $dari))
            ->when($request->date('sampai'), fn ($query, $sampai) => $query->whereDate('tanggal', '<=', $sampai))
            ->when($request->string('jenis')->trim()->value(), fn ($query, $jenis) => $query->where('jenis', $jenis))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate($request->integer('per_halaman', 10))
            ->withQueryString();

        return view('kas.index', [
            'daftarTransaksi' => $transaksi,
            'saldo' => TransaksiKas::saldoTotal(),
            'totalPengeluaran' => TransaksiKas::where('jenis', TransaksiKas::JENIS_KELUAR)->sum('jumlah'),
            'pengaturan' => $pengaturan,
        ]);
    }

    public function create(): View
    {
        return view('kas.create', $this->dataForm());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $data['kode'] = $this->kodeBerikutnya();
        $data['user_id'] = $request->user()->id;
        $data['biaya'] = $this->hitungBiaya($data['jumlah']);

        $transaksi = TransaksiKas::create($data);

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_INPUT,
            'Menambahkan transaksi kas '.rupiah($transaksi->jumlah),
            $transaksi->kode,
        );

        return redirect()
            ->route('kas.show', $transaksi)
            ->with('sukses', 'Transaksi kas berhasil dicatat.');
    }

    public function show(TransaksiKas $kas): View
    {
        return view('kas.show', [
            'transaksi' => $kas,
            'saldoSetelah' => TransaksiKas::saldoSampai($kas),
        ]);
    }

    public function faktur(TransaksiKas $kas): View
    {
        return view('kas.faktur', [
            'transaksi' => $kas,
            'saldoSetelah' => TransaksiKas::saldoSampai($kas),
        ]);
    }

    public function edit(TransaksiKas $kas): View|RedirectResponse
    {
        if ($kas->otomatis) {
            return redirect()->route('kas.show', $kas)->with('gagal', $this->pesanOtomatis());
        }

        return view('kas.edit', [...$this->dataForm(), 'transaksi' => $kas]);
    }

    public function update(Request $request, TransaksiKas $kas): RedirectResponse
    {
        if ($kas->otomatis) {
            return back()->with('gagal', $this->pesanOtomatis());
        }

        $data = $this->validasi($request);
        $data['biaya'] = $this->hitungBiaya($data['jumlah']);

        $kas->update($data);

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_SUNTING,
            'Mengubah transaksi kas '.rupiah($kas->jumlah),
            $kas->kode,
        );

        return redirect()
            ->route('kas.show', $kas)
            ->with('sukses', 'Transaksi kas berhasil diperbarui.');
    }

    public function destroy(TransaksiKas $kas): RedirectResponse
    {
        if ($kas->otomatis) {
            return back()->with('gagal', $this->pesanOtomatis());
        }

        $kode = $kas->kode;
        $jumlah = rupiah($kas->jumlah);
        $kas->delete();

        LogAktivitas::catat(LogAktivitas::TINDAKAN_HAPUS, "Menghapus transaksi kas {$jumlah}", $kode);

        return redirect()
            ->route('kas.index')
            ->with('sukses', 'Transaksi kas berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(): array
    {
        return [
            'daftarKategori' => KategoriKas::untukPengguna()->orderBy('nama')->get(),
            'daftarKelompok' => KelompokTani::orderBy('nama_kelompok')->get(),
            'pengaturan' => Pengaturan::ambil(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        return $request->validate([
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', Rule::in([TransaksiKas::JENIS_MASUK, TransaksiKas::JENIS_KELUAR])],
            'kategori_kas_id' => ['required', Rule::exists('kategori_kas', 'id')],
            'kelompok_tani_id' => ['nullable', Rule::exists('kelompok_tani', 'id')],
            'jumlah' => ['required', 'integer', 'min:1'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ], attributes: [
            'kategori_kas_id' => 'kategori',
            'kelompok_tani_id' => 'kelompok tani',
        ]);
    }

    /**
     * Biaya operasional yang menempel pada transaksi, memakai persentase yang
     * berlaku saat ini lalu disimpan sebagai snapshot.
     */
    private function hitungBiaya(float|string $jumlah): float
    {
        return round((float) $jumlah * (float) Pengaturan::ambil()->potongan_operasional / 100, 2);
    }

    private function kodeBerikutnya(): string
    {
        return sprintf('TXN-%05d', 10000 + (int) TransaksiKas::max('id') + 1);
    }

    private function pesanOtomatis(): string
    {
        return 'Transaksi ini dibuat otomatis dari pencairan pinjaman. Ubah data pinjamannya bila nominalnya salah.';
    }
}
