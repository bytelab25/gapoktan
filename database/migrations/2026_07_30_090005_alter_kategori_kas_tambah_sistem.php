<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori sistem dipakai transaksi yang dibuat otomatis (pencairan
     * pinjaman, setoran talangan, pembayaran) sehingga tidak ditawarkan
     * di form input manual — tapi tetap tampil di tabel dan filter.
     */
    public function up(): void
    {
        Schema::table('kategori_kas', function (Blueprint $table) {
            $table->boolean('sistem')->default(false)->after('bawaan');
        });
    }

    public function down(): void
    {
        Schema::table('kategori_kas', function (Blueprint $table) {
            $table->dropColumn('sistem');
        });
    }
};
