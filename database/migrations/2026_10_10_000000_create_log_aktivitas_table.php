<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Nama & role disalin saat kejadian agar log tetap terbaca walau akun diubah/dihapus.
            $table->string('nama_pengguna', 150);
            $table->string('role', 30)->nullable();
            $table->string('aksi', 40);
            $table->string('modul', 60);
            $table->string('deskripsi', 500);
            $table->string('status', 10)->default('berhasil');
            $table->json('perubahan')->nullable();
            $table->string('route', 120)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('perangkat', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['modul', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
