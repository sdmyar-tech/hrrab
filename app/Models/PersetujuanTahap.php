<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Satu persetujuan pada satu tahap tangga alur sebuah pengajuan (cuti / lembur / izin keluar). */
class PersetujuanTahap extends Model
{
    protected $fillable = ['tahap_ke', 'user_id'];

    protected $casts = ['tahap_ke' => 'integer'];

    public function pengajuan(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
