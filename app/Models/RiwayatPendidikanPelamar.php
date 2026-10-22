<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPendidikanPelamar extends Model
{
    use HasFactory;

    protected $table = 'riwayat_pendidikan_pelamars';

    public const JENJANG = ['SD', 'SMP', 'SMA', 'SMK', 'MA', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3', 'Profesi', 'Lainnya'];

    protected $fillable = [
        'data_pelamar_id',
        'jenjang',
        'nama_institusi',
        'jurusan',
        'tahun_masuk',
        'tahun_lulus',
        'nilai',
    ];

    protected $casts = [
        'tahun_masuk' => 'integer',
        'tahun_lulus' => 'integer',
    ];

    public function dataPelamar(): BelongsTo
    {
        return $this->belongsTo(DataPelamar::class);
    }

    public function periodeLabel(): string
    {
        return ($this->tahun_masuk ?? '?') . ' - ' . ($this->tahun_lulus ?? 'Sekarang');
    }
}
