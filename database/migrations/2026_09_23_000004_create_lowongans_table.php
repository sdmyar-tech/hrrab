<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lowongans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_formasi_id')->nullable()->constrained('pengajuan_formasis')->nullOnDelete();
            $table->string('judul');
            $table->string('unit_kerja');
            $table->string('jabatan');
            $table->unsignedTinyInteger('jumlah_dibutuhkan')->default(1);
            $table->unsignedTinyInteger('jumlah_terisi')->default(0);
            $table->text('deskripsi')->nullable();
            $table->text('kualifikasi')->nullable();
            $table->string('lokasi_kerja')->nullable();
            $table->string('tipe_kerja')->nullable()->comment('Full Time, Part Time, Kontrak, Magang');
            $table->string('status', 20)->default('draft');
            $table->date('tanggal_tutup')->nullable();
            $table->foreignId('dipublikasikan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dipublikasikan_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lowongans');
    }
};
