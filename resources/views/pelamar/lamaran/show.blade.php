@extends('layouts.pelamar.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <a href="{{ route('pelamar.lamaran.index') }}" class="text-decoration-none text-muted small d-inline-block mb-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Lamaran Saya
        </a>

        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-send"></i></span>
                <div>
                    <p class="eyebrow mb-1">Lamaran Saya</p>
                    <h1 class="h3 mb-1">{{ $lamaran->lowongan?->judul }}</h1>
                    <p class="text-muted mb-0">{{ $lamaran->lowongan?->unit_kerja }} &mdash; {{ $lamaran->lowongan?->jabatan }}</p>
                </div>
            </div>
            <div class="heading-actions">
                <span class="badge fs-6 {{ $lamaran->badgeClass() }}">{{ $lamaran->statusLabel() }}</span>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="panel p-4">
                    <h2 class="h6 fw-bold mb-3">Status Lamaran</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7"><span class="badge {{ $lamaran->badgeClass() }}">{{ $lamaran->statusLabel() }}</span></dd>
                        <dt class="col-5 text-muted">Tahap</dt>
                        <dd class="col-7">{{ $lamaran->isProses() ? $lamaran->tahapLabel() : '-' }}</dd>
                        @if ($lamaran->catatan_admin)
                            <dt class="col-5 text-muted">Catatan Admin</dt>
                            <dd class="col-7">{{ $lamaran->catatan_admin }}</dd>
                        @endif
                        <dt class="col-5 text-muted">Tanggal Melamar</dt>
                        <dd class="col-7">{{ $lamaran->created_at->translatedFormat('d M Y') }}</dd>
                    </dl>
                </div>

                @if (! $lamaran->isSelesai())
                    <form action="{{ route('pelamar.lamaran.tolak', $lamaran) }}" method="POST" class="mt-3"
                        data-confirm="Batalkan lamaran ini?">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-x-lg me-1"></i> Batalkan Lamaran
                        </button>
                    </form>
                @endif
            </div>

            <div class="col-lg-7">
                @php
                    $urutanTahap = ['verifikasi', 'interview', 'screening', 'kontrak', 'selesai'];
                    $tahapSekarang = $lamaran->tahap ?? 'verifikasi';
                    $indexSekarang = array_search($tahapSekarang, $urutanTahap, true) ?: 0;
                    $ditolakAtauDibatalkan = in_array($lamaran->status, [\App\Models\Lamaran::STATUS_DITOLAK, \App\Models\Lamaran::STATUS_DIBATALKAN], true);

                    $stepState = function ($stepIndex) use ($indexSekarang, $ditolakAtauDibatalkan) {
                        if ($ditolakAtauDibatalkan) {
                            return $stepIndex < $indexSekarang ? 'done' : ($stepIndex === $indexSekarang ? 'stopped' : 'pending');
                        }
                        return $stepIndex < $indexSekarang ? 'done' : ($stepIndex === $indexSekarang ? 'active' : 'pending');
                    };

                    $iconFor = fn ($state) => match ($state) {
                        'done' => ['bi-check-circle-fill', 'text-success'],
                        'active' => ['bi-arrow-right-circle-fill', 'text-primary'],
                        'stopped' => ['bi-x-circle-fill', 'text-danger'],
                        default => ['bi-circle', 'text-muted'],
                    };
                @endphp

                <div class="panel p-4">
                    <h2 class="h6 fw-bold mb-3">Proses Lamaran</h2>

                    <div class="proses-timeline">
                        @php [$icon, $color] = $iconFor($stepState(0)); @endphp
                        <div class="d-flex gap-2 mb-3">
                            <div><i class="bi {{ $icon }} {{ $color }} fs-5"></i></div>
                            <div>
                                <strong>Verifikasi Data Pelamar</strong>
                                <div class="text-muted small">
                                    @if ($indexSekarang === 0 && $ditolakAtauDibatalkan)
                                        Ditolak pada tahap verifikasi.
                                        @if ($lamaran->catatan_admin) <br>Alasan: {{ $lamaran->catatan_admin }} @endif
                                    @elseif ($indexSekarang === 0)
                                        Menunggu diverifikasi Admin/SDM.
                                    @else
                                        Terverifikasi{{ $lamaran->diverifikasi_pada ? ' pada ' . $lamaran->diverifikasi_pada->translatedFormat('d M Y') : '' }}.
                                    @endif
                                </div>
                            </div>
                        </div>

                        @php [$icon, $color] = $iconFor($stepState(1)); @endphp
                        <div class="d-flex gap-2 mb-3">
                            <div><i class="bi {{ $icon }} {{ $color }} fs-5"></i></div>
                            <div class="flex-grow-1">
                                <strong>Interview</strong>
                                @if ($lamaran->interviewJadwals->isEmpty())
                                    <div class="text-muted small">
                                        {{ $indexSekarang === 1 && $ditolakAtauDibatalkan ? 'Dibatalkan sebelum jadwal interview dibuat.' : 'Belum ada jadwal interview.' }}
                                    </div>
                                @else
                                    @foreach ($lamaran->interviewJadwals as $jadwal)
                                        <div class="border rounded p-2 mb-1 small">
                                            <strong>Interview {{ $jadwal->tahap_interview }}</strong>
                                            &mdash; {{ $jadwal->tanggal->translatedFormat('d M Y') }}, {{ substr((string) $jadwal->waktu_mulai, 0, 5) }}
                                            @if ($jadwal->lokasi) &middot; @include('partials.lokasi', ['lokasi' => $jadwal->lokasi, 'tanpaIkon' => true]) @endif
                                            <br>
                                            <span class="badge text-bg-light border">{{ $jadwal->statusLabel() }}</span>
                                            <span class="badge {{ $jadwal->hasilBadgeClass() }}">{{ $jadwal->hasilLabel() }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        @php [$icon, $color] = $iconFor($stepState(2)); @endphp
                        <div class="d-flex gap-2 mb-3">
                            <div><i class="bi {{ $icon }} {{ $color }} fs-5"></i></div>
                            <div class="flex-grow-1">
                                <strong>Screening/Onboarding</strong>
                                @if ($lamaran->screeningOnboardings->isEmpty())
                                    <div class="text-muted small">
                                        {{ $indexSekarang === 2 && $ditolakAtauDibatalkan ? 'Dibatalkan sebelum penugasan screening/onboarding.' : 'Belum ada penugasan screening/onboarding.' }}
                                    </div>
                                @else
                                    @foreach ($lamaran->screeningOnboardings as $screening)
                                        <div class="border rounded p-2 mb-1 small">
                                            {{ $screening->tanggal_mulai->translatedFormat('d M Y') }} &ndash; {{ $screening->tanggal_selesai->translatedFormat('d M Y') }}
                                            <br>
                                            <span class="badge text-bg-light border">{{ $screening->statusLabel() }}</span>
                                            <span class="badge {{ $screening->hasilBadgeClass() }}">{{ $screening->hasilLabel() }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        @php [$icon, $color] = $iconFor($stepState(3)); @endphp
                        <div class="d-flex gap-2 mb-3">
                            <div><i class="bi {{ $icon }} {{ $color }} fs-5"></i></div>
                            <div class="flex-grow-1">
                                <strong>Tanda Tangan Kontrak</strong>
                                @if ($lamaran->kontrakJadwals->isEmpty())
                                    <div class="text-muted small">Belum ada jadwal kontrak.</div>
                                @else
                                    @foreach ($lamaran->kontrakJadwals as $jadwal)
                                        <div class="border rounded p-2 mb-1 small">
                                            {{ $jadwal->tanggal->translatedFormat('d M Y') }}
                                            @if ($jadwal->waktu) , {{ $jadwal->waktu }} @endif
                                            @if ($jadwal->lokasi) &middot; @include('partials.lokasi', ['lokasi' => $jadwal->lokasi, 'tanpaIkon' => true]) @endif
                                            <span class="badge text-bg-light border">{{ $jadwal->statusLabel() }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        @php [$icon, $color] = $iconFor($stepState(4)); @endphp
                        <div class="d-flex gap-2">
                            <div><i class="bi {{ $icon }} {{ $color }} fs-5"></i></div>
                            <div>
                                <strong>Keputusan Akhir</strong>
                                <div class="text-muted small">
                                    @if ($lamaran->status === \App\Models\Lamaran::STATUS_DITERIMA)
                                        Selamat! Anda direkrut{{ $lamaran->diputuskan_pada ? ' pada ' . $lamaran->diputuskan_pada->translatedFormat('d M Y') : '' }}.
                                    @elseif ($lamaran->status === \App\Models\Lamaran::STATUS_DITOLAK)
                                        Lamaran ditolak{{ $lamaran->diputuskan_pada ? ' pada ' . $lamaran->diputuskan_pada->translatedFormat('d M Y') : '' }}.
                                    @elseif ($lamaran->status === \App\Models\Lamaran::STATUS_DIBATALKAN)
                                        Anda membatalkan lamaran ini.
                                    @else
                                        Menunggu keputusan akhir Admin/SDM.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
