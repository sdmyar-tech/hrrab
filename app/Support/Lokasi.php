<?php

namespace App\Support;

/** Lokasi teks bebas: nama tempat, link Google Maps, atau link meeting online. */
class Lokasi
{
    public static function isUrl(?string $lokasi): bool
    {
        return $lokasi !== null && filter_var(trim($lokasi), FILTER_VALIDATE_URL) !== false;
    }

    public static function isPeta(?string $lokasi): bool
    {
        return self::isUrl($lokasi) && preg_match('~(google\.[a-z.]+/maps|maps\.google\.|maps\.app\.goo\.gl|goo\.gl/maps)~i', $lokasi) === 1;
    }

    /** URL tujuan: link asli bila sudah berupa URL, selain itu pencarian Google Maps. */
    public static function url(?string $lokasi): ?string
    {
        $lokasi = trim((string) $lokasi);

        if ($lokasi === '') {
            return null;
        }

        return self::isUrl($lokasi) ? $lokasi : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lokasi);
    }

    /** Teks yang ditampilkan: nama tempat apa adanya, link diringkas. */
    public static function label(?string $lokasi): string
    {
        return match (true) {
            self::isPeta($lokasi) => 'Lokasi di Google Maps',
            self::isUrl($lokasi) => 'Link meeting online',
            default => (string) $lokasi,
        };
    }
}
