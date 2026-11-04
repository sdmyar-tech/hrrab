@extends('layouts.admin.app')

@section('title', 'Dashboard')

@section('content')
@include('partials.viz.style')
@php
    $paletStatus = ['Tetap' => 1, 'Kontrak' => 2, 'Harian Lepas' => 3, 'Probation' => 4, 'Magang' => 5];
    $paletGender = ['Laki-laki' => 1, 'Perempuan' => 2];
    $adaAgenda = $agenda->isNotEmpty() || $user->bisaMenginterview();
    $jam = fn ($n) => rtrim(rtrim(number_format($n, 1, ',', '.'), '0'), ',');
    $bisaPegawai = $user->can('lihat-tabel-karyawan');
    $toneKartu = ['primary' => 'primary', 'success' => 'success', 'warning' => 'warning', 'danger' => 'danger', 'prpl' => 'info'];

    // Tren: bulan penuh terakhir dibanding bulan sebelumnya (bulan berjalan belum lengkap → tidak dibandingkan).
    $trenPersen = function (array $seri) {
        $kini = $seri[count($seri) - 2] ?? null;
        $lalu = $seri[count($seri) - 3] ?? null;
        if (! $kini || ! $lalu || ! $lalu['jumlah']) {
            return null;
        }

        return ['persen' => round(($kini['jumlah'] - $lalu['jumlah']) / $lalu['jumlah'] * 100, 1), 'kini' => $kini, 'lalu' => $lalu];
    };
@endphp
<main class="dashboard-content">
    <div class="container-fluid">

        {{-- ============ Kepala: sapaan · lingkup · ekspor ============ --}}
        <x-page-header :eyebrow="'Dashboard ' . $user->roleLabel()" :title="'Halo, ' . \Illuminate\Support\Str::of($user->name)->before(',')"
            :subtitle="$judul . ' · ' . now()->translatedFormat('l, d F Y')">
            @if ($unitDipilih)
                <x-badge tone="primary" icon="bi-funnel">Bagian: {{ $unitDipilih }}</x-badge>
                <a href="{{ route('admin.dashboard') }}" class="small fw-semibold">Tampilkan semua bagian</a>
            @endif
            <x-slot:actions>
                @if (count($pilihanUnit) > 1)
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="dash-pilih-unit">
                        <label for="pilih-unit" class="visually-hidden">Tampilkan bagian</label>
                        <div class="input-group hr-input-group">
                            <span class="input-group-text"><i class="bi bi-funnel" aria-hidden="true"></i></span>
                            <select name="unit" id="pilih-unit" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua bagian ({{ count($pilihanUnit) }})</option>
                                @foreach ($pilihanUnit as $u)
                                    <option value="{{ $u }}" @selected($unitDipilih === $u)>{{ $u }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @endif
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.dashboard.export', 'eksporTeks' => 'Ekspor'])
            </x-slot:actions>
        </x-page-header>

        @include('partials.jadwal-ttd-kontrak')

        {{-- ============ 1. Kartu angka ============ --}}
        <section class="row g-3" aria-label="Ringkasan">
            @foreach ($kartu as $k)
                <div class="col-6 col-xl-3">
                    <x-stat-card :label="$k['label']" :value="$k['nilai']" :icon="$k['ikon']" :tone="$toneKartu[$k['warna']] ?? 'primary'"
                        :meta="$k['meta']" :href="$k['url']" />
                </div>
            @endforeach
        </section>

        {{-- ============ Pusat kerja: tugas yang menunggu + agenda ============ --}}
        @if ($tugas || $adaAgenda)
            <section class="row g-3 mt-0 pt-2">
                @if ($tugas)
                    @php
                        // Yang ada antreannya tampil di atas; yang kosong diringkas agar tugas nyata tidak tenggelam.
                        [$tugasAda, $tugasKosong] = collect($tugas)->partition(fn ($t) => $t['jumlah'] > 0);
                    @endphp
                    <div class="col-12 {{ $adaAgenda ? 'col-xl-5' : '' }}">
                        <x-card title="Perlu Tindakan" subtitle="Pekerjaan yang menunggu Anda." icon="bi-list-check" class="h-100">
                            @if ($tugasAda->isNotEmpty())
                                <x-slot:actions><x-badge tone="warning">{{ $tugasAda->sum('jumlah') }} menunggu</x-badge></x-slot:actions>
                            @endif
                            <ul class="hr-list">
                                @forelse ($tugasAda as $t)
                                    <li>
                                        <a href="{{ $t['url'] }}" class="hr-list-item">
                                            <span class="hr-stat-ikon hr-tone-warning dash-ikon-kecil"><i class="bi {{ $t['ikon'] }}" aria-hidden="true"></i></span>
                                            <span class="hr-list-utama"><span class="hr-list-judul">{{ $t['label'] }}</span></span>
                                            <x-badge tone="warning">{{ $t['jumlah'] }}</x-badge>
                                            <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @empty
                                    <li>
                                        <x-empty-state icon="bi-check2-circle" title="Semua beres" compact>Tidak ada pekerjaan yang menunggu.</x-empty-state>
                                    </li>
                                @endforelse
                            </ul>
                            @if ($tugasKosong->isNotEmpty())
                                <details class="tugas-kosong mt-3">
                                    <summary class="small text-muted">{{ $tugasKosong->count() }} jenis tugas lain tanpa antrean</summary>
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        @foreach ($tugasKosong as $t)
                                            <a href="{{ $t['url'] }}" class="hr-badge hr-badge-neutral text-decoration-none"><i class="bi {{ $t['ikon'] }}" aria-hidden="true"></i>{{ $t['label'] }}</a>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        </x-card>
                    </div>
                @endif

                @if ($adaAgenda)
                    <div class="col-12 {{ $tugas ? 'col-xl-7' : '' }}">
                        <x-card title="Agenda 14 Hari" subtitle="Interview, onboarding, dan tanda tangan kontrak." icon="bi-calendar-event" class="h-100">
                            <ul class="hr-list">
                                @forelse ($agenda as $a)
                                    <li class="hr-list-item">
                                        <span class="dash-tanggal {{ $a['tanggal']->isToday() ? 'is-hari-ini' : '' }}">
                                            <span>{{ $a['tanggal']->translatedFormat('M') }}</span>
                                            <strong>{{ $a['tanggal']->format('d') }}</strong>
                                        </span>
                                        <span class="hr-list-utama">
                                            <span class="hr-list-judul">
                                                @if ($a['url'])<a href="{{ $a['url'] }}" class="text-reset text-decoration-none stretched-link">{{ $a['judul'] }}</a>@else{{ $a['judul'] }}@endif
                                            </span>
                                            <span class="hr-list-sub">
                                                <i class="bi {{ $a['ikon'] }} me-1" aria-hidden="true"></i>{{ $a['tanggal']->isToday() ? 'Hari ini' : $a['tanggal']->translatedFormat('l') }}{{ $a['jam'] ? ', ' . $a['jam'] : '' }}@if ($a['sub']) · {{ $a['sub'] }}@endif
                                            </span>
                                        </span>
                                        @if ($a['tanggal']->isToday())<x-badge tone="danger" dot>Hari ini</x-badge>@endif
                                    </li>
                                @empty
                                    <li><x-empty-state icon="bi-calendar-check" title="Agenda kosong" compact>Tidak ada agenda dalam 14 hari ke depan.</x-empty-state></li>
                                @endforelse
                            </ul>
                        </x-card>
                    </div>
                @endif
            </section>
        @endif

        @include('partials.pengumuman-ringkas', ['pengumuman' => $pengumuman])

        {{-- ============ 2. Grafik ============ --}}
        @if ($tren)
            <section class="hr-section" aria-labelledby="judul-tren">
                <div class="hr-section-head">
                    <h2 class="hr-section-title" id="judul-tren">Tren 6 Bulan</h2>
                    <p class="hr-section-sub">{{ $labelLingkup }} · klik batang untuk melihat daftar bulan itu</p>
                </div>
                <div class="row g-3">
                    @foreach ([['cuti', 'Hari Cuti Disetujui', 'bi-calendar2-check', 'hari', 'Cuti'], ['lembur', 'Jam Lembur Disetujui', 'bi-clock-history', 'jam', 'Lembur']] as [$kunci, $judulTren, $ikonTren, $satuan, $namaGrafik])
                        @php $tp = $trenPersen($tren[$kunci]); @endphp
                        <div class="col-12 col-lg-6">
                            <x-card :title="$judulTren" :icon="$ikonTren" class="h-100">
                                <x-slot:actions>
                                    @if ($tp)
                                        <span class="hr-tren {{ $tp['persen'] == 0 ? 'is-datar' : 'is-netral-arah' }}" title="{{ $tp['kini']['judul'] }} dibanding {{ $tp['lalu']['judul'] }}">
                                            <i class="bi {{ $tp['persen'] > 0 ? 'bi-arrow-up-right' : ($tp['persen'] < 0 ? 'bi-arrow-down-right' : 'bi-dash') }}" aria-hidden="true"></i>
                                            {{ number_format(abs($tp['persen']), abs($tp['persen']) < 10 ? 1 : 0, ',', '.') }}%
                                            <span class="fw-normal">{{ $tp['kini']['label'] }} vs {{ $tp['lalu']['label'] }}</span>
                                        </span>
                                    @endif
                                </x-slot:actions>
                                <p class="dash-angka-ringkas">
                                    <strong>{{ $jam(collect($tren[$kunci])->sum('jumlah')) }}</strong> {{ $satuan }} dalam 6 bulan
                                </p>
                                @include('partials.viz.kolom', ['items' => $tren[$kunci], 'satuan' => $satuan, 'judulGrafik' => $namaGrafik])
                            </x-card>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @php
            // Diagram per unit: batang = jumlah pegawai, warna = status kerja; klik untuk rincian.
            $itemUnit = $unitDipilih ? [] : $ringkasanUnit->map(fn ($r) => [
                'kunci' => $r['unit'], 'nama' => $r['unit'], 'jumlah' => $r['jumlah'], 'status' => $r['status'],
                'cari' => $r['unit'] . ' ' . $r['kepala'],
                'perhatian' => ($r['kepala'] ? 0 : 3) + $r['kontrak_habis'] * 2 + ($bisaLaporan ? $r['pending'] : 0),
                'peringatan' => array_values(array_filter([
                    $r['kepala'] ? null : ['bi-exclamation-circle', 'Belum ada kepala'],
                    $r['kontrak_habis'] ? ['bi-hourglass-split', $r['kontrak_habis'] . ' kontrak berakhir ≤ 60 hari'] : null,
                ])),
                'detail' => array_values(array_filter([
                    $r['kepala'] ? ['bi-person-badge', 'Kepala: ' . $r['kepala']] : ['bi-exclamation-circle', 'Belum ada kepala bagian', 'is-danger'],
                    ['bi-gender-ambiguous', "Laki-laki {$r['laki']} · Perempuan {$r['perempuan']}"],
                    $r['kontrak_habis'] ? ['bi-hourglass-split', $r['kontrak_habis'] . ' kontrak berakhir ≤ 60 hari', 'is-danger'] : null,
                    $bisaLaporan && $r['pending'] ? ['bi-inbox', $r['pending'] . ' pengajuan menunggu persetujuan', 'is-warning'] : null,
                    $bisaLaporan && $r['cuti_hari_ini'] ? ['bi-calendar-x', $r['cuti_hari_ini'] . ' orang cuti hari ini'] : null,
                    $bisaLaporan && $r['cuti_bulan_ini'] ? ['bi-calendar2-check', $r['cuti_bulan_ini'] . ' hari cuti bulan ini'] : null,
                    $bisaLaporan && $r['lembur_bulan_ini'] ? ['bi-clock-history', $jam($r['lembur_bulan_ini']) . ' jam lembur bulan ini'] : null,
                ])),
                'tautan' => array_values(array_filter([[route('admin.dashboard', ['unit' => $r['unit']]), 'Dashboard bagian'],
                    $bisaPegawai ? [route('admin.pegawai.index', ['status_rekrutmen' => 'Aktif', 'unit_kerja' => $r['unit']]), 'Lihat pegawai'] : null])),
            ])->all();
            $itemArea = $ringkasanArea->map(fn ($r) => [
                'kunci' => $r['area'], 'nama' => $r['area'], 'jumlah' => $r['jumlah'], 'status' => $r['status'],
                'cari' => $r['area'] . ' ' . implode(' ', array_keys($r['unit'])),
                'perhatian' => $r['kontrak_habis'],
                'peringatan' => $r['kontrak_habis'] ? [['bi-hourglass-split', $r['kontrak_habis'] . ' kontrak berakhir ≤ 60 hari']] : [],
                'detail' => array_values(array_filter([
                    ['bi-diagram-3', 'Bagian: ' . collect($r['unit'])->map(fn ($n, $u) => "{$u} ({$n})")->implode(', ')],
                    ['bi-gender-ambiguous', "Laki-laki {$r['laki']} · Perempuan {$r['perempuan']}"],
                    $r['kontrak_habis'] ? ['bi-hourglass-split', $r['kontrak_habis'] . ' kontrak berakhir ≤ 60 hari', 'is-danger'] : null,
                ])),
                'tautan' => $bisaPegawai ? [route('admin.pegawai.index', array_filter(['status_rekrutmen' => 'Aktif', 'lokasi' => $r['area'], 'unit_kerja' => $unitDipilih])), 'Lihat pegawai di area ini'] : null,
            ])->all();
        @endphp

        @if ($itemUnit || $itemArea)
            <section class="hr-section" id="ringkasan-unit" aria-labelledby="judul-sebaran">
                <div class="hr-section-head">
                    <h2 class="hr-section-title" id="judul-sebaran">Sebaran Pegawai Aktif</h2>
                    <p class="hr-section-sub"><i class="bi bi-hand-index me-1" aria-hidden="true"></i>Klik potongan diagram atau nama untuk rincian</p>
                </div>
                <div class="row g-3">
                    @foreach (array_filter([
                        $itemUnit ? ['id' => 'diagram-unit', 'judul' => 'Per Bagian', 'ikon' => 'bi-diagram-3', 'items' => $itemUnit, 'ket' => count($itemUnit) . ' bagian · pegawai rangkap dihitung di setiap bagiannya', 'tengah' => 'pegawai · bagian'] : null,
                        $itemArea ? ['id' => 'diagram-area', 'judul' => 'Per Area Kerja', 'ikon' => 'bi-geo-alt', 'items' => $itemArea, 'ket' => count($itemArea) . ' area kerja (lokasi)', 'tengah' => 'pegawai · area'] : null,
                    ]) as $d)
                        <div class="col-12 {{ $itemUnit && $itemArea ? 'col-xxl-6' : '' }}">
                            <x-card :title="$d['judul']" :subtitle="$d['ket']" :icon="$d['ikon']" class="h-100">
                                @include('partials.viz.kipas-detail', ['id' => $d['id'], 'items' => collect($d['items'])->sortByDesc('jumlah')->values()->all(), 'palet' => $paletStatus, 'judulTotal' => $d['tengah']])
                            </x-card>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="hr-section">
            @include('admin.partials.keluar-masuk', ['data' => $keluarMasuk, 'labelLingkup' => $labelLingkup])
        </div>

        <section class="hr-section" aria-labelledby="judul-komposisi">
            <div class="hr-section-head">
                <h2 class="hr-section-title" id="judul-komposisi">Komposisi Pegawai Aktif</h2>
                <x-badge tone="neutral" icon="bi-funnel">{{ $labelLingkup }}</x-badge>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-4">
                    <x-card title="Status Kerja" class="h-100">@include('partials.viz.komposisi', ['items' => $komposisi['status'], 'palet' => $paletStatus])</x-card>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <x-card title="Jenis Kelamin" class="h-100">@include('partials.viz.komposisi', ['items' => $komposisi['gender'], 'palet' => $paletGender])</x-card>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <x-card title="Masa Kerja" class="h-100">@include('partials.viz.batang', ['items' => $komposisi['masa'], 'satuan' => 'orang'])</x-card>
                </div>
                <div class="col-12 col-md-6 col-xl-6">
                    <x-card title="Kelompok Usia" class="h-100">@include('partials.viz.batang', ['items' => $komposisi['usia'], 'satuan' => 'orang'])</x-card>
                </div>
                <div class="col-12 col-xl-6">
                    <x-card title="Pendidikan Terakhir" class="h-100">@include('partials.viz.batang', ['items' => $komposisi['pendidikan'], 'satuan' => 'orang'])</x-card>
                </div>
            </div>
        </section>

        @if ($pipeline || $akunPerRole)
            <section class="row g-3 hr-section">
                @if ($pipeline)
                    <div class="col-12 {{ $akunPerRole ? 'col-xl-6' : '' }}">
                        <x-card title="Rekrutmen Berjalan" icon="bi-funnel" class="h-100">
                            <x-slot:actions><a href="{{ route('admin.lamaran.index') }}">Lihat lamaran</a></x-slot:actions>
                            <div class="dash-pipa">
                                @foreach ($pipeline as $p)
                                    <a href="{{ route('admin.lamaran.index') }}" class="dash-pipa-tahap {{ $p['jumlah'] ? '' : 'is-kosong' }}">
                                        <strong>{{ $p['jumlah'] }}</strong>
                                        <span>{{ $p['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </x-card>
                    </div>
                @endif
                @if ($akunPerRole)
                    <div class="col-12 {{ $pipeline ? 'col-xl-6' : '' }}">
                        <x-card title="Akun per Role" icon="bi-person-lock" class="h-100">
                            @include('partials.viz.batang', ['items' => collect(\App\Models\Role::bawaanAktif())->map(fn ($r) => [
                                'label' => \App\Models\User::roleLabelFor($r), 'jumlah' => (int) ($akunPerRole[$r] ?? 0), 'url' => route('admin.users.index', ['role' => $r]),
                            ])->all(), 'satuan' => 'akun'])
                        </x-card>
                    </div>
                @endif
            </section>
        @endif

        {{-- ============ 3. Daftar & tabel ============ --}}
        <section class="hr-section" aria-labelledby="judul-pantau">
            <div class="hr-section-head">
                <h2 class="hr-section-title" id="judul-pantau">Pantauan Pegawai</h2>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-4">
                    <x-card title="Kontrak Berakhir ≤ 60 Hari" icon="bi-hourglass-split" class="h-100">
                        @if ($bisaPegawai && $kontrakHabis->isNotEmpty())
                            <x-slot:actions><a href="{{ route('admin.pegawai.index', array_filter(['status_rekrutmen' => 'Aktif', 'kontrak' => 60, 'unit_kerja' => $unitDipilih])) }}">Lihat semua</a></x-slot:actions>
                        @endif
                        <ul class="hr-list dash-scroll" style="max-height: 320px;">
                            @forelse ($kontrakHabis as $p)
                                @php $sisa = (int) now()->startOfDay()->diffInDays($p->kepegawaian->tanggal_habis_kontrak); @endphp
                                <li>
                                    <a @if ($bisaPegawai) href="{{ route('admin.pegawai.show', $p->id) }}" @endif class="hr-list-item">
                                        <x-avatar :src="$p->dokumen?->pas_foto ? \App\Support\Berkas::url($p->dokumen->pas_foto) : null" :name="$p->nama_lengkap" size="sm" />
                                        <span class="hr-list-utama">
                                            <span class="hr-list-judul">{{ $p->nama_lengkap }}</span>
                                            <span class="hr-list-sub">{{ $p->kepegawaian->unit_kerja }} · {{ $p->kepegawaian->tanggal_habis_kontrak->translatedFormat('d M Y') }}</span>
                                        </span>
                                        <x-badge :tone="$sisa <= 14 ? 'danger' : 'warning'">{{ $sisa }} hari</x-badge>
                                    </a>
                                </li>
                            @empty
                                <li><x-empty-state icon="bi-hourglass" title="Aman" compact>Tidak ada kontrak yang berakhir dalam 60 hari.</x-empty-state></li>
                            @endforelse
                        </ul>
                    </x-card>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <x-card title="Sedang Cuti Hari Ini" icon="bi-calendar-x" class="h-100">
                        @if ($bisaLaporan && $cutiHariIni->isNotEmpty())
                            <x-slot:actions><a href="{{ route('admin.pengajuan-cuti.index', ['berlangsung' => 1]) }}">Lihat semua</a></x-slot:actions>
                        @endif
                        @if (! $bisaLaporan)
                            <x-empty-state icon="bi-info-circle" compact>Cuti bagian ini diurus secara internal (tidak lewat sistem).</x-empty-state>
                        @else
                            <ul class="hr-list">
                                @forelse ($cutiHariIni as $c)
                                    <li>
                                        <a href="{{ route('admin.pengajuan-cuti.show', $c) }}" class="hr-list-item">
                                            <x-avatar :name="$c->dataPegawai?->nama_lengkap ?? '-'" size="sm" />
                                            <span class="hr-list-utama">
                                                <span class="hr-list-judul">{{ $c->dataPegawai?->nama_lengkap }}</span>
                                                <span class="hr-list-sub">{{ $c->unit_kerja }} · {{ $c->jenisLabel() }}</span>
                                            </span>
                                            <span class="hr-list-ekor">s/d {{ $c->tanggal_selesai->translatedFormat('d M') }}</span>
                                        </a>
                                    </li>
                                @empty
                                    <li><x-empty-state icon="bi-calendar-check" title="Semua masuk" compact>Tidak ada yang cuti hari ini.</x-empty-state></li>
                                @endforelse
                            </ul>
                        @endif
                    </x-card>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <x-card title="Pegawai Baru (90 Hari)" icon="bi-person-plus" class="h-100">
                        @if ($bisaPegawai && $pegawaiBaru->isNotEmpty())
                            <x-slot:actions><a href="{{ route('admin.pegawai.index', array_filter(['status_rekrutmen' => 'Aktif', 'urut' => 'masuk_baru', 'unit_kerja' => $unitDipilih])) }}">Lihat semua</a></x-slot:actions>
                        @endif
                        <ul class="hr-list">
                            @forelse ($pegawaiBaru as $p)
                                <li>
                                    <a @if ($bisaPegawai) href="{{ route('admin.pegawai.show', $p->id) }}" @endif class="hr-list-item">
                                        <x-avatar :src="$p->dokumen?->pas_foto ? \App\Support\Berkas::url($p->dokumen->pas_foto) : null" :name="$p->nama_lengkap" size="sm" />
                                        <span class="hr-list-utama">
                                            <span class="hr-list-judul">{{ $p->nama_lengkap }}</span>
                                            <span class="hr-list-sub">{{ $p->kepegawaian->jabatan }}</span>
                                        </span>
                                        <span class="hr-list-ekor">{{ $p->kepegawaian->tanggal_masuk->translatedFormat('d M') }}</span>
                                    </a>
                                </li>
                            @empty
                                <li><x-empty-state icon="bi-person-plus" compact>Belum ada pegawai baru dalam 90 hari.</x-empty-state></li>
                            @endforelse
                        </ul>
                    </x-card>
                </div>
            </div>
        </section>

        {{-- ============ Bagan bagian: bagian terpilih, atau bagian yang dipimpin pengguna ============ --}}
        @php $unitBagan = $unitDipilih ? [$unitDipilih] : $user->unitsDipimpin(); @endphp
        @if ($unitBagan)
            @foreach ($unitBagan as $u)
                <section class="hr-section">@include('partials.bagan-bagian', ['unit' => $u])</section>
            @endforeach
        @elseif (count($pilihanUnit) > 1)
            <section class="hr-section">
                <x-card title="Bagan Bagian" icon="bi-diagram-3">
                    <x-empty-state icon="bi-diagram-3" title="Pilih bagian untuk melihat bagannya" compact>
                        Gunakan pilihan <strong>Semua bagian</strong> di kanan atas, contoh: pilih SDM untuk melihat susunan pegawai bagian SDM.
                    </x-empty-state>
                </x-card>
            </section>
        @endif

        @if ($anggota->isNotEmpty())
            <section class="hr-section">
                <x-card :title="'Anggota ' . $labelLingkup" icon="bi-person-lines-fill" :padding="false"
                    :subtitle="$anggota->count() . ' pegawai aktif · ' . $anggota->where('cuti', true)->count() . ' sedang cuti'">
                    <x-slot:actions>
                        <div class="input-group hr-input-group dash-cari-anggota">
                            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                            <input type="search" class="form-control" placeholder="Cari pegawai, contoh: Siti atau Guru" data-saring="#tabel-anggota"
                                autocomplete="off" aria-label="Cari anggota menurut nama atau jabatan">
                        </div>
                    </x-slot:actions>
                    <x-table id="tabel-anggota" caption="Anggota bagian" sticky class="dash-scroll" style="max-height: 520px;">
                        <x-slot:head>
                            <tr>
                                <th>Pegawai</th>
                                <th>Status Kerja</th>
                                <th>Masa Kerja</th>
                                <th>Kontrak s/d</th>
                                <th>Keterangan</th>
                                @can('lihat-tabel-karyawan')<th class="text-end">Aksi</th>@endcan
                            </tr>
                        </x-slot:head>
                        @foreach ($anggota as $a)
                            <tr data-cari="{{ mb_strtolower($a['nama'] . ' ' . $a['jabatan']) }}">
                                <td>
                                    <div class="d-flex align-items-center gap-3 table-media">
                                        <x-avatar :src="$a['foto'] ? \App\Support\Berkas::url($a['foto']) : null" :name="$a['nama']" size="sm" />
                                        <div class="min-w-0">
                                            <span class="fw-semibold d-block">{{ $a['nama'] }}</span>
                                            <span class="small text-muted">{{ $a['jabatan'] ?? '-' }}@if (! $unitDipilih && $a['unit']) · {{ $a['unit'] }}@endif</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="d-inline-flex align-items-center gap-2 viz">
                                        <span class="dash-titik-status" style="background: var(--viz-{{ $paletStatus[$a['status_kerja']] ?? 'netral' }});" aria-hidden="true"></span>
                                        {{ $a['status_kerja'] ?? 'Belum diisi' }}
                                    </span>
                                </td>
                                <td>{{ $a['masa_kerja'] }}</td>
                                <td class="text-nowrap">
                                    @if ($a['habis_kontrak'])
                                        <span class="{{ $a['habis_kontrak']->isPast() ? 'text-danger' : ($a['habis_kontrak']->lte(now()->addDays(60)) ? 'text-warning fw-semibold' : '') }}">
                                            {{ $a['habis_kontrak']->translatedFormat('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @if ($a['kepala'])<x-badge tone="primary">Kepala</x-badge>@endif
                                        @foreach ($a['rangkap'] as $r)<x-badge tone="info">Rangkap {{ $r }}</x-badge>@endforeach
                                        @if ($a['cuti'])<x-badge tone="warning">Cuti</x-badge>@endif
                                    </div>
                                </td>
                                @can('lihat-tabel-karyawan')
                                    <td class="text-end"><x-button variant="ghost" size="sm" :href="route('admin.pegawai.show', $a['id'])" icon-right="bi-chevron-right">Detail</x-button></td>
                                @endcan
                            </tr>
                        @endforeach
                        <tr data-saring-kosong="#tabel-anggota" hidden>
                            <td colspan="{{ auth()->user()->can('lihat-tabel-karyawan') ? 6 : 5 }}">
                                <x-empty-state icon="bi-search" title="Tidak ditemukan" compact>Tidak ada pegawai yang cocok dengan pencarian ini.</x-empty-state>
                            </td>
                        </tr>
                    </x-table>
                </x-card>
            </section>
        @endif
    </div>
</main>

{{-- Cari anggota ditangani global (main.js: data-saring). --}}
@endsection
