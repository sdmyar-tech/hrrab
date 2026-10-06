@extends('layouts.public.app')

@section('title', 'Jadwal Seleksi')

@section('styles')
    <meta name="robots" content="noindex, nofollow">
    <style>
        .jadwal-item {
            border: 1px solid #ece2e2;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            background: #fff;
        }

        .jadwal-item.aktif {
            border-color: var(--brand);
            box-shadow: 0 8px 24px rgba(136, 15, 18, .08);
        }

        .jadwal-item .ikon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #f9e5e6;
            color: var(--brand-dark);
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
    </style>
@endsection

@section('content')
@php
    $wawancara = $lamaran->interviewJadwals;
    $screening = $lamaran->screeningOnboardings->sortBy('id')->last();
    $kontrak = $lamaran->kontrakJadwals->where('status', '!=', 'dibatalkan')->sortBy('id')->last();
@endphp

<section class="public-hero detail-hero">
    <div class="container" style="max-width:760px;">
        <p class="hero-eyebrow mb-2">Jadwal Seleksi</p>
        <h1 class="h2 mb-1">Halo, {{ $lamaran->namaKandidat() }}</h1>
        <p class="mb-0" style="opacity:.9;">
            Posisi: <strong>{{ $lamaran->jabatanTujuan() }}</strong> &middot; {{ $lamaran->unitKerjaTujuan() }}
        </p>
    </div>
</section>

<div class="container pb-4" style="max-width:760px;">
    <div class="card detail-card bg-white">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 class="h6 fw-bold mb-0">Status Seleksi</h2>
                <span class="badge {{ $lamaran->badgeClass() }} fs-6">
                    @if ($lamaran->status === \App\Models\Lamaran::STATUS_DITOLAK)
                        Seleksi Selesai
                    @else
                        {{ $lamaran->statusLabel() }}
                    @endif
                </span>
            </div>

            @if ($lamaran->status === \App\Models\Lamaran::STATUS_DITERIMA)
                <div class="alert alert-success mb-3">
                    <i class="bi bi-check-circle me-1"></i> Selamat! Anda dinyatakan diterima. Informasi selanjutnya akan disampaikan oleh tim SDM.
                </div>
            @elseif ($lamaran->status === \App\Models\Lamaran::STATUS_DITOLAK)
                <div class="alert alert-secondary mb-3">
                    Terima kasih telah mengikuti proses seleksi. Proses seleksi untuk posisi ini telah selesai.
                </div>
            @endif

            <div class="d-grid gap-2">
                @forelse ($wawancara as $jadwal)
                    <div class="jadwal-item {{ $jadwal->isTerjadwal() ? 'aktif' : '' }}">
                        <div class="d-flex gap-3">
                            <span class="ikon"><i class="bi bi-chat-square-text"></i></span>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between flex-wrap gap-1">
                                    <strong>Interview {{ $jadwal->tahap_interview }}</strong>
                                    @if ($jadwal->isTerjadwal())
                                        <span class="badge text-bg-primary">Terjadwal</span>
                                    @else
                                        <span class="badge text-bg-light border">Selesai</span>
                                    @endif
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-calendar-event me-1"></i>{{ $jadwal->tanggal->translatedFormat('l, d F Y') }}
                                    &middot; <i class="bi bi-clock me-1"></i>{{ substr($jadwal->waktu_mulai, 0, 5) }}{{ $jadwal->waktu_selesai ? ' – ' . substr($jadwal->waktu_selesai, 0, 5) : '' }} WIB
                                </div>
                                @if ($jadwal->lokasi)
                                    <div class="small mt-1">@include('partials.lokasi', ['lokasi' => $jadwal->lokasi])</div>
                                @endif
                                @if ($jadwal->interviewer)
                                    <div class="small text-muted mt-1"><i class="bi bi-person me-1"></i>Pewawancara: {{ $jadwal->interviewer->name }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="jadwal-item text-center text-muted">
                        <i class="bi bi-hourglass-split d-block fs-4 mb-1"></i>
                        Jadwal interview sedang disiapkan. Silakan buka kembali link ini secara berkala.
                    </div>
                @endforelse

                @if ($screening)
                    <div class="jadwal-item">
                        <div class="d-flex gap-3">
                            <span class="ikon"><i class="bi bi-briefcase"></i></span>
                            <div>
                                <strong>Screening / Onboarding</strong>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-calendar-range me-1"></i>{{ $screening->tanggal_mulai->translatedFormat('d F Y') }} – {{ $screening->tanggal_selesai->translatedFormat('d F Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($kontrak)
                    <div class="jadwal-item">
                        <div class="d-flex gap-3">
                            <span class="ikon"><i class="bi bi-pen"></i></span>
                            <div>
                                <strong>Tanda Tangan Kontrak</strong>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-calendar-event me-1"></i>{{ $kontrak->tanggal->translatedFormat('l, d F Y') }}{{ $kontrak->waktu ? ', ' . substr($kontrak->waktu, 0, 5) . ' WIB' : '' }}
                                </div>
                                @if ($kontrak->lokasi)
                                    <div class="small mt-1">@include('partials.lokasi', ['lokasi' => $kontrak->lokasi])</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <p class="text-muted small mt-4 mb-0">
                <i class="bi bi-shield-lock me-1"></i>Link ini bersifat pribadi — jangan dibagikan ke orang lain.
                Jadwal pada halaman ini selalu diperbarui otomatis oleh tim SDM.
            </p>
        </div>
    </div>
</div>
@endsection
