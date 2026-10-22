<?php

namespace App\Support;

/**
 * Bagan unit hanya baca (view admin.pengajuan-formasi._bagan-statis), disusun seperti bagan di formulir pengajuan formasi:
 * kepala di atas, cabang per jabatan di kiri/kanan tulang punggung. Pegawai yang digantikan dan posisi baru ditandai.
 * Dipakai rincian formasi untuk penyetuju dan gambaran posisi di formulir kandidat langsung.
 */
class BaganStatis
{
    // Ukuran sama dengan formulir (pengajuan-formasi/create.blade.php): lebar simpul 148px, maks 4 berjajar/kolom rak, dilipat > 12.
    private const NODE = 148;

    private const SEJAJAR = 4;

    private const BATAS_LIPAT = 12;

    /**
     * @param  array{kepala: list<string>, grup: array<string, list<array{id: int, nama: string, aktif: bool}>>}  $bagan  RekrutmenAccess::baganUnit()
     * @param  string|null  $jabatan  jabatan tujuan (cabangnya ditandai; posisi baru ditaruh di sini)
     * @param  list<array{id: int, nama: string, aktif: bool, jabatan: string}>  $diganti  pegawai yang digantikan (data tersimpan)
     * @param  list<array{nama: string, judul: string, kelas?: string}>  $baru  simpul posisi baru di cabang $jabatan
     * @return array{kepala: list<string>, tunggal: bool, baris: list<array{kiri: list<array>, kanan: list<array>}>}
     */
    public static function susun(array $bagan, ?string $jabatan, array $diganti = [], array $baru = []): array
    {
        $setengah = 16 + self::SEJAJAR * (self::NODE + 8) + 14;   // satu sisi tulang punggung ≈ satu cabang terlebar
        $lebar = fn (int $n) => 16 + max(self::NODE, $n > self::SEJAJAR ? min(self::SEJAJAR, $n) * (self::NODE + 8) + 14 : $n * (self::NODE + 8));

        $grup = $bagan['grup'];
        $jabatan = mb_strtoupper(trim((string) $jabatan));
        $digantiId = array_column($diganti, 'id');
        // Yang digantikan tetap tampil walau kini sudah pindah unit/jabatan (data tersimpan saat pengajuan).
        $adaDiBagan = collect($grup)->flatten(1)->pluck('id')->all();
        foreach ($diganti as $o) {
            if (! in_array($o['id'], $adaDiBagan)) {
                $grup[$o['jabatan']][] = ['id' => $o['id'], 'nama' => $o['nama'], 'aktif' => $o['aktif']];
            }
        }
        if ($baru && $jabatan !== '' && ! isset($grup[$jabatan])) {
            $grup[$jabatan] = [];
        }
        ksort($grup);

        $cabang = [];
        foreach ($grup as $jab => $isi) {
            $simpul = [];
            foreach ($isi as $o) {
                $ganti = in_array($o['id'], $digantiId);
                $simpul[] = [
                    'kelas' => ($o['aktif'] ? 'aktif' : 'nonaktif') . ($ganti ? ' diganti' : ''),
                    'ikon' => $o['aktif'] ? 'bi-person-fill' : 'bi-person-dash',
                    'nama' => $o['nama'],
                    'judul' => $o['nama'] . ($o['aktif'] ? ' (aktif)' : ' (tidak aktif)') . ($ganti ? ' — digantikan' : ''),
                    'lencana' => $ganti ? 'bi-arrow-repeat' : null,
                ];
            }
            if ($jab === $jabatan) {
                foreach ($baru as $b) {
                    $simpul[] = ['kelas' => $b['kelas'] ?? 'kosong slot', 'ikon' => 'bi-person-plus', 'nama' => $b['nama'], 'judul' => $b['judul'], 'lencana' => 'bi-plus-lg'];
                }
            }
            // Banyak orang: yang ditandai selalu tampil, sisanya dilipat setelah 11 orang.
            $biasa = 0;
            $lipat = count($simpul) > self::BATAS_LIPAT;
            foreach ($simpul as &$s) {
                $s['lebih'] = $lipat && ! $s['lencana'] && $biasa++ >= self::BATAS_LIPAT - 1;
            }
            unset($s);
            $cabang[] = ['jab' => $jab, 'total' => count($isi), 'terpilih' => $jab === $jabatan, 'simpul' => $simpul,
                'sisa' => count(array_filter($simpul, fn ($s) => $s['lebih'])), 'lebar' => $lebar(count($simpul))];
        }

        // Bagi ke beberapa baris: tiap sisi maksimal $setengah, urutan tetap (sama dengan formulir).
        $baris = [];
        $kini = ['kiri' => [], 'kanan' => [], 'lk' => 0, 'lka' => 0];
        foreach ($cabang as $c) {
            if (! $kini['kanan'] && (! $kini['kiri'] || $setengah >= $kini['lk'] + $c['lebar'])) {
                $kini['kiri'][] = $c;
                $kini['lk'] += $c['lebar'];
            } elseif (! $kini['kanan'] || $setengah >= $kini['lka'] + $c['lebar']) {
                $kini['kanan'][] = $c;
                $kini['lka'] += $c['lebar'];
            } else {
                $baris[] = $kini;
                $kini = ['kiri' => [$c], 'kanan' => [], 'lk' => $c['lebar'], 'lka' => 0];
            }
        }
        if ($cabang) {
            $baris[] = $kini;
        }

        // Seimbangkan kiri/kanan bila kedua sisi tetap muat.
        $jumlahLebar = fn (array $a) => array_sum(array_column($a, 'lebar'));
        $baris = array_map(function ($r) use ($jumlahLebar, $setengah) {
            $b = [...$r['kiri'], ...$r['kanan']];
            $total = $jumlahLebar($b);
            [$kiri, $terbaik, $selisih] = [0, 1, PHP_INT_MAX];
            foreach ($b as $i => $c) {
                $kiri += $c['lebar'];
                if (abs($total - 2 * $kiri) < $selisih) {
                    $selisih = abs($total - 2 * $kiri);
                    $terbaik = $i + 1;
                }
            }
            if ($jumlahLebar(array_slice($b, 0, $terbaik)) > $setengah || $jumlahLebar(array_slice($b, $terbaik)) > $setengah) {
                $terbaik = count($r['kiri']);
            }

            return ['kiri' => array_slice($b, 0, $terbaik), 'kanan' => array_slice($b, $terbaik)];
        }, $baris);

        return ['kepala' => $bagan['kepala'], 'tunggal' => count($cabang) === 1, 'baris' => $baris];
    }
}
