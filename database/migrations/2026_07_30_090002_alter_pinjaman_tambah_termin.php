<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            // Satu termin = 4 bulan. Termin bertambah tiap kali kelompok melunasi
            // bunga tapi pokoknya belum, sehingga jatuh tempo bergeser 4 -> 8 -> 12 bulan.
            $table->unsignedTinyInteger('termin')->default(1)->after('tanggal_jatuh_tempo');
            // Bunga yang sudah jatuh tempo tapi belum tertutup pembayaran (nyicil).
            $table->decimal('tunggakan_bunga', 18, 2)->default(0)->after('bunga_persen');
            // Porsi dana talangan di dalam potongan muka, disimpan sebagai
            // snapshot supaya perubahan pengaturan tidak menulis ulang sejarah.
            $table->decimal('potongan_talangan_persen', 5, 2)->default(0)->after('potongan_persen');
        });
    }

    public function down(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropColumn(['termin', 'tunggakan_bunga', 'potongan_talangan_persen']);
        });
    }
};
