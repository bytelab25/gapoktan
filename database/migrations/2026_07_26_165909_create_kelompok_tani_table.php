<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelompok_tani', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama_kelompok');
            $table->string('nama_ketua');
            $table->string('nik_ketua', 16);
            $table->string('no_hp', 20);
            $table->string('alamat');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelompok_tani');
    }
};
