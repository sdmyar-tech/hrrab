<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_jadwals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lamaran_id')->constrained('lamarans')->cascadeOnDelete();
            $table->unsignedTinyInteger('pertemuan_ke')->default(1);
            $table->date('tanggal');
            $table->time('waktu_mulai');
            $table->time('waktu_selesai')->nullable();
            $table->string('lokasi')->nullable()->comment('Alamat atau link meeting online');
            $table->text('catatan')->nullable();
            $table->string('status', 20)->default('terjadwal');
            $table->foreignId('dijadwalkan_oleh')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_jadwals');
    }
};
