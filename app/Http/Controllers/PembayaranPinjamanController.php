<?php

namespace App\Http\Controllers;

use App\Models\KelompokTani;
use App\Models\LogAktivitas;
use App\Models\PembayaranPinjaman;
use App\Models\Pinjaman;
use App\Services\SetoranPinjaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PembayaranPinjamanController extends Controller
{
    /**
     * Pilih kelompok dulu bila belum ditentukan, mis. dari pintasan topbar.
     */
    public function pilih(): View
    {
        return view('pinjaman.pilih-kelompok', [
            'daftarKelompok' => KelompokTani::query()
                ->withCount([
                    'pinjaman as pinjaman_aktif_count' => fn ($query) => $query
                        ->where('status', '!=', Pinjaman::STATUS_LUNAS),
                    // Hanya pinjaman yang terminnya sudah genap yang bisa disetor.
                    'pinjaman as pinjaman_bisa_dibayar_count' => fn ($query) => $query
                        ->where('status', '!=', Pinjaman::STATUS_LUNAS)
                        ->whereDate('tanggal_transaksi', '<=', now()->subMonths(Pinjaman::BULAN_PER_TERMIN)),
                ])
                ->orderBy('nama_kelompok')
                ->get(),
        ]);
    }

    public function create(KelompokTani $kelompok): View
    {
        $layanan = new SetoranPinjaman($kelompok);

        return view('pinjaman.bayar', [
            'kelompok' => $kelompok,
            'tagihan' => $layanan->tagihan(),
            'belumJatuhTermin' => $layanan->belumJatuhTermin(),
            'totalSisaPokok' => $layanan->totalSisaPokok(),
            'totalBunga' => $layanan->totalBungaTerhutang(),
            'totalPelunasan' => $layanan->totalPelunasan(),
        ]);
    }

    public function store(Request $request, KelompokTani $kelompok): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ], attributes: [
            'jumlah' => 'jumlah setoran',
        ]);

        $tanggal = Carbon::parse($data['tanggal']);
        $layanan = new SetoranPinjaman($kelompok);
        $maksimal = $layanan->totalPelunasan($tanggal);

        if ($maksimal < 0.01) {
            $tertahan = $layanan->belumJatuhTermin($tanggal);

            return back()->with('gagal', $tertahan->isEmpty()
                ? 'Kelompok ini tidak punya tagihan yang perlu dibayar.'
                : 'Pinjaman kelompok ini belum genap '.Pinjaman::BULAN_PER_TERMIN
                    .' bulan, jadi belum bisa disetor. Paling cepat '
                    .tanggal_id($tertahan->min(fn ($pinjaman) => $pinjaman->jatuhTempoPertama()), 'd F Y').'.');
        }

        if ((float) $data['jumlah'] - $maksimal > 0.01) {
            throw ValidationException::withMessages([
                'jumlah' => 'Setoran melebihi total tagihan '.rupiah($maksimal).'.',
            ]);
        }

        $baris = $layanan->catat($tanggal, (float) $data['jumlah'], $data['deskripsi'] ?? null, $request->user());

        if ($baris->isEmpty()) {
            return back()->with('gagal', 'Tidak ada tagihan yang bisa dialokasikan untuk setoran ini.');
        }

        $pertama = $baris->first();

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_INPUT,
            'Setoran pembayaran pinjaman '.rupiah((float) $baris->sum('total_bayar'))
                .' dari '.$kelompok->nama_kelompok
                .($baris->count() > 1 ? ' (terbagi ke '.$baris->count().' pinjaman)' : ''),
            $pertama->kode_setoran,
        );

        return redirect()
            ->route('pembayaran.faktur', $pertama)
            ->with('sukses', 'Setoran berhasil dicatat.');
    }

    public function faktur(PembayaranPinjaman $pembayaran): View
    {
        return view('pinjaman.faktur-bayar', [
            'pembayaran' => $pembayaran,
            'daftarBaris' => $pembayaran->satuSetoran(),
        ]);
    }

}
