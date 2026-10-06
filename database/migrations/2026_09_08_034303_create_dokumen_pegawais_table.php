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
        Schema::create('dokumen_pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')
                ->constrained('data_pegawais')
                ->cascadeOnDelete();
            $table->string('upload_ktp')->nullable();
            $table->string('upload_kk')->nullable();
            $table->string('upload_cv')->nullable();
            $table->string('upload_ijazah_terakhir')->nullable();
            $table->string('upload_buku_tabungan')->nullable();
            $table->string('pas_foto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_pegawais');
    }
};

