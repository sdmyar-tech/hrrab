<?php

namespace App\Support;

use App\Models\InterviewJadwal;
use App\Models\Lamaran;
use App\Models\Penugasan;
use App\Models\User;

/** Alur interview rekrutmen — minimal 2 interview Lulus. */
class InterviewFlow
{
    public const MIN_LULUS = 2;

    public static function interviewerTahap1(): ?User
    {
        return User::where('role', User::ROLE_KEPALA_SDM)->orderBy('id')->first();
    }

    /** Interviewer tahap 2: kepala unit tujuan. */
    public static function interviewerTahap2(Lamaran $lamaran): ?User
    {
        $unit = $lamaran->unitKerjaTujuan();
        $kepala = self::kepalaUnit($unit);

        if ($kepala && $kepala->id !== self::interviewerTahap1()?->id) {
            return $kepala;
        }

        return User::gmUtamaUntuk($unit)->first();
    }

    /** Kepala sebuah unit: akun pimpinan. */
    public static function kepalaUnit(?string $unit): ?User
    {
        if ($unit === null) {
            return null;
        }

        $pegawaiIds = Penugasan::aktif()->kepala()->where('unit_kerja', $unit)->pluck('data_pegawai_id');

        return User::whereIn('data_pegawai_id', $pegawaiIds)
            ->whereIn('role', [User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM, User::ROLE_GM])
            ->orderBy('id')
            ->first();
    }

    /** Tahap wajib yang perlu dijadwalkan berikutnya. */
    public static function tahapBerikutnya(Lamaran $lamaran): ?int
    {
        if (! self::bolehJadwalkan($lamaran)) {
            return null;
        }

        if (! self::tahapLulus($lamaran, InterviewJadwal::TAHAP_KEPALA_SDM)) {
            return InterviewJadwal::TAHAP_KEPALA_SDM;
        }

        if (! self::tahapLulus($lamaran, InterviewJadwal::TAHAP_KEPALA_BAGIAN) && self::interviewerTahap2($lamaran)) {
            return InterviewJadwal::TAHAP_KEPALA_BAGIAN;
        }

        return null;
    }

    /** Interview Lanjutan bisa ditambah setelah Interview 1 Lulus & tidak ada tahap wajib yang menunggu dijadwalkan. */
    public static function bisaTambahLanjutan(Lamaran $lamaran): bool
    {
        return self::bolehJadwalkan($lamaran)
            && self::tahapLulus($lamaran, InterviewJadwal::TAHAP_KEPALA_SDM)
            && self::tahapBerikutnya($lamaran) === null;
    }

    /** Pilihan interviewer Interview Lanjutan: Kepala SDM, GM & Kepala Bagian — kecuali kandidat sendiri. */
    public static function opsiInterviewerLanjutan(Lamaran $lamaran): \Illuminate\Support\Collection
    {
        return User::whereIn('role', [User::ROLE_KEPALA_SDM, User::ROLE_GM, User::ROLE_KEPALA_BAGIAN])
            ->when($lamaran->data_pegawai_id, fn ($q) => $q->where(fn ($w) => $w->whereNull('data_pegawai_id')->orWhere('data_pegawai_id', '!=', $lamaran->data_pegawai_id)))
            ->orderByRaw("FIELD(role, 'kepala_sdm', 'gm', 'kepala_bagian')")->orderBy('name')
            ->get(['id', 'name', 'role']);
    }

    public static function jumlahLulus(Lamaran $lamaran): int
    {
        return $lamaran->interviewJadwals->where('hasil_status', InterviewJadwal::HASIL_LULUS)->count();
    }

    /** Tombol "Konfirmasi Interview Selesai". */
    public static function bisaKonfirmasiSelesai(Lamaran $lamaran): bool
    {
        return self::bolehJadwalkan($lamaran)
            && self::tahapLulus($lamaran, InterviewJadwal::TAHAP_KEPALA_SDM)
            && self::jumlahLulus($lamaran) >= self::MIN_LULUS;
    }

    public static function interviewerUntukTahap(Lamaran $lamaran, int $tahap): ?User
    {
        return $tahap === InterviewJadwal::TAHAP_KEPALA_BAGIAN
            ? self::interviewerTahap2($lamaran)
            : self::interviewerTahap1();
    }

    /** Masih tahap interview, tidak ada jadwal berjalan, dan interview terakhir bukan Tidak Lulus. */
    private static function bolehJadwalkan(Lamaran $lamaran): bool
    {
        $jadwals = $lamaran->interviewJadwals;
        $terakhir = $jadwals->where('status', InterviewJadwal::STATUS_SELESAI)->sortBy('id')->last();

        return $lamaran->tahap === Lamaran::TAHAP_INTERVIEW
            && $lamaran->isProses()
            && ! $jadwals->contains(fn ($j) => $j->isTerjadwal())
            && $terakhir?->hasil_status !== InterviewJadwal::HASIL_TIDAK_LULUS;
    }

    private static function tahapLulus(Lamaran $lamaran, int $tahap): bool
    {
        return $lamaran->interviewJadwals->contains(fn ($j) => $j->tahap_interview === $tahap && $j->hasil_status === InterviewJadwal::HASIL_LULUS);
    }

    /** Jadwal (tanggal/jam/lokasi/catatan) boleh diubah selama interview belum selesai. */
    public static function bisaUbahJadwal(User $user, InterviewJadwal $jadwal): bool
    {
        return self::bisaIsiHasil($user, $jadwal);
    }

    /** Yang boleh mengisi hasil: reviewer rekrutmen (Kepala SDM/GM) atau interviewer jadwal itu sendiri. */
    public static function bisaIsiHasil(User $user, InterviewJadwal $jadwal): bool
    {
        $kandidatSendiri = $user->data_pegawai_id && $user->data_pegawai_id === $jadwal->lamaran?->data_pegawai_id;

        return $jadwal->isTerjadwal()
            && (bool) $jadwal->lamaran?->isProses()
            && ! $kandidatSendiri
            && (RekrutmenAccess::canReview($user) || $jadwal->interviewer_id === $user->id);
    }
}
