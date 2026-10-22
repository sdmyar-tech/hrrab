<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lamarans', function (Blueprint $table) {
            $table->index('data_pelamar_id');
        });

        Schema::table('lamarans', function (Blueprint $table) {
            $table->dropUnique(['data_pelamar_id', 'lowongan_id']);
        });

        Schema::table('interview_jadwals', function (Blueprint $table) {
            $table->foreignId('interviewer_id')->nullable()->after('lamaran_id')->constrained('users')->nullOnDelete();
            $table->string('hasil_status', 20)->nullable()->after('status');
            $table->text('catatan_hasil')->nullable()->after('hasil_status');
            $table->foreignId('diisi_oleh')->nullable()->after('catatan_hasil')->constrained('users')->nullOnDelete();
            $table->timestamp('diisi_pada')->nullable()->after('diisi_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('interview_jadwals', function (Blueprint $table) {
            $table->dropForeign(['interviewer_id']);
            $table->dropForeign(['diisi_oleh']);
            $table->dropColumn(['interviewer_id', 'hasil_status', 'catatan_hasil', 'diisi_oleh', 'diisi_pada']);
        });

        Schema::table('lamarans', function (Blueprint $table) {
            $table->dropIndex(['data_pelamar_id']);
            $table->unique(['data_pelamar_id', 'lowongan_id']);
        });
    }
};
