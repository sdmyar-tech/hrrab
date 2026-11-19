<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pengumuman bisa ditujukan per jabatan dan per nama (selain per bagian). Ketiganya kosong = semua pegawai. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengumumans', function (Blueprint $table) {
            $table->json('sasaran_jabatan')->nullable()->after('sasaran_unit');
            $table->json('sasaran_user')->nullable()->after('sasaran_jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('pengumumans', function (Blueprint $table) {
            $table->dropColumn(['sasaran_jabatan', 'sasaran_user']);
        });
    }
};
