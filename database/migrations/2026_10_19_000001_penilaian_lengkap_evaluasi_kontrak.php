<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluasi kontrak memakai formulir penilaian lengkap (50 butir, skala 1–4, config/penilaian_kontrak.php)
 * menggantikan 3 aspek ringkas; ditambah periode penilaian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasi_kontraks', function (Blueprint $table) {
            $table->json('nilai')->nullable()->after('lama_bulan');            // {nomor_butir: 1..4}
            $table->unsignedSmallInteger('total_nilai')->nullable()->after('nilai');
            $table->decimal('rata_nilai', 3, 2)->nullable()->after('total_nilai');
            $table->date('periode_mulai')->nullable()->after('rata_nilai');
            $table->date('periode_selesai')->nullable()->after('periode_mulai');
            $table->dropColumn(['nilai_kinerja', 'nilai_disiplin', 'nilai_sikap']);
        });
    }

    public function down(): void
    {
        Schema::table('evaluasi_kontraks', function (Blueprint $table) {
            $table->dropColumn(['nilai', 'total_nilai', 'rata_nilai', 'periode_mulai', 'periode_selesai']);
            $table->unsignedTinyInteger('nilai_kinerja')->nullable();
            $table->unsignedTinyInteger('nilai_disiplin')->nullable();
            $table->unsignedTinyInteger('nilai_sikap')->nullable();
        });
    }
};
