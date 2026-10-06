<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengajuanFormasi extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_formasis';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $fillable = [
        'diajukan_oleh',
        'unit_kerja',
        'jabatan',
        'jumlah_dibutuhkan',
        'kualifikasi',
        'target_waktu',
        'alasan',
        'status',
        'catatan_admin',
        'direview_oleh',
        'direview_pada',
    ];

    protected $casts = [
        'jumlah_dibutuhkan' => 'integer',
        'target_waktu' => 'date',
        'direview_pada' => 'datetime',
    ];

    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function direviewOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direview_oleh');
    }

    public function lowongan(): HasOne
    {
        return $this->hasOne(Lowongan::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_DIBATALKAN => 'Dibatalkan',
            default => 'Menunggu',
        };
    }

    public function badgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'text-bg-success',
            self::STATUS_REJECTED => 'text-bg-danger',
            self::STATUS_DIBATALKAN => 'text-bg-secondary',
            default => 'text-bg-warning',
        };
    }
}
