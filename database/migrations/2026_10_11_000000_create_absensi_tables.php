<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul absensi (mesin fingerprint Solution X105, import file flashdisk).
 *  - data_pegawais.finger_id   : ID pegawai di mesin (AC-No.)
 *  - data_pegawais.ikut_absensi: hanya pegawai yang dicentang yang dinilai absensinya
 *  - jadwal_kerja_pegawais     : jam kerja per orang
 *  - absensis                  : 1 baris per finger/pegawai per tanggal (scan mesin + koreksi SDM)
 *  - absensi_imports           : riwayat import file
 *  - izin_absensis             : izin telat / pulang cepat (per pegawai, per unit, atau semua)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_pegawais', function (Blueprint $table) {
            $table->string('finger_id', 20)->nullable()->unique()->after('nip');
            $table->boolean('ikut_absensi')->default(false)->after('finger_id');
        });

        Schema::create('jadwal_kerja_pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')->unique()->constrained('data_pegawais')->cascadeOnDelete();
            $table->time('jam_masuk');
            $table->time('jam_pulang');
            $table->unsignedSmallInteger('toleransi_menit')->nullable(); // null = bawaan config
            $table->json('hari_kerja')->nullable();   // [1..7] ISO; null = bawaan config
            $table->json('jam_per_hari')->nullable(); // {"5": {"masuk": "07:30", "pulang": "11:30"}}
            $table->timestamps();
        });

        Schema::create('absensi_imports', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->date('periode_dari')->nullable();
            $table->date('periode_sampai')->nullable();
            $table->unsignedInteger('jumlah_baris')->default(0);
            $table->unsignedInteger('jumlah_baru')->default(0);
            $table->unsignedInteger('jumlah_diperbarui')->default(0);
            $table->unsignedInteger('jumlah_dilewati')->default(0);
            $table->json('ringkasan')->nullable();
            $table->foreignId('diimport_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')->nullable()->constrained('data_pegawais')->nullOnDelete();
            $table->string('finger_id', 20)->nullable();
            $table->string('nama_mesin', 100)->nullable();
            $table->date('tanggal');
            // Jam asli dari mesin (tidak pernah diubah manual).
            $table->time('scan_masuk')->nullable();
            $table->time('scan_keluar')->nullable();
            // Jam yang dipakai penilaian (= scan, atau hasil koreksi SDM/Admin).
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->string('status_manual', 20)->nullable(); // hadir|izin|sakit|dinas_luar|alpa
            $table->unsignedSmallInteger('lembur_manual_menit')->nullable();
            $table->boolean('dikoreksi')->default(false);
            $table->string('alasan_koreksi', 500)->nullable();
            $table->foreignId('dikoreksi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('dikoreksi_pada')->nullable();
            $table->foreignId('absensi_import_id')->nullable()->constrained('absensi_imports')->nullOnDelete();
            $table->timestamps();

            $table->unique(['finger_id', 'tanggal']);
            $table->unique(['data_pegawai_id', 'tanggal']);
            $table->index('tanggal');
        });

        Schema::create('izin_absensis', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('jenis', 20); // telat|pulang_cepat
            $table->unsignedSmallInteger('menit');
            $table->foreignId('data_pegawai_id')->nullable()->constrained('data_pegawais')->cascadeOnDelete();
            $table->string('unit_kerja')->nullable(); // pegawai & unit kosong = berlaku untuk semua
            $table->string('alasan');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tanggal', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izin_absensis');
        Schema::dropIfExists('absensis');
        Schema::dropIfExists('absensi_imports');
        Schema::dropIfExists('jadwal_kerja_pegawais');
        Schema::table('data_pegawais', function (Blueprint $table) {
            $table->dropUnique(['finger_id']);
            $table->dropColumn(['finger_id', 'ikut_absensi']);
        });
    }
};
