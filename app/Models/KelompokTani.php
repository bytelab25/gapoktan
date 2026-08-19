<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelompokTani extends Model
{
    use HasFactory;

    protected $table = 'kelompok_tani';

    protected $fillable = [
        'kode',
        'nama_kelompok',
        'nama_ketua',
        'nik_ketua',
        'no_hp',
        'alamat',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function pinjaman(): HasMany
    {
        return $this->hasMany(Pinjaman::class);
    }

    public function danaTalangan(): HasMany
    {
        return $this->hasMany(DanaTalangan::class);
    }

    public function transaksiKas(): HasMany
    {
        return $this->hasMany(TransaksiKas::class);
    }

    /**
     * Sisa pokok dari seluruh pinjaman kelompok yang belum lunas.
     */
    public function getTotalPinjamanAttribute(): float
    {
        return (float) $this->pinjaman
            ->where('status', '!=', Pinjaman::STATUS_LUNAS)
            ->sum(fn (Pinjaman $pinjaman) => $pinjaman->sisa_pokok);
    }

    /**
     * Status kelompok terhadap kewajibannya. Berbeda arti dengan status pada
     * satu pinjaman: di sini "menunggak" berarti kelompok sudah menyetor
     * sebagian (bunga atau pokok) tapi belum melunasi semuanya.
     *
     * - lunas       : tidak ada pinjaman aktif tersisa
     * - belum bayar : punya pinjaman aktif dan belum pernah menyetor sepeser pun
     * - menunggak   : punya pinjaman aktif dan sudah menyetor sebagian
     */
    public function getStatusPinjamanAttribute(): string
    {
        $aktif = $this->pinjaman->where('status', '!=', Pinjaman::STATUS_LUNAS);

        if ($aktif->isEmpty()) {
            return Pinjaman::STATUS_LUNAS;
        }

        $sudahMenyetor = $aktif->contains(fn (Pinjaman $pinjaman) => $pinjaman->pembayaran->isNotEmpty());

        return $sudahMenyetor ? Pinjaman::STATUS_MENUNGGAK : Pinjaman::STATUS_BELUM_BAYAR;
    }

    /**
     * Kelompok yang belum pernah menyetor untuk pinjaman berjalannya tidak
     * boleh menambah pinjaman baru.
     */
    public function bisaMengajukanPinjaman(): bool
    {
        return $this->status_pinjaman !== Pinjaman::STATUS_BELUM_BAYAR;
    }
}
