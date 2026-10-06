<?php

namespace App\Models;

use App\Models\Concerns\SnapshotUnitKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanLembur extends Model
{
    use HasFactory, SnapshotUnitKerja;

    protected $table = 'pengajuan_lemburs';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'data_pegawai_id',
        'unit_kerja',
        'tanggal',
        'hari',
        'uraian_tugas',
        'jam_mulai',
        'jam_selesai',
        'jumlah_jam',
        'status',
        'current_level',
        'catatan_admin',
        'approved_by',
        'approved_at',
        'kepala_approved_by',
        'kepala_approved_at',
        'gm_approved_by',
        'gm_approved_at',
        'gm_pengganti',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah_jam' => 'decimal:2',
        'approved_at' => 'datetime',
        'kepala_approved_at' => 'datetime',
        'gm_approved_at' => 'datetime',
        'gm_pengganti' => 'boolean',
    ];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function kepalaApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepala_approved_by');
    }

    public function gmApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gm_approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function badgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'text-bg-success',
            self::STATUS_REJECTED => 'text-bg-danger',
            default => 'text-bg-warning',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Menunggu',
        };
    }

    public function currentLevelLabel(): string
    {
        return \App\Support\PengajuanWorkflow::levelLabel($this->current_level);
    }
}
