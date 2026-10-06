<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_pegawais', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('nip')->nullable()->change();
            $table->string('nik', 20)->nullable()->change();
            $table->string('no_hp', 20)->nullable()->change();
            $table->string('tempat_lahir')->nullable()->change();
            $table->date('tanggal_lahir')->nullable()->change();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable()->change();
            $table->string('agama')->nullable()->change();
            $table->enum('status_pernikahan', ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'])->nullable()->default('Belum Menikah')->change();
            $table->string('nama_ibu_kandung')->nullable()->change();
            $table->string('pendidikan_terakhir')->nullable()->change();
            $table->text('alamat_ktp')->nullable()->change();
        });

        Schema::table('kepegawaians', function (Blueprint $table) {
            $table->string('lokasi_kerja')->nullable()->change();
            $table->date('tanggal_masuk')->nullable()->change();
        });

        Schema::table('kontak_darurats', function (Blueprint $table) {
            $table->string('no_hp_kerabat', 20)->nullable()->change();
            $table->string('nama_kerabat')->nullable()->change();
            $table->string('hubungan_kerabat')->nullable()->change();
        });
    }

    public function down(): void
    {
        //
    }
};
