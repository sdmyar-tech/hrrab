<?php

namespace App\Http\Middleware;

use App\Models\LogAktivitas;
use App\Models\PengumumanPeserta;
use App\Models\PersetujuanTahap;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setelah operasi (tambah/ubah/hapus/aksi lain) pengguna dikembalikan ke halaman TEMPAT ia mengerjakannya, bukan
 * ke halaman bawaan controller yang sering salah:
 *  1. Controller mengarahkan ke halaman yang sama dengan asal (mis. daftar) → pakai alamat asal lengkap
 *     (halaman ke-N, cari, filter, urutan tetap terjaga), query dari controller (mis. ?bulan=, ?tab=) menang.
 *  2. Aksi dikerjakan di halaman detail (/x/5) dan controller mengarahkan ke daftarnya (/x) → tetap di detail.
 *     Dikecualikan: hapus (detailnya sudah tidak ada) dan asal berupa form tambah/ubah (/create, /edit).
 * Setelah tambah/ubah berhasil, id data yang berubah dikirim ke halaman lewat flash "sorot": halaman menaikkan
 * barisnya ke paling atas & menyorotnya (partial layouts.partials.sorot), atau memberi tahu bila tidak tampil.
 */
class KembaliKeHalamanAsal
{
    /** Data pendamping yang tidak boleh dianggap "data yang dibuat/diubah" pengguna. */
    private const ABAIKAN = [LogAktivitas::class, PersetujuanTahap::class, PengumumanPeserta::class];

    private const KUNCI_DAFTAR = 'daftar_terakhir';

    private static ?Model $dibuatPertama = null;
    private static bool $terpasang = false;

    public function handle(Request $request, Closure $next): Response
    {
        self::$dibuatPertama = null;
        if (! self::$terpasang) {
            self::$terpasang = true;
            Event::listen('eloquent.created: *', function (string $nama, array $data) {
                $model = $data[0] ?? null;
                if ($model instanceof Model && self::$dibuatPertama === null && ! in_array($model::class, self::ABAIKAN, true)) {
                    self::$dibuatPertama = $model;
                }
            });
        }

        $response = $next($request);

        $this->ingatDaftar($request, $response);

        if (! $response instanceof RedirectResponse || $request->isMethodSafe() || $request->expectsJson() || ! $request->user()
            || $request->routeIs('login*', 'logout', 'pelamar.*', 'akun.*')) {
            return $response;
        }

        $sesi = $request->hasSession() ? $request->session() : null;
        $gagal = $sesi && ($sesi->get('error') || ($sesi->get('errors')?->any()));

        if (! $gagal) {
            $this->kembalikan($request, $response);
            if ($sesi && $request->method() !== 'DELETE' && ($kunci = $this->kunciBerubah($request)) !== null) {
                $sesi->flash('sorot', ['kunci' => $kunci]);
            }
        }

        return $response;
    }

    private function kembalikan(Request $request, RedirectResponse $response): void
    {
        $asal = $request->input('_kembali') ?: $request->headers->get('referer');
        if (! is_string($asal) || $asal === '') {
            return;
        }
        $a = parse_url($asal);
        $t = parse_url($response->getTargetUrl());
        if (! $a || ! $t || ($a['host'] ?? null) !== ($t['host'] ?? null) || ($a['host'] ?? null) !== $request->getHost()) {
            return;
        }

        $pathAsal = rtrim($a['path'] ?? '/', '/') ?: '/';
        $pathTujuan = rtrim($t['path'] ?? '/', '/') ?: '/';
        parse_str($a['query'] ?? '', $queryAsal);
        parse_str($t['query'] ?? '', $queryTujuan);
        $akar = ($t['scheme'] ?? $request->getScheme()) . '://' . $t['host'] . (isset($t['port']) ? ':' . $t['port'] : '');

        if ($pathAsal === $pathTujuan) {
            // 1. Halaman yang sama: pertahankan halaman/cari/filter asal.
            $query = array_merge($queryAsal, $queryTujuan);
            $url = $akar . $pathTujuan . ($query ? '?' . http_build_query($query) : '') . (isset($t['fragment']) ? '#' . $t['fragment'] : '');
            $response->setTargetUrl($url);

            return;
        }

        // 2. Dikerjakan di halaman detail, controller mengarah ke daftarnya → tetap di detail.
        if ($request->method() !== 'DELETE' && substr_count($pathTujuan, '/') >= 2
            && preg_match('#^' . preg_quote($pathTujuan, '#') . '/([^/]+)$#', $pathAsal, $m) && ! in_array($m[1], ['create', 'edit'], true)) {
            $response->setTargetUrl($akar . $pathAsal . ($queryAsal ? '?' . http_build_query($queryAsal) : ''));

            return;
        }

        // 3. Dari form tambah/ubah/detail kembali ke daftar tanpa query → pakai daftar yang terakhir dibuka (halaman, cari, filter).
        if (! $queryTujuan && ! isset($t['fragment'])) {
            $ingat = $request->session()->get(self::KUNCI_DAFTAR . '.' . md5($pathTujuan));
            if (is_string($ingat) && $ingat !== '') {
                $response->setTargetUrl($akar . $pathTujuan . '?' . $ingat);
            }
        }
    }

    /** Ingat alamat daftar terakhir (dengan query) per halaman agar bisa kembali ke halaman/cari/filter yang sama. */
    private function ingatDaftar(Request $request, Response $response): void
    {
        if (! $request->hasSession() || ! $request->isMethod('GET') || $request->expectsJson() || $request->ajax() || ! $request->user()
            || $response->getStatusCode() !== 200) {
            return;
        }
        $sesi = $request->session();
        $kunci = self::KUNCI_DAFTAR . '.' . md5('/' . ltrim($request->path(), '/'));
        $query = $request->getQueryString();
        if ($query) {
            $sesi->put($kunci, $query);
        } elseif ($sesi->has($kunci)) {
            $sesi->forget($kunci);
        }
    }

    /** Id data yang baru dibuat, atau data (model terikat rute) yang diubah oleh aksi ini. */
    private function kunciBerubah(Request $request): int|string|null
    {
        if (self::$dibuatPertama) {
            return self::$dibuatPertama->getKey();
        }
        foreach ($request->route()?->parameters() ?? [] as $nilai) {
            if ($nilai instanceof Model) {
                return $nilai->getKey();
            }
        }

        return null;
    }
}
