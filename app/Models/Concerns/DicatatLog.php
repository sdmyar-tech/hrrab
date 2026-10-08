<?php

namespace App\Models\Concerns;

use App\Support\PencatatLog;

/** Rekam perubahan kolom model ke log aktivitas request yang sedang berjalan. */
trait DicatatLog
{
    public static function bootDicatatLog(): void
    {
        static::created(fn ($model) => PencatatLog::rekamModel($model, 'tambah'));
        static::updated(fn ($model) => PencatatLog::rekamModel($model, 'ubah'));
        static::deleted(fn ($model) => PencatatLog::rekamModel($model, 'hapus'));
    }
}
