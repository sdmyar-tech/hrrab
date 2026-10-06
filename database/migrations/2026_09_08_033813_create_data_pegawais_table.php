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
        Schema::create('data_pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('nip')->unique()->comment('No. Induk Pegawai');
            $table->string('nama_lengkap');
            $table->string('nik', 20)->unique()->comment('No. KTP');
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
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_pegawais');
    }
};
