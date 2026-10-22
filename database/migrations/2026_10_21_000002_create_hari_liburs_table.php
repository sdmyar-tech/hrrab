<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal merah (sebelumnya daftar tetap di config/hrrab.php 'hari_libur').
     * sumber "api" = hasil sinkronisasi otomatis (nama/jenis boleh diperbarui sinkron berikutnya; status aktif tidak);
     * sumber "manual" = diisi/diubah pengelola, tidak pernah disentuh sinkronisasi.
     */
    public function up(): void
    {
        Schema::create('hari_liburs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('nama', 150);
            $table->string('jenis', 20)->default('nasional');
            $table->string('sumber', 10)->default('manual');
            $table->boolean('aktif')->default(true);
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_liburs');
    }
};
