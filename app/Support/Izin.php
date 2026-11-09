<?php

namespace App\Support;

use App\Models\User;

/**
 * Katalog permission (hak akses fitur). Satu-satunya sumber daftar permission: seeder mengisi tabel `permissions`,
 * gate didaftarkan dari sini, dan UI Master Role / Kelola Akun menampilkan centangnya dari sini.
 *
 * Aturan: setiap fitur dibuka HANYA bila akun memegang permission-nya (dari role atau centang tambahan per akun).
 * Tidak ada akses bawaan dari unit/divisi. Role & jabatan tetap dipakai untuk ALUR persetujuan (siapa penyetuju tahap
 * berikutnya), bukan untuk membuka fitur.
 */
class Izin
{
    // Pengajuan cuti, lembur & izin keluar
    public const PENGAJUAN_CREATE = 'pengajuan.create';

    public const PENGAJUAN_APPROVE = 'pengajuan.approve';

    public const PENGAJUAN_APPROVE_GM = 'pengajuan.approve_gm';

    public const PENGAJUAN_REPORT = 'pengajuan.report';

    // Kepegawaian
    public const PEGAWAI_VIEW = 'pegawai.view';

    public const PEGAWAI_CREATE = 'pegawai.create';

    public const PEGAWAI_UPDATE = 'pegawai.update';

    public const PEGAWAI_DELETE = 'pegawai.delete';

    public const PEGAWAI_ALUR = 'pegawai.alur.manage';

    public const PENUGASAN_MANAGE = 'penugasan.manage';

    // Evaluasi kontrak & surat peringatan
    public const EVALUASI_VIEW_ALL = 'evaluasi_kontrak.view_all';

    public const SP_VIEW_ALL = 'sp.view_all';

    public const SP_MANAGE_ALL = 'sp.manage_all';

    // Absensi
    public const ABSENSI_VIEW = 'absensi.view';

    public const ABSENSI_MANAGE = 'absensi.manage';

    // Rekrutmen
    public const REKRUTMEN_VIEW = 'rekrutmen.view';

    public const FORMASI_CREATE = 'rekrutmen.formasi.create';

    public const FORMASI_VIEW_ALL = 'rekrutmen.formasi.view_all';

    public const LAMARAN_PROCESS = 'rekrutmen.lamaran.process';

    public const LOWONGAN_CREATE = 'rekrutmen.lowongan.create';

    public const LOWONGAN_MANAGE = 'rekrutmen.lowongan.manage';

    public const INTERVIEW = 'rekrutmen.interview';

    // Informasi
    public const PENGUMUMAN_MANAGE = 'pengumuman.manage';

    public const PENGUMUMAN_MANAGE_ALL = 'pengumuman.manage_all';

    public const ACARA_ABSEN = 'acara.absen.manage';

    public const NOTIFIKASI_SEND = 'notifikasi.send';

    public const NOTIFIKASI_SEND_ROLE = 'notifikasi.send_role';

    public const KALENDER_MANAGE = 'kalender.manage';

    // Sistem
    public const AKUN_MANAGE = 'sistem.akun.manage';

    public const ROLE_MANAGE = 'sistem.role.manage';

    public const MASTER_MANAGE = 'sistem.master.manage';

    public const LOG_VIEW = 'sistem.log.view';

    public const EDITOR_DATA = 'sistem.editor_data';

    public const OVERRIDE = 'sistem.override';

    /**
     * Grup tampilan => [ikon, [kode => [nama, keterangan]]]. Urutan di sini = urutan di UI.
     *
     * @var array<string, array{0: string, 1: array<string, array{0: string, 1: string}>}>
     */
    public const KATALOG = [
        'Pengajuan' => ['bi-send', [
            self::PENGAJUAN_CREATE => ['Mengajukan cuti, lembur & izin', 'Membuka menu Pengajuan Saya (tetap mengikuti aturan unit: unit sekolah tidak mengajukan).'],
            self::PENGAJUAN_APPROVE => ['Menyetujui pengajuan', 'Membuka daftar persetujuan. Tahap yang bisa disetujui tetap mengikuti Tangga Alur / alur jabatan.'],
            self::PENGAJUAN_APPROVE_GM => ['Menyetujui tahap GM', 'Boleh menyetujui di tahap GM (dan tahap kepala bila bagian tanpa kepala). Hanya berlaku untuk akun ber-role GM.'],
            self::PENGAJUAN_REPORT => ['Lihat data pengajuan', 'Melihat daftar & rekap cuti, lembur, izin sesuai cakupan unit.'],
        ]],
        'Kepegawaian' => ['bi-people', [
            self::PEGAWAI_VIEW => ['Lihat data pegawai', 'Membuka daftar & detail pegawai.'],
            self::PEGAWAI_CREATE => ['Tambah pegawai', 'Menambah data pegawai baru.'],
            self::PEGAWAI_UPDATE => ['Ubah pegawai', 'Mengubah data pegawai & mencatat pegawai keluar.'],
            self::PEGAWAI_DELETE => ['Hapus pegawai', 'Menghapus data pegawai.'],
            self::PEGAWAI_ALUR => ['Atur alur & akun pegawai', 'Tangga Alur, atasan/alur khusus pegawai, dan paksa ganti kata sandi.'],
            self::PENUGASAN_MANAGE => ['Mutasi, rangkap & bagian', 'Mengelola mutasi, rangkap tugas, koordinator bagian & master unit.'],
        ]],
        'Kinerja' => ['bi-clipboard-check', [
            self::EVALUASI_VIEW_ALL => ['Lihat semua evaluasi kontrak', 'Melihat evaluasi kontrak seluruh pegawai (penilai tetap melihat yang dinilainya).'],
            self::SP_VIEW_ALL => ['Lihat semua surat peringatan', 'Melihat SP seluruh pegawai.'],
            self::SP_MANAGE_ALL => ['Kelola semua surat peringatan', 'Memberi SP ke pegawai mana pun & menghapus SP pemberi lain.'],
        ]],
        'Absensi' => ['bi-fingerprint', [
            self::ABSENSI_VIEW => ['Lihat absensi', 'Absensi harian & rekap (Kepala Bagian: unitnya; pemegang Kelola absensi: semua unit).'],
            self::ABSENSI_MANAGE => ['Kelola absensi', 'Import data mesin, koreksi, izin & pengaturan absensi semua unit.'],
        ]],
        'Rekrutmen' => ['bi-briefcase', [
            self::REKRUTMEN_VIEW => ['Buka menu rekrutmen', 'Formasi, lamaran & lowongan (data dibatasi unit yang dipimpin).'],
            self::FORMASI_CREATE => ['Ajukan formasi', 'Mengajukan formasi untuk unit yang dipimpin.'],
            self::FORMASI_VIEW_ALL => ['Lihat semua formasi & lamaran', 'Tanpa batas unit.'],
            self::LAMARAN_PROCESS => ['Proses lamaran', 'Verifikasi, jadwal interview, onboarding, kontrak, rekrut/tolak, tambah kandidat internal.'],
            self::LOWONGAN_CREATE => ['Buat lowongan', 'Membuat draf lowongan.'],
            self::LOWONGAN_MANAGE => ['Kelola semua lowongan', 'Mengubah, mempublikasikan, menutup & membuka kembali lowongan semua unit.'],
            self::INTERVIEW => ['Menjadi interviewer', 'Menu Jadwal Interview Saya; bisa ditunjuk sebagai pewawancara.'],
        ]],
        'Informasi' => ['bi-megaphone', [
            self::PENGUMUMAN_MANAGE => ['Kelola pengumuman', 'Membuat & mengubah pengumuman sendiri.'],
            self::PENGUMUMAN_MANAGE_ALL => ['Kelola semua pengumuman', 'Mengubah & menghapus pengumuman pembuat lain.'],
            self::ACARA_ABSEN => ['Kelola absen acara', 'Menetapkan & memantau absen acara (QR).'],
            self::NOTIFIKASI_SEND => ['Kirim notifikasi', 'Mengirim notifikasi ke pegawai.'],
            self::NOTIFIKASI_SEND_ROLE => ['Kirim notifikasi per role', 'Sasaran notifikasi berdasarkan role akun.'],
            self::KALENDER_MANAGE => ['Kelola kalender', 'Mengubah tanggal merah & sinkron hari libur.'],
        ]],
        'Sistem' => ['bi-gear', [
            self::AKUN_MANAGE => ['Kelola akun', 'Membuat, mengubah & menghapus akun login serta centang hak aksesnya.'],
            self::ROLE_MANAGE => ['Kelola Master Role', 'Mengatur role dan permission tiap role.'],
            self::MASTER_MANAGE => ['Kelola master data', 'Master jabatan, lokasi kerja, dan tambah/ubah/hapus unit.'],
            self::LOG_VIEW => ['Lihat log aktivitas', 'Membuka & mengekspor log aktivitas.'],
            self::EDITOR_DATA => ['Editor Data', 'Mengubah/menghapus baris data apa pun secara langsung.'],
            self::OVERRIDE => ['Mode Admin (lewati aturan)', 'Melewati batas tanggal, kuota, urutan tahap & kunci status di semua modul.'],
        ]],
    ];

    /**
     * Permission bawaan tiap role sistem (sama persis dengan hak yang berlaku sebelum perombakan RBAC).
     * Admin memegang SEMUA permission (lihat semua()).
     *
     * @var array<string, list<string>>
     */
    public const BAWAAN_ROLE = [
        User::ROLE_PEMBINA => [
            self::PENGAJUAN_REPORT, self::PEGAWAI_VIEW, self::EVALUASI_VIEW_ALL, self::SP_VIEW_ALL,
            self::REKRUTMEN_VIEW, self::FORMASI_VIEW_ALL,
        ],
        User::ROLE_GM => [
            self::PENGAJUAN_APPROVE, self::PENGAJUAN_APPROVE_GM, self::PENGAJUAN_REPORT, self::PEGAWAI_VIEW,
            self::EVALUASI_VIEW_ALL, self::SP_VIEW_ALL,
            self::REKRUTMEN_VIEW, self::FORMASI_VIEW_ALL, self::LAMARAN_PROCESS, self::LOWONGAN_CREATE, self::LOWONGAN_MANAGE, self::INTERVIEW,
            self::PENGUMUMAN_MANAGE, self::ACARA_ABSEN, self::NOTIFIKASI_SEND,
        ],
        User::ROLE_KEPALA_BAGIAN => [
            self::PENGAJUAN_CREATE, self::PENGAJUAN_APPROVE, self::PENGAJUAN_REPORT, self::PEGAWAI_VIEW, self::ABSENSI_VIEW,
            self::REKRUTMEN_VIEW, self::FORMASI_CREATE, self::LOWONGAN_CREATE, self::INTERVIEW,
            self::PENGUMUMAN_MANAGE, self::NOTIFIKASI_SEND,
        ],
        User::ROLE_KEPALA_SDM => [
            self::PENGAJUAN_CREATE, self::PENGAJUAN_APPROVE, self::PENGAJUAN_REPORT,
            self::PEGAWAI_VIEW, self::PEGAWAI_CREATE, self::PEGAWAI_UPDATE, self::PEGAWAI_DELETE, self::PEGAWAI_ALUR, self::PENUGASAN_MANAGE,
            self::EVALUASI_VIEW_ALL, self::SP_VIEW_ALL, self::SP_MANAGE_ALL,
            self::ABSENSI_VIEW, self::ABSENSI_MANAGE,
            self::REKRUTMEN_VIEW, self::FORMASI_CREATE, self::FORMASI_VIEW_ALL, self::LAMARAN_PROCESS, self::LOWONGAN_CREATE, self::LOWONGAN_MANAGE, self::INTERVIEW,
            self::PENGUMUMAN_MANAGE, self::PENGUMUMAN_MANAGE_ALL, self::ACARA_ABSEN, self::NOTIFIKASI_SEND, self::NOTIFIKASI_SEND_ROLE, self::KALENDER_MANAGE,
        ],
        User::ROLE_STAFF => [self::PENGAJUAN_CREATE],
        User::ROLE_ADMIN => [], // diisi semua() oleh seeder & dikunci
    ];

    /** Role yang memegang seluruh permission dan centangnya tidak bisa dilepas (mencegah Admin terkunci di luar sistem). */
    public const ROLE_PENUH = User::ROLE_ADMIN;

    /**
     * Peta hak lama (roles.hak_akses JSON & jabatan_unit.hak_akses) → permission baru. Dipakai migrasi data saja.
     *
     * @var array<string, list<string>>
     */
    public const PETA_LAMA = [
        'mengajukan' => [self::PENGAJUAN_CREATE],
        'approve' => [self::PENGAJUAN_APPROVE],
        'lihat-laporan' => [self::PENGAJUAN_REPORT],
        'lihat-tabel-karyawan' => [self::PEGAWAI_VIEW],
        'kelola-pegawai' => [self::PEGAWAI_VIEW, self::PEGAWAI_CREATE, self::PEGAWAI_UPDATE, self::PEGAWAI_DELETE],
        'kelola-penugasan' => [self::PENUGASAN_MANAGE],
        'lihat-absensi' => [self::ABSENSI_VIEW],
        'kelola-absensi' => [self::ABSENSI_VIEW, self::ABSENSI_MANAGE],
        'kelola-pengumuman' => [self::PENGUMUMAN_MANAGE],
        'kirim-notifikasi' => [self::NOTIFIKASI_SEND],
        'kelola-sistem' => [self::AKUN_MANAGE, self::ROLE_MANAGE, self::MASTER_MANAGE, self::LOG_VIEW],
        'lihat-dashboard' => [],
    ];

    /** @return list<string> semua kode permission sesuai urutan katalog */
    public static function semua(): array
    {
        return array_merge(...array_map(fn ($g) => array_keys($g[1]), array_values(self::KATALOG)));
    }

    /** @return array{0: string, 1: string}|null [nama, keterangan] */
    public static function info(string $kode): ?array
    {
        foreach (self::KATALOG as [, $daftar]) {
            if (isset($daftar[$kode])) {
                return $daftar[$kode];
            }
        }

        return null;
    }

    public static function nama(string $kode): string
    {
        return self::info($kode)[0] ?? $kode;
    }

    /** @return list<string> permission bawaan role sistem $role */
    public static function bawaanRole(string $role): array
    {
        return $role === self::ROLE_PENUH ? self::semua() : (self::BAWAAN_ROLE[$role] ?? []);
    }

    /**
     * Terjemahkan daftar hak lama ke permission baru (role kustom: ditambah hak dari jenis dasarnya yang dulu
     * diberikan lewat pengecekan role di kode, mis. Kepala SDM → kalender).
     *
     * @param  list<string>  $hakLama
     * @return list<string>
     */
    public static function dariHakLama(array $hakLama): array
    {
        return array_values(array_unique(array_merge(...array_map(fn ($h) => self::PETA_LAMA[$h] ?? [], $hakLama ?: ['lihat-dashboard']))));
    }
}
