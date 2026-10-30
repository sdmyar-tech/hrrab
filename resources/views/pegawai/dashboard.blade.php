@extends('layouts.pegawai.app')

@section('title', 'Dashboard')

@section('content')
@include('partials.viz.style')
@php
    $kep = $pegawai?->kepegawaian;
    $jam = fn ($n) => rtrim(rtrim(number_format($n, 1, ',', '.'), '0'), ',');
    $namaSapa = \Illuminate\Support\Str::of($user->name)->before(',');
    $fotoPegawai = $pegawai?->dokumen?->pas_foto ? \App\Support\Berkas::url($pegawai->dokumen->pas_foto) : null;
    $ikutAbsensi = (bool) $pegawai?->ikut_absensi;
    $bisaIzinKeluar = $user->can('ajukan-izin-keluar');

    // Aksi cepat: hanya yang boleh dipakai pengguna ini.
    $aksiCepat = array_values(array_filter([
        $bisaCuti ? ['Ajukan Cuti', 'Sisa ' . $sisaCuti . ' hari', 'bi-calendar-plus', 'success', route('pegawai.pengajuan-cuti')] : null,
        $bisaLembur ? ['Ajukan Lembur', 'Dengan surat tugas', 'bi-stopwatch', 'info', route('pegawai.pengajuan-lembur')] : null,
        $bisaIzinKeluar ? ['Izin Keluar', 'Keluar saat jam kerja', 'bi-door-open', 'warning', route('pegawai.izin-keluar')] : null,
        $ikutAbsensi ? ['Absensi Saya', 'Riwayat kehadiran', 'bi-fingerprint', 'primary', route('absensi.saya')] : null,
        ['Data Diri', $isIncomplete ? "Baru {$kelengkapan}% lengkap" : 'Sudah lengkap', 'bi-person-vcard', $isIncomplete ? 'danger' : 'neutral', route($isIncomplete ? 'pegawai.pengkinian-data' : 'pegawai.data-pegawai')],
        ['Kalender', 'Libur & agenda', 'bi-calendar3', 'neutral', route('kalender.index')],
    ]));
@endphp
<main class="dashboard-content">
    <div class="container-fluid">

        {{-- ============ Kepala: sapaan + identitas ============ --}}
        <x-page-header :eyebrow="'Dashboard Pegawai · ' . now()->translatedFormat('l, d F Y')" :title="'Halo, ' . $namaSapa"
            :subtitle="($kep?->jabatan ?? 'Jabatan belum diisi') . ' · ' . ($kep?->unit_kerja ?? '-') . ($pegawai?->nip ? ' · NIP ' . $pegawai->nip : '')">
            <x-slot:media><x-avatar :src="$fotoPegawai" :name="$user->name" size="xl" /></x-slot:media>
            <x-badge tone="primary" icon="bi-briefcase">{{ $kep?->status_kerja ?? 'Status belum diisi' }}</x-badge>
            <x-badge tone="neutral" icon="bi-hourglass-split">Masa kerja {{ $masaKerja }}</x-badge>
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'pegawai.dashboard.export', 'eksporTeks' => 'Ekspor'])
                @if ($bisaCuti)
                    <x-button :href="route('pegawai.pengajuan-cuti')" icon="bi-calendar-plus">Ajukan Cuti</x-button>
                @endif
            </x-slot:actions>
        </x-page-header>

        @include('partials.jadwal-ttd-kontrak')

        @if ($isIncomplete)
            <x-alert tone="warning" :title="'Data Anda baru ' . $kelengkapan . '% lengkap'" class="mb-4">
                Belum diisi: {{ implode(', ', array_slice($dataKurang, 0, 5)) }}{{ count($dataKurang) > 5 ? ', +' . (count($dataKurang) - 5) . ' lainnya' : '' }}.
                <x-slot:action>
                    <x-button size="sm" variant="secondary" :href="route('pegawai.pengkinian-data')" icon="bi-pencil-square">Lengkapi Data</x-button>
                </x-slot:action>
            </x-alert>
        @endif

        @foreach ($seleksiSaya as $seleksi)
            <x-alert tone="info" icon="bi-person-check-fill" class="mb-4"
                :title="'Anda mengikuti seleksi ' . $seleksi->jabatanTujuan() . ' (' . $seleksi->unitKerjaTujuan() . ')'">
                <span class="d-block">Tahap: {{ $seleksi->tahapLabel() }}</span>
                @foreach ($seleksi->interviewJadwals as $jadwal)
                    <span class="d-block mt-1">
                        <i class="bi bi-calendar-event me-1" aria-hidden="true"></i><strong>{{ $jadwal->tahapLabel() }}</strong>:
                        {{ $jadwal->tanggal->translatedFormat('l, d F Y') }}, {{ substr($jadwal->waktu_mulai, 0, 5) }} WIB
                        @if ($jadwal->lokasi) · @include('partials.lokasi', ['lokasi' => $jadwal->lokasi, 'tanpaIkon' => true])@endif
                    </span>
                @endforeach
                <x-slot:action>
                    <x-button size="sm" variant="secondary" :href="$seleksi->linkJadwal()" target="_blank" rel="noopener" icon="bi-calendar-week">Lihat Jadwal</x-button>
                </x-slot:action>
            </x-alert>
        @endforeach

        {{-- ============ 1. Kartu angka ============ --}}
        <section class="row g-3" aria-label="Ringkasan saya">
            @if ($bisaCuti)
                <div class="col-6 col-xl-3">
                    <x-stat-card label="Sisa Cuti Tahunan" :value="$sisaCuti" :unit="'/ ' . $kuotaCuti . ' hari'" icon="bi-calendar2-check" tone="success"
                        :meta="$masaKerjaCukup ? 'Pengajuan ' . $pengajuanCutiTahunIni . '/' . $maxPengajuanCuti . ' kali tahun ini' : 'Masa kerja belum 1 tahun'"
                        :href="route('pegawai.pengajuan-cuti')" />
                </div>
            @endif
            @if ($bisaLembur)
                <div class="col-6 col-xl-3">
                    <x-stat-card label="Lembur Bulan Ini" :value="$jam($lemburBulanIni)" unit="jam" icon="bi-clock-history" tone="info"
                        meta="Yang sudah disetujui" :href="route('pegawai.pengajuan-lembur')" />
                </div>
            @endif
            <div class="col-6 col-xl-3">
                <x-stat-card label="Pengajuan Menunggu" :value="$pending" icon="bi-hourglass-split" :tone="$pending ? 'warning' : 'neutral'"
                    meta="Cuti & lembur belum diputuskan" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat-card label="Kelengkapan Data" :value="$kelengkapan" unit="%" icon="bi-person-vcard" :tone="$kelengkapan >= 100 ? 'success' : 'danger'"
                    :href="route($isIncomplete ? 'pegawai.pengkinian-data' : 'pegawai.data-pegawai')">
                    <div class="progress mt-3" style="height: 6px;" role="progressbar" aria-label="Kelengkapan data" aria-valuenow="{{ $kelengkapan }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar {{ $kelengkapan >= 100 ? 'bg-success' : 'bg-danger' }}" style="width: {{ $kelengkapan }}%"></div>
                    </div>
                </x-stat-card>
            </div>
        </section>

        {{-- ============ Aksi cepat ============ --}}
        <section class="hr-section" aria-labelledby="judul-aksi">
            <div class="hr-section-head">
                <h2 class="hr-section-title" id="judul-aksi">Aksi Cepat</h2>
            </div>
            <div class="hr-aksi-cepat">
                @foreach ($aksiCepat as [$labelAksi, $ketAksi, $ikonAksi, $toneAksi, $urlAksi])
                    <a href="{{ $urlAksi }}" class="hr-aksi-ubin">
                        <span class="hr-stat-ikon hr-tone-{{ $toneAksi }}"><i class="bi {{ $ikonAksi }}" aria-hidden="true"></i></span>
                        <span class="min-w-0">{{ $labelAksi }}<small>{{ $ketAksi }}</small></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- ============ Agenda & pengumuman mendatang ============ --}}
        @if ($pengumuman->isNotEmpty())
            <div class="hr-section">
                @include('partials.pengumuman-ringkas', ['pengumuman' => $pengumuman, 'kolom' => 'col-12 col-md-4'])
            </div>
        @endif

        {{-- ============ 2. Grafik & status ============ --}}
        <section class="row g-3 hr-section">
            @if ($trenLembur)
                <div class="col-lg-7 col-xl-8">
                    <x-card title="Jam Lembur Saya" subtitle="6 bulan terakhir, yang sudah disetujui." icon="bi-bar-chart" class="h-100">
                        <x-slot:actions><span class="small text-muted">Total <strong class="text-body">{{ $jam(collect($trenLembur)->sum('jumlah')) }} jam</strong></span></x-slot:actions>
                        @include('partials.viz.kolom', ['items' => $trenLembur, 'satuan' => 'jam', 'judulGrafik' => 'Lembur'])
                    </x-card>
                </div>
            @endif
            <div class="{{ $trenLembur ? 'col-lg-5 col-xl-4' : 'col-12' }}">
                <x-card title="Status Kepegawaian" icon="bi-file-earmark-text" class="h-100">
                    <dl class="dash-dl">
                        <div><dt>Status</dt><dd>{{ $kep?->status_kerja ?? '-' }}</dd></div>
                        <div><dt>Tanggal masuk</dt><dd>{{ $kep?->tanggal_masuk?->translatedFormat('d M Y') ?? '-' }}</dd></div>
                        <div><dt>Masa kerja</dt><dd>{{ $masaKerja }}</dd></div>
                        <div><dt>Lokasi</dt><dd>{{ $kep?->lokasi_kerja ?? '-' }}</dd></div>
                    </dl>
                    @if ($kontrak)
                        <div class="dash-kontrak">
                            <div class="d-flex justify-content-between gap-2 small mb-2">
                                <span>Kontrak s/d <strong>{{ $kontrak['habis']->translatedFormat('d M Y') }}</strong></span>
                                @if ($kontrak['sisa'] < 0)
                                    <x-badge tone="danger">Berakhir</x-badge>
                                @else
                                    <x-badge :tone="$kontrak['sisa'] <= 60 ? 'warning' : 'neutral'">{{ $kontrak['sisa'] }} hari lagi</x-badge>
                                @endif
                            </div>
                            <div class="progress" style="height: 6px;" role="progressbar" aria-label="Masa kontrak berjalan" aria-valuenow="{{ $kontrak['persen'] }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar {{ $kontrak['sisa'] <= 60 ? 'bg-warning' : '' }}" style="width: {{ $kontrak['persen'] }}%"></div>
                            </div>
                        </div>
                    @endif
                </x-card>
            </div>
        </section>

        {{-- ============ 3. Tabel & bagian ============ --}}
        <section class="row g-3 hr-section">
            <div class="col-lg-7 col-xl-8">
                <x-card title="Pengajuan Terbaru" subtitle="Status cuti & lembur yang Anda ajukan." icon="bi-send" :padding="! ($bisaCuti || $bisaLembur)" class="h-100">
                    @if (! $bisaCuti && ! $bisaLembur)
                        <x-empty-state icon="bi-info-circle" compact>Pengajuan cuti & lembur bagian Anda diurus secara internal (tidak lewat sistem).</x-empty-state>
                    @else
                        <x-table caption="Pengajuan terbaru">
                            <x-slot:head>
                                <tr><th>Jenis</th><th>Tanggal</th><th>Detail</th><th class="text-end">Status</th></tr>
                            </x-slot:head>
                            @forelse ($terbaru as $t)
                                <tr>
                                    <td class="text-nowrap"><i class="bi {{ $t['ikon'] }} me-2 text-muted" aria-hidden="true"></i>{{ $t['jenis'] }}</td>
                                    <td class="text-nowrap">{{ $t['tanggal']?->translatedFormat('d M Y') }}</td>
                                    <td class="text-muted">
                                        {{ $t['detail'] }}
                                        @if ($t['catatan'])<span class="d-block small text-danger">{{ \Illuminate\Support\Str::limit($t['catatan'], 60) }}</span>@endif
                                    </td>
                                    <td class="text-end">
                                        <span class="badge {{ $t['badge'] }}">{{ $t['status'] }}</span>
                                        @if ($t['tahap'])<span class="d-block small text-muted mt-1">di {{ $t['tahap'] }}</span>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <x-empty-state icon="bi-inbox" title="Belum ada pengajuan" compact>
                                            Pengajuan cuti & lembur Anda akan tampil di sini.
                                            @if ($bisaCuti)
                                                <x-slot:action><x-button size="sm" :href="route('pegawai.pengajuan-cuti')" icon="bi-calendar-plus">Ajukan Cuti</x-button></x-slot:action>
                                            @endif
                                        </x-empty-state>
                                    </td>
                                </tr>
                            @endforelse
                        </x-table>
                    @endif
                </x-card>
            </div>

            <div class="col-lg-5 col-xl-4">
                <x-card title="Bagian Saya" icon="bi-building" class="h-100">
                    @if ($unitSaya)
                        <div class="dash-mini-grid mb-3">
                            <div><strong>{{ $unitSaya['jumlahRekan'] }}</strong><span>rekan di {{ $unitSaya['nama'] }}</span></div>
                            <div><strong class="text-truncate d-block" title="{{ $unitSaya['kepala'] }}">{{ $unitSaya['kepala'] ? \Illuminate\Support\Str::of($unitSaya['kepala'])->before(',') : '-' }}</strong><span>Kepala bagian</span></div>
                        </div>
                    @endif
                    <ul class="hr-list">
                        @forelse ($penugasan as $p)
                            <li class="hr-list-item">
                                <span class="hr-list-utama">
                                    <span class="hr-list-judul">{{ $p->unit_kerja }}</span>
                                    <span class="hr-list-sub">{{ $p->jabatan ?? '-' }}</span>
                                </span>
                                <x-badge :tone="$p->is_utama ? 'primary' : 'info'">{{ $p->is_utama ? 'Utama' : 'Rangkap' }}</x-badge>
                            </li>
                        @empty
                            <li><x-empty-state icon="bi-building" compact>Belum ada penugasan aktif.</x-empty-state></li>
                        @endforelse
                    </ul>

                    @if ($unitSaya && $unitSaya['cutiHariIni']->isNotEmpty())
                        <p class="small fw-semibold mt-4 mb-2"><i class="bi bi-calendar-x me-1" aria-hidden="true"></i>Rekan yang cuti hari ini</p>
                        <ul class="hr-list">
                            @foreach ($unitSaya['cutiHariIni'] as $c)
                                <li class="hr-list-item">
                                    <x-avatar :name="$c->dataPegawai?->nama_lengkap ?? '-'" size="sm" />
                                    <span class="hr-list-utama"><span class="hr-list-judul">{{ $c->dataPegawai?->nama_lengkap }}</span></span>
                                    <span class="hr-list-ekor">s/d {{ $c->tanggal_selesai->translatedFormat('d M') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            </div>
        </section>
    </div>
</main>
@endsection
