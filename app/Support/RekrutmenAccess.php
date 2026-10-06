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

    public static function canReviewFormasi(User $user, PengajuanFormasi $formasi): bool
    {
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
