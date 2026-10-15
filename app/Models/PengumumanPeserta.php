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
    // Berhalangan: tidak hadir dengan keterangan (tidak dihitung "belum absen" dan tidak diingatkan).
    public const STATUS_IZIN = 'izin';
    public const STATUS_SAKIT = 'sakit';
    public const STATUS_DINAS = 'dinas';
    public const STATUS_CUTI = 'cuti';

    public const BERHALANGAN = [self::STATUS_IZIN, self::STATUS_SAKIT, self::STATUS_DINAS, self::STATUS_CUTI];

    /** Status yang bisa ditetapkan panitia (cuti selalu otomatis dari data cuti). */
    public const BISA_DITETAPKAN = [self::STATUS_IZIN => 'Izin', self::STATUS_SAKIT => 'Sakit', self::STATUS_DINAS => 'Dinas luar'];

    public const SUMBER_MANUAL = 'manual';
    public const SUMBER_CUTI = 'cuti';
    public const SUMBER_ABSENSI = 'absensi';

    protected $fillable = [
        'pengumuman_id', 'user_id', 'data_pegawai_id', 'status', 'sumber', 'sumber_id', 'waktu_absen',
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

    /** Sudah hadir (scan QR atau ditandai hadir panitia). */
    public function sudahAbsen(): bool
    {
        return in_array($this->status, [self::STATUS_HADIR, self::STATUS_MANUAL], true);
    }

    public function berhalangan(): bool
    {
        return in_array($this->status, self::BERHALANGAN, true);
    }

    /** Status otomatis dari data lain (cuti / absensi harian) — dirapikan ulang saat datanya berubah. */
    public function otomatis(): bool
    {
        return in_array($this->sumber, [self::SUMBER_CUTI, self::SUMBER_ABSENSI], true);
    }

    public function sumberLabel(): ?string
    {
        return match ($this->sumber) {
            self::SUMBER_CUTI => 'otomatis dari data cuti',
            self::SUMBER_ABSENSI => 'otomatis dari absensi harian',
            self::SUMBER_MANUAL => 'ditetapkan ' . ($this->penanda?->name ?? 'panitia'),
            default => null,
        };
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
            $this->status === self::STATUS_IZIN => ['kunci' => 'izin', 'label' => 'Izin', 'kelas' => 'text-bg-info', 'ikon' => 'bi-envelope-paper'],
            $this->status === self::STATUS_SAKIT => ['kunci' => 'sakit', 'label' => 'Sakit', 'kelas' => 'text-bg-warning', 'ikon' => 'bi-bandaid'],
            $this->status === self::STATUS_DINAS => ['kunci' => 'dinas', 'label' => 'Dinas luar', 'kelas' => 'text-bg-info', 'ikon' => 'bi-briefcase'],
            $this->status === self::STATUS_CUTI => ['kunci' => 'cuti', 'label' => 'Cuti', 'kelas' => 'text-bg-secondary', 'ikon' => 'bi-calendar2-x'],
            $tutup => ['kunci' => 'kedaluwarsa', 'label' => 'Kedaluwarsa', 'kelas' => 'text-bg-secondary', 'ikon' => 'bi-hourglass-bottom'],
            default => ['kunci' => 'belum', 'label' => 'Belum absen', 'kelas' => 'text-bg-light border', 'ikon' => 'bi-circle'],
        };
    }
}
