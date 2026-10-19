@extends('layouts.pegawai.app')

@section('content')
@include('partials.viz.style')
@php
    $kep = $pegawai?->kepegawaian;
    $jam = fn ($n) => rtrim(rtrim(number_format($n, 1, ',', '.'), '0'), ',');
@endphp
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">

        <section class="dash-hero p-3 p-lg-4 mb-3">
            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
                @if ($pegawai?->dokumen?->pas_foto)
                    <img src="{{ asset('storage/' . $pegawai->dokumen->pas_foto) }}" alt="" class="rounded-circle border" style="width:72px;height:72px;object-fit:cover;">
                @else
                    <span class="page-icon" style="width:72px;height:72px;font-size:1.6rem;"><i class="bi bi-person"></i></span>
                @endif
                <div class="flex-grow-1">
                    <p class="eyebrow mb-1">Dashboard Staff &middot; {{ now()->translatedFormat('l, d F Y') }}</p>
                    <h1 class="h3 mb-1">Halo, {{ \Illuminate\Support\Str::of($user->name)->before(',') }}</h1>
                    <p class="text-muted mb-0">
                        {{ $kep?->jabatan ?? 'Jabatan belum diisi' }} &middot; {{ $kep?->unit_kerja ?? '-' }}
                        @if ($pegawai?->nip) &middot; NIP {{ $pegawai->nip }} @endif
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <div class="dash-mini-stat viz text-center" style="min-width:100px;">
                        <div class="n">{{ $kep?->status_kerja ?? '-' }}</div>
                        <div class="l">Status kerja</div>
                    </div>
                    <div class="dash-mini-stat viz text-center" style="min-width:100px;">
                        <div class="n">{{ $masaKerja }}</div>
                        <div class="l">Masa kerja</div>
                    </div>
                </div>
            </div>
        </section>

        @if ($isIncomplete)
            <div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3" role="alert">
                <div>
                    <strong>Data Anda baru {{ $kelengkapan }}% lengkap.</strong>
                    Belum diisi: {{ implode(', ', array_slice($dataKurang, 0, 5)) }}{{ count($dataKurang) > 5 ? ', +' . (count($dataKurang) - 5) . ' lainnya' : '' }}.
                </div>
                <a href="{{ route('pegawai.pengkinian-data') }}" class="btn btn-warning btn-sm text-nowrap"><i class="bi bi-pencil-square me-1"></i>Lengkapi Data</a>
            </div>
        @endif

        @foreach ($seleksiSaya as $seleksi)
            <div class="panel p-3 mb-3 border-start border-4 border-primary">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                    <div>
                        <div class="fw-semibold"><i class="bi bi-person-check me-1"></i>Anda mengikuti seleksi posisi {{ $seleksi->jabatanTujuan() }} ({{ $seleksi->unitKerjaTujuan() }})</div>
                        <div class="small text-muted">Tahap: {{ $seleksi->tahapLabel() }}</div>
                        @foreach ($seleksi->interviewJadwals as $jadwal)
                            <div class="small mt-1">
                                <i class="bi bi-calendar-event me-1"></i><strong>{{ $jadwal->tahapLabel() }}</strong>:
                                {{ $jadwal->tanggal->translatedFormat('l, d F Y') }}, {{ substr($jadwal->waktu_mulai, 0, 5) }} WIB
                                @if ($jadwal->lokasi) &middot; @include('partials.lokasi', ['lokasi' => $jadwal->lokasi, 'tanpaIkon' => true])@endif
                            </div>
                        @endforeach
                    </div>
                    <div>
                        <a href="{{ $seleksi->linkJadwal() }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="bi bi-calendar-week me-1"></i>Lihat Jadwal Lengkap</a>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="row g-3">
            <div class="col-6 col-xl-3">
                <a href="{{ route($isIncomplete ? 'pegawai.pengkinian-data' : 'pegawai.data-pegawai') }}" class="text-reset text-decoration-none">
                    <div class="metric-card metric-primary h-100">
                        <div class="metric-top">
                            <span class="metric-label">Kelengkapan Data</span>
                            <span class="metric-icon"><i class="bi bi-person-vcard"></i></span>
                        </div>
                        <div class="metric-value">{{ $kelengkapan }}%</div>
                        <div class="progress mt-2" style="height:6px;" role="progressbar" aria-valuenow="{{ $kelengkapan }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar {{ $kelengkapan >= 100 ? 'bg-success' : '' }}" style="width: {{ $kelengkapan }}%"></div>
                        </div>
                    </div>
                </a>
            </div>

            @if ($bisaCuti)
                <div class="col-6 col-xl-3">
                    <a href="{{ route('pegawai.pengajuan-cuti') }}" class="text-reset text-decoration-none">
                        <div class="metric-card metric-success h-100">
                            <div class="metric-top">
                                <span class="metric-label">Sisa Cuti Tahunan</span>
                                <span class="metric-icon"><i class="bi bi-calendar2-check"></i></span>
                            </div>
                            <div class="metric-value">{{ $sisaCuti }} <small class="fs-6 text-muted">/ {{ $kuotaCuti }} hari</small></div>
                            <div class="metric-meta">
                                <span class="{{ $masaKerjaCukup ? 'text-success' : 'text-warning' }}">
                                    {{ $masaKerjaCukup ? "Pengajuan {$pengajuanCutiTahunIni}/{$maxPengajuanCuti} kali tahun ini" : 'Masa kerja belum 1 tahun' }}
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            @endif

            @if ($bisaLembur)
                <div class="col-6 col-xl-3">
                    <a href="{{ route('pegawai.pengajuan-lembur') }}" class="text-reset text-decoration-none">
                        <div class="metric-card metric-prpl h-100">
                            <div class="metric-top">
                                <span class="metric-label">Lembur Bulan Ini</span>
                                <span class="metric-icon"><i class="bi bi-clock-history"></i></span>
                            </div>
                            <div class="metric-value">{{ $jam($lemburBulanIni) }} <small class="fs-6 text-muted">jam</small></div>
                            <div class="metric-meta"><span class="text-muted">yang sudah disetujui</span></div>
                        </div>
                    </a>
                </div>
            @endif

            <div class="col-6 col-xl-3">
                <div class="metric-card metric-warning h-100">
                    <div class="metric-top">
                        <span class="metric-label">Pengajuan Menunggu</span>
                        <span class="metric-icon"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                    <div class="metric-value">{{ $pending }}</div>
                    <div class="metric-meta"><span class="text-muted">cuti & lembur belum diputuskan</span></div>
                </div>
            </div>
        </div>

        @include('partials.pengumuman-ringkas', ['pengumuman' => $pengumuman, 'kolom' => 'col-12 col-md-4'])

        <div class="row g-3 mt-1">
            <div class="col-lg-8 d-flex flex-column gap-3">
                <div class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="h5 mb-1 section-title"><i class="bi bi-send"></i><span>Pengajuan Terbaru</span></h2>
                            <p class="text-muted mb-0">Status cuti & lembur yang Anda ajukan.</p>
                        </div>
                    </div>
                    @if (! $bisaCuti && ! $bisaLembur)
                        <p class="text-muted small mb-0">Pengajuan cuti & lembur unit Anda diurus secara internal (tidak lewat sistem).</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr><th>Jenis</th><th>Tanggal</th><th>Detail</th><th class="text-end">Status</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($terbaru as $t)
                                        <tr>
                                            <td><i class="bi {{ $t['ikon'] }} me-1 text-muted"></i>{{ $t['jenis'] }}</td>
                                            <td>{{ $t['tanggal']?->translatedFormat('d M Y') }}</td>
                                            <td class="text-muted small">
                                                {{ $t['detail'] }}
                                                @if ($t['catatan'])<span class="d-block text-danger">{{ \Illuminate\Support\Str::limit($t['catatan'], 60) }}</span>@endif
                                            </td>
                                            <td class="text-end">
                                                <span class="badge {{ $t['badge'] }}">{{ $t['status'] }}</span>
                                                @if ($t['tahap'])<span class="d-block small text-muted">di {{ $t['tahap'] }}</span>@endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada pengajuan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                @if ($trenLembur)
                    <div class="panel">
                        <h2 class="h6 fw-bold mb-1"><i class="bi bi-bar-chart me-1"></i>Jam Lembur Saya</h2>
                        <p class="small text-muted mb-2">6 bulan terakhir, yang sudah disetujui.</p>
                        @include('partials.viz.kolom', ['items' => $trenLembur, 'satuan' => 'jam', 'judulGrafik' => 'Lembur'])
                    </div>
                @endif
            </div>

            <div class="col-lg-4 d-flex flex-column gap-3">
                <div class="panel">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-file-earmark-text me-1"></i>Status Kepegawaian</h2>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted fw-normal">Status</dt><dd class="col-7">{{ $kep?->status_kerja ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Tanggal masuk</dt><dd class="col-7">{{ $kep?->tanggal_masuk?->translatedFormat('d M Y') ?? '-' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Masa kerja</dt><dd class="col-7">{{ $masaKerja }}</dd>
                        <dt class="col-5 text-muted fw-normal">Lokasi</dt><dd class="col-7">{{ $kep?->lokasi_kerja ?? '-' }}</dd>
                    </dl>
                    @if ($kontrak)
                        <div class="mt-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Kontrak s/d <strong>{{ $kontrak['habis']->translatedFormat('d M Y') }}</strong></span>
                                <span class="{{ $kontrak['sisa'] < 0 ? 'text-danger' : ($kontrak['sisa'] <= 60 ? 'text-warning fw-semibold' : 'text-muted') }}">
                                    {{ $kontrak['sisa'] < 0 ? 'berakhir' : $kontrak['sisa'] . ' hari lagi' }}
                                </span>
                            </div>
                            <div class="progress" style="height:6px;" role="progressbar" aria-label="Masa kontrak berjalan" aria-valuenow="{{ $kontrak['persen'] }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar {{ $kontrak['sisa'] <= 60 ? 'bg-warning' : '' }}" style="width: {{ $kontrak['persen'] }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>

                @include('partials.jadwal-ttd-kontrak')
                <div class="panel">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-building me-1"></i>Unit Saya</h2>
                    @if ($unitSaya)
                        <div class="d-flex gap-2 mb-3">
                            <div class="dash-mini-stat viz flex-fill">
                                <div class="n">{{ $unitSaya['jumlahRekan'] }}</div>
                                <div class="l">rekan di {{ $unitSaya['nama'] }}</div>
                            </div>
                            <div class="dash-mini-stat viz flex-fill">
                                <div class="n text-truncate" style="font-size:.9rem;" title="{{ $unitSaya['kepala'] }}">{{ $unitSaya['kepala'] ? \Illuminate\Support\Str::of($unitSaya['kepala'])->before(',') : '-' }}</div>
                                <div class="l">Kepala unit</div>
                            </div>
                        </div>
                    @endif
                    @foreach ($penugasan as $p)
                        <div class="d-flex justify-content-between align-items-center small mb-2">
                            <span><span class="fw-semibold">{{ $p->unit_kerja }}</span><span class="text-muted d-block">{{ $p->jabatan ?? '-' }}</span></span>
                            <span class="badge {{ $p->is_utama ? 'text-bg-primary' : 'text-bg-info' }}">{{ $p->is_utama ? 'Utama' : 'Rangkap' }}</span>
                        </div>
                    @endforeach

                    @if ($unitSaya && $unitSaya['cutiHariIni']->isNotEmpty())
                        <hr class="my-2">
                        <p class="small fw-semibold mb-1"><i class="bi bi-calendar-x me-1"></i>Rekan yang cuti hari ini</p>
                        @foreach ($unitSaya['cutiHariIni'] as $c)
                            <div class="small d-flex justify-content-between"><span>{{ $c->dataPegawai?->nama_lengkap }}</span><span class="text-muted">s/d {{ $c->tanggal_selesai->translatedFormat('d M') }}</span></div>
                        @endforeach
                    @endif

                </div>

                <div class="panel">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-lightning me-1"></i>Menu Cepat</h2>
                    <div class="d-grid gap-2">
                        @if ($bisaCuti)
                            <a href="{{ route('pegawai.pengajuan-cuti') }}" class="btn btn-outline-primary btn-sm text-start"><i class="bi bi-calendar2-plus me-2"></i>Ajukan Cuti</a>
                        @endif
                        @if ($bisaLembur)
                            <a href="{{ route('pegawai.pengajuan-lembur') }}" class="btn btn-outline-primary btn-sm text-start"><i class="bi bi-clock me-2"></i>Ajukan Lembur</a>
                        @endif
                        <a href="{{ route('pegawai.data-pegawai') }}" class="btn btn-outline-secondary btn-sm text-start"><i class="bi bi-person-vcard me-2"></i>Data Pegawai Saya</a>
                        <a href="{{ route('profile') }}" class="btn btn-outline-secondary btn-sm text-start"><i class="bi bi-person-lines-fill me-2"></i>Profil & Password</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
