<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_formasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diajukan_oleh')->constrained('users')->cascadeOnDelete();
            $table->string('unit_kerja');
            $table->string('jabatan');
            $table->unsignedTinyInteger('jumlah_dibutuhkan')->default(1);
            $table->text('alasan');
            $table->string('status', 20)->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('direview_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('direview_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_formasis');
    }
};
