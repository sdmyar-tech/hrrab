<?php

namespace App\Support;

use App\Models\PengajuanFormasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** Hak akses modul rekrutmen: Kepala SDM & GM bisa approve/aksi di semua divisi. */
class RekrutmenAccess
{
    public static function canReview(User $user): bool
    {
        return $user->isKepalaSdm() || $user->isGm();
    }

    /** Kepala Bagian mengajukan untuk unit yang dipimpin; Kepala SDM boleh mengajukan untuk unit mana pun. */
    public static function bisaAjukanFormasi(User $user): bool
    {
        return $user->isKepalaSdm()
            || PengajuanWorkflow::submitterLevel($user) === PengajuanWorkflow::LEVEL_KEPALA_DIVISI;
    }

    /** @return list<string> */
    public static function unitAjuanFormasi(User $user): array
    {
        return $user->isKepalaSdm() ? config('kepegawaian.unit_kerja') : $user->unitsDipimpin();
    }

    public static function canReviewFormasi(User $user, PengajuanFormasi $formasi): bool
    {
        // Pengaju (termasuk akun lain milik orang yang sama) tidak boleh mereview formasinya sendiri.
        $pengaju = $formasi->diajukanOleh;
        if ($formasi->diajukan_oleh === $user->id
            || ($user->data_pegawai_id && $pengaju?->data_pegawai_id === $user->data_pegawai_id)) {
            return false;
        }

        if (in_array($formasi->unit_kerja, config('kepegawaian.unit_langsung_pembina', []), true)) {
            return $user->isPembina();
        }

        return self::canReview($user);
    }

    public static function canLihatSemuaFormasi(User $user): bool
    {
        return self::canReview($user) || $user->isPembina();
    }

    public static function scopeUnitKerja(Builder $query, User $user, string $column = 'unit_kerja'): Builder
    {
        if (self::canReview($user) || $user->isAdmin() || $user->isPembina()) {
            return $query;
        }

        return $query->whereIn($column, $user->unitsDipimpinUntukQuery());
    }
}
