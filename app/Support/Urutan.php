<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Pengurutan daftar yang dipilih pengguna lewat parameter ?urut=... (tanggal terbaru/terlama, A–Z/Z–A).
 *
 * Opsi berbentuk: kunci => [label, kolom, arah]
 *  - kolom: ekspresi SQL dari kode (bukan input pengguna) atau Closure($query, $arah)
 *  - opsi pertama = urutan bawaan
 */
class Urutan
{
    /**
     * Terapkan urutan ke query dan kembalikan data untuk partial `partials.urut`.
     *
     * @param  Builder|Relation  $query
     * @param  array<string, array{0: string, 1: string|Closure, 2: string}>  $opsi
     * @return array{urutOpsi: array<string, string>, urutAktif: string}
     */
    public static function terapkan($query, array $opsi): array
    {
        $kunci = request()->query('urut');
        if (! is_string($kunci) || ! array_key_exists($kunci, $opsi)) {
            $kunci = array_key_first($opsi);
        }

        [, $kolom, $arah] = $opsi[$kunci];
        $arah = $arah === 'asc' ? 'asc' : 'desc';

        $query->reorder();
        if ($kolom instanceof Closure) {
            $kolom($query, $arah);
        } else {
            // Data kosong (NULL) selalu di bawah, apa pun arahnya.
            $query->orderByRaw("({$kolom}) IS NULL")->orderByRaw("{$kolom} {$arah}");
        }
        // Pemecah seri agar urutan stabil antarhalaman.
        $query->orderBy($query->getModel()->getQualifiedKeyName(), $arah);

        return ['urutOpsi' => array_map(fn ($o) => $o[0], $opsi), 'urutAktif' => $kunci];
    }

    /** Pasangan opsi tanggal: "<label> terbaru" lalu "<label> terlama". */
    public static function tanggal(string $kunci, string $label, string|Closure $kolom): array
    {
        return [
            "{$kunci}_baru" => ["{$label} terbaru", $kolom, 'desc'],
            "{$kunci}_lama" => ["{$label} terlama", $kolom, 'asc'],
        ];
    }

    /** Pasangan opsi abjad: "<label> A–Z" lalu "<label> Z–A". */
    public static function abjad(string $kunci, string $label, string|Closure $kolom): array
    {
        return [
            "{$kunci}_az" => ["{$label} A–Z", $kolom, 'asc'],
            "{$kunci}_za" => ["{$label} Z–A", $kolom, 'desc'],
        ];
    }

    /** Subquery nama pegawai untuk tabel yang punya kolom data_pegawai_id. */
    public static function namaPegawai(string $tabel): string
    {
        return "(select nama_lengkap from data_pegawais where data_pegawais.id = {$tabel}.data_pegawai_id)";
    }
}
