@extends($layout)

@section('content')
@php $durasi = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m); @endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-badge"></i></span>
                <div>
                    <p class="eyebrow mb-1">Absensi Pegawai</p>
                    <h1 class="h3 mb-1">{{ $pegawai->nama_lengkap }}</h1>
                    <p class="text-muted mb-0">
                        {{ $pegawai->kepegawaian?->unit_kerja ?? '-' }} &middot; ID mesin {{ $pegawai->finger_id ?? '–' }}
                        &middot; Jadwal: {{ $pegawai->jadwalKerja?->ringkas() ?? 'belum diatur' }}
                        @if ($pegawai->jadwalKerja) &middot; toleransi {{ $pegawai->jadwalKerja->toleransi() }} menit @endif
                    </p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                <a href="{{ route('admin.absensi.rekap', ['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Rekap</a>
                @if ($bisaKelola)
                    <a href="{{ route('admin.absensi.pengaturan', ['q' => $pegawai->nip ?: $pegawai->nama_lengkap]) }}" class="btn btn-outline-primary"><i class="bi bi-sliders me-1"></i> Atur Jadwal</a>
                @endif
            </div>
        </div>

        @unless ($pegawai->ikut_absensi)
            <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle me-1"></i>Pegawai ini <strong>tidak ikut absensi</strong>, sehingga tidak muncul di Absensi Harian & Rekap.</div>
        @endunless

        <div class="row g-2 g-md-3 mb-3">
            @foreach ([
                ['Hadir', $rekap['hadir_total'] . ' / ' . $rekap['hari_kerja'] . ' hari', 'bi-person-check', 'success'],
                ['Telat', $rekap['telat'] . ' kali' . ($rekap['telat'] ? ' · ' . $durasi($rekap['telat_menit']) : ''), 'bi-alarm', 'danger'],
                ['Alpa', $rekap['alpa'] . ' hari', 'bi-x-octagon', 'dark'],
                ['Lembur', $durasi($rekap['lembur_menit']) . ($rekap['lembur_surat_menit'] ? ' / ' . $durasi($rekap['lembur_surat_menit']) : ''), 'bi-clock-history', 'primary'],
            ] as [$label, $nilai, $ikon, $warna])
                <div class="col-6 col-md-3">
                    <div class="panel p-3 h-100 d-flex align-items-center gap-3">
                        <span class="absensi-kartu-ikon text-{{ $warna }}"><i class="bi {{ $ikon }}"></i></span>
                        <span><span class="fw-bold d-block">{{ $nilai }}</span><small class="text-muted">{{ $label }}</small></span>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="panel">
            <form method="GET" class="panel-filter px-3 pt-3 pb-2">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1" for="dari">Dari</label>
                        <input type="date" name="dari" id="dari" class="form-control form-control-sm" value="{{ $dari->toDateString() }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1" for="sampai">Sampai</label>
                        <input type="date" name="sampai" id="sampai" class="form-control form-control-sm" value="{{ $sampai->toDateString() }}">
                    </div>
                    <div class="col-md-auto"><button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Tampilkan</button></div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    @foreach ($preset as $label => [$pDari, $pSampai])
                        <a href="{{ route('admin.absensi.pegawai', ['pegawai' => $pegawai, 'dari' => $pDari->toDateString(), 'sampai' => $pSampai->toDateString()]) }}"
                            class="btn btn-sm {{ $dari->isSameDay($pDari) && $sampai->isSameDay($pSampai) ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jadwal</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Status</th>
                            @if ($bisaKelola)<th class="text-end">Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hari as $h)
                            <tr class="{{ in_array($h['status'], ['libur', 'belum'], true) ? 'absensi-hari-libur' : '' }}">
                                <td class="text-nowrap">
                                    <span class="fw-semibold">{{ $h['tanggal']->translatedFormat('d M Y') }}</span>
                                    <small class="text-muted d-block">{{ $h['tanggal']->translatedFormat('l') }}</small>
                                </td>
                                <td class="small text-nowrap">{{ $h['jadwal'] ? $h['jadwal']['masuk'] . '–' . $h['jadwal']['pulang'] : '–' }}</td>
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
                                    @if ($h['dikoreksi'])
                                        <small class="text-muted d-block mt-1"><i class="bi bi-pencil-square me-1"></i>{{ $h['absensi']->alasan_koreksi }} — {{ $h['absensi']->dikoreksiOleh?->name ?? 'SDM' }}, {{ $h['absensi']->dikoreksi_pada?->translatedFormat('d M Y H:i') }}</small>
                                    @endif
                                    @foreach ($h['catatan'] as $c)
                                        <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>{{ $c }}</small>
                                    @endforeach
                                </td>
                                @if ($bisaKelola)
                                    <td class="text-end">@include('admin.absensi._tombol-koreksi', ['pegawai' => $pegawai, 'h' => $h, 'tombolKecil' => true])</td>
                                @endif
                            </tr>
                        @endforeach
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
