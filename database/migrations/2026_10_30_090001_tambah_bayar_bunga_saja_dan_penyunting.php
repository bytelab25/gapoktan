<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revisi 30 Okt 2026:
     *
     * - setoran bisa ditandai "bunga saja", sehingga sisa uang tidak memotong
     *   pokok pinjaman tertua dan bunga semua pinjaman kelompok tertutup;
     * - faktur ditandatangani orang yang terakhir mengubah transaksi, bukan
     *   selalu pencatat awalnya.
     */
    private const TABEL_PENYUNTING = ['pinjaman', 'pembayaran_pinjaman', 'transaksi_kas', 'dana_talangan'];

    public function up(): void
    {
        Schema::table('pembayaran_pinjaman', function (Blueprint $table) {
            $table->boolean('hanya_bunga')->default(false)->after('kode_setoran');
        });

        foreach (self::TABEL_PENYUNTING as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->foreignId('diubah_oleh_id')->nullable()->after('user_id')
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABEL_PENYUNTING as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->dropConstrainedForeignId('diubah_oleh_id');
            });
        }

        Schema::table('pembayaran_pinjaman', function (Blueprint $table) {
            $table->dropColumn('hanya_bunga');
        });
    }
};
