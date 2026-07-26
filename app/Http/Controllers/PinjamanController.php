<?php

namespace App\Http\Controllers;

use App\Models\KelompokTani;
use App\Models\LogAktivitas;
use App\Models\Pengaturan;
use App\Models\Pinjaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PinjamanController extends Controller
{
    public function index(Request $request): View
    {
        $pinjaman = Pinjaman::query()
            ->with(['kelompokTani', 'pembayaran'])
            ->when($request->string('status')->trim()->value(), fn ($query, $status) => $query->where('status', $status))
            ->latest('tanggal_transaksi')
            ->paginate(10)
            ->withQueryString();

        return view('pinjaman.index', [
            'daftarPinjaman' => $pinjaman,
            'totalPokok' => Pinjaman::sum('jumlah_pinjaman'),
        ]);
    }

    public function create(): View
    {
        return view('pinjaman.create', $this->dataForm());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $pengaturan = Pengaturan::ambil();

        // Potongan muka menggabungkan biaya operasional dan kontribusi dana talangan.
        $persenPotongan = (float) $pengaturan->potongan_operasional + (float) $pengaturan->potongan_dana_talangan;
        $potongan = round((float) $data['jumlah_pinjaman'] * $persenPotongan / 100, 2);

        $pinjaman = Pinjaman::create([
            ...$data,
            'kode' => $this->kodeBerikutnya(),
            'potongan_persen' => $persenPotongan,
            'potongan_nominal' => $potongan,
            'jumlah_diterima' => (float) $data['jumlah_pinjaman'] - $potongan,
            'bunga_persen' => (float) $pengaturan->suku_bunga_pinjaman,
            'status' => Pinjaman::STATUS_BELUM_BAYAR,
            'user_id' => $request->user()->id,
        ]);

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_INPUT,
            'Membuat pinjaman '.rupiah($pinjaman->jumlah_pinjaman).' untuk '.$pinjaman->kelompokTani->nama_kelompok,
            $pinjaman->kode,
        );

        return redirect()
            ->route('pinjaman.show', $pinjaman)
            ->with('sukses', 'Pinjaman berhasil dibuat.');
    }

    public function show(Pinjaman $pinjaman): View
    {
        $pinjaman->load(['kelompokTani', 'pembayaran' => fn ($query) => $query->latest('tanggal')]);

        return view('pinjaman.show', [
            'pinjaman' => $pinjaman,
        ]);
    }

    public function faktur(Pinjaman $pinjaman): View
    {
        $pinjaman->load('kelompokTani');

        return view('pinjaman.faktur', [
            'pinjaman' => $pinjaman,
        ]);
    }

    public function edit(Pinjaman $pinjaman): View
    {
        return view('pinjaman.edit', [...$this->dataForm(), 'pinjaman' => $pinjaman]);
    }

    public function update(Request $request, Pinjaman $pinjaman): RedirectResponse
    {
        $data = $this->validasi($request);

        // Persentase tetap memakai snapshot saat pinjaman dibuat supaya nilai
        // yang sudah dibayar kelompok tidak berubah di belakang layar.
        $potongan = round((float) $data['jumlah_pinjaman'] * (float) $pinjaman->potongan_persen / 100, 2);

        $pinjaman->update([
            ...$data,
            'potongan_nominal' => $potongan,
            'jumlah_diterima' => (float) $data['jumlah_pinjaman'] - $potongan,
        ]);

        $pinjaman->segarkanStatus();

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
        if ($pinjaman->pembayaran()->exists()) {
            return back()->with('gagal', 'Pinjaman tidak bisa dihapus karena sudah punya riwayat pembayaran.');
        }

        $kode = $pinjaman->kode;
        $nama = $pinjaman->kelompokTani->nama_kelompok;
        $pinjaman->delete();

        LogAktivitas::catat(LogAktivitas::TINDAKAN_HAPUS, "Menghapus pinjaman {$nama}", $kode);

        return redirect()
            ->route('pinjaman.index')
            ->with('sukses', 'Pinjaman berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(): array
    {
        $pengaturan = Pengaturan::ambil();

        return [
            'daftarKelompok' => KelompokTani::orderBy('nama_kelompok')->get(),
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
            'tanggal_jatuh_tempo' => ['required', 'date', 'after:tanggal_transaksi'],
            'jumlah_pinjaman' => ['required', 'numeric', 'min:1'],
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
        $tahun = now()->year;
        $urutan = Pinjaman::whereYear('created_at', $tahun)->count() + 1;

        return sprintf('LP-%s-%03d', $tahun, $urutan);
    }
}
