<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Izin Keluar menjadi Izin umum: jenis "jam" (keluar beberapa jam, bertiket — data lama)
     * dan "harian" (tidak masuk 1 hari atau lebih, dihitung hari kerja, tercatat di absensi).
     */
    public function up(): void
    {
        Schema::table('pengajuan_izin_keluars', function (Blueprint $table) {
            $table->string('jenis', 10)->default('jam')->after('unit_kerja');
            $table->date('tanggal_selesai')->nullable()->after('tanggal');   // hanya izin harian
            $table->unsignedSmallInteger('lama_hari')->nullable()->after('tanggal_selesai');
            $table->time('jam_keluar')->nullable()->change();               // izin harian tanpa jam
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_izin_keluars', function (Blueprint $table) {
            $table->dropColumn(['jenis', 'tanggal_selesai', 'lama_hari']);
        });
    }
};
