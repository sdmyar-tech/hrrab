<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Riwayat import file absensi dari flashdisk mesin. */
class AbsensiImport extends Model
{
    protected $fillable = [
        'nama_file', 'periode_dari', 'periode_sampai',
        'jumlah_baris', 'jumlah_baru', 'jumlah_diperbarui', 'jumlah_dilewati',
        'ringkasan', 'diimport_oleh',
    ];

    protected $casts = [
        'periode_dari' => 'date',
        'periode_sampai' => 'date',
        'ringkasan' => 'array',
    ];

    public function diimportOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diimport_oleh');
    }
}
