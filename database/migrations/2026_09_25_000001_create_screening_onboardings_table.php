<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screening_onboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lamaran_id')->constrained('lamarans')->cascadeOnDelete();
            $table->foreignId('pengawas_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status', 20)->default('berjalan');
            $table->string('hasil_status', 20)->nullable();
            $table->text('catatan_hasil')->nullable();
            $table->foreignId('diisi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diisi_pada')->nullable();
            $table->foreignId('dijadwalkan_oleh')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screening_onboardings');
    }
};
