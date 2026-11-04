@extends('layouts.admin.app')

@section('title', 'Detail Pengajuan Cuti')

@section('content')
@php
    $p = $pengajuanCuti;
    $r = \App\Support\RingkasanPengajuan::cuti($p);
    $nama = $p->dataPegawai?->nama_lengkap ?? '-';
    $tanggal = $p->tanggal_mulai->translatedFormat('d M Y') . ($p->tanggal_mulai->isSameDay($p->tanggal_selesai) ? '' : ' – ' . $p->tanggal_selesai->translatedFormat('d M Y'));
    $konteks = [
        ['Pegawai', $nama . ($p->unit_kerja ? ' · ' . $p->unit_kerja : '')],
        ['Jenis', $p->jenisLabel() . ($p->kategoriBesarLabel() ? ' · ' . $p->kategoriBesarLabel() : '')],
        ['Tanggal', $tanggal . ' (' . $p->lama_cuti . ' hari)'],
        ['Keterangan', $p->keterangan],
        ['Tahap', $p->currentLevelLabel()],
    ];
    // Parameter tindakan dipakai bersama bilah cepat (HP) & kartu Tindakan (desktop).
    $aksi = [
        'item' => $p,
        'setujui' => route('admin.pengajuan-cuti.approve', $p->id),
        'tolak' => route('admin.pengajuan-cuti.reject', $p->id),
        'judul' => 'pengajuan cuti',
        'konteks' => $konteks,
        'alur' => $r['alur'],
        ...($p->perluPengganti() ? ['pengganti' => $p->substituteOptions()] : []),
    ];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="'Pengajuan Cuti · ' . $nama" eyebrow="Data Pengajuan"
            :subtitle="$p->jenisLabel() . ' · ' . $tanggal . ' · ' . $p->lama_cuti . ' hari'"
            :back="route('admin.pengajuan-cuti.index')" back-label="Pengajuan Cuti">
            <x-status-badge :badge="$p->badgeClass()" size="lg" dot>{{ $p->statusLabel() }}</x-status-badge>
            @if ($p->isPending())<x-badge tone="neutral" icon="bi-signpost-split">Tahap: {{ $p->currentLevelLabel() }}</x-badge>@endif
            <x-slot:actions>
                @include('admin.editor-data._tombol', ['entitas' => 'cuti', 'id' => $p->id, 'teks' => 'Ubah semua kolom', 'kembaliKe' => route('admin.pengajuan-cuti.index')])
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
                        @include('admin.pengajuan-cuti._ubah-admin')
                    @endcan
                </div>
            </div>
        </div>
    </div>
</main>

@include('admin.pengajuan._persetujuan')
@endsection
