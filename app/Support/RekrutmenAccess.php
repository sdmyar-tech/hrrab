<?php

namespace App\Support;

use App\Models\PengajuanFormasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** Hak akses modul rekrutmen: Kepala SDM, GM & Admin bisa approve/aksi di semua divisi. */
class RekrutmenAccess
{
    public static function canReview(User $user): bool
    {
        return $user->isKepalaSdm() || $user->isGm() || $user->isAdmin();
    }

    /** Admin (super admin) mengelola lowongan semua divisi bersama Kepala SDM & GM. */
    public static function canKelolaSemuaLowongan(User $user): bool
    {
        return self::canReview($user) || $user->isAdmin();
    }

    /** Hak lihat seluruh data rekrutmen (lamaran, formasi) tanpa batas divisi. */
    public static function canLihatSemua(User $user): bool
    {
        return self::canReview($user) || $user->isAdmin() || $user->isPembina();
    }

    /** Kepala Bagian mengajukan untuk unit yang dipimpin; Kepala SDM boleh mengajukan untuk unit mana pun. */
    public static function bisaAjukanFormasi(User $user): bool
    {
        return $user->isKepalaSdm() || $user->isAdmin()
            || PengajuanWorkflow::submitterLevel($user) === PengajuanWorkflow::LEVEL_KEPALA_DIVISI;
    }

    /** @return list<string> */
    public static function unitAjuanFormasi(User $user): array
    {
        return ($user->isKepalaSdm() || $user->isAdmin()) ? config('kepegawaian.unit_kerja') : $user->unitsDipimpin();
    }

    /**
     * Jabatan yang boleh diajukan untuk $unit: jabatan yang memang ada di unit itu (penugasan aktif & data kepegawaian),
     * dan terdaftar di Master Jabatan. Mencegah Kepala Keuangan memilih mis. "STAFF STCH".
     *
     * @return list<string>
     */
    public static function jabatanUnit(string $unit): array
    {
        $dipakai = \App\Models\Penugasan::aktif()->where('unit_kerja', $unit)->pluck('jabatan')
            ->merge(\App\Models\Kepegawaian::where('unit_kerja', $unit)->where('status_rekrutmen', 'Aktif')->pluck('jabatan'))
            ->filter()->map(fn ($j) => mb_strtoupper(trim($j)))->unique();

        return array_values(array_filter(\App\Models\Jabatan::daftar(), fn ($j) => $dipakai->contains(mb_strtoupper($j))));
    }

    /** GM unit: GM yang memegang divisi itu; bila tak ada, GM tanpa batas divisi. Bisa kosong. */
    public static function gmFormasi(PengajuanFormasi $formasi): \Illuminate\Support\Collection
    {
        $utama = User::gmUtamaUntuk($formasi->unit_kerja);

        return $utama->isNotEmpty() ? $utama : User::where('role', User::ROLE_GM)->get()->filter(fn (User $g) => empty($g->divisi))->values();
    }

    /**
     * Sisi persetujuan formasi "penambahan" yang masih bisa dilakukan $user: 'gm', 'owner', atau null.
     * GM unit & Owner menyetujui paralel (tidak bertahap); tiap sisi cukup sekali.
     */
    public static function sisiPersetujuan(User $user, PengajuanFormasi $formasi): ?string
    {
        if (! $formasi->butuhDuaPersetujuan()) {
            return null;
        }
        if ($user->isPembina() && ! $formasi->owner_disetujui_oleh) {
            return 'owner';
        }
        if ($user->isGm() && ! $formasi->gm_disetujui_oleh && self::gmFormasi($formasi)->contains('id', $user->id)) {
            return 'gm';
        }

        return null;
    }

    /** Penambahan: GM unit tidak ada → cukup persetujuan Owner. */
    public static function perluGm(PengajuanFormasi $formasi): bool
    {
        return self::gmFormasi($formasi)->isNotEmpty();
    }

    public static function canReviewFormasi(User $user, PengajuanFormasi $formasi): bool
    {
        // Admin bebas mereview formasi apa pun (termasuk unit langsung Pembina).
        if ($user->isAdmin()) {
            return true;
        }

        // Pengaju (termasuk akun lain milik orang yang sama) tidak boleh mereview formasinya sendiri.
        $pengaju = $formasi->diajukanOleh;
        if ($formasi->diajukan_oleh === $user->id
            || ($user->data_pegawai_id && $pengaju?->data_pegawai_id === $user->data_pegawai_id)) {
            return false;
        }

        // Penambahan karyawan: hanya GM unit & Owner, masing-masing untuk sisinya sendiri.
        if ($formasi->butuhDuaPersetujuan()) {
            return self::sisiPersetujuan($user, $formasi) !== null;
        }

        if (in_array($formasi->unit_kerja, config('kepegawaian.unit_langsung_pembina', []), true)) {
            return $user->isPembina();
        }

        return self::canReview($user);
    }

    public static function canLihatSemuaFormasi(User $user): bool
    {
        return self::canLihatSemua($user);
    }

    public static function scopeUnitKerja(Builder $query, User $user, string $column = 'unit_kerja'): Builder
    {
        if (self::canReview($user) || $user->isAdmin() || $user->isPembina()) {
            return $query;
        }

        return $query->whereIn($column, $user->unitsDipimpinUntukQuery());
    }
}
