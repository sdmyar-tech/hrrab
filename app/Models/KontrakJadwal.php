<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KontrakJadwal extends Model
{
    use HasFactory, \App\Models\Concerns\DicatatLog;

    protected static function booted(): void
    {
        static::created(fn (self $jadwal) => \App\Support\Notifikasi::jadwalKandidat($jadwal));
        static::updated(function (self $jadwal) {
            if ($jadwal->wasChanged(['tanggal', 'waktu', 'lokasi'])) {
                \App\Support\Notifikasi::jadwalKandidat($jadwal);
            }
        });
    }

    protected $table = 'kontrak_jadwals';

    public const STATUS_TERJADWAL = 'terjadwal';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $fillable = [
        'lamaran_id',
        'tanggal',
        'waktu',
        'lokasi',
        'catatan',
        'status',
        'dijadwalkan_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function lamaran(): BelongsTo
    {
        return $this->belongsTo(Lamaran::class);
    }

    public function dijadwalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dijadwalkan_oleh');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_DIBATALKAN => 'Dibatalkan',
            default => 'Terjadwal',
        };
    }
}
