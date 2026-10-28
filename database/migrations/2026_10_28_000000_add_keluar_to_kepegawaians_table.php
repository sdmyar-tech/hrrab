<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pegawai keluar (resign, kontrak tidak diperpanjang, PHK, dll.): jenis & alasan keluar, siapa yang memproses,
 * dan potret keadaan sebelum keluar (akun, penugasan, pengajuan, absensi) agar proses keluar bisa dibatalkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kepegawaians', function (Blueprint $table) {
            $table->string('jenis_keluar', 30)->nullable()->after('tanggal_keluar');
            $table->text('alasan_keluar')->nullable()->after('jenis_keluar');
            $table->foreignId('keluar_diproses_oleh')->nullable()->after('alasan_keluar')->constrained('users')->nullOnDelete();
            $table->json('keluar_snapshot')->nullable()->after('keluar_diproses_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('kepegawaians', function (Blueprint $table) {
            $table->dropConstrainedForeignId('keluar_diproses_oleh');
            $table->dropColumn(['jenis_keluar', 'alasan_keluar', 'keluar_snapshot']);
        });
    }
};
