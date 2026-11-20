<?php

namespace App\Services;

use App\Models\DanaTalangan;
use App\Models\KategoriKas;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Transaksi yang otomatis lahir saat pinjaman dicairkan:
 *
 * - kas keluar sebesar dana yang diterima kelompok ditambah potongan operasional,
 * - kas masuk sebesar potongan operasional, sebagai bukti penerimaan tersendiri,
 * - kas keluar sebesar kontribusi dana talangan, karena uang itu pindah ke pos
 *   terpisah dan tidak lagi jadi saldo kas,
 * - dana talangan masuk dengan nominal yang sama.
 *
 * Dua baris operasional saling mengimbangi: uangnya tetap di kas, tetapi
 * penerimaannya memiliki nomor transaksi dan faktur sendiri.
 */
class PencairanPinjaman
{
    public function __construct(private readonly Pinjaman $pinjaman) {}

    public function sinkronkan(User $pencatat): void
    {
        $persenTalangan = (float) $this->pinjaman->potongan_talangan_persen;
        $pokok = (float) $this->pinjaman->jumlah_pinjaman;
        $nominalTalangan = round($pokok * $persenTalangan / 100, 2);
        $nominalOperasional = round((float) $this->pinjaman->potongan_nominal - $nominalTalangan, 2);

        $this->simpanKas(KategoriKas::PENCAIRAN_PINJAMAN, [
            'jumlah' => round((float) $this->pinjaman->jumlah_diterima + $nominalOperasional, 2),
            'biaya' => $nominalOperasional,
            'deskripsi' => 'Pencairan pinjaman '.$this->pinjaman->kode
                .' — '.$this->pinjaman->kelompokTani->nama_kelompok,
        ], $pencatat, TransaksiKas::JENIS_KELUAR);

        if ($nominalOperasional > 0) {
            $this->simpanKas(KategoriKas::POTONGAN_OPERASIONAL_PINJAMAN, [
                'jumlah' => $nominalOperasional,
                'biaya' => 0,
                'deskripsi' => 'Potongan operasional dari pinjaman '.$this->pinjaman->kode,
            ], $pencatat, TransaksiKas::JENIS_MASUK);
        } else {
            $this->pinjaman->transaksiKas()
                ->whereRelation('kategori', 'nama', KategoriKas::POTONGAN_OPERASIONAL_PINJAMAN)
                ->delete();
        }

        if ($nominalTalangan <= 0) {
            $this->pinjaman->transaksiKas()
                ->whereRelation('kategori', 'nama', KategoriKas::SETORAN_TALANGAN)
                ->delete();
            $this->pinjaman->danaTalangan()->delete();

            return;
        }

        $this->simpanKas(KategoriKas::SETORAN_TALANGAN, [
            'jumlah' => $nominalTalangan,
            'biaya' => 0,
            'deskripsi' => 'Kontribusi dana talangan dari pinjaman '.$this->pinjaman->kode,
        ], $pencatat, TransaksiKas::JENIS_KELUAR);

        $talangan = $this->pinjaman->danaTalangan()->firstOrNew([]);
        $talangan->fill([
            'kode' => $talangan->kode ?? $this->kodeTalanganBerikutnya(),
            'kelompok_tani_id' => $this->pinjaman->kelompok_tani_id,
            'tanggal' => $this->pinjaman->tanggal_transaksi,
            'jenis' => DanaTalangan::JENIS_MASUK,
            'jumlah' => $nominalTalangan,
            'deskripsi' => 'Kontribusi '.$persenTalangan.'% dari pencairan pinjaman '.$this->pinjaman->kode,
            ...$this->jejakPengisi($talangan, $pencatat),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $nilai
     */
    private function simpanKas(string $namaKategori, array $nilai, User $pencatat, string $jenis): void
    {
        $kategori = KategoriKas::sistem($namaKategori);

        $kas = $this->pinjaman->transaksiKas()->firstOrNew(['kategori_kas_id' => $kategori->id]);
        $kas->fill([
            ...$nilai,
            'kode' => $kas->kode ?? $this->kodeKasBerikutnya(),
            'tanggal' => $this->pinjaman->tanggal_transaksi,
            'jenis' => $jenis,
            'kategori_kas_id' => $kategori->id,
            'kelompok_tani_id' => $this->pinjaman->kelompok_tani_id,
            ...$this->jejakPengisi($kas, $pencatat),
        ])->save();
    }

    /**
     * Pencatat awal dipertahankan; yang menyunting pinjaman tercatat sebagai
     * pengubah baris turunannya, sehingga faktur memakai namanya.
     *
     * @return array<string, int|null>
     */
    private function jejakPengisi(Model $baris, User $pencatat): array
    {
        return $baris->exists
            ? ['user_id' => $baris->user_id ?? $pencatat->id, 'diubah_oleh_id' => $pencatat->id]
            : ['user_id' => $pencatat->id];
    }

    private function kodeKasBerikutnya(): string
    {
        return sprintf('TXN-%05d', 10000 + (int) TransaksiKas::max('id') + 1);
    }

    private function kodeTalanganBerikutnya(): string
    {
        return sprintf('DT-%04d', (int) DanaTalangan::max('id') + 1);
    }
}
