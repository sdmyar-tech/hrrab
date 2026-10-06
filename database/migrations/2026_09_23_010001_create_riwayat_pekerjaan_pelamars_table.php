<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_pelamars', function (Blueprint $table) {
            $table->string('ijazah_path')->nullable()->after('cv_path');
            $table->string('sertifikat_path')->nullable()->after('ijazah_path');
        });

        Schema::create('riwayat_pekerjaan_pelamars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pelamar_id')->constrained('data_pelamars')->cascadeOnDelete();
            $table->string('nama_perusahaan');
            $table->string('posisi');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable()->comment('Kosong berarti masih bekerja di sana');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_pekerjaan_pelamars');

        Schema::table('data_pelamars', function (Blueprint $table) {
            $table->dropColumn(['ijazah_path', 'sertifikat_path']);
        });
    }
};
