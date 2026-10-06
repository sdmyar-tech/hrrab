<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lowongan extends Model
{
    use HasFactory;

    protected $table = 'lowongans';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_DIPUBLIKASIKAN = 'dipublikasikan';
    public const STATUS_DITUTUP = 'ditutup';

    protected $fillable = [
        'pengajuan_formasi_id',
        'judul',
        'unit_kerja',
        'jabatan',
        'jumlah_dibutuhkan',
        'jumlah_terisi',
        'deskripsi',
        'kualifikasi',
        'lokasi_kerja',
        'tipe_kerja',
        'status',
        'tanggal_tutup',
        'dipublikasikan_oleh',
        'dipublikasikan_pada',
    ];

    protected $casts = [
        'jumlah_dibutuhkan' => 'integer',
        'jumlah_terisi' => 'integer',
        'tanggal_tutup' => 'date',
        'dipublikasikan_pada' => 'datetime',
    ];

    public function pengajuanFormasi(): BelongsTo
    {
        return $this->belongsTo(PengajuanFormasi::class);
    }

    public function dipublikasikanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dipublikasikan_oleh');
    }

    public function lamarans(): HasMany
    {
        return $this->hasMany(Lamaran::class);
    }

    public function isDipublikasikan(): bool
    {
        return $this->status === self::STATUS_DIPUBLIKASIKAN;
    }

    public function isMenerimaLamaran(): bool
    {
        return $this->isDipublikasikan()
            && (! $this->tanggal_tutup || $this->tanggal_tutup->gte(now()->startOfDay()))
            && $this->jumlah_terisi < $this->jumlah_dibutuhkan;
    }

    public function isKuotaPenuh(): bool
    {
        return $this->jumlah_terisi >= $this->jumlah_dibutuhkan;
    }

    public function bisaDibukaKembali(): bool
    {
        return $this->status === self::STATUS_DITUTUP && ! $this->isKuotaPenuh();
    }

    public static function tutupYangKedaluwarsa(): int
    {
        return static::where('status', self::STATUS_DIPUBLIKASIKAN)
            ->whereNotNull('tanggal_tutup')
            ->where('tanggal_tutup', '<', now()->toDateString())
            ->update(['status' => self::STATUS_DITUTUP, 'updated_at' => now()]);
    }

    public function scopeTerbuka($query)
    {
        return $query->where('status', self::STATUS_DIPUBLIKASIKAN)
            ->where(function ($q) {
                $q->whereNull('tanggal_tutup')->orWhere('tanggal_tutup', '>=', now()->toDateString());
            })
            ->whereColumn('jumlah_terisi', '<', 'jumlah_dibutuhkan');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DIPUBLIKASIKAN => 'Dipublikasikan',
            self::STATUS_DITUTUP => 'Ditutup',
            default => 'Draft',
        };
    }
}
