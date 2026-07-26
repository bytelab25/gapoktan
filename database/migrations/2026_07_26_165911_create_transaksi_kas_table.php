<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_kas', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->date('tanggal');
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->foreignId('kategori_kas_id')->constrained('kategori_kas');
            $table->foreignId('kelompok_tani_id')->nullable()->constrained('kelompok_tani')->nullOnDelete();
            $table->decimal('jumlah', 18, 2);
            // Biaya operasional saat transaksi dibuat. Disimpan sebagai snapshot
            // karena persentase di tabel pengaturan bisa berubah kemudian.
            $table->decimal('biaya', 18, 2)->default(0);
            $table->text('deskripsi')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_kas');
    }
};
