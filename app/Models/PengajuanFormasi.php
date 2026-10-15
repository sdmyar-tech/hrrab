<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengajuanFormasi extends Model
{
    use HasFactory, \App\Models\Concerns\DicatatLog;

    protected $table = 'pengajuan_formasis';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    /** Kebutuhan karyawan karena: */
    public const JENIS_REPLACEMENT = 'replacement';
    public const JENIS_PENAMBAHAN = 'penambahan';

    public const JENIS_LABEL = [
        self::JENIS_REPLACEMENT => 'Replacement / Mengganti karyawan keluar / berhenti',
        self::JENIS_PENAMBAHAN => 'Penambahan karyawan',
    ];

    public const PENDIDIKAN = ['SD', 'SMP', 'SMA/SMK', 'D1', 'D2', 'D3', 'S1/D4', 'S2', 'S3', 'Profesi'];

    protected $fillable = [
        'diajukan_oleh',
        'unit_kerja',
        'jenis_kebutuhan',
        'jabatan',
        'jumlah_dibutuhkan',
        'jumlah_laki',
        'jumlah_perempuan',
        'jumlah_bebas',
        'min_pendidikan',
        'min_usia',
        'atasan_langsung',
        'tugas',
        'diganti',
        'slot_tambahan',
        'kualifikasi',
        'target_waktu',
        'alasan',
        'status',
        'catatan_admin',
        'direview_oleh',
        'direview_pada',
        'gm_disetujui_oleh',
        'gm_disetujui_pada',
        'owner_disetujui_oleh',
        'owner_disetujui_pada',
    ];

    protected $casts = [
        'jumlah_dibutuhkan' => 'integer',
        'jumlah_laki' => 'integer',
        'jumlah_perempuan' => 'integer',
        'jumlah_bebas' => 'integer',
        'diganti' => 'array',
        'slot_tambahan' => 'array',
        'min_usia' => 'integer',
        'target_waktu' => 'date',
        'direview_pada' => 'datetime',
        'gm_disetujui_pada' => 'datetime',
        'owner_disetujui_pada' => 'datetime',
    ];

    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function direviewOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direview_oleh');
    }

    public function gmPenyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gm_disetujui_oleh');
    }

    public function ownerPenyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_disetujui_oleh');
    }

    public function lowongan(): HasOne
    {
        return $this->hasOne(Lowongan::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** Penambahan karyawan: wajib disetujui GM unit & Owner (paralel, bukan bertahap). */
    public function butuhDuaPersetujuan(): bool
    {
        return $this->jenis_kebutuhan === self::JENIS_PENAMBAHAN;
    }

    public function jenisLabel(): string
    {
        return self::JENIS_LABEL[$this->jenis_kebutuhan] ?? '-';
    }

    /** "3 orang (L 1, P 1, bebas 1)" — rincian hanya bila diisi. */
    public function jumlahLabel(): string
    {
        $bagian = array_filter([
            $this->jumlah_laki ? "L {$this->jumlah_laki}" : null,
            $this->jumlah_perempuan ? "P {$this->jumlah_perempuan}" : null,
            $this->jumlah_bebas ? "bebas {$this->jumlah_bebas}" : null,
        ]);

        return "{$this->jumlah_dibutuhkan} orang" . ($bagian ? ' (' . implode(', ', $bagian) . ')' : '');
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
