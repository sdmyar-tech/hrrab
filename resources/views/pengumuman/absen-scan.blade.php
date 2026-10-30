{{--
    Hasil scan QR absen acara (pengguna sudah masuk). $keadaan: siap · sudah · belum_buka · tutup · bukan_peserta · qr_kedaluwarsa.
    "siap" langsung mengirim absen (tanpa ketukan tambahan); tombol tetap ada bila skrip gagal.
    $layout = panel sesuai peran, atau layout tamu untuk akun pelamar (body-class di bawah hanya dipakai layout tamu).
--}}
@extends($layout)

@section('title', 'Absen Acara')
@section('body-class', 'auth-body')

@section('content')
@php
    $info = [
        'sudah' => ['ikon' => 'bi-check-lg', 'tone' => 'success', 'judul' => 'Kehadiran tercatat',
            'teks' => 'Anda tercatat hadir pukul ' . $peserta?->waktu_absen?->format('H:i') . '. Terima kasih sudah datang.'],
        'belum_buka' => ['ikon' => 'bi-clock', 'tone' => 'info', 'judul' => 'Absen belum dibuka',
            'teks' => 'Absen dibuka ' . $pengumuman->absen_buka?->translatedFormat('l, d M Y \p\u\k\u\l H:i') . '.'],
        'tutup' => ['ikon' => 'bi-hourglass-bottom', 'tone' => 'neutral', 'judul' => 'Waktu absen sudah ditutup',
            'teks' => 'Absen ditutup ' . $pengumuman->absen_tutup?->translatedFormat('d M Y, H:i') . '. Hubungi SDM bila ada kendala.'],
        'bukan_peserta' => ['ikon' => 'bi-person-x', 'tone' => 'neutral', 'judul' => 'Anda bukan peserta acara ini',
            'teks' => 'Absen hanya untuk pegawai yang dipilih panitia. Hubungi SDM bila seharusnya Anda ikut.'],
        'qr_kedaluwarsa' => ['ikon' => 'bi-qr-code', 'tone' => 'warning', 'judul' => 'Scan QR di lokasi acara',
            'teks' => 'QR berganti setiap 30 detik. Scan QR yang sedang tampil di layar panitia.'],
    ][$keadaan] ?? null;
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <div class="absen-acara mx-auto">
            {{-- Info acara --}}
            <x-card>
                <div class="d-flex align-items-start gap-3">
                    <span class="hr-stat-ikon hr-tone-primary"><i class="bi bi-qr-code-scan" aria-hidden="true"></i></span>
                    <div class="min-w-0">
                        <p class="eyebrow mb-1">Absen Acara</p>
                        <h1 class="absen-acara-judul">{{ $pengumuman->judul }}</h1>
                        <ul class="pub-rincian mt-2">
                            <li><i class="bi bi-calendar-event" aria-hidden="true"></i>{{ $pengumuman->absenJendelaLabel() }}</li>
                            @if ($pengumuman->lokasi)<li>@include('partials.lokasi', ['lokasi' => $pengumuman->lokasi])</li>@endif
                        </ul>
                    </div>
                </div>
            </x-card>

            {{-- Status absen --}}
            @if ($keadaan === 'siap')
                <x-card class="mt-3 absen-acara-status">
                    <span class="absen-acara-ikon hr-tone-success"><i class="bi bi-qr-code-scan" aria-hidden="true"></i></span>
                    <h2 class="absen-acara-status-judul">QR terverifikasi</h2>
                    <p class="text-muted mb-4" data-status-absen>
                        <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Mencatat kehadiran Anda…
                    </p>
                    <form method="POST" action="{{ route('acara.absen.simpan', $pengumuman) }}" id="formAbsenAcara">
                        @csrf
                        <x-button type="submit" size="lg" block icon="bi-person-check">Absen Sekarang</x-button>
                    </form>
                </x-card>
            @elseif ($info)
                <x-card class="mt-3 absen-acara-status {{ $keadaan === 'sudah' ? 'is-sukses' : '' }}">
                    <span class="absen-acara-ikon hr-tone-{{ $info['tone'] }}"><i class="bi {{ $info['ikon'] }}" aria-hidden="true"></i></span>
                    <h2 class="absen-acara-status-judul">{{ $info['judul'] }}</h2>
                    <p class="text-muted mb-4">{{ $info['teks'] }}</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        @if ($keadaan === 'qr_kedaluwarsa')
                            <x-button data-scan-acara icon="bi-camera">Buka Pemindai QR</x-button>
                        @endif
                        <x-button variant="secondary" :href="route('pengumuman.index') . '#pengumuman-' . $pengumuman->id" icon="bi-megaphone">Ke Pengumuman</x-button>
                    </div>
                </x-card>
            @endif
        </div>
    </div>
</main>

@if ($keadaan === 'qr_kedaluwarsa')
    @include('pengumuman._modal-scan')
@endif

@if ($keadaan === 'siap' && ! session('error'))
<script>
    // QR sudah sah: langsung catat hadir tanpa ketukan tambahan. Tombol tetap ada bila skrip gagal.
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formAbsenAcara');
        form.querySelector('button').disabled = true;
        form.submit();
    });
</script>
@endif
@endsection
