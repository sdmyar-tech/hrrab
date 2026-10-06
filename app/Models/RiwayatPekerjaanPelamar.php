<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPekerjaanPelamar extends Model
{
    use HasFactory;

    protected $table = 'riwayat_pekerjaan_pelamars';

    protected $fillable = [
        'data_pelamar_id',
        'nama_perusahaan',
        'posisi',
        'tanggal_mulai',
        'tanggal_selesai',
        'deskripsi',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function dataPelamar(): BelongsTo
    {
        return $this->belongsTo(DataPelamar::class);
    }
}
