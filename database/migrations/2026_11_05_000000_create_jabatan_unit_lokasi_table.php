<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lokasi kerja per jabatan di tiap bagian (boleh lebih dari satu, mis. CS di beberapa gedung). Dipakai mengisi /
     * membatasi pilihan lokasi saat bagian & jabatan dipilih (data pegawai, lowongan, kandidat).
     * Isi awal: pasangan bagian + jabatan + lokasi dari pegawai aktif yang jabatannya sudah tertaut ke bagian itu.
     */
    public function up(): void
    {
        Schema::create('jabatan_unit_lokasi', function (Blueprint $table) {
            $table->foreignId('jabatan_id')->constrained('jabatans')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('lokasi_kerja_id')->constrained('lokasi_kerjas')->cascadeOnDelete();
            $table->primary(['jabatan_id', 'unit_id', 'lokasi_kerja_id']);
        });

        $kunci = fn ($v) => mb_strtoupper(trim((string) $v));
        $unitId = DB::table('units')->pluck('id', 'nama')->mapWithKeys(fn ($id, $n) => [$kunci($n) => $id]);
        $jabatanId = DB::table('jabatans')->pluck('id', 'nama')->mapWithKeys(fn ($id, $n) => [$kunci($n) => $id]);
        $lokasiId = DB::table('lokasi_kerjas')->pluck('id', 'nama')->mapWithKeys(fn ($id, $n) => [$kunci($n) => $id]);
        $tertaut = DB::table('jabatan_unit')->get()->map(fn ($r) => $r->jabatan_id . '-' . $r->unit_id)->flip();

        $baris = DB::table('kepegawaians')->where('status_rekrutmen', 'Aktif')->get(['unit_kerja', 'jabatan', 'lokasi_kerja'])
            ->map(fn ($r) => ['jabatan_id' => $jabatanId[$kunci($r->jabatan)] ?? null, 'unit_id' => $unitId[$kunci($r->unit_kerja)] ?? null,
                'lokasi_kerja_id' => $lokasiId[$kunci($r->lokasi_kerja)] ?? null])
            ->filter(fn ($p) => $p['jabatan_id'] && $p['unit_id'] && $p['lokasi_kerja_id'] && $tertaut->has($p['jabatan_id'] . '-' . $p['unit_id']))
            ->unique(fn ($p) => implode('-', $p))
            ->values();
        foreach ($baris->chunk(500) as $potong) {
            DB::table('jabatan_unit_lokasi')->insert($potong->all());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan_unit_lokasi');
    }
};
