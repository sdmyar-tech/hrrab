{{-- Tamu yang scan QR absen: minta masuk dulu (gaya kartu masuk Batch 1). Tombol login hanya tampil bila QR yang di-scan masih sah ($urlMasuk). --}}
@extends('layouts.guest.app')

@section('title', 'Absen Acara')
@section('body-class', 'auth-body')
@section('pakai-banner', '1')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <x-auth-card eyebrow="Absen Acara" eyebrow-icon="bi-qr-code-scan" :title="$pengumuman->judul" :subtitle="$pengumuman->absenJendelaLabel()">
        @if ($urlMasuk)
            <div class="auth-akun">
                <span class="hr-stat-ikon hr-tone-info"><i class="bi bi-person-lock" aria-hidden="true"></i></span>
                <p class="small mb-0">Masuk dulu untuk mencatat kehadiran. Setelah masuk Anda otomatis kembali ke sini dan absen langsung tercatat.</p>
            </div>
            <x-button :href="$urlMasuk" size="lg" block icon="bi-box-arrow-in-right">Masuk untuk Absen</x-button>
            <p class="small text-muted text-center mt-3 mb-0"><i class="bi bi-clock-history me-1" aria-hidden="true"></i>QR berlaku beberapa menit. Bila kedaluwarsa, scan ulang QR di layar panitia.</p>
        @else
            <x-alert tone="warning" title="QR sudah tidak berlaku" class="mb-0">
                Scan ulang QR yang sedang tampil di layar panitia, lalu masuk dari halaman yang muncul.
            </x-alert>
        @endif
    </x-auth-card>
@endsection
