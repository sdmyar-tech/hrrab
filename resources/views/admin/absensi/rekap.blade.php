@extends($layout)

@section('content')
@php
    $durasi = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m);
    $angka = fn ($n, $kelas = '') => $n ? '<span class="' . $kelas . '">' . $n . '</span>' : '<span class="absensi-nol">–</span>';
    $total = collect($baris)->pluck('r');
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi {{ $ik['rekap'] }}"></i></span>
                <div>
                    <p class="eyebrow mb-1">Absensi</p>
                    <h1 class="h3 mb-1">Rekap Absensi</h1>
                    <p class="text-muted mb-0">Ringkasan kehadiran per pegawai · {{ $dari->translatedFormat('d M Y') }} – {{ $sampai->translatedFormat('d M Y') }}</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.absensi.rekap.export', 'eksporParam' => request()->only(['unit', 'q', 'urut']) + ['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()]])
            </div>
        </div>

        @include('admin.absensi._nav')

        @foreach ($pesanRentang as $p)
            <div class="alert alert-warning small py-2 d-flex gap-2 align-items-center"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ $p }}</span></div>
        @endforeach

        <div class="row g-2 g-md-3 mb-3">
            @foreach ([
                ['Hadir', $total->sum('hadir_total'), 'bi-person-check', 'success'],
                ['Telat', $total->sum('telat'), 'bi-alarm', 'danger'],
                ['Alpa', $total->sum('alpa'), 'bi-x-octagon', 'dark'],
                ['Lembur terverifikasi', $durasi($total->sum('lembur_menit')), $ik['lembur'], 'purple'],
            ] as [$label, $nilai, $ikon, $warna])
                <div class="col-6 col-md-3">
                    <div class="panel p-3 h-100 d-flex align-items-center gap-3">
                        <span class="absensi-kartu-ikon {{ $warna }}"><i class="bi {{ $ikon }}"></i></span>
                        <span><span class="h5 mb-0 d-block">{{ $nilai }}</span><small class="text-muted">{{ $label }}</small></span>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="panel">
            <form method="GET" class="panel-filter px-3 pt-3 pb-2" id="filterRekap">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1" for="dari">Dari</label>
                        <input type="date" name="dari" id="dari" class="form-control form-control-sm" value="{{ $dari->toDateString() }}" max="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1" for="sampai">Sampai</label>
                        <input type="date" name="sampai" id="sampai" class="form-control form-control-sm" value="{{ $sampai->toDateString() }}" max="{{ now()->toDateString() }}">
                    </div>
                    @if (count($unitOpsi) > 1)
                        <div class="col-6 col-md-2">
                            <select name="unit" class="form-select form-select-sm" aria-label="Unit" onchange="this.form.submit()">
                                <option value="">Semua unit</option>
                                @foreach ($unitOpsi as $u)
                                    <option value="{{ $u }}" @selected(request('unit') === $u)>{{ $u }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-12 col-md-4 col-xl-3">
                        @include('partials.daftar-cari', ['cariTeks' => 'Cari pegawai, contoh: Siti', 'cariLabel' => 'Cari rekap menurut nama, NIP, atau unit'])
                    </div>
                    <div class="col-md-auto">@include('partials.urut', ['urutForm' => 'filterRekap'])</div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-1 mt-2">
                    <span class="small text-muted me-1 align-self-center">Periode cepat:</span>
                    @foreach ($preset as $label => [$pDari, $pSampai])
                        <a href="{{ route('admin.absensi.rekap', ['dari' => $pDari->toDateString(), 'sampai' => $pSampai->toDateString()] + request()->only(['unit', 'q', 'urut'])) }}"
                            class="abs-chip text-decoration-none {{ $dari->isSameDay($pDari) && $sampai->isSameDay($pSampai) ? 'aktif' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
                @include('partials.daftar-ringkas', [
                    'ringkasJumlah' => count($baris), 'ringkasSatuan' => 'pegawai',
                    'ringkasFilter' => request()->filled('q') || request()->filled('unit'),
                    'ringkasReset' => route('admin.absensi.rekap', ['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()]),
                ])
            </form>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 absensi-rekap">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th class="text-end" title="Hari kerja sesuai jadwal">Hari Kerja</th>
                            <th class="text-end">Hadir</th>
                            <th class="text-end">On Time</th>
                            <th class="text-end" title="Jumlah hari telat (total menit)">Telat</th>
                            <th class="text-end">Telat Izin</th>
                            <th class="text-end">Pulang Cepat</th>
                            <th class="text-end" title="Tidak scan pulang">Tdk Scan Plg</th>
                            <th class="text-end">Alpa</th>
                            <th class="text-end" title="Cuti / Izin / Sakit / Dinas Luar">Cuti/Izin</th>
                            <th class="text-end" title="Lembur terverifikasi / sesuai surat">Lembur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baris as ['pegawai' => $p, 'r' => $r])
                            <tr>
                                <td>
                                    <a href="{{ route('admin.absensi.pegawai', ['pegawai' => $p, 'dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()]) }}" class="fw-semibold d-block">{{ $p->nama_lengkap }}</a>
                                    <small class="text-muted">{{ $p->kepegawaian?->unit_kerja ?? '-' }}@unless ($p->jadwalKerja) &middot; <span class="text-warning-emphasis">jadwal belum diatur</span>@endunless</small>
                                </td>
                                <td class="text-end absensi-angka">{{ $r['hari_kerja'] }}</td>
                                <td class="text-end absensi-angka fw-semibold">{!! $angka($r['hadir_total']) !!}</td>
                                <td class="text-end absensi-angka">{!! $angka($r['on_time'], 'text-success') !!}</td>
                                <td class="text-end absensi-angka">
                                    {!! $angka($r['telat'], 'text-danger fw-semibold') !!}
                                    @if ($r['telat'])<small class="text-muted d-block">{{ $durasi($r['telat_menit']) }}</small>@endif
                                </td>
                                <td class="text-end absensi-angka">{!! $angka($r['telat_izin']) !!}</td>
                                <td class="text-end absensi-angka">{!! $angka($r['pulang_cepat'], 'text-warning-emphasis') !!}</td>
                                <td class="text-end absensi-angka">{!! $angka($r['tanpa_scan_pulang']) !!}</td>
                                <td class="text-end absensi-angka">{!! $angka($r['alpa'], 'fw-semibold') !!}</td>
                                <td class="text-end absensi-angka">{!! $angka($r['cuti'] + $r['izin'] + $r['sakit'] + $r['dinas_luar']) !!}</td>
                                <td class="text-end absensi-angka">
                                    @if ($r['lembur_surat_menit'])
                                        {{ $durasi($r['lembur_menit']) }}
                                        <small class="text-muted d-block">surat {{ $durasi($r['lembur_surat_menit']) }}@if ($r['lembur_belum']) · <span class="text-warning-emphasis">{{ $r['lembur_belum'] }} belum</span>@endif</small>
                                    @else
                                        <span class="absensi-nol">–</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11">@include('partials.kosong', ['teks' => 'Belum ada pegawai yang ikut absensi.', 'ikonKosong' => $ik['rekap']])</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
@include('admin.absensi._gaya')
@endsection
