<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_pinjaman', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('pinjaman_id')->constrained('pinjaman')->cascadeOnDelete();
            $table->date('tanggal');
            // Jumlah bulan bunga yang diakumulasi pada pembayaran ini.
            $table->unsignedSmallInteger('bulan_bunga')->default(0);
            $table->decimal('bunga_nominal', 18, 2)->default(0);
            $table->decimal('bayar_pokok', 18, 2)->default(0);
            $table->decimal('total_bayar', 18, 2);
            $table->text('deskripsi')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['pinjaman_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_pinjaman');
    }
};
