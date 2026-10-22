<?php

namespace App\Models;

use App\Support\Notifikasi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreeningOnboarding extends Model
{
    use \App\Models\Concerns\DicatatLog, HasFactory;

    protected static function booted(): void
    {
        static::created(fn (self $jadwal) => Notifikasi::jadwalKandidat($jadwal));
    }

    protected $table = 'screening_onboardings';

    public const DURASI_HARI = 3;

    public const STATUS_BERJALAN = 'berjalan';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const HASIL_LULUS = 'lulus';

    public const HASIL_TIDAK_LULUS = 'tidak_lulus';

    protected $fillable = [
        'lamaran_id',
        'pengawas_id',
        'kepala_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'durasi_hari',
        'lokasi',
        'catatan',
        'status',
        'hasil_status',
        'catatan_hasil',
        'diisi_oleh',
        'diisi_pada',
        'dijadwalkan_oleh',
        'hasil_sdm',
        'catatan_sdm',
        'konfirmasi_sdm_oleh',
        'konfirmasi_sdm_pada',
        'hasil_kepala',
        'catatan_kepala',
        'konfirmasi_kepala_oleh',
        'konfirmasi_kepala_pada',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'diisi_pada' => 'datetime',
        'durasi_hari' => 'integer',
        'konfirmasi_sdm_pada' => 'datetime',
        'konfirmasi_kepala_pada' => 'datetime',
    ];

    public function lamaran(): BelongsTo
    {
        return $this->belongsTo(Lamaran::class);
    }

    public function pengawas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengawas_id');
    }

    public function kepala(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepala_id');
    }

    public function konfirmasiSdmOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'konfirmasi_sdm_oleh');
    }

    public function konfirmasiKepalaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'konfirmasi_kepala_oleh');
    }

    public static function labelHasil(?string $hasil): string
    {
        return match ($hasil) {
            self::HASIL_LULUS => 'Lulus',
            self::HASIL_TIDAK_LULUS => 'Tidak Lulus',
            default => 'Menunggu',
        };
    }

    public function diisiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diisi_oleh');
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
            default => 'Berjalan',
        };
    }

    public function hasilLabel(): string
    {
        return match ($this->hasil_status) {
            self::HASIL_LULUS => 'Lulus',
            self::HASIL_TIDAK_LULUS => 'Tidak Lulus',
            default => 'Belum Diisi',
        };
    }

    public function hasilBadgeClass(): string
    {
        return match ($this->hasil_status) {
            self::HASIL_LULUS => 'text-bg-success',
            self::HASIL_TIDAK_LULUS => 'text-bg-danger',
            default => 'text-bg-light border',
        };
    }
}
