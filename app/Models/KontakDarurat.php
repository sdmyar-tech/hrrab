<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KontakDarurat extends Model
{
    use HasFactory, \App\Models\Concerns\DicatatLog;

    protected $table = 'kontak_darurats';

    protected $fillable = [
        'data_pegawai_id',
        'no_hp_kerabat',
        'nama_kerabat',
        'hubungan_kerabat',
    ];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }
}
