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
                    // Setoran tersedia sejak tanggal pencairan.
                    'pinjaman as pinjaman_bisa_dibayar_count' => fn ($query) => $query
                        ->where('status', '!=', Pinjaman::STATUS_LUNAS)
                        ->whereDate('tanggal_transaksi', '<=', now()->toDateString()),
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
            'belumDicairkan' => $layanan->belumDicairkan(),
            'totalSisaPokok' => $layanan->totalSisaPokok(),
            'totalBunga' => $layanan->totalBungaTerhutang(),
            'totalPelunasan' => $layanan->totalPelunasan(),
        ]);
    }

    public function store(Request $request, KelompokTani $kelompok): RedirectResponse
    {
        $data = $this->validasi($request);

        $tanggal = Carbon::parse($data['tanggal']);
        $layanan = new SetoranPinjaman($kelompok);
        $jumlah = (float) $data['jumlah'];
        $hanyaBunga = $request->boolean('hanya_bunga');

        // Tagihan hari ini hanya tepat bila tidak ada setoran yang lebih baru.
        // Setoran bertanggal mundur divalidasi saat seluruh riwayat diputar ulang.
        if (! $layanan->adaSetoranSesudah($tanggal)) {
            $maksimal = $layanan->batasSetoran($tanggal, $hanyaBunga);

            if ($hanyaBunga && $maksimal < 0.01 && $layanan->totalPelunasan($tanggal) >= 0.01) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Belum ada bunga terhutang pada tanggal ini. Bunga dihitung per bulan penuh sejak setoran terakhir.',
                ]);
            }

            if ($maksimal < 0.01) {
                $tertahan = $layanan->belumDicairkan($tanggal);

                return back()->withInput()->with('gagal', $tertahan->isEmpty()
                    ? 'Kelompok ini tidak punya tagihan yang perlu dibayar.'
                    : 'Tanggal setoran mendahului pencairan pinjaman. Paling cepat '
                        .tanggal_id($tertahan->min('tanggal_transaksi'), 'd F Y').'.');
            }

            // Setoran selalu rupiah bulat; selisih di bawah Rp 1 akibat pembulatan
            // tagihan ke atas dipangkas, selebihnya dianggap kelebihan bayar.
            if ($jumlah - $maksimal >= 1) {
                throw ValidationException::withMessages([
                    'jumlah' => $hanyaBunga
                        ? 'Setoran bunga saja melebihi total bunga '.rupiah(ceil($maksimal)).'.'
                        : 'Setoran melebihi total tagihan '.rupiah(ceil($maksimal)).'.',
                ]);
            }
        }

        $baris = $layanan->catat($tanggal, $jumlah, $data['deskripsi'] ?? null, $request->user(), $hanyaBunga);

        if ($baris->isEmpty()) {
            return back()->with('gagal', 'Tidak ada tagihan yang bisa dialokasikan untuk setoran ini.');
        }

        $pertama = $baris->first();

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_INPUT,
            'Setoran '.($hanyaBunga ? 'bunga ' : '').'pembayaran pinjaman '.rupiah((float) $baris->sum('total_bayar'))
                .' dari '.$kelompok->nama_kelompok
                .($baris->count() > 1 ? ' (terbagi ke '.$baris->count().' pinjaman)' : ''),
            $pertama->kode_setoran,
        );

        // Faktur tidak dibuka otomatis; bisa dicetak kapan saja dari riwayat pembayaran.
        return redirect()
            ->route('pinjaman.kelompok', $kelompok)
            ->with('sukses', 'Setoran '.$pertama->kode_setoran.' berhasil dicatat.');
    }

    public function edit(PembayaranPinjaman $pembayaran): View
    {
        $pembayaran->load('pinjaman.kelompokTani');
        $daftarBaris = $pembayaran->satuSetoran();

        return view('pinjaman.sunting-setoran', [
            'pembayaran' => $pembayaran,
            'kelompok' => $pembayaran->pinjaman->kelompokTani,
            'daftarBaris' => $daftarBaris,
            'jumlah' => (float) $daftarBaris->sum('total_bayar'),
        ]);
    }

    public function update(Request $request, PembayaranPinjaman $pembayaran): RedirectResponse
    {
        $data = $this->validasi($request);
        $kelompok = $pembayaran->pinjaman->kelompokTani;
        $kodeSetoran = $pembayaran->kode_setoran ?? $pembayaran->kode;

        (new SetoranPinjaman($kelompok))->ubah(
            $kodeSetoran,
            Carbon::parse($data['tanggal']),
            (float) $data['jumlah'],
            $data['deskripsi'] ?? null,
            $request->user(),
            $request->boolean('hanya_bunga'),
        );

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_SUNTING,
            'Mengubah setoran pinjaman '.$kelompok->nama_kelompok.' menjadi '.rupiah((float) $data['jumlah']),
            $kodeSetoran,
        );

        return redirect()
            ->route('pinjaman.kelompok', $kelompok)
            ->with('sukses', 'Setoran '.$kodeSetoran.' berhasil diperbarui. Riwayat pembayaran kelompok dihitung ulang.');
    }

    public function destroy(Request $request, PembayaranPinjaman $pembayaran): RedirectResponse
    {
        $kelompok = $pembayaran->pinjaman->kelompokTani;
        $kodeSetoran = $pembayaran->kode_setoran ?? $pembayaran->kode;
        $total = (float) $pembayaran->satuSetoran()->sum('total_bayar');

        try {
            (new SetoranPinjaman($kelompok))->hapus($kodeSetoran, $request->user());
        } catch (ValidationException $galat) {
            return back()->with('gagal', 'Setoran tidak bisa dihapus. '.collect($galat->errors())->flatten()->first());
        }

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_HAPUS,
            'Menghapus setoran pinjaman '.rupiah($total).' dari '.$kelompok->nama_kelompok,
            $kodeSetoran,
        );

        return redirect()
            ->route('pinjaman.kelompok', $kelompok)
            ->with('sukses', 'Setoran '.$kodeSetoran.' berhasil dihapus beserta transaksi kasnya.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        return $request->validate([
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'hanya_bunga' => ['nullable', 'boolean'],
        ], attributes: [
            'jumlah' => 'jumlah setoran',
        ]);
    }

    public function faktur(PembayaranPinjaman $pembayaran): View
    {
        return view('pinjaman.faktur-bayar', [
            'pembayaran' => $pembayaran,
            'daftarBaris' => $pembayaran->satuSetoran(),
        ]);
    }
}
