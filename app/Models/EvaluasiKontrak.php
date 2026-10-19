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

    /** Lokasi bawaan penandatanganan kontrak. */
    public const TTD_LOKASI_BAWAAN = 'Ruangan SDM';

    /** Hari sebelum habis kontrak saat evaluasi dibuat (H-1 bulan). */
    public const HARI_SEBELUM = 30;

    protected $fillable = [
        'data_pegawai_id', 'unit_kerja', 'status_kerja', 'tanggal_habis_kontrak', 'status',
        'rekomendasi', 'lama_bulan', 'naik_gaji', 'naik_gaji_nominal', 'naik_gaji_keterangan', 'nilai', 'total_nilai', 'rata_nilai', 'periode_mulai', 'periode_selesai', 'catatan', 'diisi_oleh', 'diisi_pada',
        'keputusan', 'keputusan_lama_bulan', 'keputusan_naik_gaji', 'keputusan_naik_gaji_nominal', 'catatan_sdm', 'diputuskan_oleh', 'diputuskan_pada', 'ttd_jadwal', 'ttd_lokasi',
    ];

    protected $casts = [
        'tanggal_habis_kontrak' => 'date',
        'diisi_pada' => 'datetime',
        'diputuskan_pada' => 'datetime',
        'ttd_jadwal' => 'datetime',
        'lama_bulan' => 'integer',
        'keputusan_lama_bulan' => 'integer',
        'naik_gaji' => 'boolean',
        'keputusan_naik_gaji' => 'boolean',
        'naik_gaji_nominal' => 'integer',
        'keputusan_naik_gaji_nominal' => 'integer',
        'nilai' => 'array',
        'total_nilai' => 'integer',
        'rata_nilai' => 'float',
        'periode_mulai' => 'date',
        'periode_selesai' => 'date',
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
            self::STATUS_MENUNGGU_ATASAN => 'Menunggu penilaian',
            self::STATUS_MENUNGGU_SDM => 'Menunggu keputusan',
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

    /** "Perpanjang kontrak 12 bulan + naik gaji Rp 300.000" / "Angkat jadi pegawai tetap" / "Tidak diperpanjang". */
    public static function labelPilihan(?string $pilihan, ?int $lama = null, bool $naikGaji = false, ?int $nominal = null): string
    {
        return ($pilihan ? self::PILIHAN[$pilihan] ?? $pilihan : '-') . ($pilihan === self::PERPANJANG && $lama ? " {$lama} bulan" : '')
            . ($naikGaji && $pilihan !== self::TIDAK ? ' + naik gaji' . ($nominal ? ' ' . self::rupiah($nominal) : '') : '');
    }

    public static function rupiah(?int $n): string
    {
        return $n === null ? '-' : 'Rp ' . number_format($n, 0, ',', '.');
    }

    /** Label rekomendasi atasan lengkap (termasuk usulan naik gaji). */
    public function labelRekomendasi(): string
    {
        return self::labelPilihan($this->rekomendasi, $this->lama_bulan, (bool) $this->naik_gaji, $this->naik_gaji_nominal);
    }

    /** Label keputusan Kepala SDM lengkap (termasuk naik gaji). */
    public function labelKeputusan(): string
    {
        return self::labelPilihan($this->keputusan, $this->keputusan_lama_bulan, (bool) $this->keputusan_naik_gaji, $this->keputusan_naik_gaji_nominal);
    }

    /** Semua butir penilaian: [nomor => teks] (config/penilaian_kontrak.php). */
    public static function butir(): array
    {
        // Gabung manual (operator +) agar nomor butir 1–50 tetap jadi kunci; flatMap/array_merge menomori ulang dari 0.
        $semua = [];
        foreach (config('penilaian_kontrak.bagian') as $b) {
            foreach ($b['kelompok'] as $k) {
                $semua += $k['butir'];
            }
        }

        return $semua;
    }

    /** Rata-rata nilai 1–4 (null bila belum dinilai). */
    public function rataNilai(): ?float
    {
        return $this->rata_nilai;
    }

    /** Predikat dari total nilai (config penilaian_kontrak.predikat_total), mis. 151–200 = Sangat Baik. */
    public static function predikat(?int $total): string
    {
        if ($total === null) {
            return '-';
        }
        foreach (self::rentangPredikat() as [$min, , $label]) {
            if ($total >= $min) {
                return $label;
            }
        }

        return '-';
    }

    /**
     * Rentang skor untuk keterangan: [[min, maks, predikat], ...] dari tertinggi; maks teratas = 4 × jumlah butir.
     *
     * @return list<array{0: int, 1: int, 2: string}>
     */
    public static function rentangPredikat(): array
    {
        $batas = config('penilaian_kontrak.predikat_total');
        krsort($batas);
        $maks = count(self::butir()) * max(array_keys(config('penilaian_kontrak.skala')));
        $rentang = [];
        foreach ($batas as $min => $label) {
            $rentang[] = [$min, $maks, $label];
            $maks = $min - 1;
        }

        return $rentang;
    }

    /** Rata-rata per bagian (A, B, …) untuk ringkasan. @return array<string, float|null> */
    public function rataBagian(): array
    {
        $nilai = $this->nilai ?? [];

        return collect(config('penilaian_kontrak.bagian'))->mapWithKeys(function ($b) use ($nilai) {
            $isi = collect($b['kelompok'])->flatMap(fn ($k) => array_keys($k['butir']))->map(fn ($no) => $nilai[$no] ?? null)->filter();

            return [$b['kode'] => $isi->isEmpty() ? null : round($isi->avg(), 2)];
        })->all();
    }

    /**
     * Periode penilaian bawaan: sejak kontrak dinilai terakhir (atau TMT) sampai tanggal habis kontrak ini.
     *
     * @return array{0: ?\Carbon\Carbon, 1: \Carbon\Carbon}
     */
    public function periodeBawaan(): array
    {
        $sebelumnya = self::where('data_pegawai_id', $this->data_pegawai_id)->whereKeyNot($this->id)
            ->whereDate('tanggal_habis_kontrak', '<', $this->tanggal_habis_kontrak)->max('tanggal_habis_kontrak');
        $mulai = $sebelumnya ? \Carbon\Carbon::parse($sebelumnya)->addDay() : $this->dataPegawai?->kepegawaian?->tanggal_masuk;

        return [$mulai, $this->tanggal_habis_kontrak];
    }

    /** "Senin, 26 Okt 2026 09:00 · Ruangan SDM" atau null bila belum dijadwalkan. */
    public function labelTtd(): ?string
    {
        return $this->ttd_jadwal ? $this->ttd_jadwal->translatedFormat('l, d M Y H:i') . ' · ' . ($this->ttd_lokasi ?: self::TTD_LOKASI_BAWAAN) : null;
    }

    /** Sisa hari menuju habis kontrak (negatif = sudah lewat). */
    public function sisaHari(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->tanggal_habis_kontrak, false);
    }
}
