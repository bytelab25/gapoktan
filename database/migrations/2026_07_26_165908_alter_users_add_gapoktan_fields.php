<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->string('no_hp', 20)->nullable()->after('email');
            $table->string('alamat')->nullable()->after('no_hp');
            $table->string('role')->default('Ketua Poktan')->after('alamat');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'no_hp', 'alamat', 'role']);
        });
    }
};
