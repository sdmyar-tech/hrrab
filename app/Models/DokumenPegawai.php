<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenPegawai extends Model
{
    use \App\Models\Concerns\DicatatLog, HasFactory;

    protected $table = 'dokumen_pegawais';

    protected $fillable = [
        'data_pegawai_id',
        'upload_ktp',
        'upload_kk',
        'upload_cv',
        'upload_ijazah_terakhir',
        'upload_buku_tabungan',
        'pas_foto',
    ];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }
}
