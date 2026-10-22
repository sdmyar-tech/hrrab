<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Surat peringatan (SP) bulanan: SP1 → SP2 → SP3 bertahap dalam satu bulan kalender, mulai lagi tiap bulan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_peringatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')->constrained('data_pegawais')->cascadeOnDelete();
            $table->string('unit_kerja')->nullable();                 // snapshot saat SP diberikan
            $table->date('tanggal');
            $table->unsignedTinyInteger('tingkat');                   // 1–3, dihitung per bulan tanggal
            $table->string('alasan', 255);
            $table->text('keterangan')->nullable();
            $table->foreignId('diberikan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['data_pegawai_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_peringatans');
    }
};
