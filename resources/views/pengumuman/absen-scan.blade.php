@extends($layout)

@section('content')
@php
    $info = [
        'sudah' => ['ikon' => 'bi-check-circle-fill text-success', 'judul' => 'Anda sudah absen',
            'teks' => 'Tercatat pukul ' . $peserta?->waktu_absen?->format('H:i') . '.'],
        'belum_buka' => ['ikon' => 'bi-clock text-primary', 'judul' => 'Absen belum dibuka',
            'teks' => 'Absen dibuka ' . $pengumuman->absen_buka?->translatedFormat('l, d M Y \p\u\k\u\l H:i') . '.'],
        'tutup' => ['ikon' => 'bi-hourglass-bottom text-secondary', 'judul' => 'Waktu absen sudah ditutup',
            'teks' => 'Absen ditutup ' . $pengumuman->absen_tutup?->translatedFormat('d M Y, H:i') . '. Hubungi SDM bila ada kendala.'],
        'bukan_peserta' => ['ikon' => 'bi-person-x text-secondary', 'judul' => 'Anda bukan peserta absen acara ini',
            'teks' => 'Absen hanya untuk pegawai yang dipilih panitia. Hubungi SDM bila seharusnya Anda ikut.'],
        'qr_kedaluwarsa' => ['ikon' => 'bi-qr-code text-warning', 'judul' => 'Scan QR di lokasi acara',
            'teks' => 'QR berganti setiap 30 detik. Scan QR yang sedang tampil di layar panitia.'],
    ][$keadaan] ?? null;
@endphp
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="absen-scan mx-auto">
            <section class="panel p-4 text-center">
                <p class="eyebrow mb-1"><i class="bi bi-qr-code-scan me-1"></i>Absen Acara</p>
                <h1 class="h4 mb-1">{{ $pengumuman->judul }}</h1>
                <p class="small text-muted mb-0">
                    {{ $pengumuman->absenJendelaLabel() }}
                    @if ($pengumuman->lokasi) · @include('partials.lokasi', ['lokasi' => $pengumuman->lokasi]) @endif
                </p>
            </section>

            @if ($keadaan === 'siap')
                <section class="panel p-4 mt-3 text-center">
                    <i class="bi bi-check-circle-fill text-success d-block mb-2" style="font-size:2.75rem"></i>
                    <h2 class="h5 mb-1">QR terverifikasi</h2>
                    <p class="text-muted mb-3" data-status-absen>Mencatat kehadiran Anda…</p>
                    <form method="POST" action="{{ route('acara.absen.simpan', $pengumuman) }}" id="formAbsenAcara">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-person-check me-1"></i>Konfirmasi Hadir</button>
                    </form>
                </section>
            @elseif ($info)
                <section class="panel p-4 mt-3 text-center">
                    <i class="bi {{ $info['ikon'] }} d-block mb-2" style="font-size:2.75rem"></i>
                    <h2 class="h5">{{ $info['judul'] }}</h2>
                    <p class="text-muted mb-3">{{ $info['teks'] }}</p>
                    @if ($keadaan === 'qr_kedaluwarsa')
                        <button type="button" class="btn btn-primary" data-scan-acara><i class="bi bi-camera me-1"></i>Buka Pemindai QR</button>
                    @endif
                    <a href="{{ route('pengumuman.index') }}#pengumuman-{{ $pengumuman->id }}" class="btn btn-outline-secondary {{ $keadaan === 'qr_kedaluwarsa' ? 'ms-1' : '' }}">Ke Pengumuman</a>
                </section>
            @endif
        </div>
    </div>
</div>

<style>
    .absen-scan { max-width: 480px; }
</style>

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
