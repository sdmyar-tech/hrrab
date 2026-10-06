<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screening_onboardings', function (Blueprint $table) {
            $table->unsignedTinyInteger('durasi_hari')->default(3)->after('tanggal_selesai');
            $table->string('lokasi')->nullable()->after('durasi_hari');
            $table->text('catatan')->nullable()->after('lokasi');
            $table->foreignId('kepala_id')->nullable()->after('pengawas_id')->constrained('users')->nullOnDelete();

            $table->string('hasil_sdm', 20)->nullable();
            $table->text('catatan_sdm')->nullable();
            $table->foreignId('konfirmasi_sdm_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('konfirmasi_sdm_pada')->nullable();

            $table->string('hasil_kepala', 20)->nullable();
            $table->text('catatan_kepala')->nullable();
            $table->foreignId('konfirmasi_kepala_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('konfirmasi_kepala_pada')->nullable();
        });

        DB::statement('UPDATE screening_onboardings SET durasi_hari = GREATEST(1, DATEDIFF(tanggal_selesai, tanggal_mulai))');
    }

    public function down(): void
    {
        Schema::table('screening_onboardings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kepala_id');
            $table->dropConstrainedForeignId('konfirmasi_sdm_oleh');
            $table->dropConstrainedForeignId('konfirmasi_kepala_oleh');
            $table->dropColumn(['durasi_hari', 'lokasi', 'catatan', 'hasil_sdm', 'catatan_sdm', 'konfirmasi_sdm_pada',
                'hasil_kepala', 'catatan_kepala', 'konfirmasi_kepala_pada']);
        });
    }
};
