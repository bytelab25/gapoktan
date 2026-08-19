<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dana masuk boleh dicatat tanpa kelompok tani, mis. hibah atau setoran
     * kolektif. Klaim keluar tetap wajib menyebut kelompoknya.
     */
    public function up(): void
    {
        Schema::table('dana_talangan', function (Blueprint $table) {
            $table->foreignId('kelompok_tani_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dana_talangan', function (Blueprint $table) {
            $table->foreignId('kelompok_tani_id')->nullable(false)->change();
        });
    }
};
