<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengajuan formasi:
 * - jumlah_bebas: kebutuhan tanpa syarat jenis kelamin;
 * - diganti: pegawai lama yang digantikan (dipilih dari bagan unit, untuk "replacement");
 * - slot_tambahan: keterangan tiap posisi baru (kotak kosong di bagan, untuk "penambahan").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_formasis', function (Blueprint $table) {
            $table->unsignedTinyInteger('jumlah_bebas')->default(0)->after('jumlah_perempuan');
            $table->json('diganti')->nullable()->after('tugas');
            $table->json('slot_tambahan')->nullable()->after('diganti');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_formasis', function (Blueprint $table) {
            $table->dropColumn(['jumlah_bebas', 'diganti', 'slot_tambahan']);
        });
    }
};
