<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu kontrak (tanggal habis yang sama) boleh dievaluasi ulang bila keputusan sebelumnya tidak lagi berlaku
 * (mis. tanggal habis dikembalikan setelah diperpanjang). Unik → indeks biasa; evaluasi lama tetap jadi riwayat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasi_kontraks', function (Blueprint $table) {
            // Indeks biasa dibuat dulu: foreign key data_pegawai_id butuh indeks sebelum unik dilepas.
            $table->index(['data_pegawai_id', 'tanggal_habis_kontrak'], 'evaluasi_pegawai_tanggal_idx');
            $table->dropUnique(['data_pegawai_id', 'tanggal_habis_kontrak']);
        });
    }

    public function down(): void
    {
        Schema::table('evaluasi_kontraks', function (Blueprint $table) {
            $table->unique(['data_pegawai_id', 'tanggal_habis_kontrak']);
            $table->dropIndex('evaluasi_pegawai_tanggal_idx');
        });
    }
};
