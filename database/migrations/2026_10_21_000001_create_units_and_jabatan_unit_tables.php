<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Unit (sebelumnya daftar tetap di config kepegawaian.unit_kerja) + hubungan jabatan ↔ unit.
     * Isi awal hanya dari data yang sudah ada (instalasi baru = kosong): unit yang terpakai di data; pasangan
     * jabatan–unit dari pegawai & penugasan aktif (sama dengan aturan lama "jabatan yang ada di unit itu").
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->unsignedInteger('urutan')->default(0);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('jabatan_unit', function (Blueprint $table) {
            $table->foreignId('jabatan_id')->constrained('jabatans')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->primary(['jabatan_id', 'unit_id']);
        });

        $sekarang = now();
        $nama = DB::table('kepegawaians')->whereNotNull('unit_kerja')->distinct()->pluck('unit_kerja')
            ->merge(DB::table('penugasans')->whereNotNull('unit_kerja')->distinct()->pluck('unit_kerja'))
            ->map(fn ($u) => trim((string) $u))->filter()->unique(fn ($u) => mb_strtoupper($u))->values();
        DB::table('units')->insert($nama->map(fn ($u, $i) => ['nama' => $u, 'urutan' => $i + 1, 'created_at' => $sekarang, 'updated_at' => $sekarang])->all());

        $unitId = DB::table('units')->pluck('id', 'nama')->mapWithKeys(fn ($id, $n) => [mb_strtoupper($n) => $id]);
        $jabatanId = DB::table('jabatans')->pluck('id', 'nama')->mapWithKeys(fn ($id, $n) => [mb_strtoupper(trim($n)) => $id]);
        $hariIni = $sekarang->toDateString();

        $pasangan = DB::table('penugasans')->where('tanggal_mulai', '<=', $hariIni)
            ->where(fn ($q) => $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $hariIni))
            ->get(['unit_kerja', 'jabatan'])
            ->concat(DB::table('kepegawaians')->where('status_rekrutmen', 'Aktif')->get(['unit_kerja', 'jabatan']))
            ->map(fn ($r) => [$jabatanId[mb_strtoupper(trim((string) $r->jabatan))] ?? null, $unitId[mb_strtoupper(trim((string) $r->unit_kerja))] ?? null])
            ->filter(fn ($p) => $p[0] && $p[1])
            ->unique(fn ($p) => $p[0] . '-' . $p[1])
            ->map(fn ($p) => ['jabatan_id' => $p[0], 'unit_id' => $p[1]])
            ->values();
        foreach ($pasangan->chunk(500) as $potong) {
            DB::table('jabatan_unit')->insert($potong->all());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan_unit');
        Schema::dropIfExists('units');
    }
};
