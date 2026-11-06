<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lokasi kerja tujuan pada penugasan (mutasi): ikut diterapkan ke data pegawai saat mutasi berlaku (bisa terjadwal).
     * Kosong = lokasi mengikuti tautan jabatan (satu lokasi) atau tidak diubah.
     */
    public function up(): void
    {
        Schema::table('penugasans', function (Blueprint $table) {
            $table->string('lokasi_kerja', 150)->nullable()->after('jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('penugasans', function (Blueprint $table) {
            $table->dropColumn('lokasi_kerja');
        });
    }
};
