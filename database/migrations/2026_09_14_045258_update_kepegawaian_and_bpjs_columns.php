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
        Schema::table('kepegawaians', function (Blueprint $table) {
            $table->dropColumn('masa_kerja');
        });

        Schema::table('bpjs_pegawais', function (Blueprint $table) {
            $table->string('faskes_tingkat_1')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kepegawaians', function (Blueprint $table) {
            $table->string('masa_kerja')->nullable();
        });

        Schema::table('bpjs_pegawais', function (Blueprint $table) {
            $table->string('faskes_tingkat_1')->nullable()->change();
        });
    }
};
