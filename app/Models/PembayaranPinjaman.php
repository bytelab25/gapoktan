<?php

namespace App\Models;

use App\Models\Concerns\PunyaPenyunting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranPinjaman extends Model
{
    use HasFactory, PunyaPenyunting;

    protected $table = 'pembayaran_pinjaman';

    protected $fillable = [
        'kode',
        'kode_setoran',
        'hanya_bunga',
        'pinjaman_id',
        'tanggal',
        'bulan_bunga',
        'bunga_nominal',
        'bayar_pokok',
        'total_bayar',
        'deskripsi',
        'user_id',
        'diubah_oleh_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'hanya_bunga' => 'boolean',
            'bulan_bunga' => 'integer',
            'bunga_nominal' => 'decimal:2',
            'bayar_pokok' => 'decimal:2',
            'total_bayar' => 'decimal:2',
        ];
    }

    /**
     * Baris pembayaran dibuat ulang setiap kali setoran kelompok dihitung
     * ulang, jadi ID-nya berubah. Kode BP-... tetap, maka URL memakai kode.
     */
    public function getRouteKeyName(): string
    {
        return 'kode';
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
     * Baris-baris lain dari setoran yang sama, termasuk baris ini sendiri.
     * Satu setoran bisa terpecah ke beberapa pinjaman karena alokasi FIFO.
     */
    public function satuSetoran(): Collection
    {
        return static::with('pinjaman.kelompokTani')
            ->when(
                $this->kode_setoran,
                fn ($query) => $query->where('kode_setoran', $this->kode_setoran),
                fn ($query) => $query->whereKey($this->getKey()),
            )
            ->orderBy('id')
            ->get();
    }
}
