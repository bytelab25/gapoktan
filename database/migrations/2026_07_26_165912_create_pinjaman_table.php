<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pinjaman', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('kelompok_tani_id')->constrained('kelompok_tani')->cascadeOnDelete();
            $table->date('tanggal_transaksi');
            $table->date('tanggal_jatuh_tempo');
            $table->decimal('jumlah_pinjaman', 18, 2);
            // Snapshot persentase potongan muka saat pinjaman dibuat.
            $table->decimal('potongan_persen', 5, 2);
            $table->decimal('potongan_nominal', 18, 2);
            $table->decimal('jumlah_diterima', 18, 2);
            // Snapshot suku bunga bulanan yang berlaku untuk pinjaman ini.
            $table->decimal('bunga_persen', 5, 2);
            $table->enum('status', ['belum_bayar', 'menunggak', 'lunas'])->default('belum_bayar');
            $table->text('deskripsi')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['kelompok_tani_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pinjaman');
    }
};
