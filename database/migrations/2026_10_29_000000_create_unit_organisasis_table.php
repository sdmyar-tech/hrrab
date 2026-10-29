<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Unit (tanpa koordinator) — terpisah dari Bagian (tabel units, punya koordinator & alur persetujuan).
     * Pegawai memilih Unit di data kepegawaian (kepegawaians.unit_organisasi, menyimpan nama unit). Isi awal kosong.
     */
    public function up(): void
    {
        Schema::create('unit_organisasis', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->unsignedInteger('urutan')->default(0);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
        });

        Schema::table('kepegawaians', function (Blueprint $table) {
            $table->string('unit_organisasi', 100)->nullable()->after('unit_kerja')->index();
        });
    }

    public function down(): void
    {
        Schema::table('kepegawaians', function (Blueprint $table) {
            $table->dropIndex(['unit_organisasi']);
            $table->dropColumn('unit_organisasi');
        });
        Schema::dropIfExists('unit_organisasis');
    }
};
