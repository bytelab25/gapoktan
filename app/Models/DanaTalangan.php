<?php

namespace App\Models;

use App\Models\Concerns\PunyaPenyunting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaTalangan extends Model
{
    use HasFactory, PunyaPenyunting;

    public const JENIS_MASUK = 'masuk';
    public const JENIS_KELUAR = 'keluar';

    protected $table = 'dana_talangan';

    protected $fillable = [
        'kode',
        'kelompok_tani_id',
        'pinjaman_id',
        'tanggal',
        'jenis',
        'penerima',
        'jumlah',
        'deskripsi',
        'user_id',
        'diubah_oleh_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
        ];
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
     * Kontribusi 1% yang lahir otomatis dari pencairan pinjaman — dikelola
     * sistem, jadi tidak boleh disunting atau dihapus manual.
     */
    public function getOtomatisAttribute(): bool
    {
        return $this->pinjaman_id !== null;
    }

    public function getNilaiAttribute(): float
    {
        return $this->jenis === self::JENIS_MASUK
            ? (float) $this->jumlah
            : -(float) $this->jumlah;
    }

    public static function saldoTotal(): float
    {
        return (float) static::query()
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END), 0) AS saldo")
            ->value('saldo');
    }
}
