<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kepegawaians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')
                ->constrained('data_pegawais')
                ->cascadeOnDelete();
            $table->string('jabatan');
            $table->string('jabatan_tambahan')->nullable();
            $table->string('unit_kerja');
            $table->string('lokasi_kerja');
            $table->date('tanggal_masuk');
            $table->string('masa_kerja')->nullable()->comment('Bisa dihitung otomatis dari tanggal_masuk, disimpan sebagai cache/snapshot');
            $table->enum('status_rekrutmen', ['Aktif', 'Tidak Aktif'])->default('Aktif');
            $table->enum('status_kerja', ['Tetap', 'Kontrak', 'Magang', 'Harian Lepas', 'Probation'])->default('Kontrak');
            $table->date('tanggal_habis_kontrak')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kepegawaians');
    }
};
