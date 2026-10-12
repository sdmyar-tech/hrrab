<?php

namespace App\Models;

use App\Models\Concerns\SnapshotUnitKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanCuti extends Model
{
    use HasFactory, SnapshotUnitKerja, \App\Models\Concerns\DicatatLog;

    protected $table = 'pengajuan_cutis';

    public const JENIS_TAHUNAN = 'tahunan';
    public const JENIS_BESAR = 'besar';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const ANNUAL_QUOTA = 12;

    public const MAX_PER_REQUEST = 6;

    public const MAX_PENGAJUAN_PER_TAHUN = 6;

    public const KATEGORI_BESAR_OPTIONS = [
        'nikah_sendiri' => 'Pernikahan Pegawai Sendiri',
        'anak_menikah' => 'Perkawinan Anak Kandung',
        'khitan_baptis_anak' => 'Mengkhitankan/Membaptiskan Anak',
        'keluarga_inti_meninggal' => 'Istri/Suami/Anak Meninggal Dunia',
        'orang_tua_mertua_meninggal' => 'Orang Tua Kandung/Mertua Meninggal Dunia',
        'istri_melahirkan' => 'Istri Melahirkan/Keguguran Kandungan',
        'saudara_kandung_meninggal' => 'Saudara Kandung Meninggal Dunia',
        'bencana' => 'Korban Kebakaran/Banjir/Penggusuran',
        'panggilan_pengadilan' => 'Panggilan Pengadilan/Pihak Berwajib',
        'izin_studi' => 'Izin Studi/Kuliah (sesuai SOP Izin Studi)',
    ];

    public const KATEGORI_BESAR_DURASI = [
        'nikah_sendiri' => 3,
        'anak_menikah' => 2,
        'khitan_baptis_anak' => 2,
        'keluarga_inti_meninggal' => 3,
        'orang_tua_mertua_meninggal' => 3,
        'istri_melahirkan' => 2,
        'saudara_kandung_meninggal' => 1,
        'bencana' => 1,
        'panggilan_pengadilan' => null,
        'izin_studi' => null,
    ];

    /**
     * Cuti besar = izin karena alasan penting (UU 13/2003 Pasal 93): TIDAK bergantung masa kerja.
     * Pengecualian yang tetap butuh masa kerja minimal 1 tahun (sama seperti cuti tahunan, Pasal 79):
     */
    public const KATEGORI_PERLU_MASA_KERJA = ['izin_studi'];

    /** Cuti besar mendadak: bebas aturan H-7, boleh mulai di hari yang sama dengan pengajuan (tidak boleh tanggal lampau). */
    public const KATEGORI_DADAKAN = [
        'keluarga_inti_meninggal',
        'orang_tua_mertua_meninggal',
        'saudara_kandung_meninggal',
        'istri_melahirkan',
        'bencana',
        'panggilan_pengadilan',
    ];


    public static function isDadakan(?string $jenis, ?string $kategori): bool
    {
        return $jenis === self::JENIS_BESAR && in_array($kategori, self::KATEGORI_DADAKAN, true);
    }

    protected $fillable = [
        'data_pegawai_id',
        'unit_kerja',
        'jenis_cuti',
        'kategori_cuti_besar',
        'tanggal_mulai',
        'tanggal_selesai',
        'lama_cuti',
        'keterangan',
        'pengganti_nama',
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
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
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

    public function jenisLabel(): string
    {
        return $this->jenis_cuti === self::JENIS_BESAR ? 'Cuti Besar' : 'Cuti Tahunan';
    }

    public function kategoriBesarLabel(): ?string
    {
        return self::KATEGORI_BESAR_OPTIONS[$this->kategori_cuti_besar] ?? null;
    }

    public static function durasiKategoriBesar(?string $kategori): ?int
    {
        return self::KATEGORI_BESAR_DURASI[$kategori] ?? null;
    }

    public static function sisaCutiTahunan(?DataPegawai $pegawai, ?int $tahun = null): int
    {
        if (! $pegawai) {
            return self::ANNUAL_QUOTA;
        }

        $terpakai = (int) $pegawai->pengajuanCutis()
            ->where('jenis_cuti', self::JENIS_TAHUNAN)
            ->where('status', self::STATUS_APPROVED)
            ->whereYear('tanggal_mulai', $tahun ?? now()->year)
            ->sum('lama_cuti');

        return max(0, self::ANNUAL_QUOTA - $terpakai);
    }

    public static function jumlahPengajuanTahunIni(?DataPegawai $pegawai, ?int $tahun = null): int
    {
        if (! $pegawai) {
            return 0;
        }

        return (int) $pegawai->pengajuanCutis()
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED])
            ->whereYear('tanggal_mulai', $tahun ?? now()->year)
            ->count();
    }

    public static function adaBentrokBerturutTurut(?DataPegawai $pegawai, $mulai, $selesai): bool
    {
        if (! $pegawai) {
            return false;
        }

        $mulai = \Carbon\Carbon::parse($mulai);
        $selesai = \Carbon\Carbon::parse($selesai);

        return $pegawai->pengajuanCutis()
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED])
            ->where('tanggal_mulai', '<=', $selesai->copy()->addDay())
            ->where('tanggal_selesai', '>=', $mulai->copy()->subDay())
            ->exists();
    }

    public function currentLevelLabel(): string
    {
        return \App\Support\PengajuanWorkflow::levelLabel($this->current_level);
    }

    public function substituteOptions(): \Illuminate\Support\Collection
    {
        $unitKerja = $this->unit_kerja ?? $this->dataPegawai?->kepegawaian?->unit_kerja;

        if (! $unitKerja) {
            return collect();
        }

        return DataPegawai::query()
            ->whereHas('penugasanAktif', fn ($q) => $q->where('unit_kerja', $unitKerja))
            ->where('id', '!=', $this->data_pegawai_id)
            ->orderBy('nama_lengkap')
            ->pluck('nama_lengkap');
    }
}
