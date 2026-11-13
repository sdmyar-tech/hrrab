<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pegawai mengajukan penggantian kata sandi; setelah disetujui Admin/SDM, form ganti kata sandi
     * (dialog) terbuka untuk pegawai itu. Satu persetujuan = satu kali ganti (status "used").
     */
    public function up(): void
    {
        Schema::create('permintaan_ganti_passwords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('alasan', 500)->nullable();
            $table->string('status', 10)->default('pending')->index(); // pending|approved|rejected|used
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_pada')->nullable();
            $table->string('catatan', 500)->nullable();
            $table->timestamp('dipakai_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_ganti_passwords');
    }
};
