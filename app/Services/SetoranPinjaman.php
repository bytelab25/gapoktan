<?php

namespace App\Services;

use App\Models\KategoriKas;
use App\Models\KelompokTani;
use App\Models\PembayaranPinjaman;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Setoran pembayaran pinjaman satu kelompok tani.
 *
 * Aturan koperasi yang diwakili kelas ini:
 * 1. Bunga selalu didahulukan; pokok baru terpotong setelah bunga tertutup.
 *    Karena itu kelompok boleh nyicil — kurang dari bunga pun tetap diterima
 *    dan kekurangannya jadi tunggakan bunga.
 * 2. Bila kelompok punya lebih dari satu pinjaman, uang masuk ke pinjaman
 *    tertua lebih dulu (FIFO). Sisanya baru mengalir ke pinjaman berikutnya.
 * 3. Pinjaman berjalan per termin 4 bulan. Melunasi bunga tanpa memotong pokok
 *    menggeser jatuh tempo ke termin berikutnya (4 -> 8 -> 12 bulan).
 */
class SetoranPinjaman
{
    public function __construct(private readonly KelompokTani $kelompok) {}

    /**
     * Pinjaman yang masih harus dibayar, urut tertua dulu.
     *
     * @return Collection<int, Pinjaman>
     */
    public function tagihan(): Collection
    {
        return $this->kelompok
            ->pinjaman()
            ->with('pembayaran')
            ->belumLunas()
            ->get();
    }

    public function totalSisaPokok(): float
    {
        return round($this->tagihan()->sum(fn (Pinjaman $pinjaman) => $pinjaman->sisa_pokok), 2);
    }

    public function totalBungaTerhutang(?Carbon $sampai = null): float
    {
        return round($this->tagihan()->sum(fn (Pinjaman $pinjaman) => $pinjaman->bungaTerhutang($sampai)), 2);
    }

    /**
     * Nominal untuk melunasi seluruh pinjaman kelompok pada tanggal tertentu.
     */
    public function totalPelunasan(?Carbon $sampai = null): float
    {
        return round($this->totalSisaPokok() + $this->totalBungaTerhutang($sampai), 2);
    }

    /**
     * Bagi satu setoran ke pinjaman-pinjaman kelompok secara FIFO.
     *
     * @return Collection<int, PembayaranPinjaman> baris pembayaran yang terbentuk
     */
    public function catat(Carbon $tanggal, float $jumlah, ?string $deskripsi, User $pencatat): Collection
    {
        return DB::transaction(function () use ($tanggal, $jumlah, $deskripsi, $pencatat) {
            $kodeSetoran = $this->kodeSetoranBerikutnya();
            $sisaUang = round($jumlah, 2);
            $baris = new Collection;

            foreach ($this->tagihan() as $pinjaman) {
                if ($sisaUang < 0.01) {
                    break;
                }

                $baris->push($this->bayarSatuPinjaman(
                    $pinjaman, $tanggal, $sisaUang, $kodeSetoran, $deskripsi, $pencatat,
                ));
            }

            if ($baris->isEmpty()) {
                return $baris;
            }

            $this->catatKasMasuk($tanggal, (float) $baris->sum('total_bayar'), $kodeSetoran, $pencatat);

            return $baris;
        });
    }

    /**
     * Alokasi ke satu pinjaman. Mengurangi $sisaUang sesuai yang terpakai.
     */
    private function bayarSatuPinjaman(
        Pinjaman $pinjaman,
        Carbon $tanggal,
        float &$sisaUang,
        string $kodeSetoran,
        ?string $deskripsi,
        User $pencatat,
    ): PembayaranPinjaman {
        $bungaTerhutang = $pinjaman->bungaTerhutang($tanggal);
        $bulanBunga = $pinjaman->bulanBungaBerjalan($tanggal);

        $bayarBunga = round(min($sisaUang, $bungaTerhutang), 2);
        $sisaUang = round($sisaUang - $bayarBunga, 2);

        // Pokok hanya terpotong kalau bunga sudah tertutup semua.
        $bayarPokok = 0.0;
        if ($bungaTerhutang - $bayarBunga < 0.01) {
            $bayarPokok = round(min($sisaUang, $pinjaman->sisa_pokok), 2);
            $sisaUang = round($sisaUang - $bayarPokok, 2);
        }

        $pembayaran = $pinjaman->pembayaran()->create([
            'kode' => $this->kodeBayarBerikutnya(),
            'kode_setoran' => $kodeSetoran,
            'tanggal' => $tanggal,
            'bulan_bunga' => $bulanBunga,
            'bunga_nominal' => $bayarBunga,
            'bayar_pokok' => $bayarPokok,
            'total_bayar' => round($bayarBunga + $bayarPokok, 2),
            'deskripsi' => $deskripsi,
            'user_id' => $pencatat->id,
        ]);

        $pinjaman->tunggakan_bunga = max(0, round($bungaTerhutang - $bayarBunga, 2));

        // Bunga termin ini beres tapi pokok masih jalan: jatuh tempo maju 4 bulan.
        $pinjaman->load('pembayaran');
        if ($pinjaman->tunggakan_bunga < 0.01 && $pinjaman->sisa_pokok > 0) {
            $pinjaman->majukanTermin($tanggal);
        }

        $pinjaman->segarkanStatus();

        return $pembayaran;
    }

    /**
     * Setoran kelompok masuk kembali ke kas koperasi sebagai satu transaksi.
     */
    private function catatKasMasuk(Carbon $tanggal, float $total, string $kodeSetoran, User $pencatat): void
    {
        TransaksiKas::create([
            'kode' => sprintf('TXN-%05d', 10000 + (int) TransaksiKas::max('id') + 1),
            'tanggal' => $tanggal,
            'jenis' => TransaksiKas::JENIS_MASUK,
            'kategori_kas_id' => KategoriKas::sistem(KategoriKas::PEMBAYARAN_PINJAMAN)->id,
            'kelompok_tani_id' => $this->kelompok->id,
            'jumlah' => $total,
            'biaya' => 0,
            'deskripsi' => 'Setoran '.$kodeSetoran.' dari '.$this->kelompok->nama_kelompok,
            'user_id' => $pencatat->id,
        ]);
    }

    private function kodeSetoranBerikutnya(): string
    {
        $tahun = now()->year;
        $urutan = PembayaranPinjaman::whereYear('created_at', $tahun)
            ->distinct()
            ->count('kode_setoran') + 1;

        return sprintf('ST-%s-%03d', $tahun, $urutan);
    }

    private function kodeBayarBerikutnya(): string
    {
        $tahun = now()->year;
        $urutan = PembayaranPinjaman::whereYear('created_at', $tahun)->count() + 1;

        return sprintf('BP-%s-%03d', $tahun, $urutan);
    }
}
