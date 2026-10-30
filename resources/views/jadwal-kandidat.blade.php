{{-- Jadwal seleksi kandidat (tautan token pribadi, tanpa login): status + timeline interview → screening → kontrak. --}}
@extends('layouts.public.app')

@section('title', 'Jadwal Seleksi')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
@php
    $wawancara = $lamaran->interviewJadwals;
    $screening = $lamaran->screeningOnboardings->sortBy('id')->last();
    $kontrak = $lamaran->kontrakJadwals->where('status', '!=', 'dibatalkan')->sortBy('id')->last();
    $ditolak = $lamaran->status === \App\Models\Lamaran::STATUS_DITOLAK;
    $diterima = $lamaran->status === \App\Models\Lamaran::STATUS_DITERIMA;
    $tone = match ($lamaran->status) {
        \App\Models\Lamaran::STATUS_DITERIMA => 'success',
        \App\Models\Lamaran::STATUS_PROSES => 'info',
        \App\Models\Lamaran::STATUS_DITOLAK, \App\Models\Lamaran::STATUS_DIBATALKAN => 'neutral',
        default => 'warning',
    };
    $posisi = $lamaran->jabatanTujuan() . ' · ' . $lamaran->unitKerjaTujuan();

    // Tautan "Tambah ke Google Calendar" (waktu WIB; tanpa jam selesai → 1 jam).
    $kalender = function (string $judul, $tanggal, ?string $mulai, ?string $selesai, ?string $lokasi) use ($posisi) {
        $awal = \Illuminate\Support\Carbon::parse($tanggal->format('Y-m-d') . ' ' . ($mulai ?: '08:00'));
        $akhir = $selesai ? \Illuminate\Support\Carbon::parse($tanggal->format('Y-m-d') . ' ' . $selesai) : $awal->copy()->addHour();

        return 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action' => 'TEMPLATE', 'text' => $judul . ' — Yayasan Abdurrab',
            'dates' => $awal->format('Ymd\THis') . '/' . $akhir->format('Ymd\THis'), 'ctz' => 'Asia/Jakarta',
            'details' => 'Seleksi posisi ' . $posisi, 'location' => $lokasi ? \App\Support\Lokasi::label($lokasi) : '',
        ]);
    };
@endphp

<div class="pub-wadah pub-isi">
    <div class="pub-jadwal mx-auto">
        <x-card class="pub-jadwal-kepala">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div class="min-w-0">
                    <p class="eyebrow mb-1">Jadwal Seleksi</p>
                    <h1 class="pub-jadwal-judul">Halo, {{ $lamaran->namaKandidat() }}</h1>
                    <p class="text-muted mb-0">Posisi <strong class="text-body">{{ $lamaran->jabatanTujuan() }}</strong> · {{ $lamaran->unitKerjaTujuan() }}</p>
                </div>
                <x-badge :tone="$tone" size="lg">{{ $ditolak ? 'Seleksi Selesai' : $lamaran->statusLabel() }}</x-badge>
            </div>
        </x-card>

        @if ($diterima)
            <x-alert tone="success" title="Selamat, Anda diterima!" class="mt-3 mb-0">Informasi selanjutnya akan disampaikan oleh tim SDM.</x-alert>
        @elseif ($ditolak)
            <x-alert tone="info" title="Terima kasih telah mengikuti seleksi" class="mt-3 mb-0">Proses seleksi untuk posisi ini telah selesai.</x-alert>
        @endif

        <x-card title="Tahapan & Jadwal" subtitle="Diperbarui otomatis oleh tim SDM — buka kembali tautan ini secara berkala." icon="bi-signpost-split" class="mt-3">
            <x-timeline label="Tahapan seleksi">
                @forelse ($wawancara as $jadwal)
                    @php $aktif = $jadwal->isTerjadwal(); @endphp
                    <x-timeline.item :state="$aktif ? 'active' : 'done'" :title="'Interview ' . $jadwal->tahap_interview"
                        :meta="$aktif ? 'Terjadwal' : 'Selesai'">
                        <ul class="pub-rincian">
                            <li><i class="bi bi-calendar-event" aria-hidden="true"></i>{{ $jadwal->tanggal->translatedFormat('l, d F Y') }}</li>
                            <li><i class="bi bi-clock" aria-hidden="true"></i>{{ substr($jadwal->waktu_mulai, 0, 5) }}{{ $jadwal->waktu_selesai ? ' – ' . substr($jadwal->waktu_selesai, 0, 5) : '' }} WIB</li>
                            @if ($jadwal->lokasi)<li>@include('partials.lokasi', ['lokasi' => $jadwal->lokasi])</li>@endif
                            @if ($jadwal->interviewer)<li><i class="bi bi-person" aria-hidden="true"></i>Pewawancara: {{ $jadwal->interviewer->name }}</li>@endif
                        </ul>
                        @if ($aktif)
                            <x-button size="sm" variant="secondary" icon="bi-calendar-plus" target="_blank" rel="noopener" class="mt-2"
                                :href="$kalender('Interview ' . $jadwal->tahap_interview, $jadwal->tanggal, $jadwal->waktu_mulai, $jadwal->waktu_selesai, $jadwal->lokasi)">Tambah ke Google Calendar</x-button>
                        @endif
                    </x-timeline.item>
                @empty
                    <x-timeline.item state="active" icon="bi-hourglass-split" title="Interview">
                        Jadwal interview sedang disiapkan. Silakan buka kembali tautan ini secara berkala.
                    </x-timeline.item>
                @endforelse

                <x-timeline.item :state="$screening ? ($kontrak || $diterima ? 'done' : 'active') : 'pending'" title="Screening / Onboarding"
                    :meta="$screening ? null : 'Belum dijadwalkan'">
                    @if ($screening)
                        <ul class="pub-rincian">
                            <li><i class="bi bi-calendar-range" aria-hidden="true"></i>{{ $screening->tanggal_mulai->translatedFormat('d F Y') }} – {{ $screening->tanggal_selesai->translatedFormat('d F Y') }}</li>
                        </ul>
                    @endif
                </x-timeline.item>

                <x-timeline.item :state="$kontrak ? ($diterima ? 'done' : 'active') : 'pending'" title="Tanda Tangan Kontrak" :meta="$kontrak ? null : 'Belum dijadwalkan'">
                    @if ($kontrak)
                        <ul class="pub-rincian">
                            <li><i class="bi bi-calendar-event" aria-hidden="true"></i>{{ $kontrak->tanggal->translatedFormat('l, d F Y') }}{{ $kontrak->waktu ? ', ' . substr($kontrak->waktu, 0, 5) . ' WIB' : '' }}</li>
                            @if ($kontrak->lokasi)<li>@include('partials.lokasi', ['lokasi' => $kontrak->lokasi])</li>@endif
                        </ul>
                        @unless ($diterima)
                            <x-button size="sm" variant="secondary" icon="bi-calendar-plus" target="_blank" rel="noopener" class="mt-2"
                                :href="$kalender('Tanda tangan kontrak', $kontrak->tanggal, $kontrak->waktu ? substr($kontrak->waktu, 0, 5) : null, null, $kontrak->lokasi)">Tambah ke Google Calendar</x-button>
                        @endunless
                    @endif
                </x-timeline.item>

                <x-timeline.item :state="$diterima ? 'done' : ($ditolak ? 'stopped' : 'pending')" title="Keputusan Akhir"
                    :meta="$diterima ? 'Diterima' : ($ditolak ? 'Selesai' : null)" />
            </x-timeline>
        </x-card>

        <p class="pub-catatan">
            <i class="bi bi-shield-lock" aria-hidden="true"></i>
            Tautan ini bersifat pribadi — jangan dibagikan ke orang lain. Berhalangan hadir? Segera hubungi tim SDM Yayasan Abdurrab.
        </p>
    </div>
</div>
@endsection
