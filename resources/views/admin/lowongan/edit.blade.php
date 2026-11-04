@extends('layouts.admin.app')

@section('title', 'Ubah Lowongan')

{{-- Galat isian ditampilkan di halaman ini (ringkasan + di bawah isian); banner global tidak perlu. --}}
@section('galat-di-halaman', '1')

@section('content')
@php
    $statusInfo = [
        \App\Models\Lowongan::STATUS_DRAFT => ['Draf', 'neutral', 'bi-pencil-square'],
        \App\Models\Lowongan::STATUS_DIPUBLIKASIKAN => ['Dipublikasikan', 'success', 'bi-megaphone'],
        \App\Models\Lowongan::STATUS_DITUTUP => ['Ditutup', 'danger', 'bi-lock'],
    ];
    [$labelStatus, $toneStatus, $ikonStatus] = $statusInfo[$lowongan->status] ?? $statusInfo[\App\Models\Lowongan::STATUS_DRAFT];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Ubah Lowongan" eyebrow="Rekrutmen" :back="route('admin.lowongan.index')" back-label="Lowongan"
            :subtitle="$lowongan->judul . ' · ' . $lowongan->unit_kerja">
            <x-badge :tone="$toneStatus" :icon="$ikonStatus" size="lg">{{ $labelStatus }}</x-badge>
        </x-page-header>

        @if ($errors->any())
            <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-4">
                Perubahan belum tersimpan. Perbaiki isian berikut, lalu simpan lagi:
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form action="{{ route('admin.lowongan.update', $lowongan) }}" method="POST" id="formLowongan" data-confirm="Simpan perubahan lowongan ini?">
            @csrf
            @method('PUT')
            @include('admin.lowongan._form')
        </form>

        {{-- Form terpisah (tidak boleh bersarang di form utama); tombolnya di bagian Status & Publikasi memakai atribut form. --}}
        <form id="close-form" action="{{ route('admin.lowongan.close', $lowongan) }}" method="POST" class="d-none"
            data-confirm="Tutup lowongan {{ $lowongan->judul }} ({{ $lowongan->unit_kerja }})? Lowongan tidak lagi menerima lamaran baru; pelamar yang sudah masuk tetap bisa diproses.">
            @csrf
        </form>
        @if ($lowongan->bisaDibukaKembali() || ($lowongan->status === \App\Models\Lowongan::STATUS_DITUTUP && auth()->user()->can('sistem.override')))
            <form id="reopen-form" action="{{ route('admin.lowongan.reopen', $lowongan) }}" method="POST" class="d-none form-reopen-lowongan">
                @csrf
                <input type="hidden" name="tanggal_tutup" value="">
            </form>
        @endif
    </div>
</main>
@endsection
