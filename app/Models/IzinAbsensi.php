<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kelonggaran absensi pada satu tanggal (mis. hujan deras → izin telat 60 menit).
 * Sasaran: satu pegawai, satu unit, atau semua pegawai (keduanya kosong).
 */
class IzinAbsensi extends Model
{
    use Concerns\DicatatLog;

    public const JENIS_TELAT = 'telat';

    public const JENIS_PULANG_CEPAT = 'pulang_cepat';

    public const JENIS = [
        self::JENIS_TELAT => 'Izin Telat',
        self::JENIS_PULANG_CEPAT => 'Izin Pulang Cepat',
    ];

    protected $fillable = ['tanggal', 'jenis', 'menit', 'data_pegawai_id', 'unit_kerja', 'alasan', 'dibuat_oleh'];

    protected $casts = [
        'tanggal' => 'date',
        'menit' => 'integer',
    ];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function sasaranLabel(): string
    {
        return $this->dataPegawai?->nama_lengkap ?? ($this->unit_kerja ? 'Unit ' . $this->unit_kerja : 'Semua pegawai');
    }

    public function berlakuUntuk(int $pegawaiId, ?string $unit): bool
    {
        return $this->data_pegawai_id
            ? $this->data_pegawai_id === $pegawaiId
            : ($this->unit_kerja === null || $this->unit_kerja === $unit);
    }
}
