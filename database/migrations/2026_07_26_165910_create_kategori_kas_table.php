<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_kas', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            // Kategori bawaan sistem tidak boleh dihapus pengguna.
            $table->boolean('bawaan')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_kas');
    }
};
