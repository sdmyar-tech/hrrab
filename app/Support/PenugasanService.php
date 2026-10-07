<?php

namespace App\Support;

use App\Models\DataPegawai;
use App\Models\InterviewJadwal;
use App\Models\PengajuanCuti;
use App\Models\PengajuanLembur;
use App\Models\Penugasan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Semua perubahan penempatan pegawai lewat sini. */
class PenugasanService
{
    public const PENDING_LANJUT = 'lanjut';
    public const PENDING_TOLAK = 'tolak';

    /** Penempatan awal pegawai baru (tambah pegawai, rekrut pelamar, import). */
    public static function buatUtama(DataPegawai $pegawai, string $unit, ?string $jabatan, $mulai = null, string $peran = Penugasan::PERAN_ANGGOTA, ?User $oleh = null): Penugasan
    {
        return $pegawai->penugasans()->create([
            'unit_kerja' => $unit,
            'jabatan' => $jabatan,
            'peran' => $peran,
            'is_utama' => true,
            'jenis' => Penugasan::JENIS_AWAL,
            'tanggal_mulai' => Carbon::parse($mulai ?? now())->toDateString(),
            'dibuat_oleh' => $oleh?->id,
        ]);
    }

    /**
     * Pindahkan unit utama pegawai mulai tanggal efektif (boleh tanggal ke depan — dijalankan scheduler).
     *
     * @param  array{unit_kerja: string, jabatan: ?string, peran: string, tanggal_efektif: string, no_sk?: ?string, keterangan?: ?string, pengajuan_pending?: string}  $data
     * @return list<string> peringatan untuk ditampilkan ke admin
     */
    public static function mutasi(DataPegawai $pegawai, array $data, ?User $oleh): array
    {
        $efektif = Carbon::parse($data['tanggal_efektif'])->startOfDay();
        $lama = self::utamaTerbuka($pegawai);

        if ($pegawai->penugasans()->where('is_utama', true)->where('tanggal_mulai', '>', now()->toDateString())->exists()) {
            throw ValidationException::withMessages(['tanggal_efektif' => 'Pegawai ini masih punya mutasi terjadwal. Batalkan dulu mutasi tersebut.']);
        }
        if ($lama && $efektif->lte($lama->tanggal_mulai)) {
            throw ValidationException::withMessages(['tanggal_efektif' => 'Tanggal efektif harus setelah ' . $lama->tanggal_mulai->format('d-m-Y') . ' (mulai penugasan sekarang).']);
        }
        if ($lama && $lama->unit_kerja === $data['unit_kerja'] && $lama->jabatan === ($data['jabatan'] ?? $lama->jabatan) && $lama->peran === $data['peran']) {
            throw ValidationException::withMessages(['unit_kerja' => 'Unit, jabatan, dan peran sama dengan penugasan sekarang.']);
        }

        return DB::transaction(function () use ($pegawai, $data, $oleh, $efektif, $lama) {
            $sehariSebelum = $efektif->copy()->subDay()->toDateString();
            $lama?->update(['tanggal_selesai' => $sehariSebelum]);

            $pegawai->penugasans()->where('is_utama', false)->where('unit_kerja', $data['unit_kerja'])
                ->aktif($efektif)->update(['tanggal_selesai' => $sehariSebelum]);

            $pegawai->penugasans()->create([
                'unit_kerja' => $data['unit_kerja'],
                'jabatan' => $data['jabatan'] ?? $lama?->jabatan,
                'peran' => $data['peran'],
                'is_utama' => true,
                'jenis' => Penugasan::JENIS_MUTASI,
                'tanggal_mulai' => $efektif->toDateString(),
                'no_sk' => $data['no_sk'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'dibuat_oleh' => $oleh?->id,
            ]);

            $peringatan = [];
            if ($lama && ($data['pengajuan_pending'] ?? self::PENDING_LANJUT) === self::PENDING_TOLAK) {
                $jumlah = self::tolakPending($pegawai, $lama->unit_kerja, $oleh);
                if ($jumlah) {
                    $peringatan[] = "{$jumlah} pengajuan cuti/lembur yang masih menunggu di unit {$lama->unit_kerja} ditolak otomatis.";
                }
            }

            if ($lama?->isKepala() && $lama->unit_kerja !== $data['unit_kerja'] && self::unitTanpaKepala($lama->unit_kerja, $efektif)) {
                $peringatan[] = "Unit {$lama->unit_kerja} tidak punya kepala mulai {$efektif->format('d-m-Y')}. Tunjuk kepala baru supaya pengajuan di unit itu tidak macet.";
            }

            return [...$peringatan, ...self::sinkron($pegawai)];
        });
    }

    /**
     * Tambah rangkap tugas di unit lain tanpa melepas unit utama.
     *
     * @param  array{unit_kerja: string, jabatan: ?string, peran: string, tanggal_mulai: string, tanggal_selesai?: ?string, no_sk?: ?string, keterangan?: ?string}  $data
     * @return list<string>
     */
    public static function tambahRangkap(DataPegawai $pegawai, array $data, ?User $oleh): array
    {
        $mulai = Carbon::parse($data['tanggal_mulai'])->toDateString();
        $selesai = ! empty($data['tanggal_selesai']) ? Carbon::parse($data['tanggal_selesai'])->toDateString() : null;

        $bentrok = $pegawai->penugasans()->where('unit_kerja', $data['unit_kerja'])
            ->where(fn ($q) => $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $mulai))
            ->when($selesai, fn ($q) => $q->where('tanggal_mulai', '<=', $selesai))
            ->exists();
        if ($bentrok) {
            throw ValidationException::withMessages(['unit_kerja' => "Pegawai sudah bertugas di unit {$data['unit_kerja']} pada periode itu."]);
        }

        return DB::transaction(function () use ($pegawai, $data, $oleh, $mulai, $selesai) {
            $pegawai->penugasans()->create([
                'unit_kerja' => $data['unit_kerja'],
                'jabatan' => $data['jabatan'] ?? null,
                'peran' => $data['peran'],
                'is_utama' => false,
                'jenis' => Penugasan::JENIS_RANGKAP,
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'no_sk' => $data['no_sk'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'dibuat_oleh' => $oleh?->id,
            ]);

            return self::sinkron($pegawai);
        });
    }

    /**
     * Akhiri rangkap tugas per $tanggal (hari terakhir bertugas).
     *
     * @return list<string>
     */
    public static function akhiri(Penugasan $penugasan, $tanggal): array
    {
        $tanggal = Carbon::parse($tanggal)->startOfDay();

        if ($penugasan->is_utama) {
            throw ValidationException::withMessages(['tanggal_selesai' => 'Penugasan utama tidak bisa diakhiri. Gunakan Mutasi untuk memindahkan unit utama.']);
        }
        if ($tanggal->lt($penugasan->tanggal_mulai)) {
            throw ValidationException::withMessages(['tanggal_selesai' => 'Tanggal selesai tidak boleh sebelum tanggal mulai (' . $penugasan->tanggal_mulai->format('d-m-Y') . ').']);
        }

        return DB::transaction(function () use ($penugasan, $tanggal) {
            $penugasan->update(['tanggal_selesai' => $tanggal->toDateString()]);

            $peringatan = [];
            if ($penugasan->isKepala() && self::unitTanpaKepala($penugasan->unit_kerja, $tanggal->copy()->addDay())) {
                $peringatan[] = "Unit {$penugasan->unit_kerja} tidak punya kepala mulai {$tanggal->copy()->addDay()->format('d-m-Y')}.";
            }

            return [...$peringatan, ...self::sinkron($penugasan->dataPegawai)];
        });
    }

    /**
     * Hapus mutasi/rangkap (terjadwal, berlaku, maupun selesai) — untuk pembatalan atau koreksi salah input.
     * Mutasi hanya bisa dihapus bila merupakan mutasi terakhir; penugasan utama sebelumnya dibuka kembali.
     *
     * @return list<string>
     */
    public static function hapus(Penugasan $penugasan, bool $bebas = false): array
    {
        if ($penugasan->jenis === Penugasan::JENIS_AWAL && ! $bebas) {
            throw ValidationException::withMessages(['penugasan' => 'Penempatan awal pegawai tidak bisa dihapus. Gunakan Mutasi untuk memindahkan unit.']);
        }

        $pegawai = $penugasan->dataPegawai;

        if ($penugasan->jenis === Penugasan::JENIS_AWAL && ! $pegawai->penugasans()->where('is_utama', true)->whereKeyNot($penugasan->id)->exists()) {
            throw ValidationException::withMessages(['penugasan' => 'Ini satu-satunya penempatan utama pegawai, tidak bisa dihapus. Ubah unitnya lewat Edit.']);
        }

        // Mutasi di tengah riwayat boleh dihapus Admin: penempatan sebelumnya disambung sampai tanggal selesai mutasi ini.
        if (! $bebas && $penugasan->is_utama && $pegawai->penugasans()->where('is_utama', true)
            ->where('tanggal_mulai', '>', $penugasan->tanggal_mulai->toDateString())->exists()) {
            throw ValidationException::withMessages(['penugasan' => 'Masih ada mutasi setelah ini. Hapus mutasi yang lebih baru terlebih dahulu.']);
        }

        return DB::transaction(function () use ($penugasan, $pegawai) {
            if ($penugasan->is_utama) {
                $sehariSebelum = $penugasan->tanggal_mulai->copy()->subDay()->toDateString();

                // Buka lagi penempatan utama sebelumnya (dan rangkap di unit tujuan yang ditutup oleh mutasi ini).
                $pegawai->penugasans()->where('is_utama', true)->whereKeyNot($penugasan->id)
                    ->whereDate('tanggal_selesai', $sehariSebelum)
                    ->update(['tanggal_selesai' => $penugasan->tanggal_selesai?->toDateString()]);
                $pegawai->penugasans()->where('is_utama', false)->where('unit_kerja', $penugasan->unit_kerja)
                    ->whereDate('tanggal_selesai', $sehariSebelum)->update(['tanggal_selesai' => null]);
            }

            $penugasan->delete();

            $peringatan = self::sinkron($pegawai);
            if ($penugasan->isKepala() && self::unitTanpaKepala($penugasan->unit_kerja)) {
                $peringatan[] = "Unit {$penugasan->unit_kerja} sekarang tidak punya kepala aktif.";
            }

            return $peringatan;
        });
    }

    /**
     * Admin: ubah langsung satu baris penugasan (unit, jabatan, peran, tanggal) tanpa aturan mutasi.
     *
     * @param  array{unit_kerja: string, jabatan: ?string, peran: string, tanggal_mulai: string, tanggal_selesai: ?string, no_sk: ?string, keterangan: ?string}  $data
     * @return list<string>
     */
    public static function ubahLangsung(Penugasan $penugasan, array $data): array
    {
        return DB::transaction(function () use ($penugasan, $data) {
            $penugasan->update($data);

            return self::sinkron($penugasan->dataPegawai);
        });
    }

    /**
     * Atur unit yang dipimpin pegawai (form Kelola User & import).
     *
     * @param  list<string>  $units
     */
    public static function aturKepala(DataPegawai $pegawai, array $units, ?User $oleh = null): void
    {
        $hariIni = now()->toDateString();
        $aktif = $pegawai->penugasans()->aktif()->get();

        foreach ($aktif as $p) {
            $harusKepala = in_array($p->unit_kerja, $units, true);

            if ($harusKepala && ! $p->isKepala()) {
                $p->update(['peran' => Penugasan::PERAN_KEPALA]);
            } elseif (! $harusKepala && $p->isKepala()) {
                match (true) {
                    $p->is_utama => $p->update(['peran' => Penugasan::PERAN_ANGGOTA]),
                    $p->tanggal_mulai->toDateString() === $hariIni => $p->delete(),
                    default => $p->update(['tanggal_selesai' => now()->subDay()->toDateString()]),
                };
            }
        }

        foreach (array_diff($units, $aktif->pluck('unit_kerja')->all()) as $unit) {
            $pegawai->penugasans()->create([
                'unit_kerja' => $unit,
                'jabatan' => 'KEPALA ' . $unit,
                'peran' => Penugasan::PERAN_KEPALA,
                'is_utama' => false,
                'jenis' => Penugasan::JENIS_RANGKAP,
                'tanggal_mulai' => $hariIni,
                'dibuat_oleh' => $oleh?->id,
            ]);
        }

        $pegawai->unsetRelation('penugasanAktif');
        self::sinkronInterviewer();
    }

    /**
     * Samakan turunan penugasan.
     *
     * @return list<string>
     */
    public static function sinkron(DataPegawai $pegawai): array
    {
        $pegawai->unsetRelation('penugasanAktif');
        $aktif = $pegawai->penugasans()->aktif()->get();
        $utama = $aktif->firstWhere('is_utama', true);
        $peringatan = [];

        $kep = $pegawai->kepegawaian()->first();
        if ($utama && $kep && ($kep->unit_kerja !== $utama->unit_kerja || ($utama->jabatan && $kep->jabatan !== $utama->jabatan))) {
            $kep->update(['unit_kerja' => $utama->unit_kerja, 'jabatan' => $utama->jabatan ?: $kep->jabatan]);
        }

        $memimpin = $aktif->contains(fn (Penugasan $p) => $p->isKepala());
        foreach (User::where('data_pegawai_id', $pegawai->id)->get() as $akun) {
            if ($akun->isStaff() && $memimpin) {
                $akun->update(['role' => User::ROLE_KEPALA_BAGIAN]);
                $peringatan[] = "Akun {$akun->email} dinaikkan menjadi Kepala Bagian.";
            } elseif ($akun->isKepalaBagian() && ! $memimpin) {
                $akun->update(['role' => User::ROLE_STAFF]);
                $peringatan[] = "Akun {$akun->email} diturunkan menjadi Staff (tidak lagi memimpin unit).";
            } elseif ($akun->isKepalaSdm() && ! $aktif->contains(fn (Penugasan $p) => $p->isKepala() && $p->unit_kerja === 'SDM')) {
                $peringatan[] = "Akun {$akun->email} masih ber-role Kepala SDM — sesuaikan role di Kelola User.";
            }
        }

        if (($jumlah = self::sinkronInterviewer()) > 0) {
            $peringatan[] = "{$jumlah} jadwal interview tahap 2 dialihkan ke kepala unit yang baru.";
        }

        return $peringatan;
    }

    /** Jalankan sinkron() untuk penugasan yang mulai/berakhir dalam seminggu terakhir (scheduler harian. */
    public static function sinkronTerjadwal(): int
    {
        $hariIni = now()->toDateString();
        $seminggu = now()->subDays(8)->toDateString();

        $ids = Penugasan::query()
            ->where(fn ($q) => $q->whereBetween('tanggal_mulai', [$seminggu, $hariIni])
                ->orWhereBetween('tanggal_selesai', [$seminggu, $hariIni]))
            ->distinct()->pluck('data_pegawai_id');

        DataPegawai::whereIn('id', $ids)->get()->each(fn (DataPegawai $p) => self::sinkron($p));

        return $ids->count();
    }

    /** Interviewer tahap 2 ditentukan otomatis dari kepala unit tujuan. */
    public static function sinkronInterviewer(): int
    {
        $jumlah = 0;

        InterviewJadwal::with('lamaran.lowongan')
            ->where('status', InterviewJadwal::STATUS_TERJADWAL)
            ->where('tahap_interview', InterviewJadwal::TAHAP_KEPALA_BAGIAN)
            ->get()
            ->each(function (InterviewJadwal $jadwal) use (&$jumlah) {
                $baru = $jadwal->lamaran ? InterviewFlow::interviewerTahap2($jadwal->lamaran) : null;
                if ($baru && $baru->id !== $jadwal->interviewer_id) {
                    $jadwal->update(['interviewer_id' => $baru->id]);
                    $jumlah++;
                }
            });

        return $jumlah;
    }

    public static function unitTanpaKepala(string $unit, $tanggal = null): bool
    {
        return ! Penugasan::aktif($tanggal)->kepala()->where('unit_kerja', $unit)->exists();
    }

    /**
     * Unit yang saat ini punya anggota tapi tidak punya kepala aktif.
     *
     * @return list<string>
     */
    public static function unitsTanpaKepala(): array
    {
        $berKepala = Penugasan::aktif()->kepala()->distinct()->pluck('unit_kerja')->all();

        return Penugasan::aktif()->whereNotIn('unit_kerja', $berKepala)->distinct()->orderBy('unit_kerja')->pluck('unit_kerja')->all();
    }

    /** Penugasan utama yang belum ditutup (yang akan diganti oleh mutasi). */
    private static function utamaTerbuka(DataPegawai $pegawai): ?Penugasan
    {
        return $pegawai->penugasans()->where('is_utama', true)->whereNull('tanggal_selesai')->first();
    }

    /** Tolak pengajuan pending milik pegawai di unit lama (dipilih admin saat mutasi). */
    private static function tolakPending(DataPegawai $pegawai, string $unit, ?User $oleh): int
    {
        $jumlah = 0;

        foreach ([PengajuanCuti::class, PengajuanLembur::class] as $model) {
            $jumlah += $model::where('data_pegawai_id', $pegawai->id)
                ->where('unit_kerja', $unit)
                ->where('status', $model::STATUS_PENDING)
                ->update([
                    'status' => $model::STATUS_REJECTED,
                    'current_level' => null,
                    'catatan_admin' => "Dibatalkan otomatis karena mutasi dari unit {$unit}. Silakan ajukan ulang di unit baru.",
                    'approved_by' => $oleh?->id,
                    'approved_at' => now(),
                ]);
        }

        return $jumlah;
    }
}
