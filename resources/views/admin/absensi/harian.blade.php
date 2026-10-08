@extends($layout)

@section('content')
@php
    $hitungStatus = fn ($k) => $hitung[$k] ?? 0;
    $kartu = [
        ['on_time', 'On Time', 'bi-check-circle', 'success'],
        ['telat', 'Telat', 'bi-alarm', 'danger'],
        ['telat_izin', 'Telat Izin', 'bi-cloud-drizzle', 'warning'],
        ['alpa', 'Alpa', 'bi-x-octagon', 'dark'],
        ['cuti', 'Cuti', 'bi-calendar-x', 'info'],
    ];
    $kemarin = $tanggal->copy()->subDay()->toDateString();
    $besok = $tanggal->copy()->addDay()->toDateString();
    $perluCek = $baris->filter(fn ($b) => $b['h']['tanpa_scan_pulang'] || $b['h']['lembur_status'] === 'belum')->count();
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-fingerprint"></i></span>
                <div>
                    <p class="eyebrow mb-1">Absensi</p>
                    <h1 class="h3 mb-1">Absensi Harian</h1>
                    <p class="text-muted mb-0">{{ $tanggal->translatedFormat('l, d F Y') }} &middot; {{ $jumlahPegawai }} pegawai ikut absensi</p>
                </div>
            </div>
            @if ($bisaKelola)
                <div class="heading-actions d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.absensi.import') }}" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Import Data Mesin</a>
                </div>
            @endif
        </div>

        @include('admin.absensi._nav')

        @if (! $dataTerakhir)
            <div class="alert alert-info d-flex gap-2 align-items-start">
                <i class="bi bi-info-circle-fill mt-1"></i>
                <div>Belum ada data absensi. @if ($bisaKelola)Mulai dengan <a href="{{ route('admin.absensi.pengaturan') }}">mengatur pegawai & jam kerja</a>, lalu <a href="{{ route('admin.absensi.import') }}">import file dari mesin</a>.@endif</div>
            </div>
        @elseif ($tanggal->gt(\Carbon\Carbon::parse($dataTerakhir)))
            <div class="alert alert-warning d-flex gap-2 align-items-start small">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div>Data mesin terakhir diimport sampai <strong>{{ \Carbon\Carbon::parse($dataTerakhir)->translatedFormat('d M Y') }}</strong> — tanggal ini mungkin belum ada datanya.
                    <a href="{{ route('admin.absensi.harian', ['tanggal' => \Carbon\Carbon::parse($dataTerakhir)->toDateString()] + request()->only('unit')) }}">Lihat tanggal itu</a>.</div>
            </div>
        @endif

        <div class="row g-2 g-md-3 mb-3">
            @foreach ($kartu as [$k, $label, $ikon, $warna])
                <div class="col-6 col-md">
                    <a href="{{ route('admin.absensi.harian', request()->except('status') + ['status' => request('status') === $k ? null : $k]) }}"
                        class="panel p-3 h-100 d-flex align-items-center gap-3 text-reset text-decoration-none absensi-kartu {{ request('status') === $k ? 'aktif' : '' }}">
                        <span class="absensi-kartu-ikon text-{{ $warna }}"><i class="bi {{ $ikon }}"></i></span>
                        <span>
                            <span class="h4 mb-0 d-block">{{ $hitungStatus($k) }}</span>
                            <small class="text-muted">{{ $label }}</small>
                        </span>
                    </a>
                </div>
            @endforeach
        </div>

        <section class="panel">
            <form method="GET" id="filterHarian" class="panel-filter px-3 pt-3 pb-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-auto">
                        <div class="input-group input-group-sm">
                            <a href="{{ route('admin.absensi.harian', ['tanggal' => $kemarin] + request()->except('tanggal')) }}" class="btn btn-outline-secondary" title="Hari sebelumnya"><i class="bi bi-chevron-left"></i></a>
                            <input type="date" name="tanggal" class="form-control" value="{{ $tanggal->toDateString() }}" max="{{ now()->toDateString() }}" onchange="this.form.submit()" aria-label="Tanggal">
                            <a href="{{ route('admin.absensi.harian', ['tanggal' => $besok] + request()->except('tanggal')) }}" class="btn btn-outline-secondary {{ $tanggal->gte(now()->startOfDay()) ? 'disabled' : '' }}" title="Hari berikutnya"><i class="bi bi-chevron-right"></i></a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari nama, NIP, atau ID mesin..." value="{{ request('q') }}">
                    </div>
                    @if (count($unitOpsi) > 1)
                        <div class="col-6 col-md-2">
                            <select name="unit" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua unit</option>
                                @foreach ($unitOpsi as $u)
                                    <option value="{{ $u }}" @selected(request('unit') === $u)>{{ $u }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-6 col-md-2">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua status</option>
                            <option value="perlu_cek" @selected(request('status') === 'perlu_cek')>⚠ Perlu dicek</option>
                            @foreach (\App\Support\Absensi\PenilaianAbsensi::STATUS as $k => [$label])
                                @continue($k === 'belum')
                                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($perluCek)
                        <div class="col-md-auto">
                            <a href="{{ route('admin.absensi.harian', request()->except('status') + ['status' => 'perlu_cek']) }}" class="badge text-bg-warning text-decoration-none">
                                <i class="bi bi-exclamation-triangle me-1"></i>{{ $perluCek }} perlu dicek
                            </a>
                        </div>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Jadwal</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baris as ['pegawai' => $p, 'h' => $h])
                            <tr>
                                <td>
                                    <a href="{{ route('admin.absensi.pegawai', $p) }}" class="fw-semibold d-block">{{ $p->nama_lengkap }}</a>
                                    <small class="text-muted">{{ $p->kepegawaian?->unit_kerja ?? '-' }} &middot; ID {{ $p->finger_id ?? '–' }}</small>
                                </td>
                                <td class="small text-nowrap">
                                    @if ($h['jadwal'])
                                        {{ $h['jadwal']['masuk'] }}–{{ $h['jadwal']['pulang'] }}
                                    @elseif (! $p->jadwalKerja)
                                        <span class="text-warning-emphasis"><i class="bi bi-exclamation-circle me-1"></i>Belum diatur</span>
                                    @else
                                        <span class="text-muted">Bukan hari kerja</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    {{ $h['masuk'] ?? '–' }}
                                    @if ($h['dikoreksi'] && $h['scan_masuk'] !== $h['masuk'])<small class="text-muted d-block">mesin: {{ $h['scan_masuk'] ?? '–' }}</small>@endif
                                </td>
                                <td class="text-nowrap">
                                    {{ $h['keluar'] ?? '–' }}
                                    @if ($h['dikoreksi'] && $h['scan_keluar'] !== $h['keluar'])<small class="text-muted d-block">mesin: {{ $h['scan_keluar'] ?? '–' }}</small>@endif
                                </td>
                                <td>
                                    @include('admin.absensi._status', ['h' => $h])
                                    @foreach ($h['catatan'] as $c)
                                        <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>{{ $c }}</small>
                                    @endforeach
                                </td>
                                <td class="text-end">
                                    @if ($bisaKelola)
                                        @include('admin.absensi._tombol-koreksi', ['pegawai' => $p, 'h' => $h])
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    @include('partials.kosong', [
                                        'teks' => $jumlahPegawai ? 'Tidak ada pegawai yang cocok dengan filter.' : 'Belum ada pegawai yang ikut absensi.',
                                        'ikonKosong' => 'bi-fingerprint',
                                    ])
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

@if ($bisaKelola)
    @include('admin.absensi._koreksi')
@endif
@include('admin.absensi._gaya')
@endsection
