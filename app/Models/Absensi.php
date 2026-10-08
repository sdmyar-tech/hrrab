<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu hari absensi seorang pegawai (scan mesin + koreksi SDM/Admin). */
class Absensi extends Model
{
    use Concerns\DicatatLog;

    public const MANUAL_HADIR = 'hadir';
    public const MANUAL_IZIN = 'izin';
    public const MANUAL_SAKIT = 'sakit';
    public const MANUAL_DINAS_LUAR = 'dinas_luar';
    public const MANUAL_ALPA = 'alpa';

    public const STATUS_MANUAL = [
        self::MANUAL_HADIR => 'Hadir',
        self::MANUAL_IZIN => 'Izin',
        self::MANUAL_SAKIT => 'Sakit',
        self::MANUAL_DINAS_LUAR => 'Dinas Luar',
        self::MANUAL_ALPA => 'Alpa',
    ];

    protected $fillable = [
        'data_pegawai_id', 'finger_id', 'nama_mesin', 'tanggal',
        'scan_masuk', 'scan_keluar', 'jam_masuk', 'jam_keluar',
        'status_manual', 'lembur_manual_menit',
        'dikoreksi', 'alasan_koreksi', 'dikoreksi_oleh', 'dikoreksi_pada', 'absensi_import_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'dikoreksi' => 'boolean',
        'dikoreksi_pada' => 'datetime',
        'lembur_manual_menit' => 'integer',
    ];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    public function dikoreksiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikoreksi_oleh');
    }
}
