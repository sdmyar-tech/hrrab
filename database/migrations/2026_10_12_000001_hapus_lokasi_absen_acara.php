<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Absen acara cukup dengan scan QR: semua kolom lokasi (titik, radius, GPS peserta) dihapus. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengumumans', function (Blueprint $table) {
            $table->dropColumn(['absen_lat', 'absen_lng', 'absen_radius']);
        });
        Schema::table('pengumuman_pesertas', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng', 'jarak', 'akurasi', 'gagal_terakhir']);
        });
    }

    public function down(): void
    {
        Schema::table('pengumumans', function (Blueprint $table) {
            $table->decimal('absen_lat', 10, 7)->nullable()->after('absen_tutup');
            $table->decimal('absen_lng', 10, 7)->nullable()->after('absen_lat');
            $table->unsignedSmallInteger('absen_radius')->nullable()->after('absen_lng');
        });
        Schema::table('pengumuman_pesertas', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('waktu_absen');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->unsignedInteger('jarak')->nullable()->after('lng');
            $table->unsignedInteger('akurasi')->nullable()->after('jarak');
            $table->json('gagal_terakhir')->nullable()->after('akurasi');
        });
    }
};
