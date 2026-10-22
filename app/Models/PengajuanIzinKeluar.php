<?php

namespace App\Models;

use App\Models\Concerns\SnapshotUnitKerja;
use App\Support\PengajuanWorkflow;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Izin keluar saat jam kerja. Disetujui berjenjang (PengajuanWorkflow, sama seperti cuti);
 * setelah disetujui akhir mendapat kode_tiket dan tiketnya ditunjukkan ke security gerbang.
 */
class PengajuanIzinKeluar extends Model
{
    use Concerns\DicatatLog, SnapshotUnitKerja;

    protected $table = 'pengajuan_izin_keluars';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const KEPERLUAN = [
        'pribadi' => 'Keperluan pribadi',
        'dinas' => 'Tugas kantor / dinas luar',
        'berobat' => 'Berobat',
        'keluarga' => 'Urusan keluarga',
    ];

    /** Tiket sudah bisa ditunjukkan sejak sekian menit sebelum jam keluar. */
    public const TIKET_MULAI_SEBELUM_MENIT = 30;

    protected $fillable = [
        'data_pegawai_id',
        'unit_kerja',
        'tanggal',
        'jam_keluar',
        'jam_kembali',
        'keperluan',
        'alasan',
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
        'approved_at' => 'datetime',
        'kepala_approved_at' => 'datetime',
        'gm_approved_at' => 'datetime',
        'gm_pengganti' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Kode tiket dibuat saat disetujui akhir (lewat tahapan maupun ubah langsung Admin).
        static::saving(function (self $izin) {
            if ($izin->status === self::STATUS_APPROVED && ! $izin->kode_tiket) {
                do {
                    $kode = 'IK-' . $izin->tanggal->format('ymd') . '-' . Str::upper(Str::random(4));
                } while (self::where('kode_tiket', $kode)->exists());
                $izin->kode_tiket = $kode;
            }
        });
    }

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

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
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
        return PengajuanWorkflow::levelLabel($this->current_level);
    }

    public function keperluanLabel(): string
    {
        return self::KEPERLUAN[$this->keperluan] ?? (string) $this->keperluan;
    }

    public function tidakKembali(): bool
    {
        return $this->jam_kembali === null;
    }

    /** "08:30 – 10:00" atau "08:30 – tidak kembali". */
    public function jamLabel(): string
    {
        return substr((string) $this->jam_keluar, 0, 5) . ' – ' . ($this->tidakKembali() ? 'tidak kembali' : substr((string) $this->jam_kembali, 0, 5));
    }

    /**
     * Rentang berlakunya tiket: 30 menit sebelum jam keluar s/d jam kembali (tidak kembali: akhir hari).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function masaBerlaku(): array
    {
        $tanggal = $this->tanggal->toDateString();
        $mulai = Carbon::parse("{$tanggal} {$this->jam_keluar}")->subMinutes(self::TIKET_MULAI_SEBELUM_MENIT);
        $selesai = $this->tidakKembali() ? Carbon::parse($tanggal)->endOfDay() : Carbon::parse("{$tanggal} {$this->jam_kembali}");

        return [$mulai, $selesai];
    }

    /** Status tiket saat $kini: 'berlaku' | 'belum' | 'lewat' | 'tidak_sah' (belum/tidak disetujui). */
    public function statusTiket(?Carbon $kini = null): string
    {
        if (! $this->isApproved()) {
            return 'tidak_sah';
        }
        $kini ??= now();
        [$mulai, $selesai] = $this->masaBerlaku();

        return $kini->lt($mulai) ? 'belum' : ($kini->gt($selesai) ? 'lewat' : 'berlaku');
    }
}
