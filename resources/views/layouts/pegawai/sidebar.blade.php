@php
    $u = Auth::user();
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    // Menu mengikuti hak akses (Master Role): staf unit SDM ikut mengelola pegawai & absensi, role kustom berbasis Staff
    // mendapat menu sesuai hak yang dicentang. Staf biasa tetap hanya melihat menu pribadinya.
    $bisa = fn (string $hak) => (bool) $u?->can($hak);
    $kelolaAbsensi = $bisa('kelola-absensi');
    // Bagian selalu terbuka. Notifikasi & pengumuman dibaca lewat ikon lonceng di navbar.
    $menu = [
        ['bagian' => null, 'item' => [
            ['Dashboard', 'bi-speedometer2', 'pegawai.dashboard', 'pegawai.dashboard', true, null],
            ['Kalender', 'bi-calendar3', 'kalender.index', 'kalender.*', true, null],
        ]],
        ['bagian' => 'Pengajuan Saya', 'item' => [
            ['Cuti', 'bi-calendar-plus', 'pegawai.pengajuan-cuti', 'pegawai.pengajuan-cuti*', $u?->can('ajukan-cuti'), null],
            ['Lembur', 'bi-stopwatch', 'pegawai.pengajuan-lembur', 'pegawai.pengajuan-lembur*', $u?->can('ajukan-lembur'), null],
            ['Izin Keluar', 'bi-door-open', 'pegawai.izin-keluar', 'pegawai.izin-keluar*', $u?->can('ajukan-izin-keluar'), null],
        ]],
        ['bagian' => 'Data Saya', 'item' => [
            ['Data Diri Saya', 'bi-person-vcard', 'pegawai.data-pegawai', 'pegawai.data-pegawai', true, null],
            ['Pengkinian Data', 'bi-pencil-square', 'pegawai.pengkinian-data', 'pegawai.pengkinian-data', true, $hitung['data_kurang'] ?? null, true],
            ['Absensi Saya', $ik['saya'], 'absensi.saya', 'absensi.saya', (bool) $u?->dataPegawai?->ikut_absensi, null],
        ]],
        ['bagian' => 'Data Pengajuan', 'item' => [
            ['Cuti', 'bi-calendar2-check', 'admin.pengajuan-cuti.index', 'admin.pengajuan-cuti.*', $bisa('lihat-laporan'), null],
            ['Lembur', 'bi-clock-history', 'admin.pengajuan-lembur.index', 'admin.pengajuan-lembur.*', $bisa('lihat-laporan'), null],
            ['Izin Keluar', 'bi-door-open', 'admin.izin-keluar.index', 'admin.izin-keluar.*', $bisa('lihat-laporan'), null],
        ]],
        ['bagian' => 'Kepegawaian', 'item' => [
            ['Data Pegawai', 'bi-people', 'admin.pegawai.index', 'admin.pegawai.*', $bisa('lihat-tabel-karyawan'), null],
            ['Mutasi & Rangkap', 'bi-arrow-left-right', 'admin.penugasan.index', 'admin.penugasan.*', $bisa('kelola-penugasan'), null],
            ['Unit & Koordinator', 'bi-buildings', 'admin.unit.index', 'admin.unit.*', $bisa('kelola-penugasan'), null],
        ]],
        ['bagian' => 'Absensi', 'item' => [
            ['Absensi Harian', $ik['harian'], 'admin.absensi.harian', ['admin.absensi.harian', 'admin.absensi.pegawai', 'admin.absensi.izin*'], $bisa('lihat-absensi'), null],
            ['Rekap Absensi', $ik['rekap'], 'admin.absensi.rekap', 'admin.absensi.rekap*', $bisa('lihat-absensi'), null],
            ['Import Data Mesin', $ik['import'], 'admin.absensi.import', 'admin.absensi.import*', $kelolaAbsensi, null],
            ['Pengaturan Absensi', $ik['pengaturan'], 'admin.absensi.pengaturan', 'admin.absensi.pengaturan*', $kelolaAbsensi, null],
        ]],
        ['bagian' => 'Informasi', 'item' => [
            ['Kelola Pengumuman', 'bi-megaphone', 'pengumuman.index', 'pengumuman.*', $bisa('kelola-pengumuman'), null],
            ['Kirim Notifikasi', 'bi-send', 'notifikasi.kirim', 'notifikasi.kirim*', $bisa('kirim-notifikasi'), null],
        ]],
        ['bagian' => 'Sistem', 'item' => [
            ['Kelola Akun', 'bi-person-lock', 'admin.users.index', 'admin.users.*', $bisa('kelola-sistem'), null],
            ['Master Role', 'bi-shield-check', 'admin.role.index', 'admin.role.*', $bisa('kelola-sistem'), null],
            ['Master Jabatan', 'bi-diagram-3', 'admin.jabatan.index', 'admin.jabatan.*', $bisa('kelola-sistem'), null],
            ['Master Lokasi Kerja', 'bi-geo-alt', 'admin.lokasi-kerja.index', 'admin.lokasi-kerja.*', $bisa('kelola-sistem'), null],
            ['Log Aktivitas', 'bi-journal-text', 'admin.log.index', 'admin.log.*', $bisa('kelola-sistem'), null],
        ]],
    ];
@endphp
@include('layouts.partials.sidebar', ['menu' => $menu, 'beranda' => 'pegawai.dashboard'])
