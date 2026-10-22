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
        Schema::create('kontak_darurats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')
                ->constrained('data_pegawais')
                ->cascadeOnDelete();
            $table->string('no_hp_kerabat', 20);
            $table->string('nama_kerabat');
            $table->string('hubungan_kerabat');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontak_darurats');
    }
};
