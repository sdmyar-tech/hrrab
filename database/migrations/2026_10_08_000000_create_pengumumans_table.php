<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumumans', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 150);
            $table->text('isi');
            $table->string('kategori', 20)->default('info');
            $table->string('jadwal', 10)->default('sekali');
            $table->date('tanggal')->nullable();
            $table->unsignedTinyInteger('hari')->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('lokasi', 150)->nullable();
            $table->json('sasaran_unit')->nullable();
            $table->boolean('penting')->default(false);
            $table->date('tampil_sampai')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tampil_sampai', 'penting']);
        });

        Schema::create('pengumuman_dibacas', function (Blueprint $table) {
            $table->foreignId('pengumuman_id')->constrained('pengumumans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('dibaca_pada')->useCurrent();
            $table->primary(['pengumuman_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman_dibacas');
        Schema::dropIfExists('pengumumans');
    }
};
