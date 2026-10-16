<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiKas extends Model
{
    use HasFactory;

    public const JENIS_MASUK = 'masuk';

    public const JENIS_KELUAR = 'keluar';

    protected $table = 'transaksi_kas';

    protected $fillable = [
        'kode',
        'tanggal',
        'jenis',
        'kategori_kas_id',
        'kelompok_tani_id',
        'pinjaman_id',
        'kode_setoran',
        'jumlah',
        'biaya',
        'deskripsi',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
            'biaya' => 'decimal:2',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriKas::class, 'kategori_kas_id');
    }

    public function kelompokTani(): BelongsTo
    {
        return $this->belongsTo(KelompokTani::class);
    }

    public function pinjaman(): BelongsTo
    {
        return $this->belongsTo(Pinjaman::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Baris kas yang lahir otomatis dari pencairan atau setoran pinjaman
     * tidak boleh disunting manual, supaya angkanya tetap sama dengan sumbernya.
     */
    public function getOtomatisAttribute(): bool
    {
        return $this->pinjaman_id !== null || $this->kode_setoran !== null;
    }

    /**
     * Nilai bertanda: masuk positif, keluar negatif.
     */
    public function getNilaiAttribute(): float
    {
        return $this->jenis === self::JENIS_MASUK
            ? (float) $this->jumlah
            : -(float) $this->jumlah;
    }

    /**
     * Saldo kas sampai transaksi ini. Dihitung on-the-fly, tidak disimpan,
     * karena transaksi lama bisa disunting atau dihapus.
     */
    public static function saldoSampai(self $transaksi): float
    {
        return (float) static::query()
            ->where(fn (Builder $query) => $query
                ->where('tanggal', '<', $transaksi->tanggal)
                ->orWhere(fn (Builder $sama) => $sama
                    ->where('tanggal', $transaksi->tanggal)
                    ->where('id', '<=', $transaksi->id)))
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END), 0) AS saldo")
            ->value('saldo');
    }

    public static function saldoTotal(): float
    {
        return (float) static::query()
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END), 0) AS saldo")
            ->value('saldo');
    }
}
