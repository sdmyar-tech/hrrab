<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lamaran extends Model
{
    use HasFactory, \App\Models\Concerns\DicatatLog;

    protected static function booted(): void
    {
        static::updated(fn (self $lamaran) => \App\Support\Notifikasi::lamaranBerubah($lamaran));
    }

    protected $table = 'lamarans';

    public const STATUS_MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';
    public const STATUS_PROSES = 'proses';
    public const STATUS_DITERIMA = 'diterima';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const TAHAP_VERIFIKASI = 'verifikasi';
    public const TAHAP_INTERVIEW = 'interview';
    public const TAHAP_SCREENING = 'screening';
    public const TAHAP_KONTRAK = 'kontrak';
    public const TAHAP_SELESAI = 'selesai';

    public const KEPUTUSAN_REKRUT = 'rekrut';
    public const KEPUTUSAN_TOLAK_ADMIN = 'tolak_admin';
    public const KEPUTUSAN_TOLAK_PELAMAR = 'tolak_pelamar';

    public const JALUR_UMUM = 'umum';
    public const JALUR_INTERNAL = 'internal';

    protected $fillable = [
        'data_pelamar_id',
        'data_pegawai_id',
        'lowongan_id',
        'jalur',
        'jabatan_tujuan',
        'unit_kerja_tujuan',
        'lokasi_kerja_tujuan',
        'direkomendasikan_oleh',
        'token_jadwal',
        'status',
        'tahap',
        'keputusan',
        'catatan_admin',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'diputuskan_oleh',
        'diputuskan_pada',
    ];

    protected $casts = [
        'diverifikasi_pada' => 'datetime',
        'diputuskan_pada' => 'datetime',
    ];

    public function dataPelamar(): BelongsTo
    {
        return $this->belongsTo(DataPelamar::class);
    }

    public function lowongan(): BelongsTo
    {
        return $this->belongsTo(Lowongan::class);
    }

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    public function isJalurInternal(): bool
    {
        return $this->jalur === self::JALUR_INTERNAL;
    }

    public function isKandidatPegawai(): bool
    {
        return $this->data_pegawai_id !== null;
    }

    public function jabatanTujuan(): ?string
    {
        return $this->lowongan?->jabatan ?? $this->jabatan_tujuan;
    }

    public function unitKerjaTujuan(): ?string
    {
        return $this->lowongan?->unit_kerja ?? $this->unit_kerja_tujuan;
    }

    public function lokasiKerjaTujuan(): ?string
    {
        return $this->lowongan?->lokasi_kerja ?? $this->lokasi_kerja_tujuan;
    }

    public function judulPosisi(): ?string
    {
        return $this->lowongan?->judul ?? $this->jabatan_tujuan;
    }

    public function namaKandidat(): ?string
    {
        return $this->dataPelamar?->nama_lengkap ?? $this->dataPegawai?->nama_lengkap;
    }

    public function emailKandidat(): ?string
    {
        return $this->dataPelamar?->email ?? $this->dataPegawai?->email;
    }

    public function linkJadwal(): string
    {
        if (! $this->token_jadwal) {
            $this->forceFill(['token_jadwal' => \Illuminate\Support\Str::random(40)])->save();
        }

        return route('jadwal-kandidat.show', $this->token_jadwal);
    }

    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function diputuskanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh');
    }

    public function interviewJadwals(): HasMany
    {
        return $this->hasMany(InterviewJadwal::class);
    }

    public function screeningOnboardings(): HasMany
    {
        return $this->hasMany(ScreeningOnboarding::class);
    }

    public function kontrakJadwals(): HasMany
    {
        return $this->hasMany(KontrakJadwal::class);
    }

    public function isMenungguVerifikasi(): bool
    {
        return $this->status === self::STATUS_MENUNGGU_VERIFIKASI;
    }

    public function isProses(): bool
    {
        return $this->status === self::STATUS_PROSES;
    }

    public function isSelesai(): bool
    {
        return in_array($this->status, [self::STATUS_DITERIMA, self::STATUS_DITOLAK, self::STATUS_DIBATALKAN], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PROSES => 'Proses: ' . $this->tahapLabel(),
            self::STATUS_DITERIMA => 'Diterima (Rekrut)',
            self::STATUS_DITOLAK => 'Ditolak',
            self::STATUS_DIBATALKAN => 'Dibatalkan Pelamar',
            default => 'Menunggu Verifikasi',
        };
    }

    public function tahapLabel(): string
    {
        return match ($this->tahap) {
            self::TAHAP_INTERVIEW => 'Interview',
            self::TAHAP_SCREENING => 'Screening/Onboarding',
            self::TAHAP_KONTRAK => 'Tanda Tangan Kontrak',
            self::TAHAP_SELESAI => 'Selesai',
            default => 'Verifikasi',
        };
    }

    public function badgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PROSES => 'text-bg-info',
            self::STATUS_DITERIMA => 'text-bg-success',
            self::STATUS_DITOLAK, self::STATUS_DIBATALKAN => 'text-bg-danger',
            default => 'text-bg-warning',
        };
    }
}
