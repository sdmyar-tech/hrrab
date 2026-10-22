{{-- Tamu yang scan QR absen: minta masuk dulu. Tombol login hanya tampil bila QR yang di-scan masih sah ($urlMasuk). --}}
@extends('layouts.guest.app')

@section('title', 'Absen Acara')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center p-3">
    <div class="card border-0 shadow-sm text-center p-4 w-100" style="max-width:440px;border-radius:16px">
        <i class="bi bi-qr-code-scan d-block mb-2" style="font-size:2.75rem;color:#8b1215"></i>
        <h1 class="h5 mb-1">{{ $pengumuman->judul }}</h1>
        <p class="small text-muted mb-3">{{ $pengumuman->absenJendelaLabel() }}</p>

        @if ($urlMasuk)
            <p class="mb-3">Anda belum masuk. Masuk dulu untuk mencatat kehadiran — setelah itu Anda otomatis kembali ke sini dan absen langsung tercatat.</p>
            <a href="{{ $urlMasuk }}" class="btn btn-primary btn-lg w-100"><i class="bi bi-box-arrow-in-right"></i> Masuk untuk Absen</a>
            <p class="small text-muted mt-3 mb-0">QR berlaku beberapa menit. Bila kedaluwarsa, scan ulang QR di layar panitia.</p>
        @else
            <div class="alert alert-warning small text-start mb-0">
                <strong>QR sudah tidak berlaku.</strong> Scan ulang QR yang sedang tampil di layar panitia, lalu masuk dari halaman yang muncul.
            </div>
        @endif
    </div>
</div>
@endsection
