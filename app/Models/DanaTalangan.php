<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaTalangan extends Model
{
    use HasFactory;

    public const JENIS_MASUK = 'masuk';
    public const JENIS_KELUAR = 'keluar';

    protected $table = 'dana_talangan';

    protected $fillable = [
        'kode',
        'kelompok_tani_id',
        'tanggal',
        'jenis',
        'penerima',
        'jumlah',
        'deskripsi',
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
