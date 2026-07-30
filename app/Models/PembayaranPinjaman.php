<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranPinjaman extends Model
{
    use HasFactory;

    protected $table = 'pembayaran_pinjaman';

    protected $fillable = [
        'kode',
        'kode_setoran',
        'pinjaman_id',
        'tanggal',
        'bulan_bunga',
        'bunga_nominal',
        'bayar_pokok',
        'total_bayar',
        'deskripsi',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'bulan_bunga' => 'integer',
            'bunga_nominal' => 'decimal:2',
            'bayar_pokok' => 'decimal:2',
            'total_bayar' => 'decimal:2',
        ];
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
    public function satuSetoran(): \Illuminate\Database\Eloquent\Collection
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
