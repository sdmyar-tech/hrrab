@php
    $u = Auth::user();
    $menu = [
        ['bagian' => null, 'item' => [
            ['Dashboard', 'bi-speedometer2', 'pegawai.dashboard', 'pegawai.dashboard', true, null],
        ]],
        ['bagian' => 'Pengajuan Saya', 'ikon' => 'bi-send-check', 'item' => [
            ['Ajukan Cuti', 'bi-calendar-plus', 'pegawai.pengajuan-cuti', 'pegawai.pengajuan-cuti*', $u?->can('ajukan-cuti'), null],
            ['Ajukan Lembur', 'bi-stopwatch', 'pegawai.pengajuan-lembur', 'pegawai.pengajuan-lembur*', $u?->can('ajukan-lembur'), null],
        ]],
        ['bagian' => 'Data Saya', 'ikon' => 'bi-person-vcard', 'item' => [
            ['Data Pegawai', 'bi-person-vcard', 'pegawai.data-pegawai', 'pegawai.data-pegawai', true, null],
            ['Pengkinian Data', 'bi-pencil-square', 'pegawai.pengkinian-data', 'pegawai.pengkinian-data', true, $hitung['data_kurang'] ?? null, true],
        ]],
        ['bagian' => 'Tugas SDM', 'item' => [
            ['Kelola Data Pegawai', 'bi-people', 'admin.pegawai.index', 'admin.pegawai.*', $u?->can('kelola-pegawai'), null],
        ]],
        ['bagian' => 'Informasi', 'ikon' => 'bi-info-circle', 'item' => [
            ['Pengumuman', 'bi-megaphone', 'pengumuman.index', 'pengumuman.*', true, $hitung['pengumuman'] ?? null],
            ['Notifikasi', 'bi-bell', 'notifikasi.index', 'notifikasi.*', true, $hitung['notifikasi'] ?? null],
        ]],
    ];
@endphp
@include('layouts.partials.sidebar', ['menu' => $menu, 'beranda' => 'pegawai.dashboard'])
