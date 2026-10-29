<?php

namespace App\Support;

use App\Models\User;

/** Isi dashboard admin (hasil DashboardData::untuk) sebagai lembar-lembar Excel, satu lembar per bagian. */
class EksporDashboard
{
    private const STATUS_KERJA = ['Tetap', 'Kontrak', 'Harian Lepas', 'Probation', 'Magang', 'Belum diisi'];

    /** @return list<array{name: string, headers: list<string>, rows: iterable}> */
    public static function lembar(array $d): array
    {
        $status = fn (array $hitung) => array_map(fn ($s) => collect($hitung)->firstWhere('label', $s)['jumlah'] ?? 0, self::STATUS_KERJA);

        $lembar = [
            ['name' => 'Ringkasan', 'headers' => ['Bagian', 'Keterangan', 'Nilai', 'Detail'], 'rows' => [
                ['Lingkup', 'Bagian', $d['labelLingkup'], null],
                ['Lingkup', 'Diekspor', now()->format('d-m-Y H:i'), null],
                ...array_map(fn ($k) => ['Kartu', $k['label'], $k['nilai'], $k['meta'] ?? null], $d['kartu']),
                ...array_map(fn ($t) => ['Perlu tindakan', $t['label'], $t['jumlah'], null], $d['tugas']),
                ...array_map(fn ($p) => ['Pipeline rekrutmen', $p['label'], $p['jumlah'], null], $d['pipeline'] ?? []),
                ...collect($d['akunPerRole'] ?? [])->map(fn ($n, $role) => ['Akun per role', User::roleLabelFor($role), $n, null])->values()->all(),
            ]],
            ['name' => 'Per Bagian', 'headers' => ['Bagian', 'Kepala', 'Pegawai', 'Laki-laki', 'Perempuan', ...self::STATUS_KERJA,
                'Kontrak Habis ≤60 Hari', 'Cuti Hari Ini', 'Cuti Bulan Ini (hari)', 'Lembur Bulan Ini (jam)', 'Pengajuan Menunggu'],
                'rows' => collect($d['ringkasanUnit'])->map(fn ($u) => [
                    $u['unit'], $u['kepala'], $u['jumlah'], $u['laki'], $u['perempuan'], ...$status($u['status']),
                    $u['kontrak_habis'], $u['cuti_hari_ini'], $u['cuti_bulan_ini'], $u['lembur_bulan_ini'], $u['pending'],
                ])],
            ['name' => 'Per Area Kerja', 'headers' => ['Area', 'Pegawai', 'Laki-laki', 'Perempuan', ...self::STATUS_KERJA, 'Kontrak Habis ≤60 Hari', 'Bagian'],
                'rows' => collect($d['ringkasanArea'])->map(fn ($a) => [
                    $a['area'], $a['jumlah'], $a['laki'], $a['perempuan'], ...$status($a['status']), $a['kontrak_habis'],
                    collect($a['unit'])->map(fn ($n, $u) => "{$u} ({$n})")->implode(', '),
                ])],
            ['name' => 'Komposisi', 'headers' => ['Kategori', 'Kelompok', 'Jumlah'],
                'rows' => collect(['status' => 'Status kerja', 'gender' => 'Jenis kelamin', 'masa' => 'Masa kerja', 'usia' => 'Usia', 'pendidikan' => 'Pendidikan'])
                    ->flatMap(fn ($label, $kunci) => array_map(fn ($i) => [$label, $i['label'], $i['jumlah']], $d['komposisi'][$kunci] ?? []))->values()],
            ['name' => 'Keluar Masuk', 'headers' => ['Bulan', 'Masuk', 'Keluar'],
                'rows' => array_map(fn ($b) => [$b['judul'], $b['masuk'], $b['keluar']], $d['keluarMasuk']['bulan'])],
        ];

        if ($d['tren']) {
            $lembar[] = ['name' => 'Tren Cuti & Lembur', 'headers' => ['Bulan', 'Cuti Disetujui (hari)', 'Lembur Disetujui (jam)'],
                'rows' => array_map(fn ($c, $l) => [$c['judul'], $c['jumlah'], $l['jumlah']], $d['tren']['cuti'], $d['tren']['lembur'])];
        }

        $lembar[] = ['name' => 'Kontrak Habis ≤60 Hari', 'headers' => ['Nama', 'NIP', 'Bagian', 'Jabatan', 'Habis Kontrak', 'Sisa Hari'],
            'rows' => $d['kontrakHabis']->map(fn ($p) => [
                $p->nama_lengkap, $p->nip, $p->kepegawaian?->unit_kerja, $p->kepegawaian?->jabatan,
                $p->kepegawaian?->tanggal_habis_kontrak?->format('d-m-Y'), (int) now()->startOfDay()->diffInDays($p->kepegawaian?->tanggal_habis_kontrak, false),
            ])];
        $lembar[] = ['name' => 'Cuti Hari Ini', 'headers' => ['Nama', 'Bagian', 'Jenis', 'Mulai', 'Selesai', 'Lama (hari)', 'Pengganti'],
            'rows' => $d['cutiHariIni']->map(fn ($c) => [
                $c->dataPegawai?->nama_lengkap, $c->unit_kerja, $c->jenisLabel(), $c->tanggal_mulai->format('d-m-Y'), $c->tanggal_selesai->format('d-m-Y'), $c->lama_cuti, $c->pengganti_nama,
            ])];
        $lembar[] = ['name' => 'Pegawai Baru', 'headers' => ['Nama', 'NIP', 'Bagian', 'Jabatan', 'Tanggal Masuk'],
            'rows' => $d['pegawaiBaru']->map(fn ($p) => [
                $p->nama_lengkap, $p->nip, $p->kepegawaian?->unit_kerja, $p->kepegawaian?->jabatan, $p->kepegawaian?->tanggal_masuk?->format('d-m-Y'),
            ])];

        return $lembar;
    }
}
