@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $kartu = [
        ['on_time', 'On Time', 'bi-check-circle', 'success'],
        ['telat', 'Telat', 'bi-alarm', 'danger'],
        ['telat_izin', 'Telat Izin', $ik['izin'], 'warning'],
        ['alpa', 'Alpa', 'bi-x-octagon', 'dark'],
        ['cuti_izin', 'Cuti / Izin', 'bi-calendar-x', 'info'],
        ['perlu_cek', 'Perlu Dicek', 'bi-exclamation-triangle', 'purple'],
    ];
    $statusAktif = request('status');
    $urlTanggal = fn ($t) => route('admin.absensi.harian', ['tanggal' => $t] + request()->except(['tanggal', 'page']));
    $urlStatus = fn ($s) => route('admin.absensi.harian', ['tanggal' => $tanggal->toDateString(), 'status' => $statusAktif === $s ? null : $s] + request()->only(['unit', 'q', 'urut']));
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi {{ $ik['harian'] }}"></i></span>
                <div>
                    <p class="eyebrow mb-1">Absensi</p>
                    <h1 class="h3 mb-1">Absensi Harian</h1>
                    <p class="text-muted mb-0">Siapa yang hadir, telat, atau tidak masuk pada tanggal yang dipilih.</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.absensi.harian.export', 'eksporParam' => ['tanggal' => $tanggal->toDateString()]])
                @if ($bisaKelola)
                    <a href="{{ route('admin.absensi.izin', ['tanggal' => $tanggal->toDateString()]) }}" class="btn btn-outline-secondary">
                        <i class="bi {{ $ik['izin'] }} me-1"></i> Beri Izin Telat
                    </a>
                    <a href="{{ route('admin.absensi.import') }}" class="btn btn-primary"><i class="bi {{ $ik['import'] }} me-1"></i> Import Data Mesin</a>
                @endif
            </div>
        </div>

        @include('admin.absensi._nav')
        @include('admin.absensi._langkah', ['setup' => $setup])

        <form method="GET" class="panel p-2 p-md-3 mb-3 d-flex flex-wrap align-items-center gap-2" id="formTanggal">
            @foreach (request()->only(['unit', 'q', 'status', 'urut']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <div class="d-flex align-items-center gap-1">
                <a href="{{ $urlTanggal($tanggal->copy()->subDay()->toDateString()) }}" class="btn btn-light" title="Hari sebelumnya" aria-label="Hari sebelumnya"><i class="bi bi-chevron-left"></i></a>
                <label class="position-relative">
                    <span class="visually-hidden">Pilih tanggal</span>
                    <input type="date" name="tanggal" class="form-control fw-semibold" value="{{ $tanggal->toDateString() }}" max="{{ now()->toDateString() }}" onchange="this.form.submit()">
                </label>
                <a href="{{ $urlTanggal($tanggal->copy()->addDay()->toDateString()) }}" class="btn btn-light {{ $tanggal->gte(now()->startOfDay()) ? 'disabled' : '' }}" title="Hari berikutnya" aria-label="Hari berikutnya"><i class="bi bi-chevron-right"></i></a>
            </div>
            <div>
                <div class="fw-bold">{{ $tanggal->translatedFormat('l, d F Y') }}</div>
                <small class="text-muted">{{ $jumlahPegawai }} pegawai diabsen</small>
            </div>
            <div class="ms-md-auto d-flex flex-wrap gap-2">
                @unless ($tanggal->isToday())
                    <a href="{{ $urlTanggal(now()->toDateString()) }}" class="btn btn-sm btn-outline-secondary">Hari ini</a>
                @endunless
                @if ($dataTerakhir && ! $tanggal->isSameDay(\Carbon\Carbon::parse($dataTerakhir)))
                    <a href="{{ $urlTanggal($dataTerakhir) }}" class="btn btn-sm btn-outline-secondary">Data terakhir ({{ \Carbon\Carbon::parse($dataTerakhir)->translatedFormat('d M') }})</a>
                @endif
            </div>
        </form>

        @if ($pesanTanggal)
            <div class="alert alert-warning small py-2 d-flex gap-2 align-items-center">
                <i class="bi bi-exclamation-triangle-fill"></i><span>{{ $pesanTanggal }}</span>
            </div>
        @elseif ($tanggalOtomatis)
            <div class="alert alert-info small py-2 d-flex gap-2 align-items-center">
                <i class="bi bi-info-circle-fill"></i>
                <span>Data mesin untuk hari ini belum diimport, jadi yang ditampilkan adalah data terakhir: <strong>{{ $tanggal->translatedFormat('d F Y') }}</strong>.</span>
            </div>
        @elseif ($dataTerakhir && $tanggal->gt(\Carbon\Carbon::parse($dataTerakhir)))
            <div class="alert alert-warning small py-2 d-flex gap-2 align-items-center">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Data mesin baru diimport sampai <strong>{{ \Carbon\Carbon::parse($dataTerakhir)->translatedFormat('d M Y') }}</strong>. @if ($bisaKelola)<a href="{{ route('admin.absensi.import') }}">Import data terbaru</a>.@endif</span>
            </div>
        @endif

        {{-- Ringkasan (klik untuk menyaring) --}}
        <div class="row g-2 mb-3">
            @foreach ($kartu as [$k, $label, $ikon, $warna])
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ $urlStatus($k) }}" class="panel p-3 h-100 d-flex align-items-center gap-3 text-reset text-decoration-none absensi-kartu {{ $statusAktif === $k ? 'aktif' : '' }}"
                        title="{{ $statusAktif === $k ? 'Tampilkan semua' : 'Tampilkan ' . $label }}">
                        <span class="absensi-kartu-ikon {{ $warna }}"><i class="bi {{ $ikon }}"></i></span>
                        <span><span class="h4 mb-0 d-block">{{ $hitung[$k] ?? 0 }}</span><small class="text-muted">{{ $label }}</small></span>
                    </a>
                </div>
            @endforeach
        </div>

        <section class="panel">
            <form method="GET" class="panel-filter px-3 pt-3 pb-2" id="filterHarian">
                <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">
                @if ($statusAktif)<input type="hidden" name="status" value="{{ $statusAktif }}">@endif
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" class="form-control border-start-0" placeholder="Cari nama, NIP, ID mesin, unit, atau jabatan..." value="{{ request('q') }}">
                        </div>
                    </div>
                    @if (count($unitOpsi) > 1)
                        <div class="col-md-3">
                            <select name="unit" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Unit">
                                <option value="">Semua unit</option>
                                @foreach ($unitOpsi as $u)<option value="{{ $u }}" @selected(request('unit') === $u)>{{ $u }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-auto">@include('partials.urut', ['urutForm' => 'filterHarian'])</div>
                    @if ($statusAktif || request()->filled('q') || request()->filled('unit'))
                        <div class="col-auto">
                            <a href="{{ route('admin.absensi.harian', ['tanggal' => $tanggal->toDateString()]) }}" class="btn btn-sm btn-link text-secondary"><i class="bi bi-x-circle me-1"></i>Hapus filter</a>
                        </div>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Masuk → Pulang</th>
                            <th>Keterangan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baris as ['pegawai' => $p, 'h' => $h])
                            <tr>
                                <td>
                                    <a href="{{ route('admin.absensi.pegawai', $p) }}" class="fw-semibold d-block">{{ $p->nama_lengkap }}</a>
                                    <small class="text-muted">{{ $p->kepegawaian?->unit_kerja ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="abs-jam">
                                        @if ($h['masuk']){{ $h['masuk'] }}@else<span class="kosong">–</span>@endif
                                        <span class="panah">→</span>
                                        @if ($h['keluar']){{ $h['keluar'] }}@else<span class="kosong">–</span>@endif
                                    </span>
                                    <div class="abs-jadwal">
                                        @if ($h['jadwal'])
                                            Jam kerja {{ $h['jadwal']['masuk'] }}–{{ $h['jadwal']['pulang'] }}
                                        @elseif (! $p->jadwalKerja)
                                            <span class="text-warning-emphasis"><i class="bi bi-exclamation-circle me-1"></i>Jam kerja belum diatur</span>
                                        @else
                                            Bukan hari kerja
                                        @endif
                                        @if ($h['dikoreksi'] && ($h['scan_masuk'] !== $h['masuk'] || $h['scan_keluar'] !== $h['keluar']))
                                            · mesin {{ $h['scan_masuk'] ?? '–' }}→{{ $h['scan_keluar'] ?? '–' }}
                                        @endif
                                    </div>
                                </td>
                                <td>@include('admin.absensi._status', ['h' => $h])</td>
                                <td class="text-end text-nowrap">
                                    @if ($bisaKelola)
                                        @include('admin.absensi._tombol-koreksi', ['pegawai' => $p, 'h' => $h, 'tombolKecil' => true])
                                    @endif
                                    <a href="{{ route('admin.absensi.pegawai', $p) }}" class="btn btn-outline-secondary btn-sm" title="Riwayat absensi {{ $p->nama_lengkap }}" aria-label="Riwayat absensi {{ $p->nama_lengkap }}">
                                        <i class="bi bi-calendar3"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    @if (! $jumlahPegawai)
                                        <div class="abs-kosong-besar">
                                            <i class="bi bi-people d-block mb-2"></i>
                                            <p class="fw-semibold mb-1">Belum ada pegawai yang diabsen</p>
                                            <p class="text-muted small mb-3">Pilih pegawai yang ikut absensi terlebih dahulu.</p>
                                            @if ($bisaKelola)<a href="{{ route('admin.absensi.pengaturan', ['tempel' => 1]) }}" class="btn btn-primary btn-sm">Pilih pegawai</a>@endif
                                        </div>
                                    @else
                                        @include('partials.kosong', ['teks' => 'Tidak ada pegawai dengan keterangan ini.', 'ikonKosong' => 'bi-funnel'])
                                    @endif
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
@endsection
