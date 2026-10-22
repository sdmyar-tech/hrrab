@php
    $menu = [
        ['bagian' => null, 'item' => [
            ['Lamaran Saya', 'bi-send', 'pelamar.lamaran.index', 'pelamar.lamaran.*', true, null],
            ['Data Pelamar', 'bi-person-vcard', 'pelamar.data-pelamar.edit', 'pelamar.data-pelamar.*', true, null],
            ['Notifikasi', 'bi-bell', 'notifikasi.index', 'notifikasi.*', true, $hitungPelamar['notifikasi'] ?? null],
        ]],
        ['bagian' => 'Lowongan', 'item' => [
            ['Cari Lowongan', 'bi-briefcase', 'lowongan.index', 'lowongan.*', true, null, false, true],
        ]],
    ];
@endphp
@include('layouts.partials.sidebar', ['menu' => $menu, 'beranda' => 'pelamar.lamaran.index'])
