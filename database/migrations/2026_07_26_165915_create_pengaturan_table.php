<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel baris tunggal. Semua persentase koperasi diambil dari sini,
        // lalu di-snapshot ke tiap transaksi supaya perubahan tidak mengubah riwayat.
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->id();
            $table->decimal('suku_bunga_pinjaman', 5, 2)->default(2.00);
            $table->decimal('potongan_operasional', 5, 2)->default(0.50);
            $table->decimal('potongan_dana_talangan', 5, 2)->default(1.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
