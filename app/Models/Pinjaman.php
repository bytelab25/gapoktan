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

    /**
     * Pinjaman kelompok selalu diakumulasi per 4 bulan: jatuh tempo bergerak
     * 4 -> 8 -> 12 bulan selama pokoknya belum lunas.
     */
    public const BULAN_PER_TERMIN = 4;

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
        'termin',
        'jumlah_pinjaman',
        'potongan_persen',
        'potongan_talangan_persen',
        'potongan_nominal',
        'jumlah_diterima',
        'bunga_persen',
        'tunggakan_bunga',
        'status',
        'deskripsi',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_transaksi' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'termin' => 'integer',
            'jumlah_pinjaman' => 'decimal:2',
            'potongan_persen' => 'decimal:2',
            'potongan_talangan_persen' => 'decimal:2',
            'potongan_nominal' => 'decimal:2',
            'jumlah_diterima' => 'decimal:2',
            'bunga_persen' => 'decimal:2',
            'tunggakan_bunga' => 'decimal:2',
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

    public function transaksiKas(): HasMany
    {
        return $this->hasMany(TransaksiKas::class);
    }

    public function danaTalangan(): HasMany
    {
        return $this->hasMany(DanaTalangan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pinjaman yang masih harus dibayar, urut tertua dulu (FIFO).
     */
    public function scopeBelumLunas($query)
    {
        return $query->where('status', '!=', self::STATUS_LUNAS)
            ->orderBy('tanggal_transaksi')
            ->orderBy('id');
    }

    public function getSisaPokokAttribute(): float
    {
        return round((float) $this->jumlah_pinjaman - (float) $this->pembayaran->sum('bayar_pokok'), 2);
    }

    public function getTotalBungaDibayarAttribute(): float
    {
        return (float) $this->pembayaran->sum('bunga_nominal');
    }

    /**
     * Tanggal terakhir bunga dihitung: pembayaran terakhir, atau tanggal
     * pencairan bila kelompok belum pernah menyetor.
     */
    public function getMulaiBungaAttribute(): Carbon
    {
        $terakhir = $this->pembayaran->max('tanggal');

        return $terakhir ? Carbon::parse($terakhir) : $this->tanggal_transaksi->copy();
    }

    /**
     * Jumlah bulan penuh yang berjalan sejak bunga terakhir dihitung.
     */
    public function bulanBungaBerjalan(?Carbon $sampai = null): int
    {
        $sampai ??= Carbon::today();

        return max(0, (int) $this->mulai_bunga->diffInMonths($sampai));
    }

    /**
     * Bunga bulan-bulan yang baru berjalan, belum termasuk tunggakan lama.
     */
    public function bungaBerjalan(?Carbon $sampai = null): float
    {
        return round($this->sisa_pokok * ((float) $this->bunga_persen / 100) * $this->bulanBungaBerjalan($sampai), 2);
    }

    /**
     * Seluruh bunga yang wajib dibayar sekarang: tunggakan sisa cicilan
     * sebelumnya ditambah bunga bulan berjalan.
     */
    public function bungaTerhutang(?Carbon $sampai = null): float
    {
        return round((float) $this->tunggakan_bunga + $this->bungaBerjalan($sampai), 2);
    }

    /**
     * Total yang harus disetor bila kelompok ingin melunasi pinjaman ini.
     */
    public function pelunasan(?Carbon $sampai = null): float
    {
        return round($this->sisa_pokok + $this->bungaTerhutang($sampai), 2);
    }

    /**
     * Bulan ke berapa pinjaman ini berjalan sejak dicairkan.
     */
    public function getUmurBulanAttribute(): int
    {
        return max(0, (int) $this->tanggal_transaksi->diffInMonths(Carbon::today()));
    }

    /**
     * Kelompok baru boleh menyetor setelah termin pertama genap 4 bulan,
     * karena bunga memang diakumulasi per termin.
     */
    public function bisaDibayar(?Carbon $sampai = null): bool
    {
        $sampai ??= Carbon::today();

        return $this->jatuhTempoPertama()->lte($sampai);
    }

    public function jatuhTempoPertama(): Carbon
    {
        return $this->tanggal_transaksi->copy()->addMonths(self::BULAN_PER_TERMIN);
    }

    /**
     * Batas termin berjalan, mis. termin 2 = bulan ke-8 sejak pencairan.
     */
    public function getBulanTerminAttribute(): int
    {
        return self::BULAN_PER_TERMIN * $this->termin;
    }

    public function getLabelStatusAttribute(): string
    {
        return self::LABEL_STATUS[$this->status] ?? $this->status;
    }

    /**
     * Geser jatuh tempo ke termin 4 bulan berikutnya. Dipakai saat kelompok
     * sudah melunasi bunga pada tanggal tertentu tapi pokoknya masih berjalan.
     */
    public function majukanTermin(Carbon $sampai): void
    {
        while ($this->tanggal_jatuh_tempo->lte($sampai)) {
            $this->termin++;
            $this->tanggal_jatuh_tempo = $this->tanggal_transaksi
                ->copy()
                ->addMonths($this->bulan_termin);
        }
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
            $this->status = $status;
        }

        if ($this->isDirty()) {
            $this->save();
        }
    }
}
