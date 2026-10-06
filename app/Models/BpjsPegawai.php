<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpjsPegawai extends Model
{
    use HasFactory;

    protected $table = 'bpjs_pegawais';

    protected $fillable = [
        'data_pegawai_id',
        'no_bpjs_ketenagakerjaan',
        'no_bpjs_kesehatan',
        'faskes_tingkat_1',
    ];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }
}
