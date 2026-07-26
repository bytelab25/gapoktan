<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dana_talangan', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('kelompok_tani_id')->constrained('kelompok_tani')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['masuk', 'keluar']);
            // Nama anggota yang meninggal. Teks bebas — sistem tidak punya daftar anggota.
            $table->string('penerima')->nullable();
            $table->decimal('jumlah', 18, 2);
            $table->text('deskripsi')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dana_talangan');
    }
};
