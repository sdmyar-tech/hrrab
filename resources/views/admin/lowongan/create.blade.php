@extends('layouts.admin.app')

@section('title', 'Buat Lowongan')

{{-- Galat isian ditampilkan di halaman ini (ringkasan + di bawah isian); banner global tidak perlu. --}}
@section('galat-di-halaman', '1')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Buat Lowongan" eyebrow="Rekrutmen" :back="route('admin.lowongan.index')" back-label="Lowongan"
            subtitle="Lowongan baru tersimpan sebagai draf dan belum tampil ke pelamar sampai dipublikasikan." />

        @if ($errors->any())
            <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-4">
                Lowongan belum tersimpan. Perbaiki isian berikut, lalu simpan lagi:
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form data-fokus-awal action="{{ route('admin.lowongan.store') }}" method="POST" id="formLowongan"
            data-confirm="Simpan lowongan ini sebagai draf? Lowongan belum tampil di halaman karier sampai dipublikasikan.">
            @csrf
            @include('admin.lowongan._form')
        </form>
    </div>
</main>
@endsection
