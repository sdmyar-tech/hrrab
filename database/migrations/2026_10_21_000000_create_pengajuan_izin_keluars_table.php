<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Izin keluar saat jam kerja. Alur persetujuan sama dengan cuti/lembur (atasan langsung → Kepala SDM);
     * setelah disetujui akhir, pegawai mendapat tiket (kode_tiket) yang ditunjukkan ke security gerbang.
     */
    public function up(): void
    {
        Schema::create('pengajuan_izin_keluars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')->constrained('data_pegawais')->cascadeOnDelete();
            $table->string('unit_kerja')->nullable();
            $table->date('tanggal');
            $table->time('jam_keluar');
            $table->time('jam_kembali')->nullable();   // kosong = tidak kembali lagi hari itu
            $table->string('keperluan', 20);
            $table->text('alasan');
            $table->string('status', 20)->default('pending');
            $table->string('current_level', 30)->nullable();
            $table->text('catatan_admin')->nullable();
            $table->foreignId('kepala_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('kepala_approved_at')->nullable();
            $table->foreignId('gm_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('gm_approved_at')->nullable();
            $table->boolean('gm_pengganti')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('kode_tiket', 20)->nullable()->unique();
            $table->timestamps();

            $table->index(['data_pegawai_id', 'tanggal']);
            $table->index(['status', 'current_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_izin_keluars');
    }
};
