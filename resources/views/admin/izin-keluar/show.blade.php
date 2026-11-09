@extends('layouts.admin.app')

@section('title', 'Detail Izin')

@section('content')
@php
    $p = $izinKeluar;
    $r = \App\Support\RingkasanPengajuan::izinKeluar($p);
    $nama = $p->dataPegawai?->nama_lengkap ?? '-';
    $konteks = [
        ['Pegawai', $nama . ($p->unit_kerja ? ' · ' . $p->unit_kerja : '')],
        ['Waktu', $p->tanggalLabel() . ' · ' . $p->jamLabel()],
        ['Keperluan', $p->keperluanLabel()],
        ['Alasan', $p->alasan],
        ['Tahap', $p->currentLevelLabel()],
    ];
    // Parameter tindakan dipakai bersama bilah cepat (HP) & kartu Tindakan (desktop).
    $aksi = [
        'item' => $p,
        'setujui' => route('admin.izin-keluar.approve', $p),
        'tolak' => route('admin.izin-keluar.reject', $p),
        'judul' => 'izin',
        'konteks' => $konteks,
        'alur' => $r['alur'],
    ];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="'Izin · ' . $nama" eyebrow="Data Pengajuan"
            :subtitle="$p->tanggalLabel() . ' · ' . $p->jamLabel() . ' · ' . $p->keperluanLabel()"
            :back="route('admin.izin-keluar.index')" back-label="Izin">
            <x-status-badge :badge="$p->badgeClass()" size="lg" dot>{{ $p->statusLabel() }}</x-status-badge>
            <x-badge tone="neutral" :icon="$p->isHarian() ? 'bi-calendar-x' : ($p->isTelat() ? 'bi-alarm' : 'bi-door-open')">{{ $p->jenisLabel() }}</x-badge>
            @if ($p->isPending())<x-badge tone="neutral" icon="bi-signpost-split">Tahap: {{ $p->currentLevelLabel() }}</x-badge>@endif
            <x-slot:actions>
                @if ($p->punyaTiket())
                    <x-button :href="route('pegawai.izin-keluar.tiket', $p)" variant="secondary" icon="bi-ticket-perforated">Lihat Tiket</x-button>
                @endif
                @include('admin.editor-data._tombol', ['entitas' => 'izin_keluar', 'id' => $p->id, 'teks' => 'Ubah semua kolom', 'kembaliKe' => route('admin.izin-keluar.index')])
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
                        @include('admin.izin-keluar._ubah-admin')
                    @endcan
                </div>
            </div>
        </div>
    </div>
</main>

@include('admin.pengajuan._persetujuan')
@endsection
