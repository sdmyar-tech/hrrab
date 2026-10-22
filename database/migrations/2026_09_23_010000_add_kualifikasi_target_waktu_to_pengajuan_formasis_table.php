<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_formasis', function (Blueprint $table) {
            $table->text('kualifikasi')->nullable()->after('jumlah_dibutuhkan');
            $table->date('target_waktu')->nullable()->after('kualifikasi');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_formasis', function (Blueprint $table) {
            $table->dropColumn(['kualifikasi', 'target_waktu']);
        });
    }
};
