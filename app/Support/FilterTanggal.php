<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

/**
 * Filter rentang tanggal untuk halaman daftar: ?tanggal_dari=YYYY-MM-DD&tanggal_sampai=YYYY-MM-DD
 * (nama parameter sama dengan daftar pengajuan cuti/lembur/izin keluar, jadi ekspor ikut tersaring).
 * Tanggal tidak sah diabaikan; bila "dari" lebih besar dari "sampai", keduanya ditukar.
 */
final class FilterTanggal
{
    public const DARI = 'tanggal_dari';

    public const SAMPAI = 'tanggal_sampai';

    /** @return array{0: ?CarbonInterface, 1: ?CarbonInterface} */
    public static function rentang(Request $request): array
    {
        $dari = self::tanggal($request->query(self::DARI));
        $sampai = self::tanggal($request->query(self::SAMPAI));

        if ($dari && $sampai && $dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return [$dari, $sampai];
    }

    public static function aktif(Request $request): bool
    {
        [$dari, $sampai] = self::rentang($request);

        return $dari !== null || $sampai !== null;
    }

    /** Terapkan rentang ke kolom tanggal pada query Eloquent/relasi. */
    public static function terapkan($query, string $kolom, Request $request)
    {
        [$dari, $sampai] = self::rentang($request);

        return $query
            ->when($dari, fn ($q) => $q->whereDate($kolom, '>=', $dari->toDateString()))
            ->when($sampai, fn ($q) => $q->whereDate($kolom, '<=', $sampai->toDateString()));
    }

    /** Untuk daftar yang disaring di koleksi (bukan query): apakah tanggal masuk rentang? */
    public static function cocok(?CarbonInterface $tanggal, Request $request): bool
    {
        [$dari, $sampai] = self::rentang($request);
        if (! $dari && ! $sampai) {
            return true;
        }
        if (! $tanggal) {
            return false;
        }

        return (! $dari || $tanggal->toDateString() >= $dari->toDateString())
            && (! $sampai || $tanggal->toDateString() <= $sampai->toDateString());
    }

    private static function tanggal(mixed $nilai): ?CarbonInterface
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }

        try {
            $t = Carbon::createFromFormat('!Y-m-d', $nilai);

            return $t && $t->format('Y-m-d') === $nilai ? $t : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
