<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Pinjaman extends Model
{
    use HasFactory;

    public const STATUS_BELUM_BAYAR = 'belum_bayar';
    public const STATUS_MENUNGGAK = 'menunggak';
    public const STATUS_LUNAS = 'lunas';

    public const LABEL_STATUS = [
        self::STATUS_BELUM_BAYAR => 'Belum Bayar',
        self::STATUS_MENUNGGAK => 'Menunggak',
        self::STATUS_LUNAS => 'Lunas',
    ];

    protected $table = 'pinjaman';

    protected $fillable = [
        'kode',
        'kelompok_tani_id',
        'tanggal_transaksi',
        'tanggal_jatuh_tempo',
        'jumlah_pinjaman',
        'potongan_persen',
        'potongan_nominal',
        'jumlah_diterima',
        'bunga_persen',
        'status',
        'deskripsi',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_transaksi' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'jumlah_pinjaman' => 'decimal:2',
            'potongan_persen' => 'decimal:2',
            'potongan_nominal' => 'decimal:2',
            'jumlah_diterima' => 'decimal:2',
            'bunga_persen' => 'decimal:2',
        ];
    }

    public function kelompokTani(): BelongsTo
    {
        return $this->belongsTo(KelompokTani::class);
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(PembayaranPinjaman::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSisaPokokAttribute(): float
    {
        return (float) $this->jumlah_pinjaman - (float) $this->pembayaran->sum('bayar_pokok');
    }

    public function getTotalBungaDibayarAttribute(): float
    {
        return (float) $this->pembayaran->sum('bunga_nominal');
    }

    /**
     * Tanggal mulai bunga berjalan: pembayaran terakhir, atau tanggal pencairan
     * bila kelompok belum pernah membayar.
     */
    public function getMulaiBungaAttribute(): Carbon
    {
        $terakhir = $this->pembayaran->max('tanggal');

        return $terakhir ? Carbon::parse($terakhir) : $this->tanggal_transaksi->copy();
    }

    /**
     * Jumlah bulan penuh yang sudah berjalan sejak bunga terakhir dihitung.
     */
    public function bulanBungaBerjalan(?Carbon $sampai = null): int
    {
        $sampai ??= Carbon::today();

        return max(0, $this->mulai_bunga->diffInMonths($sampai));
    }

    /**
     * Bunga yang harus dibayar bila melunasi pada tanggal tertentu.
     */
    public function bungaBerjalan(?Carbon $sampai = null): float
    {
        return round($this->sisa_pokok * ((float) $this->bunga_persen / 100) * $this->bulanBungaBerjalan($sampai), 2);
    }

    public function getLabelStatusAttribute(): string
    {
        return self::LABEL_STATUS[$this->status] ?? $this->status;
    }

    /**
     * Hitung ulang status dari sisa pokok dan tanggal jatuh tempo, lalu simpan.
     */
    public function segarkanStatus(): void
    {
        $this->load('pembayaran');

        $status = match (true) {
            $this->sisa_pokok <= 0 => self::STATUS_LUNAS,
            $this->tanggal_jatuh_tempo->isPast() => self::STATUS_MENUNGGAK,
            default => self::STATUS_BELUM_BAYAR,
        };

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }
}
