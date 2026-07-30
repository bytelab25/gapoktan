<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran_pinjaman', function (Blueprint $table) {
            // Satu setoran kelompok bisa terpecah ke beberapa pinjaman (FIFO),
            // kode setoran menyatukan baris-baris itu jadi satu faktur.
            $table->string('kode_setoran')->nullable()->after('kode');
            $table->index('kode_setoran');
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran_pinjaman', function (Blueprint $table) {
            $table->dropIndex(['kode_setoran']);
            $table->dropColumn('kode_setoran');
        });
    }
};
