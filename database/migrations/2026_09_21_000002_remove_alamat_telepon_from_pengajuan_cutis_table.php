<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->dropColumn(['alamat_selama_cuti', 'telepon_selama_cuti']);
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->text('alamat_selama_cuti')->nullable();
            $table->string('telepon_selama_cuti', 30)->nullable();
        });
    }
};
