<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Form pengajuan formasi mengikuti formulir kertas "Permintaan Karyawan":
 * jenis kebutuhan (replacement / penambahan), jumlah L/P, tanggal dibutuhkan, persyaratan (pendidikan, usia,
 * keahlian, atasan langsung, tugas). Penambahan karyawan disetujui GM unit DAN Owner secara paralel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_formasis', function (Blueprint $table) {
            // replacement | penambahan; NULL = pengajuan lama (sebelum formulir baru) → alur lama.
            $table->string('jenis_kebutuhan', 20)->nullable()->after('unit_kerja');
            $table->unsignedTinyInteger('jumlah_laki')->default(0)->after('jumlah_dibutuhkan');
            $table->unsignedTinyInteger('jumlah_perempuan')->default(0)->after('jumlah_laki');
            $table->string('min_pendidikan', 50)->nullable()->after('jumlah_perempuan');
            $table->unsignedTinyInteger('min_usia')->nullable()->after('min_pendidikan');
            $table->string('atasan_langsung', 150)->nullable()->after('min_usia');
            $table->text('tugas')->nullable()->after('atasan_langsung');
            // Persetujuan paralel (khusus penambahan): GM unit & Owner.
            $table->foreignId('gm_disetujui_oleh')->nullable()->after('direview_pada')->constrained('users')->nullOnDelete();
            $table->timestamp('gm_disetujui_pada')->nullable()->after('gm_disetujui_oleh');
            $table->foreignId('owner_disetujui_oleh')->nullable()->after('gm_disetujui_pada')->constrained('users')->nullOnDelete();
            $table->timestamp('owner_disetujui_pada')->nullable()->after('owner_disetujui_oleh');
            // "Alasan" tidak ada di formulir baru (diganti keterangan opsional).
            $table->text('alasan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_formasis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gm_disetujui_oleh');
            $table->dropConstrainedForeignId('owner_disetujui_oleh');
            $table->dropColumn(['jenis_kebutuhan', 'jumlah_laki', 'jumlah_perempuan', 'min_pendidikan', 'min_usia', 'atasan_langsung', 'tugas',
                'gm_disetujui_pada', 'owner_disetujui_pada']);
        });
    }
};
