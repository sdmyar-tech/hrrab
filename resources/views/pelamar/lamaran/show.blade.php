@extends('layouts.pelamar.app')

@section('content')
@use('App\Models\Lamaran')
@php
    $lowongan = $lamaran->lowongan;
    $pelamar = Auth::user()->dataPelamar;
    $tone = [
        Lamaran::STATUS_MENUNGGU_VERIFIKASI => 'warning', Lamaran::STATUS_PROSES => 'info',
        Lamaran::STATUS_DITERIMA => 'success', Lamaran::STATUS_DITOLAK => 'danger', Lamaran::STATUS_DIBATALKAN => 'neutral',
    ][$lamaran->status] ?? 'neutral';

    // Tahapan: verifikasi → interview → screening → kontrak → keputusan. Status tiap tahap dari posisi tahap sekarang.
    $urutanTahap = [Lamaran::TAHAP_VERIFIKASI, Lamaran::TAHAP_INTERVIEW, Lamaran::TAHAP_SCREENING, Lamaran::TAHAP_KONTRAK, Lamaran::TAHAP_SELESAI];
    $indexSekarang = array_search($lamaran->tahap ?? Lamaran::TAHAP_VERIFIKASI, $urutanTahap, true) ?: 0;
    $berhenti = in_array($lamaran->status, [Lamaran::STATUS_DITOLAK, Lamaran::STATUS_DIBATALKAN], true);
    $diterima = $lamaran->status === Lamaran::STATUS_DITERIMA;
    $state = function (int $i) use ($indexSekarang, $berhenti, $diterima) {
        if ($diterima) {
            return 'done';
        }
        if ($i < $indexSekarang) {
            return 'done';
        }
        if ($i === $indexSekarang) {
            return $berhenti ? 'stopped' : 'active';
        }

        return 'pending';
    };
    $dokumen = [['Pas Foto', $pelamar?->foto_path, 'bi-image'], ['CV / Resume', $pelamar?->cv_path, 'bi-file-earmark-person'],
        ['Ijazah', $pelamar?->ijazah_path, 'bi-mortarboard'], ['Sertifikat', $pelamar?->sertifikat_path, 'bi-award']];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="$lowongan?->judul ?? $lamaran->jabatanTujuan()" eyebrow="Lamaran Saya"
            :subtitle="($lowongan?->unit_kerja ?? $lamaran->unitKerjaTujuan()) . ' · ' . ($lowongan?->jabatan ?? $lamaran->jabatanTujuan())"
            :back="route('pelamar.lamaran.index')" back-label="Lamaran Saya">
            <x-badge :tone="$tone" size="lg" dot>{{ $lamaran->statusLabel() }}</x-badge>
            @if ($lamaran->isProses())<span class="small text-muted">Tahap: <strong class="text-body">{{ $lamaran->tahapLabel() }}</strong></span>@endif
            @unless ($lamaran->isSelesai())
                <x-slot:actions>
                    <form action="{{ route('pelamar.lamaran.tolak', $lamaran) }}" method="POST" class="m-0">
                        @csrf
                        <x-button type="submit" variant="danger-outline" icon="bi-x-lg" data-confirm="true" data-icon="warning"
                            data-title="Batalkan lamaran?" data-text="Lamaran ini akan dibatalkan dan tidak diproses lagi."
                            data-confirm-text="Ya, Batalkan" data-cancel-text="Kembali">Batalkan Lamaran</x-button>
                    </form>
                </x-slot:actions>
            @endunless
        </x-page-header>

        @if ($diterima)
            <x-alert tone="success" title="Selamat, Anda diterima!" class="mb-4">Tim SDM akan menghubungi Anda untuk langkah berikutnya.</x-alert>
        @elseif ($lamaran->status === Lamaran::STATUS_DITOLAK && $lamaran->catatan_admin)
            <x-alert tone="info" title="Catatan dari tim SDM" class="mb-4">{{ $lamaran->catatan_admin }}</x-alert>
        @endif

        <div class="row g-4 align-items-start">
            {{-- ============ Kiri: timeline proses ============ --}}
            <div class="col-lg-7">
                <x-card title="Proses Lamaran" subtitle="Diperbarui setiap kali tim SDM memproses lamaran Anda." icon="bi-signpost-split">
                    <x-timeline label="Tahapan lamaran">
                        <x-timeline.item :state="$state(0)" title="Verifikasi Data Pelamar"
                            :meta="$lamaran->created_at->translatedFormat('d M Y')">
                            @if ($indexSekarang === 0 && $berhenti)
                                Ditolak pada tahap verifikasi.@if ($lamaran->catatan_admin) Alasan: {{ $lamaran->catatan_admin }}@endif
                            @elseif ($indexSekarang === 0 && ! $diterima)
                                Menunggu diverifikasi Admin/SDM.
                            @else
                                Terverifikasi{{ $lamaran->diverifikasi_pada ? ' pada ' . $lamaran->diverifikasi_pada->translatedFormat('d M Y') : '' }}.
                            @endif
                        </x-timeline.item>

                        <x-timeline.item :state="$state(1)" title="Interview">
                            @forelse ($lamaran->interviewJadwals as $jadwal)
                                <div class="lamaran-jadwal">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <strong>Interview {{ $jadwal->tahap_interview }}</strong>
                                        <span class="d-flex flex-wrap gap-1">
                                            <x-badge tone="neutral">{{ $jadwal->statusLabel() }}</x-badge>
                                            <span class="badge {{ $jadwal->hasilBadgeClass() }}">{{ $jadwal->hasilLabel() }}</span>
                                        </span>
                                    </div>
                                    <ul class="pub-rincian mt-1">
                                        <li><i class="bi bi-calendar-event" aria-hidden="true"></i>{{ $jadwal->tanggal->translatedFormat('l, d M Y') }}, {{ substr((string) $jadwal->waktu_mulai, 0, 5) }} WIB</li>
                                        @if ($jadwal->lokasi)<li>@include('partials.lokasi', ['lokasi' => $jadwal->lokasi])</li>@endif
                                        @if ($jadwal->interviewer)<li><i class="bi bi-person" aria-hidden="true"></i>{{ $jadwal->interviewer->name }}</li>@endif
                                    </ul>
                                </div>
                            @empty
                                {{ $indexSekarang === 1 && $berhenti ? 'Dibatalkan sebelum jadwal interview dibuat.' : 'Belum ada jadwal interview.' }}
                            @endforelse
                        </x-timeline.item>

                        <x-timeline.item :state="$state(2)" title="Screening / Onboarding">
                            @forelse ($lamaran->screeningOnboardings as $screening)
                                <div class="lamaran-jadwal">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <span><i class="bi bi-calendar-range me-1" aria-hidden="true"></i>{{ $screening->tanggal_mulai->translatedFormat('d M Y') }} – {{ $screening->tanggal_selesai->translatedFormat('d M Y') }}</span>
                                        <span class="d-flex flex-wrap gap-1">
                                            <x-badge tone="neutral">{{ $screening->statusLabel() }}</x-badge>
                                            <span class="badge {{ $screening->hasilBadgeClass() }}">{{ $screening->hasilLabel() }}</span>
                                        </span>
                                    </div>
                                </div>
                            @empty
                                {{ $indexSekarang === 2 && $berhenti ? 'Dibatalkan sebelum penugasan screening/onboarding.' : 'Belum ada penugasan screening/onboarding.' }}
                            @endforelse
                        </x-timeline.item>

                        <x-timeline.item :state="$state(3)" title="Tanda Tangan Kontrak">
                            @forelse ($lamaran->kontrakJadwals as $jadwal)
                                <div class="lamaran-jadwal">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <span><i class="bi bi-calendar-event me-1" aria-hidden="true"></i>{{ $jadwal->tanggal->translatedFormat('l, d M Y') }}{{ $jadwal->waktu ? ', ' . substr((string) $jadwal->waktu, 0, 5) . ' WIB' : '' }}</span>
                                        <x-badge tone="neutral">{{ $jadwal->statusLabel() }}</x-badge>
                                    </div>
                                    @if ($jadwal->lokasi)<div class="small mt-1">@include('partials.lokasi', ['lokasi' => $jadwal->lokasi])</div>@endif
                                </div>
                            @empty
                                Belum ada jadwal kontrak.
                            @endforelse
                        </x-timeline.item>

                        <x-timeline.item :state="$diterima ? 'done' : ($berhenti ? 'stopped' : 'pending')" title="Keputusan Akhir"
                            :meta="$lamaran->diputuskan_pada?->translatedFormat('d M Y')">
                            @if ($diterima)
                                Selamat! Anda direkrut.
                            @elseif ($lamaran->status === Lamaran::STATUS_DITOLAK)
                                Lamaran ditolak.
                            @elseif ($lamaran->status === Lamaran::STATUS_DIBATALKAN)
                                Anda membatalkan lamaran ini.
                            @else
                                Menunggu keputusan akhir Admin/SDM.
                            @endif
                        </x-timeline.item>
                    </x-timeline>
                </x-card>
            </div>

            {{-- ============ Kanan: info lowongan + dokumen ============ --}}
            <div class="col-lg-5 d-grid gap-3">
                <x-card title="Info Lowongan" icon="bi-briefcase">
                    @if ($lowongan)
                        <x-slot:actions><a href="{{ route('lowongan.show', $lowongan) }}">Lihat lowongan</a></x-slot:actions>
                    @endif
                    <dl class="dash-dl mb-0">
                        <div><dt>Bagian</dt><dd>{{ $lowongan?->unit_kerja ?? $lamaran->unitKerjaTujuan() }}</dd></div>
                        <div><dt>Jabatan</dt><dd>{{ $lowongan?->jabatan ?? $lamaran->jabatanTujuan() }}</dd></div>
                        @if ($lowongan?->lokasi_kerja)<div><dt>Lokasi</dt><dd>{{ $lowongan->lokasi_kerja }}</dd></div>@endif
                        @if ($lowongan?->tipe_kerja)<div><dt>Tipe kerja</dt><dd>{{ $lowongan->tipe_kerja }}</dd></div>@endif
                        <div><dt>Tanggal melamar</dt><dd>{{ $lamaran->created_at->translatedFormat('d M Y') }}</dd></div>
                        @if ($lamaran->catatan_admin && $lamaran->status !== Lamaran::STATUS_DITOLAK)
                            <div><dt>Catatan SDM</dt><dd class="text-wrap">{{ $lamaran->catatan_admin }}</dd></div>
                        @endif
                    </dl>
                </x-card>

                <x-card title="Dokumen Terlampir" subtitle="Diambil dari Data Pelamar Anda." icon="bi-folder2">
                    <x-slot:actions><a href="{{ route('pelamar.data-pelamar.edit') }}#bagian-dokumen">Perbarui</a></x-slot:actions>
                    <ul class="hr-list">
                        @foreach ($dokumen as [$label, $path, $ikon])
                            <li class="hr-list-item">
                                <span class="hr-stat-ikon dash-ikon-kecil {{ $path ? 'hr-tone-success' : 'hr-tone-neutral' }}"><i class="bi {{ $ikon }}" aria-hidden="true"></i></span>
                                <span class="hr-list-utama">
                                    <span class="hr-list-judul">{{ $label }}</span>
                                    <span class="hr-list-sub">{{ $path ? strtoupper(pathinfo($path, PATHINFO_EXTENSION)) : 'Belum diunggah' }}</span>
                                </span>
                                @if ($path)
                                    <x-button :href="\App\Support\Berkas::url($path)" variant="ghost" size="sm" icon="bi-box-arrow-up-right" target="_blank" rel="noopener">Buka</x-button>
                                @else
                                    <x-badge tone="neutral">Kosong</x-badge>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            </div>
        </div>
    </div>
</main>
@endsection
