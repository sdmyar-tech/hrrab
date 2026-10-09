<?php

namespace App\Support\Absensi;

use Carbon\Carbon;

/**
 * Pembaca tanggal dari URL untuk halaman absensi.
 * Ketat: "2026-02-30" ditolak (bukan diam-diam jadi 2 Maret), bulan "2026-13" ditolak (bukan jadi Januari 2027).
 */
class TanggalAbsensi
{
    /** Rentang rekap/riwayat maksimal (hari) agar halaman tetap ringan. */
    public const MAKS_HARI = 366;

    /** Tanggal Y-m-d yang benar-benar ada di kalender, atau null. */
    public static function tanggal(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }
        [$t, $b, $h] = array_map('intval', explode('-', $nilai));

        return checkdate($b, $h, $t) ? Carbon::create($t, $b, $h)->startOfDay() : null;
    }

    /** Bulan Y-m yang valid (hari pertama bulan itu), atau null. */
    public static function bulan(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || ! preg_match('/^(\d{4})-(\d{2})$/', $nilai, $m) || (int) $m[2] < 1 || (int) $m[2] > 12) {
            return null;
        }

        return Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
    }

    /**
     * Rentang dari–sampai yang aman + pesan bila ada penyesuaian (ditampilkan ke pengguna, bukan diam-diam).
     *
     * @return array{0: Carbon, 1: Carbon, 2: list<string>}
     */
    public static function rentang(mixed $dari, mixed $sampai, ?Carbon $bawaanDari = null): array
    {
        $hariIni = now()->startOfDay();
        $pesan = [];
        $d = self::tanggal($dari);
        $s = self::tanggal($sampai);

        if (($dari && ! $d) || ($sampai && ! $s)) {
            $pesan[] = 'Tanggal yang dimasukkan tidak valid, jadi diganti ke bawaan.';
        }
        $d ??= $bawaanDari ?? $hariIni->copy()->startOfMonth();
        $s ??= $hariIni->copy();

        if ($s->gt($hariIni)) {
            $s = $hariIni->copy(); // data masa depan belum ada
        }
        if ($d->gt($hariIni)) {
            $d = $hariIni->copy();
        }
        if ($d->gt($s)) {
            [$d, $s] = [$s, $d];
            $pesan[] = 'Tanggal "dari" lebih akhir dari "sampai", jadi keduanya ditukar.';
        }
        if ($d->diffInDays($s) >= self::MAKS_HARI) {
            $d = $s->copy()->subDays(self::MAKS_HARI - 1);
            $pesan[] = 'Rentang maksimal 1 tahun, jadi dimulai dari ' . $d->translatedFormat('d M Y') . '.';
        }

        return [$d, $s, $pesan];
    }
}
