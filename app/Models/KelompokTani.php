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
}
