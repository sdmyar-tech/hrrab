@extends('layouts.admin.app')

@section('title', 'Dashboard')

@section('content')
@include('partials.viz.style')
@php
    $paletStatus = ['Tetap' => 1, 'Kontrak' => 2, 'Harian Lepas' => 3, 'Probation' => 4, 'Magang' => 5];
    $paletGender = ['Laki-laki' => 1, 'Perempuan' => 2];
    $adaAgenda = $agenda->isNotEmpty() || $user->bisaMenginterview();
    $inisial = fn ($nama) => collect(explode(' ', preg_replace('/[^A-Za-z ]/', '', $nama)))->filter()->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode('');
    $jam = fn ($n) => rtrim(rtrim(number_format($n, 1, ',', '.'), '0'), ',');
    $bisaPegawai = $user->can('lihat-tabel-karyawan');
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">

        <section class="dash-hero p-3 p-lg-4 mb-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div class="d-flex gap-3 align-items-start">
                    <span class="page-icon"><i class="bi bi-speedometer2"></i></span>
                    <div>
                        <p class="eyebrow mb-1">Dashboard {{ $user->roleLabel() }}</p>
                        <h1 class="h3 mb-1">Halo, {{ \Illuminate\Support\Str::of($user->name)->before(',') }}</h1>
                        <p class="text-muted mb-0">{{ $judul }} &middot; {{ now()->translatedFormat('l, d F Y') }}</p>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-end gap-2 align-self-lg-center">
                    @if (count($pilihanUnit) > 1)
                        <form method="GET" action="{{ route('admin.dashboard') }}" style="min-width: 260px;">
                            <label for="pilih-unit" class="form-label small text-muted mb-1"><i class="bi bi-funnel me-1"></i>Tampilkan bagian</label>
                            <select name="unit" id="pilih-unit" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua ({{ count($pilihanUnit) }} bagian)</option>
                                @foreach ($pilihanUnit as $u)
                                    <option value="{{ $u }}" @selected($unitDipilih === $u)>{{ $u }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                    @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.dashboard.export', 'eksporTeks' => 'Ekspor Dashboard'])
                </div>
            </div>
            @if ($unitDipilih)
                <div class="mt-3 small">
                    <span class="badge text-bg-primary">Bagian: {{ $unitDipilih }}</span>
                    <a href="{{ route('admin.dashboard') }}" class="ms-2 dash-link">Tampilkan semua bagian</a>
                </div>
            @endif
        </section>

        @include('partials.jadwal-ttd-kontrak')
        <section class="row g-3" aria-label="Ringkasan">
            @foreach ($kartu as $k)
                <div class="col-6 col-xl-3">
                    <article class="metric-card metric-{{ $k['warna'] }} h-100 position-relative">
                        <div class="metric-top">
                            <span class="metric-label">{{ $k['label'] }}</span>
                            <span class="metric-icon"><i class="bi {{ $k['ikon'] }}" aria-hidden="true"></i></span>
                        </div>
                        <div class="metric-value">{{ number_format($k['nilai']) }}</div>
                        <div class="metric-meta"><span class="text-muted text-truncate d-block">{{ $k['meta'] }}</span></div>
                        @if ($k['url'])
                            <a href="{{ $k['url'] }}" class="stretched-link" aria-label="Buka {{ $k['label'] }}"></a>
                        @endif
                    </article>
                </div>
            @endforeach
        </section>

        <section class="row g-3 mt-1">
            @if ($tugas)
                <div class="col-12 {{ $adaAgenda ? 'col-xl-5' : '' }}">
                    <div class="panel h-100">
                        <div class="panel-header">
                            <div>
                                <h2 class="h5 mb-1 section-title"><i class="bi bi-list-check" aria-hidden="true"></i><span>Perlu Tindakan</span></h2>
                                <p class="text-muted mb-0">Pekerjaan yang menunggu Anda.</p>
                            </div>
                        </div>
                        @php
                            // Yang ada antreannya tampil di atas; yang kosong diringkas agar tugas nyata tidak tenggelam.
                            [$tugasAda, $tugasKosong] = collect($tugas)->partition(fn ($t) => $t['jumlah'] > 0);
                        @endphp
                        <div class="list-group list-group-flush">
                            @forelse ($tugasAda as $t)
                                <a href="{{ $t['url'] }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <span><i class="bi {{ $t['ikon'] }} me-2 text-muted"></i>{{ $t['label'] }}</span>
                                    <span class="badge rounded-pill text-bg-warning">{{ $t['jumlah'] }}</span>
                                </a>
                            @empty
                                <div class="list-group-item px-0 text-success"><i class="bi bi-check2-circle me-2"></i>Semua beres — tidak ada pekerjaan yang menunggu.</div>
                            @endforelse
                        </div>
                        @if ($tugasKosong->isNotEmpty())
                            <details class="tugas-kosong mt-2">
                                <summary class="small text-muted">{{ $tugasKosong->count() }} jenis tugas lain tanpa antrean</summary>
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    @foreach ($tugasKosong as $t)
                                        <a href="{{ $t['url'] }}" class="badge rounded-pill text-bg-light border text-decoration-none fw-normal"><i class="bi {{ $t['ikon'] }} me-1"></i>{{ $t['label'] }}</a>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                </div>
            @endif

            @if ($adaAgenda)
                <div class="col-12 {{ $tugas ? 'col-xl-7' : '' }}">
                    <div class="panel h-100">
                        <div class="panel-header">
                            <div>
                                <h2 class="h5 mb-1 section-title"><i class="bi bi-calendar-event" aria-hidden="true"></i><span>Agenda 14 Hari</span></h2>
                                <p class="text-muted mb-0">Interview, onboarding, dan tanda tangan kontrak.</p>
                            </div>
                        </div>
                        <div class="activity-list">
                            @forelse ($agenda as $a)
                                <div class="activity-item">
                                    <span class="activity-dot {{ $a['tanggal']->isToday() ? 'bg-danger' : 'bg-primary' }}"></span>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 fw-semibold">
                                            @if ($a['url'])<a href="{{ $a['url'] }}" class="text-reset">{{ $a['judul'] }}</a>@else{{ $a['judul'] }}@endif
                                        </p>
                                        <p class="text-muted small mb-0">
                                            <i class="bi {{ $a['ikon'] }} me-1"></i>
                                            {{ $a['tanggal']->isToday() ? 'Hari ini' : $a['tanggal']->translatedFormat('D, d M') }}{{ $a['jam'] ? ', ' . $a['jam'] : '' }}
                                            @if ($a['sub']) &middot; {{ $a['sub'] }} @endif
                                        </p>
                                    </div>
                                </div>
                            @empty
                                @include('partials.kosong', ['teks' => 'Tidak ada agenda dalam 14 hari ke depan.', 'ikonKosong' => 'bi-calendar-check'])
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </section>

        @include('partials.pengumuman-ringkas', ['pengumuman' => $pengumuman])

        <section class="row g-3 mt-1">
            <div class="col-12 col-md-6 col-xl-4">
                <div class="panel h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0"><i class="bi bi-hourglass-split me-1"></i>Kontrak Berakhir ≤ 60 Hari</h2>
                        @if ($bisaPegawai && $kontrakHabis->isNotEmpty())<a href="{{ route('admin.pegawai.index', array_filter(['status_rekrutmen' => 'Aktif', 'kontrak' => 60, 'unit_kerja' => $unitDipilih])) }}" class="small dash-link">Lihat semua</a>@endif
                    </div>
                    <div class="dash-scroll" style="max-height:280px;">
                        @forelse ($kontrakHabis as $p)
                            @php $sisa = (int) now()->startOfDay()->diffInDays($p->kepegawaian->tanggal_habis_kontrak); @endphp
                            <a @if ($bisaPegawai) href="{{ route('admin.pegawai.show', $p->id) }}" @endif class="dash-baris d-flex justify-content-between align-items-center small gap-2">
                                <span class="text-truncate">
                                    <span class="fw-semibold">{{ $p->nama_lengkap }}</span>
                                    <span class="text-muted d-block">{{ $p->kepegawaian->unit_kerja }} &middot; {{ $p->kepegawaian->tanggal_habis_kontrak->translatedFormat('d M Y') }}</span>
                                </span>
                                <span class="badge {{ $sisa <= 14 ? 'text-bg-danger' : 'text-bg-warning' }} text-nowrap">{{ $sisa }} hari</span>
                            </a>
                        @empty
                            @include('partials.kosong', ['teks' => 'Tidak ada kontrak yang berakhir dalam 60 hari.', 'ikonKosong' => 'bi-hourglass'])
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="panel h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0"><i class="bi bi-calendar-x me-1"></i>Sedang Cuti Hari Ini</h2>
                        @if ($bisaLaporan && $cutiHariIni->isNotEmpty())<a href="{{ route('admin.pengajuan-cuti.index', ['berlangsung' => 1]) }}" class="small dash-link">Lihat semua</a>@endif
                    </div>
                    @if (! $bisaLaporan)
                        @include('partials.kosong', ['teks' => 'Cuti bagian ini diurus secara internal (tidak lewat sistem).', 'ikonKosong' => 'bi-info-circle'])
                    @else
                        @forelse ($cutiHariIni as $c)
                            <a href="{{ route('admin.pengajuan-cuti.show', $c) }}" class="dash-baris d-flex justify-content-between small gap-2">
                                <span class="text-truncate"><span class="fw-semibold">{{ $c->dataPegawai?->nama_lengkap }}</span>
                                    <span class="text-muted d-block">{{ $c->unit_kerja }} &middot; {{ $c->jenisLabel() }}</span></span>
                                <span class="text-muted text-nowrap">s/d {{ $c->tanggal_selesai->translatedFormat('d M') }}</span>
                            </a>
                        @empty
                            @include('partials.kosong', ['teks' => 'Tidak ada yang cuti hari ini.', 'ikonKosong' => 'bi-calendar-x'])
                        @endforelse
                    @endif
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="panel h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0"><i class="bi bi-person-plus me-1"></i>Pegawai Baru (90 Hari)</h2>
                        @if ($bisaPegawai && $pegawaiBaru->isNotEmpty())<a href="{{ route('admin.pegawai.index', array_filter(['status_rekrutmen' => 'Aktif', 'urut' => 'masuk_baru', 'unit_kerja' => $unitDipilih])) }}" class="small dash-link">Lihat semua</a>@endif
                    </div>
                    @forelse ($pegawaiBaru as $p)
                        <a @if ($bisaPegawai) href="{{ route('admin.pegawai.show', $p->id) }}" @endif class="dash-baris d-flex justify-content-between small gap-2">
                            <span class="text-truncate"><span class="fw-semibold">{{ $p->nama_lengkap }}</span>
                                <span class="text-muted d-block">{{ $p->kepegawaian->jabatan }}</span></span>
                            <span class="text-muted text-nowrap">{{ $p->kepegawaian->tanggal_masuk->translatedFormat('d M') }}</span>
                        </a>
                    @empty
                        @include('partials.kosong', ['teks' => 'Belum ada pegawai baru dalam 90 hari.', 'ikonKosong' => 'bi-person-plus'])
                    @endforelse
                </div>
            </div>
        </section>

        @if ($anggota->isNotEmpty())
            <section class="panel mt-3">
                <div class="panel-header flex-wrap gap-2">
                    <div>
                        <h2 class="h5 mb-1 section-title"><i class="bi bi-person-lines-fill" aria-hidden="true"></i><span>Anggota {{ $labelLingkup }}</span></h2>
                        <p class="text-muted mb-0">{{ $anggota->count() }} pegawai aktif &middot; {{ $anggota->where('cuti', true)->count() }} sedang cuti</p>
                    </div>
                    <input type="search" class="form-control form-control-sm" style="max-width:240px;" placeholder="Cari pegawai, contoh: Siti atau Guru" data-cari-anggota aria-label="Cari anggota">
                </div>
                <div class="table-responsive dash-scroll">
                    <table class="table align-middle mb-0 small" id="tabel-anggota">
                        <thead class="sticky-top">
                            <tr>
                                <th>Pegawai</th>
                                <th>Status Kerja</th>
                                <th>Masa Kerja</th>
                                <th>Kontrak s/d</th>
                                <th>Keterangan</th>
                                @can('lihat-tabel-karyawan')<th class="text-end">Aksi</th>@endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($anggota as $a)
                                <tr data-teks="{{ strtolower($a['nama'] . ' ' . $a['jabatan']) }}">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if ($a['foto'])
                                                <img src="{{ \App\Support\Berkas::url($a['foto']) }}" alt="" class="dash-avatar" loading="lazy" decoding="async">
                                            @else
                                                <span class="dash-avatar viz" aria-hidden="true">{{ $inisial($a['nama']) }}</span>
                                            @endif
                                            <div>
                                                <span class="fw-semibold d-block">{{ $a['nama'] }}</span>
                                                <span class="text-muted">{{ $a['jabatan'] ?? '-' }}@if (! $unitDipilih && $a['unit']) &middot; {{ $a['unit'] }}@endif</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-1 viz">
                                            <span style="width:8px;height:8px;border-radius:2px;background:var(--viz-{{ $paletStatus[$a['status_kerja']] ?? 'netral' }});display:inline-block;"></span>
                                            {{ $a['status_kerja'] ?? 'Belum diisi' }}
                                        </span>
                                    </td>
                                    <td>{{ $a['masa_kerja'] }}</td>
                                    <td>
                                        @if ($a['habis_kontrak'])
                                            <span class="{{ $a['habis_kontrak']->isPast() ? 'text-danger' : ($a['habis_kontrak']->lte(now()->addDays(60)) ? 'text-warning fw-semibold' : '') }}">
                                                {{ $a['habis_kontrak']->translatedFormat('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">–</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($a['kepala'])<span class="badge text-bg-dark">Kepala</span>@endif
                                        @foreach ($a['rangkap'] as $r)<span class="badge text-bg-info">Rangkap {{ $r }}</span>@endforeach
                                        @if ($a['cuti'])<span class="badge text-bg-warning">Cuti</span>@endif
                                    </td>
                                    @can('lihat-tabel-karyawan')
                                        <td class="text-end"><a href="{{ route('admin.pegawai.show', $a['id']) }}" class="btn btn-light btn-sm">Detail</a></td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
            <section class="mt-3" id="ringkasan-unit">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <h2 class="h5 mb-0 section-title"><i class="bi bi-pie-chart" aria-hidden="true"></i><span>Sebaran Pegawai Aktif</span></h2>
                    <span class="small text-muted"><i class="bi bi-hand-index me-1"></i>Klik potongan diagram atau nama untuk rincian</span>
                </div>
                <div class="row g-3">
                    @foreach (array_filter([
                        $itemUnit ? ['id' => 'diagram-unit', 'judul' => 'Per Bagian', 'ikon' => 'bi-diagram-3', 'items' => $itemUnit, 'ket' => count($itemUnit) . ' bagian · pegawai rangkap dihitung di setiap bagiannya', 'tengah' => 'pegawai · bagian'] : null,
                        $itemArea ? ['id' => 'diagram-area', 'judul' => 'Per Area Kerja', 'ikon' => 'bi-geo-alt', 'items' => $itemArea, 'ket' => count($itemArea) . ' area kerja (lokasi)', 'tengah' => 'pegawai · area'] : null,
                    ]) as $d)
                        <div class="col-12 {{ $itemUnit && $itemArea ? 'col-xxl-6' : '' }}">
                            <div class="panel h-100">
                                <h3 class="h6 fw-bold mb-0"><i class="bi {{ $d['ikon'] }}"></i>{{ $d['judul'] }}</h3>
                                <p class="small text-muted mb-3">{{ $d['ket'] }}</p>
                                @include('partials.viz.kipas-detail', ['id' => $d['id'], 'items' => collect($d['items'])->sortByDesc('jumlah')->values()->all(), 'palet' => $paletStatus, 'judulTotal' => $d['tengah']])
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @include('admin.partials.keluar-masuk', ['data' => $keluarMasuk, 'labelLingkup' => $labelLingkup])

        @if ($pipeline || $akunPerRole)
            <section class="row g-3 mt-1">
                @if ($pipeline)
                    <div class="col-12 {{ $akunPerRole ? 'col-xl-6' : '' }}">
                        <div class="panel h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0"><i class="bi bi-funnel me-1"></i>Rekrutmen Berjalan</h2>
                                <a href="{{ route('admin.lamaran.index') }}" class="small dash-link">Lihat lamaran</a>
                            </div>
                            {{-- Berdampingan dengan "Akun per Role": grid 2x2 yang mengisi tinggi card agar keduanya sejajar. --}}
                            <div class="row g-2 text-center flex-grow-1 align-content-stretch">
                                @foreach ($pipeline as $p)
                                    <div class="{{ $akunPerRole ? 'col-6' : 'col-6 col-md-3' }}">
                                        <a href="{{ route('admin.lamaran.index') }}" class="dash-mini-stat besar viz">
                                            <div class="n {{ $p['jumlah'] ? '' : 'text-muted' }}">{{ $p['jumlah'] }}</div>
                                            <div class="l">{{ $p['label'] }}</div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                @if ($akunPerRole)
                    <div class="col-12 {{ $pipeline ? 'col-xl-6' : '' }}">
                        <div class="panel h-100">
                            <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-lock me-1"></i>Akun per Role</h2>
                            @include('partials.viz.batang', ['items' => collect(\App\Models\Role::bawaanAktif())->map(fn ($r) => [
                                'label' => \App\Models\User::roleLabelFor($r), 'jumlah' => (int) ($akunPerRole[$r] ?? 0), 'url' => route('admin.users.index', ['role' => $r]),
                            ])->all(), 'satuan' => 'akun'])
                        </div>
                    </div>
                @endif
            </section>
        @endif

        @if ($tren)
            <section class="row g-3 mt-1">
                <div class="col-12 col-lg-6">
                    <div class="panel h-100">
                        <h2 class="h6 fw-bold mb-1"><i class="bi bi-calendar2-check me-1"></i>Hari Cuti Disetujui</h2>
                        <p class="small text-muted mb-2">6 bulan terakhir &middot; {{ $labelLingkup }}</p>
                        @include('partials.viz.kolom', ['items' => $tren['cuti'], 'satuan' => 'hari', 'judulGrafik' => 'Cuti'])
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="panel h-100">
                        <h2 class="h6 fw-bold mb-1"><i class="bi bi-clock-history me-1"></i>Jam Lembur Disetujui</h2>
                        <p class="small text-muted mb-2">6 bulan terakhir &middot; {{ $labelLingkup }}</p>
                        @include('partials.viz.kolom', ['items' => $tren['lembur'], 'satuan' => 'jam', 'judulGrafik' => 'Lembur'])
                    </div>
                </div>
            </section>
        @endif

        <section class="mt-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <h2 class="h5 mb-0 section-title"><i class="bi bi-pie-chart" aria-hidden="true"></i><span>Komposisi Pegawai Aktif</span></h2>
                <span class="badge rounded-pill text-bg-light border fw-normal"><i class="bi bi-funnel me-1"></i>{{ $labelLingkup }}</span>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="panel h-100">
                        <h3 class="h6 fw-bold mb-3">Status Kerja</h3>
                        @include('partials.viz.komposisi', ['items' => $komposisi['status'], 'palet' => $paletStatus])
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="panel h-100">
                        <h3 class="h6 fw-bold mb-3">Jenis Kelamin</h3>
                        @include('partials.viz.komposisi', ['items' => $komposisi['gender'], 'palet' => $paletGender])
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="panel h-100">
                        <h3 class="h6 fw-bold mb-3">Masa Kerja</h3>
                        @include('partials.viz.batang', ['items' => $komposisi['masa'], 'satuan' => 'orang'])
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-6">
                    <div class="panel h-100">
                        <h3 class="h6 fw-bold mb-3">Kelompok Usia</h3>
                        @include('partials.viz.batang', ['items' => $komposisi['usia'], 'satuan' => 'orang'])
                    </div>
                </div>
                <div class="col-12 col-xl-6">
                    <div class="panel h-100">
                        <h3 class="h6 fw-bold mb-3">Pendidikan Terakhir</h3>
                        @include('partials.viz.batang', ['items' => $komposisi['pendidikan'], 'satuan' => 'orang'])
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
    document.querySelectorAll('[data-cari-anggota]').forEach(function (input) {
        input.addEventListener('input', function () {
            const kata = input.value.trim().toLowerCase();
            document.querySelectorAll('#tabel-anggota tbody tr').forEach(function (tr) {
                tr.hidden = kata !== '' && ! tr.dataset.teks.includes(kata);
            });
        });
    });
</script>
@endsection
