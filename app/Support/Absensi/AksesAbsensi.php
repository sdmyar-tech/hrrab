<?php

namespace App\Support\Absensi;

use App\Models\DataPegawai;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** Siapa boleh melihat absensi siapa. */
class AksesAbsensi
{
    public static function bisaKelola(User $user): bool
    {
        return $user->hasAbility(User::ABILITY_KELOLA_ABSENSI);
    }

    /**
     * Unit yang boleh dilihat: null = semua unit (pengelola absensi), [] = tidak ada.
     *
     * @return array<int, string>|null
     */
    public static function unitTerlihat(User $user): ?array
    {
        if (self::bisaKelola($user)) {
            return null;
        }

        if (! $user->hasAbility(User::ABILITY_LIHAT_ABSENSI)) {
            return [];
        }

        // Kepala Bagian: unit yang dipimpin; pegawai lain yang diberi hak lihat (role kustom / hak jabatan): unit tempatnya bertugas.
        return $user->isKepalaBagian() ? $user->unitsDipimpin() : $user->unitsAktif();
    }

    /** Pegawai aktif yang ikut absensi & terlihat oleh user (pegawai keluar/Tidak Aktif tidak dinilai, agar tidak jadi Alpa). */
    public static function pegawai(User $user): Builder
    {
        $unit = self::unitTerlihat($user);

        return self::aktif(DataPegawai::query())
            ->where('ikut_absensi', true)
            ->when($unit !== null, fn ($q) => $q->whereHas('kepegawaian', fn ($k) => $k->whereIn('unit_kerja', $unit ?: ['__tidak_ada__'])));
    }

    public static function aktif(Builder $query): Builder
    {
        return $query->whereDoesntHave('kepegawaian', fn ($k) => $k->where('status_rekrutmen', 'Tidak Aktif'));
    }

    public static function bolehLihatPegawai(User $user, DataPegawai $pegawai): bool
    {
        $unit = self::unitTerlihat($user);

        return $unit === null || in_array($pegawai->kepegawaian?->unit_kerja, $unit, true);
    }
}
