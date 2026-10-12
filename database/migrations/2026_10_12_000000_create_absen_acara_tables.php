<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Absen acara lewat pengumuman: peserta scan QR (berganti tiap 30 detik) di lokasi acara,
 * lalu lokasi GPS dicek terhadap titik + radius acara.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengumumans', function (Blueprint $table) {
            $table->boolean('absen_aktif')->default(false)->after('tampil_sampai');
            $table->dateTime('absen_buka')->nullable()->after('absen_aktif');
            $table->dateTime('absen_tutup')->nullable()->after('absen_buka');
            $table->decimal('absen_lat', 10, 7)->nullable()->after('absen_tutup');
            $table->decimal('absen_lng', 10, 7)->nullable()->after('absen_lat');
            $table->unsignedSmallInteger('absen_radius')->nullable()->after('absen_lng');
            // Kriteria peserta: {semua: bool, unit: [], jabatan: [], user: []} — gabungan (OR).
            $table->json('absen_kriteria')->nullable()->after('absen_radius');
            // Kunci rahasia pembentuk token QR (tidak pernah dikirim ke browser).
            $table->string('absen_kunci', 64)->nullable()->after('absen_kriteria');
        });

        Schema::create('pengumuman_pesertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengumuman_id')->constrained('pengumumans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('data_pegawai_id')->nullable()->constrained('data_pegawais')->nullOnDelete();
            $table->string('status', 20)->default('belum'); // belum | hadir | manual
            $table->dateTime('waktu_absen')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('jarak')->nullable();   // meter dari titik acara
            $table->unsignedInteger('akurasi')->nullable(); // meter (akurasi GPS perangkat)
            $table->json('gagal_terakhir')->nullable();     // percobaan terakhir yang ditolak
            $table->string('catatan', 255)->nullable();     // alasan ditandai manual
            $table->foreignId('ditandai_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('diingatkan_pada')->nullable();
            $table->timestamps();

            $table->unique(['pengumuman_id', 'user_id']);
            $table->index(['pengumuman_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman_pesertas');
        Schema::table('pengumumans', function (Blueprint $table) {
            $table->dropColumn(['absen_aktif', 'absen_buka', 'absen_tutup', 'absen_lat', 'absen_lng', 'absen_radius', 'absen_kriteria', 'absen_kunci']);
        });
    }
};
