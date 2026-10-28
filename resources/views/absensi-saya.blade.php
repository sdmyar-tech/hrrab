@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $durasi = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m);
    $sampaiTampil = $bulan->copy()->endOfMonth();
    $perhatian = collect($hari)->filter(fn ($h) => in_array($h['status'], ['telat', 'alpa'], true) || $h['tanpa_scan_pulang'] || ($h['pulang_cepat_menit'] && ! $h['pulang_cepat_izin']));
    $bisaMaju = $bulan->copy()->addMonth()->lte(now());
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi {{ $ik['saya'] }}"></i></span>
                <div>
                    <p class="eyebrow mb-1">Data Saya</p>
                    <h1 class="h3 mb-1">Absensi Saya</h1>
                    <p class="text-muted mb-0">
                        @if ($pegawai->jadwalKerja)
                            Jam kerja {{ $pegawai->jadwalKerja->ringkas() }} · toleransi telat {{ $pegawai->jadwalKerja->toleransi() }} menit
                        @else
                            Rekap kehadiran Anda dari mesin absen.
                        @endif
                    </p>
                </div>
            </div>
            @if ($pegawai->ikut_absensi)
                <div class="heading-actions">
                    @include('partials.tombol-ekspor', ['eksporRoute' => 'absensi.saya.export', 'eksporParam' => ['bulan' => $bulan->format('Y-m')]])
                </div>
            @endif
        </div>

        @unless ($pegawai->ikut_absensi)
            <section class="panel">
                <div class="abs-kosong-besar">
                    <i class="bi {{ $ik['saya'] }} d-block mb-2"></i>
                    <p class="fw-semibold mb-1">Anda tidak termasuk pegawai yang diabsen</p>
                    <p class="text-muted small mb-0">Kehadiran Anda tidak dicatat melalui mesin absen. Hubungi bagian SDM bila ini keliru.</p>
                </div>
            </section>
        @else
            <div class="panel p-2 p-md-3 mb-3 d-flex align-items-center gap-2">
                <a href="{{ route('absensi.saya', ['bulan' => $bulan->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-light" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
                <div class="text-center flex-grow-1">
                    <div class="fw-bold">{{ $bulan->translatedFormat('F Y') }}</div>
                    @unless ($bulan->isSameMonth(now()))
                        <a href="{{ route('absensi.saya') }}" class="small">Kembali ke bulan ini</a>
                    @endunless
                </div>
                <a href="{{ route('absensi.saya', ['bulan' => $bulan->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-light {{ $bisaMaju ? '' : 'disabled' }}" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
            </div>

            <div class="row g-2 mb-3">
                @foreach ([
                    ['Hadir', $rekap['hadir_total'] . ($rekap['hari_kerja'] ? ' / ' . $rekap['hari_kerja'] . ' hari' : ' hari'), 'bi-person-check', 'success'],
                    ['On Time', $rekap['on_time'] . ' hari', 'bi-check-circle', 'success'],
                    ['Telat', $rekap['telat'] . ' kali' . ($rekap['telat'] ? ' · ' . $durasi($rekap['telat_menit']) : ''), 'bi-alarm', 'danger'],
                    ['Lembur', $durasi($rekap['lembur_menit']), $ik['lembur'], 'purple'],
                ] as [$label, $nilai, $ikon, $warna])
                    <div class="col-6 col-md-3">
                        <div class="panel p-3 h-100 d-flex align-items-center gap-3">
                            <span class="absensi-kartu-ikon {{ $warna }}"><i class="bi {{ $ikon }}"></i></span>
                            <span><span class="fw-bold d-block">{{ $nilai }}</span><small class="text-muted">{{ $label }}</small></span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-3 align-items-start">
                <div class="col-12 col-xl-8">
                    <section class="panel p-3 p-md-4">
                        @if ($hari)
                            @include('admin.absensi._kalender', ['hari' => $hari, 'dari' => $bulan, 'sampai' => $sampaiTampil])
                        @else
                            @include('partials.kosong', ['teks' => 'Belum ada data absensi pada bulan ini.', 'ikonKosong' => 'bi-calendar'])
                        @endif
                    </section>
                </div>
                <div class="col-12 col-xl-4">
                    <section class="panel p-3 p-md-4">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-exclamation-circle me-1"></i>Perlu Diperhatikan</h2>
                        @forelse ($perhatian as $h)
                            <div class="d-flex justify-content-between gap-2 py-2 border-bottom small">
                                <span>
                                    <span class="fw-semibold d-block">{{ $h['tanggal']->translatedFormat('l, d M') }}</span>
                                    <span class="text-muted">{{ $h['masuk'] ?? '–' }} → {{ $h['keluar'] ?? '–' }}</span>
                                </span>
                                <span class="text-end">@include('admin.absensi._status', ['h' => $h])</span>
                            </div>
                        @empty
                            <p class="small text-success mb-0"><i class="bi bi-emoji-smile me-1"></i>Tidak ada telat, alpa, atau lupa scan bulan ini. Pertahankan!</p>
                        @endforelse
                        <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Data diperbarui setelah SDM mengimport dari mesin absen. Ada yang keliru? Hubungi bagian SDM.</p>
                    </section>
                </div>
            </div>
        @endunless
    </div>
</main>
@include('admin.absensi._gaya')
@endsection
