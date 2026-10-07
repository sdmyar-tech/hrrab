<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Kalender kerja yayasan: satu sumber perhitungan tanggal (onboarding, kontrak, interview, cuti).
 * Hari kerja diatur di config/hrrab.php (default Senin–Jumat) beserta daftar tanggal libur.
 */
class Kalender
{
    public static function isHariKerja(CarbonInterface $tanggal): bool
    {
        return in_array($tanggal->dayOfWeekIso, config('hrrab.hari_kerja', [1, 2, 3, 4, 5]), true)
            && ! in_array($tanggal->toDateString(), config('hrrab.hari_libur', []), true);
    }

    /** $tanggal sendiri bila hari kerja, selain itu hari kerja berikutnya. */
    public static function hariKerjaTerdekat(CarbonInterface $tanggal): Carbon
    {
        $hasil = Carbon::parse($tanggal)->startOfDay();
        for ($i = 0; $i < 60 && ! self::isHariKerja($hasil); $i++) {
            $hasil->addDay();
        }

        return $hasil;
    }

    /** Hari kerja pertama SETELAH $tanggal. */
    public static function hariKerjaSetelah(CarbonInterface $tanggal): Carbon
    {
        return self::hariKerjaTerdekat(Carbon::parse($tanggal)->startOfDay()->addDay());
    }

    /** Hari kerja ke-$jumlah dihitung dari $mulai (termasuk $mulai bila hari kerja). 3 hari mulai Jumat = Jumat, Senin, Selasa. */
    public static function hariKerjaKe(CarbonInterface $mulai, int $jumlah): Carbon
    {
        $hasil = self::hariKerjaTerdekat($mulai);
        for ($i = 1; $i < max(1, $jumlah); $i++) {
            $hasil = self::hariKerjaSetelah($hasil);
        }

        return $hasil;
    }

    public static function jumlahHariKerja(CarbonInterface $mulai, CarbonInterface $selesai): int
    {
        $jumlah = 0;
        for ($t = Carbon::parse($mulai)->startOfDay(); $t->lte($selesai); $t->addDay()) {
            $jumlah += self::isHariKerja($t) ? 1 : 0;
        }

        return $jumlah;
    }

    /** Akhir masa kontrak: sehari sebelum tanggal yang sama N bulan kemudian (31 Jan + 1 bulan = 27/28 Feb, bukan 2/3 Mar). */
    public static function habisKontrak(CarbonInterface $masuk, int $bulan): Carbon
    {
        return Carbon::parse($masuk)->startOfDay()->addMonthsNoOverflow($bulan)->subDay();
    }

    /** Nama hari Indonesia untuk pesan validasi. */
    public static function namaHariKerja(): string
    {
        $nama = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $hari = config('hrrab.hari_kerja', [1, 2, 3, 4, 5]);

        return $nama[min($hari)] . '–' . $nama[max($hari)];
    }
}
