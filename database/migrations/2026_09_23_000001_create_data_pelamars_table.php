<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_pelamars', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('nama_lengkap');
            $table->string('nik', 20)->nullable()->unique()->comment('No. KTP');
            $table->string('npwp', 25)->nullable();
            $table->string('no_kk', 20)->nullable();
            $table->string('no_hp', 20);
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('agama');
            $table->enum('golongan_darah', ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-', 'Rh-Null', 'Belum Diketahui'])->nullable()->default(null);
            $table->enum('status_pernikahan', ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'])->default('Belum Menikah');
            $table->unsignedTinyInteger('jumlah_tanggungan')->default(0);
            $table->string('nama_ibu_kandung');
            $table->string('pendidikan_terakhir');
            $table->text('alamat_ktp');
            $table->text('alamat_domisili')->nullable();
            $table->string('foto_path')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('portofolio_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_pelamars');
    }
};
