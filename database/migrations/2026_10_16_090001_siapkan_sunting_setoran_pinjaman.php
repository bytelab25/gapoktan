<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Setoran pinjaman kini bisa disunting dan dihapus. Untuk itu seluruh
     * setoran satu kelompok dihitung ulang dari awal, sehingga sistem perlu:
     *
     * - jatuh tempo awal pinjaman, karena kolom jatuh tempo bergeser 4 bulan
     *   setiap kali bunga termin lunas;
     * - tautan baris kas masuk ke kode setorannya.
     */
    public function up(): void
    {
        Schema::table('pinjaman', function (Blueprint $table) {
            $table->date('tanggal_jatuh_tempo_awal')->nullable()->after('tanggal_jatuh_tempo');
        });

        Schema::table('transaksi_kas', function (Blueprint $table) {
            $table->string('kode_setoran')->nullable()->after('pinjaman_id')->index();
        });

        // Termin maju selalu kelipatan 4 bulan, jadi jatuh tempo awal bisa ditarik mundur.
        DB::table('pinjaman')->orderBy('id')->each(function (object $pinjaman) {
            $awal = Carbon::parse($pinjaman->tanggal_jatuh_tempo)
                ->subMonthsNoOverflow(4 * max(0, (int) $pinjaman->termin - 1));

            DB::table('pinjaman')->where('id', $pinjaman->id)
                ->update(['tanggal_jatuh_tempo_awal' => $awal->toDateString()]);
        });

        // Baris kas setoran lama hanya menyebut kodenya di deskripsi.
        DB::table('transaksi_kas')->where('deskripsi', 'like', 'Setoran ST-%')->orderBy('id')
            ->each(function (object $kas) {
                if (preg_match('/^Setoran (ST-\d{4}-\d+)/', $kas->deskripsi, $cocok)) {
                    DB::table('transaksi_kas')->where('id', $kas->id)->update(['kode_setoran' => $cocok[1]]);
                }
            });

        // Status "menunggak" sekarang berarti sudah menyetor sebagian.
        DB::table('pinjaman')->where('status', '!=', 'lunas')->orderBy('id')->each(function (object $pinjaman) {
            $adaSetoran = DB::table('pembayaran_pinjaman')->where('pinjaman_id', $pinjaman->id)->exists();

            DB::table('pinjaman')->where('id', $pinjaman->id)
                ->update(['status' => $adaSetoran ? 'menunggak' : 'belum_bayar']);
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_kas', function (Blueprint $table) {
            $table->dropIndex(['kode_setoran']);
            $table->dropColumn('kode_setoran');
        });

        Schema::table('pinjaman', function (Blueprint $table) {
            $table->dropColumn('tanggal_jatuh_tempo_awal');
        });
    }
};
