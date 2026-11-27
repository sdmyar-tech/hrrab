<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat kontrak pegawai yang diinput manual (data lama sebelum sistem dipakai): Masa Training, PKWT 1, PKWT 2, … PKWTT.
 * Dipakai untuk menghitung "kontrak ke-N" di Evaluasi Kontrak dan ditampilkan di detail pegawai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontrak_pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')->constrained('data_pegawais')->cascadeOnDelete();
            $table->string('jenis', 30);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable(); // PKWTT / lainnya tanpa batas akhir
            $table->string('nomor_kontrak', 100)->nullable();
            $table->string('keterangan', 500)->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['data_pegawai_id', 'tanggal_mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontrak_pegawais');
    }
};
