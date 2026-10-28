<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hak akses tambahan per jabatan di tiap unit (mis. "STAFF" di unit A boleh lihat absensi, di unit B tidak).
 * Ditambahkan di atas hak role akun (Master Role); null/kosong = tidak ada tambahan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jabatan_unit', function (Blueprint $table) {
            $table->json('hak_akses')->nullable()->after('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('jabatan_unit', function (Blueprint $table) {
            $table->dropColumn('hak_akses');
        });
    }
};
