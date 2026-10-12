<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Peserta absen acara sebuah pengumuman (snapshot saat pengumuman disimpan). */
class PengumumanPeserta extends Model
{
    protected $table = 'pengumuman_pesertas';

    public const STATUS_BELUM = 'belum';
    public const STATUS_HADIR = 'hadir';   // absen sendiri: scan QR yang sah
    public const STATUS_MANUAL = 'manual'; // ditandai hadir oleh SDM/Admin/GM

    protected $fillable = [
        'pengumuman_id', 'user_id', 'data_pegawai_id', 'status', 'waktu_absen',
        'catatan', 'ditandai_oleh', 'diingatkan_pada',
    ];

    protected $casts = [
        'waktu_absen' => 'datetime',
        'diingatkan_pada' => 'datetime',
    ];

    public function pengumuman(): BelongsTo
    {
        return $this->belongsTo(Pengumuman::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    public function penanda(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditandai_oleh');
    }

    public function sudahAbsen(): bool
    {
        return $this->status !== self::STATUS_BELUM;
    }

    /**
     * Status tampilan untuk monitoring.
     *
     * @return array{kunci: string, label: string, kelas: string, ikon: string}
     */
    public function tampilan(): array
    {
        $tutup = $this->pengumuman?->absenFase() === Pengumuman::ABSEN_TUTUP;

        return match (true) {
            $this->status === self::STATUS_HADIR => ['kunci' => 'valid', 'label' => 'Valid', 'kelas' => 'text-bg-success', 'ikon' => 'bi-check-circle-fill'],
            $this->status === self::STATUS_MANUAL => ['kunci' => 'manual', 'label' => 'Ditandai hadir', 'kelas' => 'text-bg-primary', 'ikon' => 'bi-person-check'],
            $tutup => ['kunci' => 'kedaluwarsa', 'label' => 'Kedaluwarsa', 'kelas' => 'text-bg-secondary', 'ikon' => 'bi-hourglass-bottom'],
            default => ['kunci' => 'belum', 'label' => 'Belum absen', 'kelas' => 'text-bg-light border', 'ikon' => 'bi-circle'],
        };
    }
}
