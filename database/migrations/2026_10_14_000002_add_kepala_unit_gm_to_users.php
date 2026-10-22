<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unit yang dipimpin akun GM yang TIDAK tertaut ke Data Pegawai (tanpa NIP).
 * Akun yang tertaut tetap memakai penugasan peran kepala seperti biasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('kepala_unit_gm')->nullable()->after('divisi');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kepala_unit_gm');
        });
    }
};
