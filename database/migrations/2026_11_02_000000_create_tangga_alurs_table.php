<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tangga Alur Pengajuan: alur persetujuan cuti/lembur/izin keluar yang diatur bebas per bagian.
 * 1. tangga_alurs: definisi tangga per bagian & sasaran (pegawai biasa / koordinator).
 * 2. Kolom tangga + tahap_ke di tiap tabel pengajuan: salinan tangga saat diajukan & tahap yang sedang berjalan.
 * 3. persetujuan_tahaps: siapa menyetujui tahap ke berapa (untuk tahap "semua harus setuju" & riwayat).
 */
return new class extends Migration
{
    private const PENGAJUAN = ['pengajuan_cutis', 'pengajuan_lemburs', 'pengajuan_izin_keluars'];

    public function up(): void
    {
        Schema::create('tangga_alurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('sasaran', 20);          // staf | koordinator
            $table->json('tahap');                  // [{nama, semua, penyetuju: [{tipe, unit, jabatan_id, user_id}]}]
            $table->string('catatan', 255)->nullable();
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['unit_id', 'sasaran']);
        });

        foreach (self::PENGAJUAN as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->json('tangga')->nullable()->after('current_level');                 // null = alur bawaan
                $table->unsignedTinyInteger('tahap_ke')->nullable()->after('tangga');       // indeks tahap tangga (0 = pertama)
            });
        }

        Schema::create('persetujuan_tahaps', function (Blueprint $table) {
            $table->id();
            $table->morphs('pengajuan');
            $table->unsignedTinyInteger('tahap_ke');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['pengajuan_type', 'pengajuan_id', 'tahap_ke', 'user_id'], 'persetujuan_tahap_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persetujuan_tahaps');
        foreach (self::PENGAJUAN as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropColumn(['tangga', 'tahap_ke']);
            });
        }
        Schema::dropIfExists('tangga_alurs');
    }
};
