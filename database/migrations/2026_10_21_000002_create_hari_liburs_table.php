<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal merah (sebelumnya daftar tetap di config/hrrab.php 'hari_libur').
     * sumber "api" = hasil sinkronisasi otomatis (nama/jenis boleh diperbarui sinkron berikutnya; status aktif tidak);
     * sumber "manual" = diisi/diubah pengelola, tidak pernah disentuh sinkronisasi.
     */
    public function up(): void
    {
        Schema::create('hari_liburs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('nama', 150);
            $table->string('jenis', 20)->default('nasional');
            $table->string('sumber', 10)->default('manual');
            $table->boolean('aktif')->default(true);
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Isi awal = libur bertanggal tetap yang dulu ada di config, ditandai "api" agar sinkron pertama menyempurnakan namanya.
        $nama = ['01-01' => 'Tahun Baru Masehi', '05-01' => 'Hari Buruh Internasional', '06-01' => 'Hari Lahir Pancasila',
            '08-17' => 'Hari Kemerdekaan R.I.', '12-25' => 'Hari Raya Natal'];
        $sekarang = now();
        $baris = [];
        foreach (range(2026, 2030) as $tahun) {
            foreach ($nama as $md => $n) {
                $baris[] = ['tanggal' => "{$tahun}-{$md}", 'nama' => $n, 'jenis' => 'nasional', 'sumber' => 'api', 'aktif' => true,
                    'created_at' => $sekarang, 'updated_at' => $sekarang];
            }
        }
        DB::table('hari_liburs')->insert($baris);
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_liburs');
    }
};
