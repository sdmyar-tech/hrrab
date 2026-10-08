<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kepegawaian extends Model
{
    use HasFactory, \App\Models\Concerns\DicatatLog;

    protected $table = 'kepegawaians';

    protected $fillable = [
        'data_pegawai_id',
        'jabatan',
        'jabatan_tambahan',
        'unit_kerja',
        'lokasi_kerja',
        'tanggal_masuk',
        'masa_kerja',
        'status_rekrutmen',
        'status_kerja',
        'tanggal_habis_kontrak',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'tanggal_habis_kontrak' => 'date',
    ];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }
}
