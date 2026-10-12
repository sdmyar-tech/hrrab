{{-- Tamu yang scan QR absen: minta masuk dulu. Sengaja TIDAK menautkan halaman login staf (alamatnya dirahasiakan). --}}
@extends('layouts.guest.app')

@section('title', 'Absen Acara')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center p-3 bg-body-tertiary">
    <div class="card border-0 shadow-sm text-center p-4" style="max-width:440px">
        <i class="bi bi-qr-code-scan d-block mb-2" style="font-size:2.75rem;color:#880f12"></i>
        <h1 class="h5 mb-1">{{ $pengumuman->judul }}</h1>
        <p class="small text-muted mb-3">{{ $pengumuman->absenJendelaLabel() }}</p>
        <div class="alert alert-info small text-start mb-0">
            <strong>Masuk ke HRRAB dulu</strong> seperti biasa di browser ini.
            Setelah masuk, Anda otomatis dibawa kembali ke halaman absen ini.
            Bila QR sudah berganti, scan ulang QR yang tampil di layar panitia.
        </div>
    </div>
</div>
@endsection
