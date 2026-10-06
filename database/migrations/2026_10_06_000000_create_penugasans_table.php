<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')->constrained('data_pegawais')->cascadeOnDelete();
            $table->string('unit_kerja', 100);
            $table->string('jabatan')->nullable();
            $table->enum('peran', ['anggota', 'kepala'])->default('anggota');
            $table->boolean('is_utama')->default(false);
            $table->string('jenis', 20)->default('awal');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('no_sk', 100)->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['unit_kerja', 'tanggal_selesai']);
            $table->index(['data_pegawai_id', 'is_utama']);
        });

        foreach (['pengajuan_cutis', 'pengajuan_lemburs'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->string('unit_kerja', 100)->nullable()->after('data_pegawai_id')->index();
            });
            DB::statement("UPDATE {$tabel} p JOIN kepegawaians k ON k.data_pegawai_id = p.data_pegawai_id SET p.unit_kerja = k.unit_kerja");
        }

        $this->isiDariDataLama();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kepala_unit');
        });
    }

    private function isiDariDataLama(): void
    {
        $now = now();
        $hariIni = $now->toDateString();

        $kepalaPerPegawai = [];
        foreach (DB::table('users')->whereNotNull('data_pegawai_id')->get(['data_pegawai_id', 'role', 'kepala_unit']) as $u) {
            $units = json_decode((string) $u->kepala_unit, true) ?: [];
            if (! $units && in_array($u->role, ['kepala_bagian', 'kepala_sdm'], true)) {
                $units = ['__unit_sendiri__'];
            }
            if ($units) {
                $kepalaPerPegawai[$u->data_pegawai_id] = $units;
            }
        }

        foreach (DB::table('kepegawaians')->whereNotNull('unit_kerja')->get() as $k) {
            $units = $kepalaPerPegawai[$k->data_pegawai_id] ?? [];
            $memimpinUtama = in_array('__unit_sendiri__', $units, true) || in_array($k->unit_kerja, $units, true);

            DB::table('penugasans')->insert([
                'data_pegawai_id' => $k->data_pegawai_id,
                'unit_kerja' => $k->unit_kerja,
                'jabatan' => $k->jabatan,
                'peran' => $memimpinUtama ? 'kepala' : 'anggota',
                'is_utama' => true,
                'jenis' => 'awal',
                'tanggal_mulai' => $k->tanggal_masuk && $k->tanggal_masuk <= $hariIni ? $k->tanggal_masuk : $hariIni,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (array_diff($units, ['__unit_sendiri__', $k->unit_kerja]) as $unit) {
                DB::table('penugasans')->insert([
                    'data_pegawai_id' => $k->data_pegawai_id,
                    'unit_kerja' => $unit,
                    'jabatan' => 'KEPALA ' . $unit,
                    'peran' => 'kepala',
                    'is_utama' => false,
                    'jenis' => 'rangkap',
                    'tanggal_mulai' => $hariIni,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('kepala_unit')->nullable()->after('divisi');
        });

        $hariIni = now()->toDateString();
        $aktif = DB::table('penugasans')->where('peran', 'kepala')->where('tanggal_mulai', '<=', $hariIni)
            ->where(fn ($q) => $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $hariIni))
            ->get()->groupBy('data_pegawai_id');
        foreach ($aktif as $pegawaiId => $rows) {
            $hanyaUtama = $rows->count() === 1 && $rows->first()->is_utama;
            foreach (DB::table('users')->where('data_pegawai_id', $pegawaiId)->get(['id', 'role']) as $u) {
                if ($hanyaUtama && in_array($u->role, ['kepala_bagian', 'kepala_sdm'], true)) {
                    continue;
                }
                DB::table('users')->where('id', $u->id)->update(['kepala_unit' => json_encode($rows->pluck('unit_kerja')->values())]);
            }
        }

        foreach (['pengajuan_cutis', 'pengajuan_lemburs'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropIndex(['unit_kerja']);
                $table->dropColumn('unit_kerja');
            });
        }

        Schema::dropIfExists('penugasans');
    }
};
