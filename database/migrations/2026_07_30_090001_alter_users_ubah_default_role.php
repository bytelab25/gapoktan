<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role berubah dari label kepengurusan menjadi tingkat hak akses:
     * sekretaris/bendahara (penuh), ketua (kelola + pengaturan), user (lihat saja).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('Ketua Poktan')->change();
        });
    }
};
