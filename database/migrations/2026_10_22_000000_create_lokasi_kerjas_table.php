<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Lokasi Kerja (sebelumnya daftar tetap di config kepegawaian.lokasi_kerja).
     * Instalasi baru = kosong. Basis data yang sudah berisi pegawai: daftar config lama + lokasi yang terpakai di data,
     * agar pilihan yang sudah ada tidak hilang.
     */
    public function up(): void
    {
        Schema::create('lokasi_kerjas', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->unsignedInteger('urutan')->default(0);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
        });

        if (! DB::table('kepegawaians')->exists()) {
            return;
        }

        $sekarang = now();
        $nama = collect(config('kepegawaian.lokasi_kerja', []))
            ->merge(DB::table('kepegawaians')->whereNotNull('lokasi_kerja')->distinct()->pluck('lokasi_kerja'))
            ->merge(DB::table('lowongans')->whereNotNull('lokasi_kerja')->distinct()->pluck('lokasi_kerja'))
            ->merge(DB::table('lamarans')->whereNotNull('lokasi_kerja_tujuan')->distinct()->pluck('lokasi_kerja_tujuan'))
            ->map(fn ($l) => trim((string) $l))->filter()->unique(fn ($l) => mb_strtoupper($l))->values();

        DB::table('lokasi_kerjas')->insert($nama->map(fn ($l, $i) => ['nama' => $l, 'urutan' => $i + 1, 'created_at' => $sekarang, 'updated_at' => $sekarang])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('lokasi_kerjas');
    }
};
