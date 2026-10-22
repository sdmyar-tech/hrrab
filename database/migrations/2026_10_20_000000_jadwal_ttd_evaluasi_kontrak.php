<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Jadwal & lokasi tanda tangan kontrak (mis. Ruangan SDM) setelah diputuskan perpanjang / angkat tetap. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasi_kontraks', function (Blueprint $table) {
            $table->dateTime('ttd_jadwal')->nullable()->after('diputuskan_pada');
            $table->string('ttd_lokasi', 150)->nullable()->after('ttd_jadwal');
        });
    }

    public function down(): void
    {
        Schema::table('evaluasi_kontraks', function (Blueprint $table) {
            $table->dropColumn(['ttd_jadwal', 'ttd_lokasi']);
        });
    }
};
