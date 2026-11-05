<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unit (Master Unit) membawahi Bagian: tiap bagian berada di paling banyak satu unit. Dipakai mengisi otomatis
     * unit pegawai dari bagiannya (data pegawai, mutasi). Instalasi lama: kosong (diatur di Master Unit).
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->foreignId('unit_organisasi_id')->nullable()->after('keterangan')->constrained('unit_organisasis')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_organisasi_id');
        });
    }
};
