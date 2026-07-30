<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baris kas dan dana talangan yang lahir otomatis dari pencairan pinjaman
     * ditautkan ke pinjamannya, supaya bisa disegarkan atau ikut terhapus.
     */
    public function up(): void
    {
        Schema::table('transaksi_kas', function (Blueprint $table) {
            $table->foreignId('pinjaman_id')->nullable()->after('kelompok_tani_id')
                ->constrained('pinjaman')->nullOnDelete();
        });

        Schema::table('dana_talangan', function (Blueprint $table) {
            $table->foreignId('pinjaman_id')->nullable()->after('kelompok_tani_id')
                ->constrained('pinjaman')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_kas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pinjaman_id');
        });

        Schema::table('dana_talangan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pinjaman_id');
        });
    }
};
