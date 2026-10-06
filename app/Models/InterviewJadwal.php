<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewJadwal extends Model
{
    use HasFactory;

    protected $table = 'interview_jadwals';

    public const STATUS_TERJADWAL = 'terjadwal';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const HASIL_LULUS = 'lulus';
    public const HASIL_DIPERTIMBANGKAN = 'dipertimbangkan';
    public const HASIL_TIDAK_LULUS = 'tidak_lulus';

    public const TAHAP_KEPALA_SDM = 1;
    public const TAHAP_KEPALA_BAGIAN = 2;
    public const TAHAP_LANJUTAN = 3;

    protected $fillable = [
        'lamaran_id',
        'interviewer_id',
        'pertemuan_ke',
        'tahap_interview',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'lokasi',
        'catatan',
        'status',
        'hasil_status',
        'catatan_hasil',
        'diisi_oleh',
        'diisi_pada',
        'dijadwalkan_oleh',
    ];

    protected $casts = [
        'pertemuan_ke' => 'integer',
        'tahap_interview' => 'integer',
        'tanggal' => 'date',
        'diisi_pada' => 'datetime',
    ];

    public function lamaran(): BelongsTo
    {
        return $this->belongsTo(Lamaran::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function diisiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diisi_oleh');
    }

    public function dijadwalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dijadwalkan_oleh');
    }

    public function tahapLabel(): string
    {
        return match ($this->tahap_interview) {
            self::TAHAP_KEPALA_BAGIAN => 'Interview 2 (Kepala Bagian)',
            self::TAHAP_LANJUTAN => 'Interview Lanjutan',
            default => 'Interview 1 (Kepala SDM)',
        };
    }

    public function isTerjadwal(): bool
    {
        return $this->status === self::STATUS_TERJADWAL;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_DIBATALKAN => 'Dibatalkan',
            default => 'Terjadwal',
        };
    }

    public function hasilLabel(): string
    {
        return match ($this->hasil_status) {
            self::HASIL_LULUS => 'Lulus',
            self::HASIL_DIPERTIMBANGKAN => 'Dipertimbangkan',
            self::HASIL_TIDAK_LULUS => 'Tidak Lulus',
            default => 'Belum Diisi',
        };
    }

    public function hasilBadgeClass(): string
    {
        return match ($this->hasil_status) {
            self::HASIL_LULUS => 'text-bg-success',
            self::HASIL_TIDAK_LULUS => 'text-bg-danger',
            self::HASIL_DIPERTIMBANGKAN => 'text-bg-warning',
            default => 'text-bg-light border',
        };
    }
}
