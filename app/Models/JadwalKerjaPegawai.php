<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jam kerja per pegawai, dengan jam khusus per hari (mis. Jumat pulang lebih awal). */
class JadwalKerjaPegawai extends Model
{
    use Concerns\DicatatLog;

    public const NAMA_HARI = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    protected $fillable = ['data_pegawai_id', 'jam_masuk', 'jam_pulang', 'toleransi_menit', 'hari_kerja', 'jam_per_hari'];

    protected $casts = [
        'toleransi_menit' => 'integer',
        'hari_kerja' => 'array',
        'jam_per_hari' => 'array',
    ];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    /** @return array<int, int> hari kerja ISO (1 = Senin) */
    public function hariKerja(): array
    {
        return array_map('intval', $this->hari_kerja ?: config('hrrab.hari_kerja', [1, 2, 3, 4, 5]));
    }

    public function toleransi(): int
    {
        return $this->toleransi_menit ?? (int) config('hrrab.absensi.toleransi_menit', 15);
    }

    /**
     * Jam masuk & pulang pada tanggal tertentu (null bila bukan hari kerjanya).
     *
     * @return array{masuk: string, pulang: string}|null format H:i
     */
    public function jamPada(CarbonInterface $tanggal): ?array
    {
        $hari = $tanggal->dayOfWeekIso;
        if (! in_array($hari, $this->hariKerja(), true)) {
            return null;
        }

        $khusus = $this->jam_per_hari[$hari] ?? $this->jam_per_hari[(string) $hari] ?? null;

        return [
            'masuk' => substr($khusus['masuk'] ?? $this->jam_masuk, 0, 5),
            'pulang' => substr($khusus['pulang'] ?? $this->jam_pulang, 0, 5),
        ];
    }

    /** Ringkasan untuk tabel, mis. "07:30–16:00 · Sen–Jum (Jum 11:30)". */
    public function ringkas(): string
    {
        $hari = $this->hariKerja();
        sort($hari);
        $singkat = fn ($h) => mb_substr(self::NAMA_HARI[$h], 0, 3);
        $urut = $hari === range(min($hari), max($hari)) && count($hari) > 2;
        $teksHari = $urut ? $singkat(min($hari)) . '–' . $singkat(max($hari)) : implode(', ', array_map($singkat, $hari));

        $khusus = collect($this->jam_per_hari ?? [])
            ->map(fn ($j, $h) => $singkat((int) $h) . ' ' . ($j['masuk'] ?? substr($this->jam_masuk, 0, 5)) . '–' . ($j['pulang'] ?? substr($this->jam_pulang, 0, 5)))
            ->implode(', ');

        return substr($this->jam_masuk, 0, 5) . '–' . substr($this->jam_pulang, 0, 5) . ' · ' . $teksHari . ($khusus ? " ({$khusus})" : '');
    }
}
