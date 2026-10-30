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
 * - kas keluar sebesar dana yang benar-benar diterima kelompok,
 * - kas keluar sebesar kontribusi dana talangan, karena uang itu pindah ke pos
 *   terpisah dan tidak lagi jadi saldo kas,
 * - dana talangan masuk dengan nominal yang sama.
 *
 * Potongan operasional tidak dibuat jadi baris kas: uangnya memang tidak pernah
 * keluar, jadi cukup disimpan sebagai kolom `biaya` di baris pencairan.
 */
class PencairanPinjaman
{
    public function __construct(private readonly Pinjaman $pinjaman) {}

    public function sinkronkan(User $pencatat): void
    {
        $persenTalangan = (float) $this->pinjaman->potongan_talangan_persen;
        $persenOperasional = (float) $this->pinjaman->potongan_persen - $persenTalangan;
        $pokok = (float) $this->pinjaman->jumlah_pinjaman;
        $nominalTalangan = round($pokok * $persenTalangan / 100, 2);

        $this->simpanKas(KategoriKas::PENCAIRAN_PINJAMAN, [
            'jumlah' => (float) $this->pinjaman->jumlah_diterima,
            'biaya' => round($pokok * $persenOperasional / 100, 2),
            'deskripsi' => 'Pencairan pinjaman '.$this->pinjaman->kode
                .' — '.$this->pinjaman->kelompokTani->nama_kelompok,
        ], $pencatat);

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
        ], $pencatat);

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
    private function simpanKas(string $namaKategori, array $nilai, User $pencatat): void
    {
        $kategori = KategoriKas::sistem($namaKategori);

        $kas = $this->pinjaman->transaksiKas()->firstOrNew(['kategori_kas_id' => $kategori->id]);
        $kas->fill([
            ...$nilai,
            'kode' => $kas->kode ?? $this->kodeKasBerikutnya(),
            'tanggal' => $this->pinjaman->tanggal_transaksi,
            'jenis' => TransaksiKas::JENIS_KELUAR,
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
