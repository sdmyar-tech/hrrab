<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lamarans', function (Blueprint $table) {
            $table->foreignId('data_pelamar_id')->nullable()->change();
            $table->foreignId('lowongan_id')->nullable()->change();

            $table->string('jalur', 20)->default('umum')->after('lowongan_id');
            $table->foreignId('data_pegawai_id')->nullable()->after('data_pelamar_id')->constrained('data_pegawais')->cascadeOnDelete();
            $table->string('jabatan_tujuan')->nullable()->after('jalur');
            $table->string('unit_kerja_tujuan')->nullable()->after('jabatan_tujuan');
            $table->string('lokasi_kerja_tujuan')->nullable()->after('unit_kerja_tujuan');
            $table->string('direkomendasikan_oleh', 150)->nullable()->after('lokasi_kerja_tujuan');
            $table->string('token_jadwal', 64)->nullable()->unique()->after('direkomendasikan_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('lamarans', function (Blueprint $table) {
            $table->dropForeign(['data_pegawai_id']);
            $table->dropUnique(['token_jadwal']);
            $table->dropColumn([
                'jalur', 'data_pegawai_id', 'jabatan_tujuan', 'unit_kerja_tujuan',
                'lokasi_kerja_tujuan', 'direkomendasikan_oleh', 'token_jadwal',
            ]);
        });
    }
};
