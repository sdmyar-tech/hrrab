<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Jumlah pengajuan per status untuk kartu angka di atas daftar (cuti, lembur, izin keluar).
 * Query yang diberikan = query daftar yang sama TANPA saringan status, sehingga angka mengikuti
 * cakupan & saringan lain (cari, tanggal, bidang GM) yang sedang aktif.
 */
class HitungStatus
{
    /** @return array{semua: int, pending: int, approved: int, rejected: int} */
    public static function dari(Builder $query): array
    {
        $kolom = $query->getModel()->qualifyColumn('status');
        $jumlah = $query->reorder()->toBase()
            ->selectRaw("{$kolom} as status_hitung, count(*) as jumlah")
            ->groupBy($kolom)
            ->pluck('jumlah', 'status_hitung');

        return [
            'semua' => (int) $jumlah->sum(),
            'pending' => (int) ($jumlah['pending'] ?? 0),
            'approved' => (int) ($jumlah['approved'] ?? 0),
            'rejected' => (int) ($jumlah['rejected'] ?? 0),
        ];
    }
}
