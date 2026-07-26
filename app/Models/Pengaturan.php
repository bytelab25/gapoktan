<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $fillable = [
        'suku_bunga_pinjaman',
        'potongan_operasional',
        'potongan_dana_talangan',
    ];

    protected function casts(): array
    {
        return [
            'suku_bunga_pinjaman' => 'decimal:2',
            'potongan_operasional' => 'decimal:2',
            'potongan_dana_talangan' => 'decimal:2',
        ];
    }

    /**
     * Baris tunggal pengaturan koperasi, dibuat dengan nilai bawaan bila belum ada.
     * Nilai bawaan ditulis eksplisit supaya instance yang dikembalikan sudah terisi,
     * bukan mengandalkan default kolom yang tidak ikut termuat setelah insert.
     */
    public static function ambil(): self
    {
        return static::firstOrCreate([], [
            'suku_bunga_pinjaman' => 2.00,
            'potongan_operasional' => 0.50,
            'potongan_dana_talangan' => 1.00,
        ]);
    }
}
