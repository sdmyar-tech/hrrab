<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tangga Alur per orang: baris tangga_alurs boleh milik satu pegawai (sasaran "pribadi", unit_id kosong).
 * Tangga pribadi didahulukan di atas tangga bagian dan alur otomatis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tangga_alurs', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropUnique(['unit_id', 'sasaran']);
        });
        Schema::table('tangga_alurs', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->change();
            $table->foreignId('data_pegawai_id')->nullable()->after('unit_id')->constrained('data_pegawais')->cascadeOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->cascadeOnDelete();
            $table->unique(['unit_id', 'sasaran']);
            $table->unique('data_pegawai_id');
        });
    }

    public function down(): void
    {
        \DB::table('tangga_alurs')->whereNotNull('data_pegawai_id')->delete();
        Schema::table('tangga_alurs', function (Blueprint $table) {
            $table->dropUnique(['data_pegawai_id']);
            $table->dropForeign(['data_pegawai_id']);
            $table->dropColumn('data_pegawai_id');
        });
    }
};
