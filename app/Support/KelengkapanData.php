<?php

namespace App\Support;

use App\Models\DataPegawai;

/** Isian wajib data pegawai (sama dengan syarat "data lengkap" di pengkinian data). */
class KelengkapanData
{
    /**
     * @return list<string>
     */
    public static function kurang(?DataPegawai $p): array
    {
        $kep = $p?->kepegawaian;

        $wajib = [
            'Nama lengkap' => $p?->nama_lengkap, 'NIK' => $p?->nik, 'No. KK' => $p?->no_kk, 'No. HP' => $p?->no_hp,
            'Tempat lahir' => $p?->tempat_lahir, 'Tanggal lahir' => $p?->tanggal_lahir, 'Jenis kelamin' => $p?->jenis_kelamin,
            'Agama' => $p?->agama, 'Alamat KTP' => $p?->alamat_ktp, 'Status pernikahan' => $p?->status_pernikahan,
            'Nama ibu kandung' => $p?->nama_ibu_kandung, 'Pendidikan terakhir' => $p?->pendidikan_terakhir,
            'Jabatan' => $kep?->jabatan, 'Unit kerja' => $kep?->unit_kerja, 'Lokasi kerja' => $kep?->lokasi_kerja, 'Tanggal masuk' => $kep?->tanggal_masuk,
            'No. HP kerabat' => $p?->kontakDarurat?->no_hp_kerabat, 'Nama kerabat' => $p?->kontakDarurat?->nama_kerabat,
            'Hubungan kerabat' => $p?->kontakDarurat?->hubungan_kerabat,
            'File KTP' => $p?->dokumen?->upload_ktp, 'File KK' => $p?->dokumen?->upload_kk, 'Pas foto' => $p?->dokumen?->pas_foto,
        ];

        return array_keys(array_filter($wajib, fn ($v) => blank($v)));
    }

    public static function jumlahWajib(): int
    {
        return 22;
    }

    public static function persen(?DataPegawai $p): int
    {
        return (int) round((self::jumlahWajib() - count(self::kurang($p))) / self::jumlahWajib() * 100);
    }
}
