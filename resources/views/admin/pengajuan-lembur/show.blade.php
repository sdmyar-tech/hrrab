@extends('layouts.admin.app')

@section('title', 'Detail Pengajuan Lembur')

@section('content')
@php
    $p = $pengajuanLembur;
    $r = \App\Support\RingkasanPengajuan::lembur($p);
    $nama = $p->dataPegawai?->nama_lengkap ?? '-';
    $jam = substr((string) $p->jam_mulai, 0, 5) . '–' . substr((string) $p->jam_selesai, 0, 5);
    $konteks = [
        ['Pegawai', $nama . ($p->unit_kerja ? ' · ' . $p->unit_kerja : '')],
        ['Tanggal', optional($p->tanggal)->translatedFormat('l, d M Y')],
        ['Waktu', $jam . ' (' . $p->jumlah_jam . ' jam)'],
        ['Uraian tugas', $p->uraian_tugas],
        ['Tahap', $p->currentLevelLabel()],
    ];
    // Parameter tindakan dipakai bersama bilah cepat (HP) & kartu Tindakan (desktop).
    $aksi = [
        'item' => $p,
        'setujui' => route('admin.pengajuan-lembur.approve', $p->id),
        'tolak' => route('admin.pengajuan-lembur.reject', $p->id),
        'judul' => 'pengajuan lembur',
        'konteks' => $konteks,
        'alur' => $r['alur'],
    ];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="'Pengajuan Lembur · ' . $nama" eyebrow="Data Pengajuan"
            :subtitle="optional($p->tanggal)->translatedFormat('l, d M Y') . ' · ' . $jam . ' · ' . $p->jumlah_jam . ' jam'"
            :back="route('admin.pengajuan-lembur.index')" back-label="Pengajuan Lembur">
            <x-status-badge :badge="$p->badgeClass()" size="lg" dot>{{ $p->statusLabel() }}</x-status-badge>
            @if ($p->isPending())<x-badge tone="neutral" icon="bi-signpost-split">Tahap: {{ $p->currentLevelLabel() }}</x-badge>@endif
            <x-slot:actions>
                @include('admin.editor-data._tombol', ['entitas' => 'lembur', 'id' => $p->id, 'teks' => 'Ubah semua kolom', 'kembaliKe' => route('admin.pengajuan-lembur.index')])
            </x-slot:actions>
        </x-page-header>

        <div class="row g-4">
            <div class="col-xl-8 d-grid gap-4 align-content-start">
                @include('admin.pengajuan._aksi-cepat', $aksi)
                <x-card title="Detail Pengajuan" icon="bi-clipboard-data">
                    @include('admin.pengajuan._ringkasan', ['r' => $r, 'tanpaAlur' => true])
                </x-card>
                @include('admin.pengajuan._riwayat-alur', ['alur' => $r['alur']])
            </div>

            <div class="col-xl-4">
                <div class="hr-detail-samping d-grid gap-4">
                    @include('admin.pengajuan._kartu-aksi', $aksi)
                    @can('sistem.override')
                        @include('admin.pengajuan-lembur._ubah-admin')
                    @endcan
                </div>
            </div>
        </div>
    </div>
</main>

@include('admin.pengajuan._persetujuan')
@endsection
