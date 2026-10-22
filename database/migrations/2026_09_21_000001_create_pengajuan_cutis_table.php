<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')
                ->constrained('data_pegawais')
                ->cascadeOnDelete();
            $table->string('jenis_cuti', 20);
            $table->string('kategori_cuti_besar', 40)->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedInteger('lama_cuti');
            $table->text('alamat_selama_cuti');
            $table->string('telepon_selama_cuti', 30);
            $table->text('keterangan')->nullable();
            $table->string('pengganti_nama')->nullable();
            $table->boolean('pengganti_setuju')->default(false);
            $table->string('status', 20)->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_cutis');
    }
};
