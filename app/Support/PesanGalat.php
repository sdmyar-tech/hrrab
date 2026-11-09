<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pesan galat untuk pengguna tanpa membocorkan detail teknis (SQL, nama database, host).
 * Detail lengkap dicatat di log dengan kode galat pendek yang tampil di layar agar mudah dicari.
 */
class PesanGalat
{
    public static function untukPengguna(Throwable $e, string $awal): string
    {
        $kode = strtoupper(Str::random(6));
        Log::error("[Galat {$kode}] {$awal}", ['exception' => $e]);

        // Kolom/tabel tidak ditemukan = migrasi belum dijalankan di server ini.
        $strukturLama = $e instanceof QueryException && in_array($e->errorInfo[0] ?? null, ['42S22', '42S02'], true);

        return $awal . ($strukturLama
            ? ' Struktur database server belum diperbarui; minta Admin menjalankan "php artisan migrate".'
            : ' Silakan coba lagi.')
            . " Jika berulang, laporkan kode galat {$kode} ke Admin.";
    }
}
