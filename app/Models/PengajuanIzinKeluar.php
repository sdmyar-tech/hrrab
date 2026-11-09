<?php

namespace App\Models;

use App\Models\Concerns\SnapshotUnitKerja;
use App\Support\PengajuanWorkflow;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Izin pegawai (nama tabel/route lama "izin keluar" dipertahankan). Tiga jenis:
 *  - jam    : Izin Keluar saat jam kerja (jam_keluar–jam_kembali); disetujui akhir → kode_tiket untuk security gerbang.
 *  - telat  : Izin Telat Masuk; jam_kembali = perkiraan/jam tiba. Disetujui → telat sampai jam itu dinilai "Telat Izin".
 *  - harian : Izin Tidak Masuk 1 hari atau lebih (lama_hari = hari kerja); disetujui → Izin/Sakit/Dinas Luar di absensi.
 * Disetujui berjenjang (PengajuanWorkflow, sama seperti cuti).
 */
class PengajuanIzinKeluar extends Model
{
    use Concerns\DicatatLog, Concerns\IkutTanggaAlur, SnapshotUnitKerja;

    protected $table = 'pengajuan_izin_keluars';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const JENIS_JAM = 'jam';

    public const JENIS_TELAT = 'telat';

    public const JENIS_HARIAN = 'harian';

    public const JENIS = [
        self::JENIS_JAM => 'Izin Keluar',
        self::JENIS_TELAT => 'Izin Telat Masuk',
        self::JENIS_HARIAN => 'Izin Tidak Masuk',
    ];

    public const KEPERLUAN = [
        'pribadi' => 'Keperluan pribadi',
        'sakit' => 'Sakit',
        'dinas' => 'Tugas kantor / dinas luar',
        'berobat' => 'Berobat',
        'keluarga' => 'Urusan keluarga',
    ];

    /** Keperluan yang hanya boleh untuk jenis tertentu (lainnya: semua jenis). Sakit tidak untuk Izin Keluar. */
    public const KEPERLUAN_KHUSUS = ['sakit' => [self::JENIS_TELAT, self::JENIS_HARIAN]];

    /** Status absensi (PenilaianAbsensi) untuk hari izin tidak masuk yang disetujui, menurut keperluan. */
    public const STATUS_ABSENSI = ['sakit' => 'sakit', 'dinas' => 'dinas_luar'];

    /** Tiket sudah bisa ditunjukkan sejak sekian menit sebelum jam keluar. */
    public const TIKET_MULAI_SEBELUM_MENIT = 30;

    protected $fillable = [
        'data_pegawai_id',
        'unit_kerja',
        'jenis',
        'tanggal',
        'tanggal_selesai',
        'lama_hari',
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
        'tanggal_selesai' => 'date',
        'lama_hari' => 'integer',
        'approved_at' => 'datetime',
        'kepala_approved_at' => 'datetime',
        'gm_approved_at' => 'datetime',
        'gm_pengganti' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Kode tiket (hanya Izin Keluar) dibuat saat disetujui akhir (lewat tahapan maupun ubah langsung Admin).
        static::saving(function (self $izin) {
            if ($izin->status === self::STATUS_APPROVED && ! $izin->kode_tiket && $izin->isKeluar()) {
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
        return PengajuanWorkflow::labelTahap($this);
    }

    public function keperluanLabel(): string
    {
        return self::KEPERLUAN[$this->keperluan] ?? (string) $this->keperluan;
    }

    public function isHarian(): bool
    {
        return $this->jenis === self::JENIS_HARIAN;
    }

    public function isTelat(): bool
    {
        return $this->jenis === self::JENIS_TELAT;
    }

    /** Izin Keluar (jenis kosong = data lama, juga Izin Keluar). */
    public function isKeluar(): bool
    {
        return ! $this->isHarian() && ! $this->isTelat();
    }

    /** @return array<string, string> pilihan keperluan yang sah untuk satu jenis izin */
    public static function keperluanUntuk(string $jenis): array
    {
        return array_filter(self::KEPERLUAN, fn ($label, $kode) => in_array($jenis, self::KEPERLUAN_KHUSUS[$kode] ?? [$jenis], true), ARRAY_FILTER_USE_BOTH);
    }

    /** "09:30" — jam tiba untuk Izin Telat Masuk. */
    public function jamTiba(): ?string
    {
        return $this->isTelat() && $this->jam_kembali ? substr((string) $this->jam_kembali, 0, 5) : null;
    }

    public function jenisLabel(): string
    {
        return self::JENIS[$this->jenis] ?? self::JENIS[self::JENIS_JAM];
    }

    /** Tanggal terakhir izin (izin keluar/telat: tanggal itu sendiri). */
    public function tanggalAkhir(): Carbon
    {
        return ($this->isHarian() && $this->tanggal_selesai ? $this->tanggal_selesai : $this->tanggal)->copy();
    }

    /** Hanya Izin Keluar yang punya tiket gerbang. */
    public function punyaTiket(): bool
    {
        return $this->isApproved() && $this->isKeluar();
    }

    /** Status di absensi untuk hari izin tidak masuk: sakit | dinas_luar | izin. */
    public function statusAbsensi(): string
    {
        return self::STATUS_ABSENSI[$this->keperluan] ?? 'izin';
    }

    /** Izin (selain yang ditolak) yang rentang tanggalnya beririsan dengan $dari–$sampai. */
    public function scopeBertumpuk(Builder $q, string $dari, string $sampai): Builder
    {
        return $q->where('status', '!=', self::STATUS_REJECTED)
            ->whereDate('tanggal', '<=', $sampai)
            ->whereRaw('COALESCE(tanggal_selesai, tanggal) >= ?', [$dari]);
    }

    public function tidakKembali(): bool
    {
        return $this->isKeluar() && $this->jam_kembali === null;
    }

    /** "08:30 – 10:00", "08:30 – tidak kembali", "Telat masuk · tiba 09:30", atau "Tidak masuk · 2 hari kerja". */
    public function jamLabel(): string
    {
        if ($this->isHarian()) {
            return 'Tidak masuk · ' . ($this->lama_hari ?? 1) . ' hari kerja';
        }
        if ($this->isTelat()) {
            return 'Telat masuk · tiba ' . $this->jamTiba();
        }

        return substr((string) $this->jam_keluar, 0, 5) . ' – ' . ($this->tidakKembali() ? 'tidak kembali' : substr((string) $this->jam_kembali, 0, 5));
    }

    /** "Senin, 23 Nov 2026" atau (izin beberapa hari) "23 Nov – 25 Nov 2026". */
    public function tanggalLabel(string $format = 'l, d M Y'): string
    {
        $akhir = $this->tanggalAkhir();
        if ($akhir->isSameDay($this->tanggal)) {
            return $this->tanggal->translatedFormat($format);
        }

        return $this->tanggal->translatedFormat($this->tanggal->isSameYear($akhir) ? 'd M' : 'd M Y') . ' – ' . $akhir->translatedFormat('d M Y');
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
        if (! $this->punyaTiket()) {
            return 'tidak_sah';
        }
        $kini ??= now();
        [$mulai, $selesai] = $this->masaBerlaku();

        return $kini->lt($mulai) ? 'belum' : ($kini->gt($selesai) ? 'lewat' : 'berlaku');
    }
}
