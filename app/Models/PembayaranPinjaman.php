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
}
