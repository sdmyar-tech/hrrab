<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Evaluasi kontrak pegawai menjelang habis: rekomendasi atasan → keputusan Kepala SDM. */
class EvaluasiKontrak extends Model
{
    use \App\Models\Concerns\DicatatLog;

    public const STATUS_MENUNGGU_ATASAN = 'menunggu_atasan';
    public const STATUS_MENUNGGU_SDM = 'menunggu_sdm';
    public const STATUS_SELESAI = 'selesai';

    public const PERPANJANG = 'perpanjang';
    public const TETAP = 'tetap';
    public const TIDAK = 'tidak';

    public const PILIHAN = [
        self::PERPANJANG => 'Perpanjang kontrak',
        self::TETAP => 'Angkat jadi pegawai tetap',
        self::TIDAK => 'Tidak diperpanjang',
    ];

    /** Lama perpanjangan yang bisa dipilih (bulan) — sama dengan masa kontrak saat rekrut. */
    public const LAMA_BULAN = [3, 6, 9, 12, 15, 18, 21, 24, 27, 30, 33, 36];

    public const ASPEK = [
        'nilai_kinerja' => 'Kinerja & hasil kerja',
        'nilai_disiplin' => 'Kedisiplinan & kehadiran',
        'nilai_sikap' => 'Sikap & kerja sama',
    ];

    /** Hari sebelum habis kontrak saat evaluasi dibuat (H-1 bulan). */
    public const HARI_SEBELUM = 30;

    protected $fillable = [
        'data_pegawai_id', 'unit_kerja', 'status_kerja', 'tanggal_habis_kontrak', 'status',
        'rekomendasi', 'lama_bulan', 'nilai_kinerja', 'nilai_disiplin', 'nilai_sikap', 'catatan', 'diisi_oleh', 'diisi_pada',
        'keputusan', 'keputusan_lama_bulan', 'catatan_sdm', 'diputuskan_oleh', 'diputuskan_pada',
    ];

    protected $casts = [
        'tanggal_habis_kontrak' => 'date',
        'diisi_pada' => 'datetime',
        'diputuskan_pada' => 'datetime',
        'lama_bulan' => 'integer',
        'keputusan_lama_bulan' => 'integer',
        'nilai_kinerja' => 'integer',
        'nilai_disiplin' => 'integer',
        'nilai_sikap' => 'integer',
    ];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    public function pengisi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diisi_oleh');
    }

    public function pemutus(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_MENUNGGU_ATASAN => 'Menunggu atasan',
            self::STATUS_MENUNGGU_SDM => 'Menunggu Kepala SDM',
            default => 'Selesai',
        };
    }

    public function badgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_MENUNGGU_ATASAN => 'text-bg-warning',
            self::STATUS_MENUNGGU_SDM => 'text-bg-info',
            default => 'text-bg-success',
        };
    }

    /** "Perpanjang kontrak 12 bulan" / "Angkat jadi pegawai tetap" / "Tidak diperpanjang". */
    public static function labelPilihan(?string $pilihan, ?int $lama = null): string
    {
        return ($pilihan ? self::PILIHAN[$pilihan] ?? $pilihan : '-') . ($pilihan === self::PERPANJANG && $lama ? " {$lama} bulan" : '');
    }

    /** Rata-rata nilai 1–5 (null bila belum dinilai). */
    public function rataNilai(): ?float
    {
        $nilai = array_filter([$this->nilai_kinerja, $this->nilai_disiplin, $this->nilai_sikap]);

        return $nilai ? round(array_sum($nilai) / count($nilai), 1) : null;
    }

    /** Sisa hari menuju habis kontrak (negatif = sudah lewat). */
    public function sisaHari(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->tanggal_habis_kontrak, false);
    }
}
