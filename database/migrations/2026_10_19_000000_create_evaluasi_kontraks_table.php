<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluasi kontrak: H-30 sebelum kontrak habis, atasan mengisi rekomendasi + penilaian,
 * lalu Kepala SDM memutuskan (perpanjang / angkat tetap / tidak diperpanjang).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi_kontraks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')->constrained('data_pegawais')->cascadeOnDelete();
            $table->string('unit_kerja')->nullable();          // snapshot saat evaluasi dibuat
            $table->string('status_kerja', 30)->nullable();
            $table->date('tanggal_habis_kontrak');              // kontrak yang dievaluasi
            $table->string('status', 20)->default('menunggu_atasan')->index();

            // Diisi atasan
            $table->string('rekomendasi', 20)->nullable();      // perpanjang | tetap | tidak
            $table->unsignedTinyInteger('lama_bulan')->nullable();
            $table->unsignedTinyInteger('nilai_kinerja')->nullable();
            $table->unsignedTinyInteger('nilai_disiplin')->nullable();
            $table->unsignedTinyInteger('nilai_sikap')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('diisi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diisi_pada')->nullable();

            // Diputuskan Kepala SDM
            $table->string('keputusan', 20)->nullable();
            $table->unsignedTinyInteger('keputusan_lama_bulan')->nullable();
            $table->text('catatan_sdm')->nullable();
            $table->foreignId('diputuskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diputuskan_pada')->nullable();

            $table->timestamps();
            $table->unique(['data_pegawai_id', 'tanggal_habis_kontrak']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_kontraks');
    }
};
