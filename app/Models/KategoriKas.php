<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriKas extends Model
{
    use HasFactory;

    /** Kategori bawaan yang bisa dipilih pengguna di form kas. */
    public const BAWAAN = ['Saldo Awal', 'Gaji', 'Operasional'];

    /** Kategori milik sistem, dipakai transaksi otomatis. */
    public const PENCAIRAN_PINJAMAN = 'Pencairan Pinjaman';
    public const PEMBAYARAN_PINJAMAN = 'Pembayaran Pinjaman';
    public const SETORAN_TALANGAN = 'Setoran Dana Talangan';

    public const SISTEM = [
        self::PENCAIRAN_PINJAMAN,
        self::PEMBAYARAN_PINJAMAN,
        self::SETORAN_TALANGAN,
    ];

    protected $table = 'kategori_kas';

    protected $fillable = ['nama', 'bawaan', 'sistem'];

    protected function casts(): array
    {
        return [
            'bawaan' => 'boolean',
            'sistem' => 'boolean',
        ];
    }

    public function transaksiKas(): HasMany
    {
        return $this->hasMany(TransaksiKas::class);
    }

    /** Kategori yang boleh dipilih manual di form transaksi kas. */
    public function scopeUntukPengguna($query)
    {
        return $query->where('sistem', false);
    }

    public static function sistem(string $nama): self
    {
        return static::firstOrCreate(['nama' => $nama], ['bawaan' => true, 'sistem' => true]);
    }
}
