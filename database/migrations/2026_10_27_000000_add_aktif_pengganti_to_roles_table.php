<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role bawaan (selain Admin) bisa "dihapus" = dinonaktifkan: hilang dari pilihan & daftar aktif, bisa dipulihkan.
     * pengganti = role tujuan ("<kode bawaan>" atau "kustom:<id>") untuk akun yang kelak mendapat role ini secara otomatis.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('aktif')->default(true)->after('bawaan');
            $table->string('pengganti', 60)->nullable()->after('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['aktif', 'pengganti']);
        });
    }
};
