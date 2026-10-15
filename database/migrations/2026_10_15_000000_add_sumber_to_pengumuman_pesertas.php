<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Absen acara: status berhalangan (izin, sakit, dinas, cuti) + asal datanya.
 * sumber: manual (ditetapkan panitia) | cuti (pengajuan cuti disetujui) | absensi (koreksi absensi harian).
 * Status dari cuti/absensi diperbarui otomatis bila datanya berubah; status manual tidak pernah ditimpa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengumuman_pesertas', function (Blueprint $table) {
            $table->string('sumber', 20)->nullable()->after('status');
            $table->unsignedBigInteger('sumber_id')->nullable()->after('sumber');
        });
    }

    public function down(): void
    {
        Schema::table('pengumuman_pesertas', function (Blueprint $table) {
            $table->dropColumn(['sumber', 'sumber_id']);
        });
    }
};
