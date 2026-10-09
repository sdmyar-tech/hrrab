@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $durasi = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m);
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-calendar3"></i></span>
                <div>
                    <p class="eyebrow mb-1"><a href="{{ route('admin.absensi.harian') }}" class="text-reset">Absensi</a> / Riwayat Pegawai</p>
                    <h1 class="h3 mb-1">{{ $pegawai->nama_lengkap }}</h1>
                    <p class="text-muted mb-0">
                        {{ $pegawai->kepegawaian?->unit_kerja ?? '-' }} · ID mesin {{ $pegawai->finger_id ?? 'belum ada' }}
                        · {{ $pegawai->jadwalKerja ? 'Jam kerja ' . $pegawai->jadwalKerja->ringkas() : 'Jam kerja belum diatur' }}
                    </p>
                </div>
            </div>
            @if ($bisaKelola)
                <div class="heading-actions">
                    <a href="{{ route('admin.absensi.pengaturan', ['q' => $pegawai->nip ?: $pegawai->nama_lengkap]) }}" class="btn btn-outline-secondary">
                        <i class="bi {{ $ik['pengaturan'] }} me-1"></i> Ubah Jam Kerja
                    </a>
                </div>
            @endif
        </div>

        @include('admin.absensi._nav')

        @unless ($pegawai->ikut_absensi)
            <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle me-1"></i>Pegawai ini <strong>tidak ikut absensi</strong>, sehingga tidak muncul di Absensi Harian & Rekap.</div>
        @endunless

        {{-- Periode --}}
        <form method="GET" class="panel p-2 p-md-3 mb-3 d-flex flex-wrap align-items-end gap-2">
            <div>
                <label class="form-label small mb-1" for="dari">Dari</label>
                <input type="date" name="dari" id="dari" class="form-control form-control-sm" value="{{ $dari->toDateString() }}">
            </div>
            <div>
                <label class="form-label small mb-1" for="sampai">Sampai</label>
                <input type="date" name="sampai" id="sampai" class="form-control form-control-sm" value="{{ $sampai->toDateString() }}">
            </div>
            <button class="btn btn-primary btn-sm">Tampilkan</button>
            <div class="d-flex flex-wrap gap-1 ms-md-2">
                @foreach ($preset as $label => [$pDari, $pSampai])
                    <a href="{{ route('admin.absensi.pegawai', ['pegawai' => $pegawai, 'dari' => $pDari->toDateString(), 'sampai' => $pSampai->toDateString()]) }}"
                        class="abs-chip text-decoration-none {{ $dari->isSameDay($pDari) && $sampai->isSameDay($pSampai) ? 'aktif' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </form>

        <div class="row g-2 mb-3">
            @foreach ([
                ['Hadir', $rekap['hadir_total'] . ' / ' . $rekap['hari_kerja'] . ' hari', 'bi-person-check', 'success'],
                ['Telat', $rekap['telat'] . ' kali' . ($rekap['telat'] ? ' · ' . $durasi($rekap['telat_menit']) : ''), 'bi-alarm', 'danger'],
                ['Alpa', $rekap['alpa'] . ' hari', 'bi-x-octagon', 'dark'],
                ['Lembur', $durasi($rekap['lembur_menit']) . ($rekap['lembur_surat_menit'] ? ' dari ' . $durasi($rekap['lembur_surat_menit']) : ''), $ik['lembur'], 'purple'],
            ] as [$label, $nilai, $ikon, $warna])
                <div class="col-6 col-md-3">
                    <div class="panel p-3 h-100 d-flex align-items-center gap-3">
                        <span class="absensi-kartu-ikon {{ $warna }}"><i class="bi {{ $ikon }}"></i></span>
                        <span><span class="fw-bold d-block">{{ $nilai }}</span><small class="text-muted">{{ $label }}</small></span>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="panel">
            <div class="panel-header flex-wrap gap-2">
                <ul class="nav nav-pills absensi-nav border-0 p-0 m-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabKalender" type="button" role="tab"><i class="bi bi-grid-3x3-gap me-1"></i>Kalender</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDaftar" type="button" role="tab"><i class="bi bi-list-ul me-1"></i>Daftar</button>
                    </li>
                </ul>
                @if ($bisaKelola)<small class="text-muted"><i class="bi bi-hand-index me-1"></i>Klik tanggal untuk mengoreksi.</small>@endif
            </div>
            <div class="tab-content">
                <div class="tab-pane fade show active p-3 p-md-4 pt-md-2" id="tabKalender" role="tabpanel">
                    @include('admin.absensi._kalender', ['hari' => $hari, 'dari' => $dari, 'sampai' => $sampai, 'pegawai' => $pegawai, 'bisaKoreksi' => $bisaKelola])
                </div>
                <div class="tab-pane fade" id="tabDaftar" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Tanggal</th><th>Masuk → Pulang</th><th>Keterangan</th>@if ($bisaKelola)<th class="text-end">Aksi</th>@endif</tr></thead>
                            <tbody>
                                @foreach ($hari as $h)
                                    <tr class="{{ in_array($h['status'], ['libur', 'belum'], true) ? 'absensi-hari-libur' : '' }}">
                                        <td class="text-nowrap"><span class="fw-semibold">{{ $h['tanggal']->translatedFormat('d M Y') }}</span><small class="text-muted d-block">{{ $h['tanggal']->translatedFormat('l') }}</small></td>
                                        <td>
                                            <span class="abs-jam">@if ($h['masuk']){{ $h['masuk'] }}@else<span class="kosong">–</span>@endif <span class="panah">→</span> @if ($h['keluar']){{ $h['keluar'] }}@else<span class="kosong">–</span>@endif</span>
                                            <div class="abs-jadwal">{{ $h['jadwal'] ? 'Jam kerja ' . $h['jadwal']['masuk'] . '–' . $h['jadwal']['pulang'] : 'Bukan hari kerja' }}</div>
                                        </td>
                                        <td>
                                            @include('admin.absensi._status', ['h' => $h, 'catatanPenuh' => true])
                                            @if ($h['dikoreksi'])
                                                <small class="text-muted d-block mt-1"><i class="bi bi-pencil-square me-1"></i>{{ $h['absensi']->alasan_koreksi }} — {{ $h['absensi']->dikoreksiOleh?->name ?? 'SDM' }}</small>
                                            @endif
                                        </td>
                                        @if ($bisaKelola)
                                            <td class="text-end">@include('admin.absensi._tombol-koreksi', ['pegawai' => $pegawai, 'h' => $h, 'tombolKecil' => true])</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

@if ($bisaKelola)
    @include('admin.absensi._koreksi')
@endif
@include('admin.absensi._gaya')
@endsection
