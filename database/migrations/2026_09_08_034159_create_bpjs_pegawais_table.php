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
        Schema::create('bpjs_pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')
                ->constrained('data_pegawais')
                ->cascadeOnDelete();
            $table->string('no_bpjs_ketenagakerjaan', 30)->nullable();
            $table->string('no_bpjs_kesehatan', 30)->nullable();
            $table->string('faskes_tingkat_1')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bpjs_pegawais');
    }
};

