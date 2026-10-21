<?php

namespace App\Models;

use App\Models\Concerns\DicatatLog;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penugasan extends Model
{
    use DicatatLog;

    public const PERAN_ANGGOTA = 'anggota';

    public const PERAN_KEPALA = 'kepala';

    public const JENIS_AWAL = 'awal';

    public const JENIS_MUTASI = 'mutasi';

    public const JENIS_RANGKAP = 'rangkap';

    protected $fillable = [
        'data_pegawai_id',
        'unit_kerja',
        'jabatan',
        'peran',
        'is_utama',
        'jenis',
        'tanggal_mulai',
        'tanggal_selesai',
        'no_sk',
        'keterangan',
        'dibuat_oleh',
    ];

    protected $casts = [
        'is_utama' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function scopeAktif(Builder $query, CarbonInterface|string|null $tanggal = null): Builder
    {
        $tanggal = $tanggal ? Carbon::parse($tanggal)->toDateString() : now()->toDateString();

        return $query->where('tanggal_mulai', '<=', $tanggal)
            ->where(fn ($q) => $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $tanggal));
    }

    public function scopeKepala(Builder $query): Builder
    {
        return $query->where('peran', self::PERAN_KEPALA);
    }

    public function isAktif(): bool
    {
        $hariIni = now()->startOfDay();

        return $this->tanggal_mulai->lte($hariIni) && (! $this->tanggal_selesai || $this->tanggal_selesai->gte($hariIni));
    }

    public function isAkanDatang(): bool
    {
        return $this->tanggal_mulai->gt(now()->startOfDay());
    }

    public function isKepala(): bool
    {
        return $this->peran === self::PERAN_KEPALA;
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->isAkanDatang() => 'Terjadwal',
            $this->isAktif() => 'Berlaku',
            default => 'Selesai',
        };
    }

    public function statusBadge(): string
    {
        return match (true) {
            $this->isAkanDatang() => 'text-bg-info',
            $this->isAktif() => 'text-bg-success',
            default => 'text-bg-secondary',
        };
    }

    public function jenisLabel(): string
    {
        return match ($this->jenis) {
            self::JENIS_MUTASI => 'Mutasi',
            self::JENIS_RANGKAP => 'Rangkap',
            default => 'Penempatan Awal',
        };
    }

    public function peranLabel(): string
    {
        return $this->isKepala() ? 'Kepala' : 'Anggota';
    }
}
