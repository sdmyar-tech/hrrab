<?php

namespace App\Support;

use App\Models\DataPelamar;
use App\Models\DokumenPegawai;
use App\Models\User;

/**
 * Dokumen pribadi (KTP, KK, CV, ijazah, buku tabungan, pas foto) di disk "public" folder dokumen-pegawai/ & dokumen-pelamar/.
 * Akses langsung /storage/dokumen-* diblokir .htaccess; file dibuka lewat route berkas.lihat yang mengecek login & hak akses.
 */
class Berkas
{
    public const FOLDER = ['dokumen-pegawai/', 'dokumen-pelamar/'];

    public const KOLOM_PEGAWAI = ['upload_ktp', 'upload_kk', 'upload_cv', 'upload_ijazah_terakhir', 'upload_buku_tabungan', 'pas_foto'];

    public const KOLOM_PELAMAR = ['foto_path', 'cv_path', 'ijazah_path', 'sertifikat_path'];

    /** URL file yang dilindungi; file di luar folder dokumen tetap memakai /storage biasa. */
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return self::dilindungi($path) ? route('berkas.lihat', ['path' => $path]) : asset('storage/' . $path);
    }

    public static function dilindungi(string $path): bool
    {
        return str_starts_with(ltrim($path, '/'), self::FOLDER[0]) || str_starts_with(ltrim($path, '/'), self::FOLDER[1]);
    }

    /** Apakah file ini foto (dipakai untuk aturan cache & hak lihat yang lebih longgar). */
    public static function foto(string $path): bool
    {
        return self::pemilik($path)['foto'];
    }

    /**
     * Pemilik: pegawai pemilik dokumen, pelamar pemilik berkas. Selain pemilik:
     * - foto: semua akun internal;
     * - dokumen pegawai: yang berhak melihat data pegawai (lihat-tabel-karyawan);
     * - berkas pelamar: akun internal pengelola rekrutmen (bukan Staff biasa).
     */
    public static function bolehLihat(User $user, string $path): bool
    {
        ['pegawai' => $pegawaiId, 'pelamar' => $pelamarId, 'foto' => $foto] = self::pemilik($path);
        if ($pegawaiId === null && $pelamarId === null) {
            // File yatim (tidak tercatat di data mana pun): hanya pengelola data pegawai.
            return $user->can(User::ABILITY_KELOLA_PEGAWAI);
        }

        $internal = in_array($user->role, User::INTERNAL_ROLES, true);

        return ($pegawaiId !== null && $user->data_pegawai_id !== null && (int) $user->data_pegawai_id === $pegawaiId)
            || ($pelamarId !== null && $user->data_pelamar_id !== null && (int) $user->data_pelamar_id === $pelamarId)
            || ($foto && $internal)
            || ($pegawaiId !== null && $user->can(User::ABILITY_LIHAT_TABEL_KARYAWAN))
            || ($pelamarId !== null && $internal && ! $user->isStaff());
    }

    /** @return array{pegawai: ?int, pelamar: ?int, foto: bool} */
    private static function pemilik(string $path): array
    {
        static $memo = [];

        return $memo[$path] ??= (function () use ($path) {
            $hasil = ['pegawai' => null, 'pelamar' => null, 'foto' => false];
            $dok = DokumenPegawai::where(fn ($q) => collect(self::KOLOM_PEGAWAI)->each(fn ($k) => $q->orWhere($k, $path)))->first();
            if ($dok) {
                $hasil['pegawai'] = (int) $dok->data_pegawai_id;
                $hasil['foto'] = $dok->pas_foto === $path;
            }
            $pelamar = DataPelamar::where(fn ($q) => collect(self::KOLOM_PELAMAR)->each(fn ($k) => $q->orWhere($k, $path)))->first();
            if ($pelamar) {
                $hasil['pelamar'] = (int) $pelamar->id;
                $hasil['foto'] = $hasil['foto'] || $pelamar->foto_path === $path;
            }

            return $hasil;
        })();
    }
}
