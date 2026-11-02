@extends($layout)

@section('title', 'Absensi Saya')

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $durasi = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m);
    $sampaiTampil = $bulan->copy()->endOfMonth();
    $perhatian = collect($hari)->filter(fn ($h) => in_array($h['status'], ['telat', 'alpa'], true) || $h['tanpa_scan_pulang'] || ($h['pulang_cepat_menit'] && ! $h['pulang_cepat_izin']));
    $bisaMaju = $bulan->copy()->addMonth()->lte(now());
    $persenHadir = $rekap['hari_kerja'] ? (int) round($rekap['hadir_total'] / $rekap['hari_kerja'] * 100) : null;
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Absensi Saya" eyebrow="Data Saya"
            :subtitle="$pegawai->jadwalKerja ? 'Jam kerja ' . $pegawai->jadwalKerja->ringkas() . ' · toleransi telat ' . $pegawai->jadwalKerja->toleransi() . ' menit' : 'Rekap kehadiran Anda dari mesin absen.'">
            @if ($pegawai->ikut_absensi)
                <x-slot:actions>
                    @include('partials.tombol-ekspor', ['eksporRoute' => 'absensi.saya.export', 'eksporParam' => ['bulan' => $bulan->format('Y-m')]])
                </x-slot:actions>
            @endif
        </x-page-header>

        @unless ($pegawai->ikut_absensi)
            <x-card>
                <x-empty-state :icon="$ik['saya']" title="Anda tidak termasuk pegawai yang diabsen">
                    Kehadiran Anda tidak dicatat melalui mesin absen. Hubungi bagian SDM bila ini keliru.
                </x-empty-state>
            </x-card>
        @else
            {{-- Navigasi bulan --}}
            <div class="hr-bulan-nav mb-4">
                <x-button variant="secondary" size="sm" :href="route('absensi.saya', ['bulan' => $bulan->copy()->subMonth()->format('Y-m')])" icon="bi-chevron-left" aria-label="Bulan sebelumnya" class="btn-ikon" />
                <div class="text-center">
                    <p class="hr-bulan-nav-judul">{{ $bulan->translatedFormat('F Y') }}</p>
                    @unless ($bulan->isSameMonth(now()))
                        <a href="{{ route('absensi.saya') }}" class="small fw-semibold">Kembali ke bulan ini</a>
                    @endunless
                </div>
                @if ($bisaMaju)
                    <x-button variant="secondary" size="sm" :href="route('absensi.saya', ['bulan' => $bulan->copy()->addMonth()->format('Y-m')])" icon="bi-chevron-right" aria-label="Bulan berikutnya" class="btn-ikon" />
                @else
                    <x-button variant="secondary" size="sm" icon="bi-chevron-right" aria-label="Bulan berikutnya belum tersedia" class="btn-ikon" disabled />
                @endif
            </div>

            {{-- Ringkasan --}}
            <section class="row g-3 mb-4" aria-label="Ringkasan kehadiran">
                <div class="col-6 col-xl-3">
                    <x-stat-card label="Hadir" :value="$rekap['hadir_total']" :unit="$rekap['hari_kerja'] ? '/ ' . $rekap['hari_kerja'] . ' hari' : 'hari'" icon="bi-person-check" tone="success"
                        :meta="$persenHadir !== null ? $persenHadir . '% dari hari kerja' : null" />
                </div>
                <div class="col-6 col-xl-3">
                    <x-stat-card label="Tepat Waktu" :value="$rekap['on_time']" unit="hari" icon="bi-check-circle" tone="success" meta="Masuk sebelum batas toleransi" />
                </div>
                <div class="col-6 col-xl-3">
                    <x-stat-card label="Terlambat" :value="$rekap['telat']" unit="kali" icon="bi-alarm" :tone="$rekap['telat'] ? 'warning' : 'neutral'"
                        :meta="$rekap['telat'] ? 'Total ' . $durasi($rekap['telat_menit']) : 'Tidak pernah telat'" />
                </div>
                <div class="col-6 col-xl-3">
                    <x-stat-card label="Lembur" :value="$durasi($rekap['lembur_menit'])" :icon="$ik['lembur']" tone="info" meta="Dari surat lembur" />
                </div>
            </section>

            <div class="row g-4 align-items-start">
                <div class="col-xl-8">
                    <x-card title="Kalender Kehadiran" subtitle="Status per hari: hadir, telat, izin, atau alpa." icon="bi-calendar3">
                        @if ($hari)
                            @include('admin.absensi._kalender', ['hari' => $hari, 'dari' => $bulan, 'sampai' => $sampaiTampil])
                        @else
                            <x-empty-state icon="bi-calendar" title="Belum ada data" compact>Belum ada data absensi pada bulan ini.</x-empty-state>
                        @endif
                    </x-card>
                </div>
                <div class="col-xl-4">
                    <x-card title="Perlu Diperhatikan" icon="bi-exclamation-circle">
                        @if ($perhatian->isNotEmpty())
                            <x-slot:actions><x-badge tone="warning">{{ $perhatian->count() }} hari</x-badge></x-slot:actions>
                        @endif
                        <ul class="hr-list">
                            @forelse ($perhatian as $h)
                                <li class="hr-list-item">
                                    <span class="dash-tanggal">
                                        <span>{{ $h['tanggal']->translatedFormat('D') }}</span>
                                        <strong>{{ $h['tanggal']->format('d') }}</strong>
                                    </span>
                                    <span class="hr-list-utama">
                                        <span class="hr-list-judul">{{ $h['tanggal']->translatedFormat('d F') }}</span>
                                        <span class="hr-list-sub">{{ $h['masuk'] ?? '–' }} → {{ $h['keluar'] ?? '–' }}</span>
                                    </span>
                                    <span class="hr-list-ekor">@include('admin.absensi._status', ['h' => $h])</span>
                                </li>
                            @empty
                                <li>
                                    <x-empty-state icon="bi-emoji-smile" title="Pertahankan!" compact>Tidak ada telat, alpa, atau lupa scan bulan ini.</x-empty-state>
                                </li>
                            @endforelse
                        </ul>
                        <x-slot:footer>
                            <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Data diperbarui setelah SDM mengimport dari mesin absen. Ada yang keliru? Hubungi bagian SDM.</p>
                        </x-slot:footer>
                    </x-card>
                </div>
            </div>
        @endunless
    </div>
</main>
@include('admin.absensi._gaya')
@endsection
