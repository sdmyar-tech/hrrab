<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Mengenali pengunjung yang pernah login lewat portal SDM (cookie penanda), agar saat sesinya habis
 * diarahkan kembali ke halaman login internal — bukan ke 404/Lowongan. Tamu lain tetap tidak diberi tahu alamat portal.
 */
class PortalStaf
{
    public const COOKIE = 'portal_staf';

    /** Area yang hanya dipakai akun internal (tamu yang membukanya lewat tautan internal = staf yang sesinya habis). */
    private const AREA_INTERNAL = ['admin', 'pegawai', 'akun'];

    public static function tandai(): void
    {
        Cookie::queue(self::COOKIE, '1', 60 * 24 * 365);
    }

    public static function dikenali(Request $request): bool
    {
        if ($request->cookie(self::COOKIE)) {
            return true;
        }
        $asal = trim((string) parse_url((string) $request->headers->get('referer'), PHP_URL_PATH), '/');

        return in_array(strtok($asal, '/'), [...self::AREA_INTERNAL, config('hrrab.staff_login_path')], true);
    }

    /** Halaman login yang tepat untuk tamu: portal SDM bila dikenali sebagai staf, selain itu login pelamar. */
    public static function halamanLogin(Request $request): string
    {
        return self::dikenali($request) ? route('login') : route('pelamar.login');
    }
}
