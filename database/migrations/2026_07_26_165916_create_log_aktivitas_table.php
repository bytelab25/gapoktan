<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Kode transaksi yang disentuh, mis. TXN-98421 atau LP-2026-001.
            $table->string('id_invoice')->nullable();
            $table->enum('jenis_tindakan', ['input', 'sunting', 'hapus']);
            $table->string('deskripsi');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
