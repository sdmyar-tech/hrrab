<?php

namespace App\Models\Concerns;

use App\Models\Kepegawaian;

trait SnapshotUnitKerja
{
    public static function bootSnapshotUnitKerja(): void
    {
        static::creating(function ($pengajuan) {
            $pengajuan->unit_kerja ??= Kepegawaian::where('data_pegawai_id', $pengajuan->data_pegawai_id)->value('unit_kerja');
        });
    }
}
