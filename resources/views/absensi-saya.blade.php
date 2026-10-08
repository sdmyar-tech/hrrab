@extends($layout)

@section('content')
@php $durasi = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m); @endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-fingerprint"></i></span>
                <div>
                    <p class="eyebrow mb-1">Data Saya</p>
                    <h1 class="h3 mb-1">Absensi Saya</h1>
                    <p class="text-muted mb-0">
                        {{ $bulan->translatedFormat('F Y') }}
                        @if ($pegawai->jadwalKerja) &middot; Jam kerja {{ $pegawai->jadwalKerja->ringkas() }} &middot; toleransi {{ $pegawai->jadwalKerja->toleransi() }} menit @endif
                    </p>
                </div>
            </div>
            <div class="heading-actions d-flex gap-1">
                <a href="{{ route('absensi.saya', ['bulan' => $bulan->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary" title="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
                <a href="{{ route('absensi.saya', ['bulan' => $bulan->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary {{ $bulan->copy()->addMonth()->gt(now()) ? 'disabled' : '' }}" title="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>

        @unless ($pegawai->ikut_absensi)
            @include('partials.kosong', ['teks' => 'Anda tidak termasuk pegawai yang diabsen melalui mesin fingerprint.', 'ikonKosong' => 'bi-fingerprint'])
        @else
            <div class="row g-2 g-md-3 mb-3">
                @foreach ([
                    ['Hadir', $rekap['hadir_total'] . ' / ' . $rekap['hari_kerja'] . ' hari', 'bi-person-check', 'success'],
                    ['On Time', $rekap['on_time'] . ' hari', 'bi-check-circle', 'success'],
                    ['Telat', $rekap['telat'] . ' kali' . ($rekap['telat'] ? ' · ' . $durasi($rekap['telat_menit']) : ''), 'bi-alarm', 'danger'],
                    ['Lembur', $durasi($rekap['lembur_menit']), 'bi-clock-history', 'primary'],
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
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Tanggal</th><th>Jadwal</th><th>Masuk</th><th>Keluar</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($hari as $h)
                                <tr class="{{ in_array($h['status'], ['libur', 'belum'], true) ? 'absensi-hari-libur' : '' }}">
                                    <td class="text-nowrap"><span class="fw-semibold">{{ $h['tanggal']->translatedFormat('d M') }}</span> <small class="text-muted">{{ $h['tanggal']->translatedFormat('D') }}</small></td>
                                    <td class="small text-nowrap">{{ $h['jadwal'] ? $h['jadwal']['masuk'] . '–' . $h['jadwal']['pulang'] : '–' }}</td>
                                    <td>{{ $h['masuk'] ?? '–' }}</td>
                                    <td>{{ $h['keluar'] ?? '–' }}</td>
                                    <td>
                                        @include('admin.absensi._status', ['h' => $h])
                                        @if ($h['dikoreksi'] && $h['absensi']?->alasan_koreksi)
                                            <small class="text-muted d-block mt-1">Dikoreksi SDM: {{ $h['absensi']->alasan_koreksi }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5">@include('partials.kosong', ['teks' => 'Belum ada data absensi pada bulan ini.', 'ikonKosong' => 'bi-calendar'])</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            <p class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>Data diperbarui setelah SDM mengimport data dari mesin absen. Ada yang keliru? Hubungi bagian SDM.</p>
        @endunless
    </div>
</main>
@include('admin.absensi._gaya')
@endsection
