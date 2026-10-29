<?php

namespace App\Support;

use App\Models\DataPegawai;

/** Ekspor lengkap satu pegawai (biodata, penugasan, cuti, lembur) — dipakai admin & pegawai untuk datanya sendiri. */
class EksporPegawai
{
    /** @return list<array{name: string, headers: list<string>, rows: iterable}> */
    public static function lembar(DataPegawai $p): array
    {
        $p->loadMissing(['kepegawaian', 'kontakDarurat', 'bpjs', 'penugasans.dibuatOleh', 'pengajuanCutis', 'pengajuanLemburs']);
        $k = $p->kepegawaian;
        $kontak = $p->kontakDarurat;
        $biodata = [
            'NIP' => $p->nip, 'Nama Lengkap' => $p->nama_lengkap, 'Email' => $p->email, 'NIK' => $p->nik, 'NPWP' => $p->npwp, 'No. KK' => $p->no_kk,
            'No. HP' => $p->no_hp, 'Tempat Lahir' => $p->tempat_lahir, 'Tanggal Lahir' => optional($p->tanggal_lahir)->format('d-m-Y'),
            'Jenis Kelamin' => $p->jenis_kelamin, 'Agama' => $p->agama, 'Golongan Darah' => $p->golongan_darah, 'Status Pernikahan' => $p->status_pernikahan,
            'Jumlah Tanggungan' => $p->jumlah_tanggungan, 'Nama Ibu Kandung' => $p->nama_ibu_kandung, 'Pendidikan Terakhir' => $p->pendidikan_terakhir,
            'Alamat KTP' => $p->alamat_ktp, 'Alamat Domisili' => $p->alamat_domisili,
            'Jabatan' => $k?->jabatan, 'Jabatan Tambahan' => $k?->jabatan_tambahan, 'Bagian' => $k?->unit_kerja, 'Lokasi Kerja' => $k?->lokasi_kerja,
            'Tanggal Masuk' => optional($k?->tanggal_masuk)->format('d-m-Y'), 'Status Rekrutmen' => $k?->status_rekrutmen, 'Status Kerja' => $k?->status_kerja,
            'Habis Kontrak' => optional($k?->tanggal_habis_kontrak)->format('d-m-Y'),
            'Kontak Darurat' => $kontak ? "{$kontak->nama_kerabat} ({$kontak->hubungan_kerabat})" : null, 'No. HP Kerabat' => $kontak?->no_hp_kerabat,
            'BPJS Ketenagakerjaan' => $p->bpjs?->no_bpjs_ketenagakerjaan, 'BPJS Kesehatan' => $p->bpjs?->no_bpjs_kesehatan, 'Faskes Tingkat 1' => $p->bpjs?->faskes_tingkat_1,
        ];

        return [
            ['name' => 'Biodata', 'headers' => ['Data', 'Isi'], 'rows' => collect($biodata)->map(fn ($v, $label) => [$label, $v])->values()],
            ['name' => 'Riwayat Penugasan', 'headers' => ['Jenis', 'Bagian', 'Jabatan', 'Peran', 'Mulai', 'Selesai', 'Status', 'No. SK', 'Keterangan', 'Dicatat Oleh'],
                'rows' => $p->penugasans->sortBy(fn ($x) => $x->tanggal_mulai->format('Y-m-d') . str_pad((string) $x->id, 9, '0', STR_PAD_LEFT))->map(fn ($x) => [
                    $x->jenisLabel(), $x->unit_kerja, $x->jabatan, $x->peranLabel(), $x->tanggal_mulai->format('d-m-Y'),
                    optional($x->tanggal_selesai)->format('d-m-Y'), $x->statusLabel(), $x->no_sk, $x->keterangan, $x->dibuatOleh?->name ?? 'sistem',
                ])->values()],
            ['name' => 'Riwayat Cuti', 'headers' => ['Diajukan', 'Jenis', 'Kategori', 'Mulai', 'Selesai', 'Lama (hari)', 'Status', 'Catatan'],
                'rows' => $p->pengajuanCutis->sortBy('id')->map(fn ($c) => [
                    optional($c->created_at)->format('d-m-Y'), $c->jenisLabel(), $c->kategoriBesarLabel(), $c->tanggal_mulai->format('d-m-Y'),
                    $c->tanggal_selesai->format('d-m-Y'), $c->lama_cuti, $c->statusLabel(), $c->catatan_admin,
                ])->values()],
            ['name' => 'Riwayat Lembur', 'headers' => ['Diajukan', 'Bagian', 'Tanggal', 'Jam', 'Jumlah Jam', 'Uraian', 'Status'],
                'rows' => $p->pengajuanLemburs->sortBy('id')->map(fn ($l) => [
                    optional($l->created_at)->format('d-m-Y'), $l->unit_kerja, $l->tanggal->format('d-m-Y'),
                    substr((string) $l->jam_mulai, 0, 5) . ' - ' . substr((string) $l->jam_selesai, 0, 5), $l->jumlah_jam, $l->uraian_tugas, $l->statusLabel(),
                ])->values()],
        ];
    }
}
