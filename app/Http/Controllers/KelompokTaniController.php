<?php

namespace App\Http\Controllers;

use App\Models\KelompokTani;
use App\Models\LogAktivitas;
use App\Models\Pinjaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelompokTaniController extends Controller
{
    public function index(Request $request): View
    {
        $kelompok = KelompokTani::query()
            ->with('pinjaman.pembayaran')
            ->when($request->string('cari')->trim()->value(), fn ($query, $cari) => $query
                ->where('nama_kelompok', 'like', "%{$cari}%")
                ->orWhere('nama_ketua', 'like', "%{$cari}%"))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('kelompok.index', [
            'daftarKelompok' => $kelompok,
            'totalPinjaman' => $kelompok->sum('total_pinjaman'),
        ]);
    }

    public function create(): View
    {
        return view('kelompok.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $data['kode'] = $this->kodeBerikutnya();

        $kelompok = KelompokTani::create($data);

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_INPUT,
            "Menambahkan kelompok tani {$kelompok->nama_kelompok}",
            $kelompok->kode,
        );

        return redirect()
            ->route('kelompok.show', $kelompok)
            ->with('sukses', 'Kelompok tani berhasil dibuat.');
    }

    public function show(KelompokTani $kelompok): View
    {
        return view('kelompok.show', [
            'kelompok' => $kelompok,
        ]);
    }

    public function transaksi(KelompokTani $kelompok): View
    {
        $pinjaman = $kelompok->pinjaman()
            ->with('pembayaran')
            ->latest('tanggal_transaksi')
            ->paginate(10);

        return view('kelompok.transaksi', [
            'kelompok' => $kelompok,
            'daftarPinjaman' => $pinjaman,
            'totalPinjaman' => $kelompok->pinjaman()->sum('jumlah_pinjaman'),
            'totalBunga' => $kelompok->pinjaman()
                ->join('pembayaran_pinjaman', 'pinjaman.id', '=', 'pembayaran_pinjaman.pinjaman_id')
                ->sum('pembayaran_pinjaman.bunga_nominal'),
        ]);
    }

    public function edit(KelompokTani $kelompok): View
    {
        return view('kelompok.edit', [
            'kelompok' => $kelompok,
        ]);
    }

    public function update(Request $request, KelompokTani $kelompok): RedirectResponse
    {
        $kelompok->update($this->validasi($request, $kelompok));

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_SUNTING,
            "Mengubah data kelompok tani {$kelompok->nama_kelompok}",
            $kelompok->kode,
        );

        return redirect()
            ->route('kelompok.show', $kelompok)
            ->with('sukses', 'Data kelompok tani berhasil diperbarui.');
    }

    public function destroy(KelompokTani $kelompok): RedirectResponse
    {
        // Riwayat keuangan kelompok harus tetap utuh, jadi penghapusan ditolak
        // selama masih ada pinjaman yang tercatat.
        if ($kelompok->pinjaman()->exists()) {
            return back()->with('gagal', 'Kelompok tani tidak bisa dihapus karena masih punya riwayat pinjaman.');
        }

        $nama = $kelompok->nama_kelompok;
        $kode = $kelompok->kode;
        $kelompok->delete();

        LogAktivitas::catat(LogAktivitas::TINDAKAN_HAPUS, "Menghapus kelompok tani {$nama}", $kode);

        return redirect()
            ->route('kelompok.index')
            ->with('sukses', 'Kelompok tani berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?KelompokTani $kelompok = null): array
    {
        return $request->validate([
            'nama_kelompok' => ['required', 'string', 'max:255'],
            'nama_ketua' => ['required', 'string', 'max:255'],
            'nik_ketua' => ['required', 'digits:16'],
            'no_hp' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string', 'max:255'],
        ], attributes: [
            'nama_kelompok' => 'nama kelompok tani',
            'nama_ketua' => 'nama ketua',
            'nik_ketua' => 'NIK ketua',
            'no_hp' => 'nomor HP',
        ]);
    }

    private function kodeBerikutnya(): string
    {
        return sprintf('KT-%03d', KelompokTani::max('id') + 1);
    }
}
