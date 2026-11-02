<?php

namespace App\Models\Concerns;

use App\Models\PersetujuanTahap;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Pengajuan yang bisa mengikuti Tangga Alur: salinan tangga saat diajukan, tahap berjalan, & catatan persetujuan per tahap. */
trait IkutTanggaAlur
{
    public function initializeIkutTanggaAlur(): void
    {
        $this->mergeFillable(['tangga', 'tahap_ke']);
        $this->mergeCasts(['tangga' => 'array', 'tahap_ke' => 'integer']);
    }

    public function persetujuanTahaps(): MorphMany
    {
        return $this->morphMany(PersetujuanTahap::class, 'pengajuan');
    }
}
