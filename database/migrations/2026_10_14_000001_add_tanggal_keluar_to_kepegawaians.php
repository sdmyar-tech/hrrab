<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanggal pegawai keluar (status jadi "Tidak Aktif"), untuk grafik pegawai masuk & keluar.
 * Data lama dibiarkan kosong karena tanggal keluarnya tidak diketahui (bisa diisi manual di form pegawai).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kepegawaians', function (Blueprint $table) {
            $table->date('tanggal_keluar')->nullable()->after('tanggal_masuk');
            $table->index('tanggal_keluar');
            $table->index('tanggal_masuk');
        });
    }

    public function down(): void
    {
        Schema::table('kepegawaians', function (Blueprint $table) {
            $table->dropIndex(['tanggal_keluar']);
            $table->dropIndex(['tanggal_masuk']);
            $table->dropColumn('tanggal_keluar');
        });
    }
};
