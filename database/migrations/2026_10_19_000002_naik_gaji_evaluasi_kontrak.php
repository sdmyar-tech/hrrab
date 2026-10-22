<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluasi kontrak: usulan naik gaji (atasan) & keputusannya (Kepala SDM).
 * Hanya catatan nominal + keterangan — tidak terhubung ke data/perhitungan gaji.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasi_kontraks', function (Blueprint $table) {
            $table->boolean('naik_gaji')->default(false)->after('lama_bulan');
            $table->unsignedInteger('naik_gaji_nominal')->nullable()->after('naik_gaji');      // rupiah
            $table->string('naik_gaji_keterangan')->nullable()->after('naik_gaji_nominal');
            $table->boolean('keputusan_naik_gaji')->default(false)->after('keputusan_lama_bulan');
            $table->unsignedInteger('keputusan_naik_gaji_nominal')->nullable()->after('keputusan_naik_gaji');
        });
    }

    public function down(): void
    {
        Schema::table('evaluasi_kontraks', function (Blueprint $table) {
            $table->dropColumn(['naik_gaji', 'naik_gaji_nominal', 'naik_gaji_keterangan', 'keputusan_naik_gaji', 'keputusan_naik_gaji_nominal']);
        });
    }
};
