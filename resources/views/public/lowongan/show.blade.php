@extends('layouts.public.app')

@section('title', $lowongan->judul)

@section('content')
@php
    $sisa = max(0, $lowongan->jumlah_dibutuhkan - $lowongan->jumlah_terisi);
@endphp

<section class="public-hero detail-hero">
    <div class="container">
        <a href="{{ route('lowongan.index') }}" class="small d-inline-block mb-3">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar lowongan
        </a>
        <div class="d-flex align-items-center gap-3">
            <img class="hero-logo d-none d-sm-block" src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="Logo Yayasan Abdurrab"
                style="width:72px; height:72px; border-radius:18px; padding:8px;">
            <div>
                <span class="stat-pill d-inline-block mb-2">{{ $lowongan->unit_kerja }}</span>
                <h1 class="h2 mb-1">{{ $lowongan->judul }}</h1>
                <p class="mb-0" style="opacity:.85;">Yayasan Abdurrab &middot; {{ $lowongan->jabatan }}</p>
            </div>
        </div>
    </div>
</section>

<div class="container pb-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card detail-card bg-white">
                <div class="card-body p-4 p-md-5">
                    @if ($lowongan->deskripsi)
                        <div class="mb-4">
                            <h2 class="section-title"><i class="bi bi-file-text"></i> Deskripsi Pekerjaan</h2>
                            <p class="text-muted mb-0" style="white-space:pre-line; line-height:1.75;">{{ $lowongan->deskripsi }}</p>
                        </div>
                    @endif

                    @if ($lowongan->kualifikasi)
                        <div>
                            <h2 class="section-title"><i class="bi bi-patch-check"></i> Kualifikasi</h2>
                            <p class="text-muted mb-0" style="white-space:pre-line; line-height:1.75;">{{ $lowongan->kualifikasi }}</p>
                        </div>
                    @endif

                    @if (! $lowongan->deskripsi && ! $lowongan->kualifikasi)
                        <p class="text-muted mb-0">Detail pekerjaan akan dijelaskan pada tahap seleksi.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card summary-card bg-white">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Ringkasan Lowongan</h2>
                    <dl class="mb-3">
                        <dt><i class="bi bi-person-badge me-1"></i> Jabatan</dt>
                        <dd>{{ $lowongan->jabatan }}</dd>
                        <dt><i class="bi bi-diagram-3 me-1"></i> Bagian</dt>
                        <dd>{{ $lowongan->unit_kerja }}</dd>
                        @if ($lowongan->lokasi_kerja)
                            <dt><i class="bi bi-geo-alt me-1"></i> Lokasi</dt>
                            <dd>{{ $lowongan->lokasi_kerja }}</dd>
                        @endif
                        @if ($lowongan->tipe_kerja)
                            <dt><i class="bi bi-clock me-1"></i> Tipe Kerja</dt>
                            <dd>{{ $lowongan->tipe_kerja }}</dd>
                        @endif
                        <dt><i class="bi bi-people me-1"></i> Kebutuhan</dt>
                        <dd>{{ $sisa }} orang</dd>
                        @if ($lowongan->tanggal_tutup)
                            <dt><i class="bi bi-calendar-event me-1"></i> Batas Lamaran</dt>
                            <dd>{{ $lowongan->tanggal_tutup->translatedFormat('d F Y') }}</dd>
                        @endif
                    </dl>

                    @if (! $lowongan->isMenerimaLamaran())
                        <div class="alert alert-secondary small mb-0">Lowongan ini sudah tidak menerima lamaran.</div>
                    @else
                        @php
                            $authUser = Auth::user();
                        @endphp

                        @if ($authUser?->isPelamar())
                            @if (! $authUser->dataPelamar?->isLengkap())
                                <a href="{{ route('pelamar.data-pelamar.edit') }}" class="btn btn-brand w-100 py-2">
                                    <i class="bi bi-person-lines-fill me-1"></i> Lengkapi Data untuk Melamar
                                </a>
                            @elseif ($sudahMelamar)
                                <div class="alert alert-info small mb-0">
                                    <i class="bi bi-check-circle me-1"></i> Anda sudah melamar pada lowongan ini.
                                    <a href="{{ route('pelamar.lamaran.index') }}" class="alert-link">Lihat status</a>
                                </div>
                            @else
                                <form action="{{ route('pelamar.lamaran.apply', $lowongan) }}" method="POST" data-confirm="Lamar lowongan ini sekarang?">
                                    @csrf
                                    <button type="submit" class="btn btn-brand w-100 py-2">
                                        <i class="bi bi-send me-1"></i> Lamar Sekarang
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('register') }}" class="btn btn-brand w-100 py-2 mb-2">
                                <i class="bi bi-person-plus me-1"></i> Daftar untuk Melamar
                            </a>
                            <a href="{{ route('pelamar.login') }}" class="btn btn-outline-brand w-100 py-2">Sudah punya akun? Masuk</a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
