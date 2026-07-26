<?php

namespace App\Http\Controllers;

use App\Models\KategoriKas;
use App\Models\LogAktivitas;
use App\Models\PembayaranPinjaman;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PembayaranPinjamanController extends Controller
{
    public function create(Pinjaman $pinjaman): View
    {
        $pinjaman->load(['kelompokTani', 'pembayaran']);

        return view('pinjaman.bayar', [
            'pinjaman' => $pinjaman,
            'bulanBerjalan' => $pinjaman->bulanBungaBerjalan(),
            'bungaBerjalan' => $pinjaman->bungaBerjalan(),
        ]);
    }

    public function store(Request $request, Pinjaman $pinjaman): RedirectResponse
    {
        $pinjaman->load('pembayaran');

        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'bayar_pokok' => ['required', 'numeric', 'min:0', 'max:'.$pinjaman->sisa_pokok],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ], messages: [
            'bayar_pokok.max' => 'Pembayaran pokok tidak boleh melebihi sisa hutang '.rupiah($pinjaman->sisa_pokok).'.',
        ], attributes: [
            'bayar_pokok' => 'jumlah bayar pokok',
        ]);

        $bulanBunga = $pinjaman->bulanBungaBerjalan();
        $bunga = $pinjaman->bungaBerjalan();

        if ((float) $data['bayar_pokok'] <= 0 && $bunga <= 0) {
            return back()->with('gagal', 'Tidak ada yang perlu dibayar untuk pinjaman ini.');
        }

        $pembayaran = DB::transaction(function () use ($pinjaman, $data, $bulanBunga, $bunga, $request) {
            $pembayaran = $pinjaman->pembayaran()->create([
                'kode' => $this->kodeBerikutnya(),
                'tanggal' => $data['tanggal'],
                'bulan_bunga' => $bulanBunga,
                'bunga_nominal' => $bunga,
                'bayar_pokok' => $data['bayar_pokok'],
                'total_bayar' => (float) $data['bayar_pokok'] + $bunga,
                'deskripsi' => $data['deskripsi'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            // Uang yang masuk kembali ke kas koperasi.
            TransaksiKas::create([
                'kode' => sprintf('TXN-%05d', 10000 + TransaksiKas::max('id') + 1),
                'tanggal' => $data['tanggal'],
                'jenis' => TransaksiKas::JENIS_MASUK,
                'kategori_kas_id' => KategoriKas::firstOrCreate(
                    ['nama' => 'Pembayaran Pinjaman'],
                    ['bawaan' => true],
                )->id,
                'kelompok_tani_id' => $pinjaman->kelompok_tani_id,
                'jumlah' => $pembayaran->total_bayar,
                'biaya' => 0,
                'deskripsi' => 'Pembayaran pinjaman '.$pinjaman->kode,
                'user_id' => $request->user()->id,
            ]);

            $pinjaman->segarkanStatus();

            return $pembayaran;
        });

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_INPUT,
            'Pembayaran pinjaman '.rupiah($pembayaran->total_bayar).' dari '.$pinjaman->kelompokTani->nama_kelompok,
            $pembayaran->kode,
        );

        return redirect()
            ->route('pembayaran.faktur', $pembayaran)
            ->with('sukses', 'Pembayaran pinjaman berhasil dicatat.');
    }

    public function faktur(PembayaranPinjaman $pembayaran): View
    {
        $pembayaran->load('pinjaman.kelompokTani');

        return view('pinjaman.faktur-bayar', [
            'pembayaran' => $pembayaran,
        ]);
    }

    private function kodeBerikutnya(): string
    {
        return sprintf('BP-%s-%03d', now()->year, PembayaranPinjaman::whereYear('created_at', now()->year)->count() + 1);
    }
}
