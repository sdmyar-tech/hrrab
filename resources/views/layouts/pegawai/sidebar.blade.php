@php
    $u = Auth::user();
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    // Staf unit SDM ikut mengelola pegawai & absensi: menunya disusun sama seperti panel Kepala SDM/Admin.
    $kelolaPegawai = (bool) $u?->can('kelola-pegawai');
    $kelolaAbsensi = (bool) $u?->can('kelola-absensi');
    $menu = [
        ['bagian' => null, 'item' => [
            ['Dashboard', 'bi-speedometer2', 'pegawai.dashboard', 'pegawai.dashboard', true, null],
        ]],
        ['bagian' => 'Pengajuan Saya', 'ikon' => 'bi-send-check', 'item' => [
            ['Ajukan Cuti', 'bi-calendar-plus', 'pegawai.pengajuan-cuti', 'pegawai.pengajuan-cuti*', $u?->can('ajukan-cuti'), null],
            ['Ajukan Lembur', 'bi-stopwatch', 'pegawai.pengajuan-lembur', 'pegawai.pengajuan-lembur*', $u?->can('ajukan-lembur'), null],
        ]],
        ['bagian' => 'Kepegawaian', 'ikon' => 'bi-people', 'item' => [
            ['Data Pegawai', 'bi-people', 'admin.pegawai.index', 'admin.pegawai.*', $kelolaPegawai, null],
        ]],
        ['bagian' => 'Absensi', 'ikon' => $ik['modul'], 'item' => [
            ['Absensi Harian', $ik['harian'], 'admin.absensi.harian', ['admin.absensi.harian', 'admin.absensi.pegawai', 'admin.absensi.izin*'], $kelolaAbsensi, null],
            ['Rekap Absensi', $ik['rekap'], 'admin.absensi.rekap', 'admin.absensi.rekap*', $kelolaAbsensi, null],
            ['Import Data Mesin', $ik['import'], 'admin.absensi.import', 'admin.absensi.import*', $kelolaAbsensi, null],
            ['Pengaturan Absensi', $ik['pengaturan'], 'admin.absensi.pengaturan', 'admin.absensi.pengaturan*', $kelolaAbsensi, null],
        ]],
        ['bagian' => 'Data Saya', 'ikon' => 'bi-person-vcard', 'item' => [
            ['Data Diri Saya', 'bi-person-vcard', 'pegawai.data-pegawai', 'pegawai.data-pegawai', true, null],
            ['Pengkinian Data', 'bi-pencil-square', 'pegawai.pengkinian-data', 'pegawai.pengkinian-data', true, $hitung['data_kurang'] ?? null, true],
            ['Absensi Saya', $ik['saya'], 'absensi.saya', 'absensi.saya', (bool) $u?->dataPegawai?->ikut_absensi, null],
        ]],
        ['bagian' => 'Informasi', 'ikon' => 'bi-info-circle', 'item' => [
            ['Pengumuman', 'bi-megaphone', 'pengumuman.index', 'pengumuman.*', true, $hitung['pengumuman'] ?? null],
            ['Notifikasi', 'bi-bell', 'notifikasi.index', 'notifikasi.*', true, $hitung['notifikasi'] ?? null],
        ]],
    ];
@endphp
@include('layouts.partials.sidebar', ['menu' => $menu, 'beranda' => 'pegawai.dashboard'])
