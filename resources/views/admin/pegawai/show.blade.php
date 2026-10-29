@extends(Auth::user()->panelLayout())

@section('content')
    @php
        $kepeg = $pegawai->kepegawaian;
        $foto = optional($pegawai->dokumen)->pas_foto;
        $fotoUrl = $foto ? \App\Support\Berkas::url($foto) : asset('assets-admin/images/avatar/avatar-fallback.jpg');
        $status = $kepeg->status_rekrutmen ?? 'Non-Aktif';
        $aktif = strtolower($status) === 'aktif';
        $utama = $pegawai->penugasanAktif->firstWhere('is_utama', true);
        $rangkapAktif = $pegawai->penugasanAktif->where('is_utama', false);
    @endphp

    <style>
        .pd-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            background: var(--admin-surface);
            box-shadow: var(--admin-shadow-sm);
        }
        .pd-hero-band {
            position: relative;
            height: 96px;
            background:
                radial-gradient(circle at 85% -20%, rgba(255, 255, 255, .18) 0, transparent 55%),
                linear-gradient(120deg, var(--admin-primary-dark) 0%, var(--admin-primary) 55%, #c9474a 100%);
        }
        .pd-hero-band .pd-crumb {
            position: absolute;
            left: 1.5rem;
            top: .9rem;
            color: rgba(255, 255, 255, .85);
            font-size: .8rem;
            font-weight: 600;
        }
        .pd-hero-band .pd-crumb a { color: #fff; text-decoration: none; }
        .pd-hero-band .pd-crumb a:hover { text-decoration: underline; }
        .pd-hero-body {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            gap: 1rem 1.5rem;
            padding: 0 1.5rem 1.35rem;
        }
        .pd-hero-body > .pd-avatar { position: relative; z-index: 1; margin-top: -56px; flex: none; }
        .pd-hero-body > .pd-identity { padding-top: .9rem; }
        .pd-hero-body > .pd-actions { padding-top: 1rem; align-self: flex-start; }
        .pd-avatar {
            width: 112px;
            height: 112px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--admin-surface);
            background: var(--admin-surface-soft);
            box-shadow: var(--admin-shadow-sm);
        }
        .pd-identity { flex: 1 1 260px; min-width: 0; }
        .pd-name { margin: 0 0 .2rem; font-weight: 800; color: var(--admin-text); font-size: 1.4rem; }
        .pd-role { color: var(--admin-muted); font-weight: 600; margin-bottom: .6rem; }
        .pd-meta { display: flex; flex-wrap: wrap; gap: .4rem .9rem; color: var(--admin-muted); font-size: .88rem; }
        .pd-meta i { color: var(--admin-primary); }
        .pd-actions { display: flex; flex-wrap: wrap; gap: .5rem; }

        .pd-chip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .22rem .6rem;
            border-radius: 999px;
            border: 1px solid var(--admin-border);
            background: var(--admin-surface-soft);
            color: var(--admin-text);
            font-size: .78rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .pd-chip-primary { border-color: transparent; background: #f9e5e6; color: var(--admin-primary-dark); }
        .pd-chip-success { border-color: transparent; background: #e3f6ee; color: #11734f; }
        .pd-chip-muted   { border-color: transparent; background: #eef1f5; color: #5b6575; }
        .pd-chip-warning { border-color: transparent; background: #fff4df; color: #9a5a05; }
        .pd-chip-dark    { border-color: transparent; background: #1f2937; color: #fff; }
        html[data-theme="dark"] .pd-chip-primary { background: #3b2020; color: #f5d4d5; }
        html[data-theme="dark"] .pd-chip-success { background: rgba(15, 118, 110, .25); color: #99f6e4; }
        html[data-theme="dark"] .pd-chip-muted   { background: #243149; color: #c7d2e1; }
        html[data-theme="dark"] .pd-chip-warning { background: rgba(217, 119, 6, .22); color: #fde68a; }
        html[data-theme="dark"] .pd-chip-dark    { background: #e5edf7; color: #111827; }

        .pd-tabs {
            display: flex;
            gap: .35rem;
            overflow-x: auto;
            padding: .35rem;
            margin: 1.5rem 0 1.25rem;
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            background: var(--admin-surface-soft);
            scrollbar-width: thin;
        }
        .pd-tabs .nav-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 0;
            border-radius: 8px;
            padding: .5rem .9rem;
            color: var(--admin-muted);
            font-weight: 700;
            font-size: .9rem;
            white-space: nowrap;
            background: transparent;
        }
        .pd-tabs .nav-link:hover { color: var(--admin-primary); background: var(--admin-surface); }
        .pd-tabs .nav-link.active {
            color: #fff;
            background: var(--admin-primary);
            box-shadow: 0 6px 14px rgba(136, 15, 18, .22);
        }

        .pd-flow-card {
            height: 100%;
            padding: 1rem;
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            background: var(--admin-surface);
        }
        .pd-flow-card.has-warning { border-color: var(--admin-warning); }
        .pd-flow-head { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-bottom: .85rem; }
        .pd-steps { list-style: none; margin: 0; padding: 0; }
        .pd-step { position: relative; display: flex; gap: .75rem; padding-bottom: .9rem; }
        .pd-step:last-child { padding-bottom: 0; }
        .pd-step:not(:last-child)::before {
            content: "";
            position: absolute;
            left: 13px;
            top: 28px;
            bottom: 2px;
            width: 2px;
            background: var(--admin-border);
        }
        .pd-step-dot {
            flex: 0 0 28px;
            width: 28px;
            height: 28px;
            display: inline-grid;
            place-items: center;
            border-radius: 50%;
            background: #f9e5e6;
            color: var(--admin-primary-dark);
            font-size: .8rem;
            font-weight: 800;
        }
        .pd-step.is-final .pd-step-dot { background: var(--admin-primary); color: #fff; }
        .pd-step.is-empty .pd-step-dot { background: #fff4df; color: #9a5a05; }
        html[data-theme="dark"] .pd-step-dot { background: #3b2020; color: #f5d4d5; }
        .pd-step-label { font-weight: 700; color: var(--admin-text); font-size: .9rem; }
        .pd-step-who { color: var(--admin-muted); font-size: .84rem; }

        .pd-table thead th {
            color: var(--admin-muted);
            font-size: .75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
            background: var(--admin-surface-soft);
            border-bottom-width: 1px;
            white-space: nowrap;
        }
        .pd-table td { vertical-align: middle; }
        .pd-row-active { box-shadow: inset 3px 0 0 var(--admin-primary); }

        .pd-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: var(--admin-muted);
            border: 1px dashed var(--admin-border);
            border-radius: 10px;
        }
        .pd-empty i { display: block; font-size: 1.6rem; margin-bottom: .4rem; }

        @media (max-width: 575.98px) {
            .pd-hero-body { padding: 0 1rem 1rem; justify-content: center; text-align: center; }
            .pd-hero-body > .pd-identity { padding-top: 0; }
            .pd-meta, .pd-actions, .pd-identity .d-flex { justify-content: center; }
            .pd-actions { width: 100%; }
            .pd-actions .btn { flex: 1 1 auto; }
        }
    </style>

    <main class="dashboard-content">
        <div class="container-fluid px-3 px-lg-4 py-4">

            <section class="pd-hero mb-0">
                <div class="pd-hero-band">
                    <nav class="pd-crumb" aria-label="breadcrumb">
                        <a href="{{ route('admin.pegawai.index') }}"><i class="bi bi-people me-1"></i>Data Pegawai</a>
                        <i class="bi bi-chevron-right mx-1" aria-hidden="true"></i> Detail Pegawai
                    </nav>
                </div>
                <div class="pd-hero-body">
                    <img src="{{ $fotoUrl }}" alt="Foto {{ $pegawai->nama_lengkap }}" class="pd-avatar">

                    <div class="pd-identity">
                        <h1 class="pd-name">{{ $pegawai->nama_lengkap }}</h1>
                        <div class="pd-role">{{ $kepeg->jabatan ?? 'Jabatan belum diisi' }}</div>

                        <div class="d-flex flex-wrap gap-1 mb-2">
                            <span class="pd-chip {{ $aktif ? 'pd-chip-success' : 'pd-chip-muted' }}">
                                <i class="bi {{ $aktif ? 'bi-check-circle-fill' : 'bi-pause-circle' }}"></i> {{ $status }}
                            </span>
                            @if ($kepeg?->unit_kerja)
                                <span class="pd-chip pd-chip-primary" title="Bagian utama">
                                    <i class="bi bi-building"></i> {{ $kepeg->unit_kerja }}{{ $utama?->isKepala() ? ' · Kepala' : '' }}
                                </span>
                            @endif
                            @foreach ($rangkapAktif as $rangkap)
                                <span class="pd-chip" title="Rangkap tugas sejak {{ $rangkap->tanggal_mulai->format('d-m-Y') }}">
                                    <i class="bi bi-diagram-3"></i> {{ $rangkap->unit_kerja }}{{ $rangkap->isKepala() ? ' · Kepala' : '' }}
                                </span>
                            @endforeach
                        </div>

                        <div class="pd-meta">
                            <span><i class="bi bi-person-badge me-1"></i>NIP {{ $pegawai->nip ?? '-' }}</span>
                            @if ($pegawai->email)
                                <span><i class="bi bi-envelope me-1"></i>{{ $pegawai->email }}</span>
                            @endif
                            @if ($pegawai->no_hp)
                                <span><i class="bi bi-telephone me-1"></i>{{ $pegawai->no_hp }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="pd-actions">
                        <a href="{{ route('admin.pegawai.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pegawai.export-detail', 'eksporParam' => ['pegawai' => $pegawai->id], 'eksporUkuran' => 'btn-sm'])
                        @can('kelola-pegawai')
                            <a href="{{ route('admin.pegawai.edit', $pegawai) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil-square"></i> Ubah Data
                            </a>
                            @if ($dampakKeluar)
                                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalKeluar">
                                    <i class="bi bi-box-arrow-right"></i> Resign / Keluar
                                </button>
                            @endif
                        @endcan
                        @if (auth()->user()->isAdmin())
                            {{-- Admin: buka data mentah pegawai ini di Editor Data (semua kolom, tanpa aturan). --}}
                            <div class="dropdown">
                                <button class="btn btn-outline-warning btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-shield-lock"></i> Data Mentah (Admin)
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.data.edit', ['pegawai', $pegawai->id, 'kembali' => request()->fullUrl()]) }}"><i class="bi bi-person me-2"></i>Data pribadi</a></li>
                                    @foreach (['kepegawaian' => ['Kepegawaian', 'bi-person-badge'], 'penugasan' => ['Penugasan / mutasi', 'bi-arrow-left-right'], 'bpjs' => ['BPJS', 'bi-shield-plus'],
                                        'kontak_darurat' => ['Kontak darurat', 'bi-telephone'], 'dokumen' => ['Dokumen', 'bi-folder2'], 'absensi' => ['Absensi', 'bi-fingerprint'],
                                        'jadwal_kerja' => ['Jam kerja', 'bi-alarm'], 'cuti' => ['Cuti', 'bi-calendar2-check'], 'lembur' => ['Lembur', 'bi-clock-history']] as $ent => [$lbl, $ik])
                                        <li><a class="dropdown-item" href="{{ route('admin.data.daftar', [$ent, 'q' => $pegawai->nama_lengkap]) }}"><i class="bi {{ $ik }} me-2"></i>{{ $lbl }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            @if (session('peringatan_keluar'))
                <div class="alert alert-info small d-flex gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <ul class="mb-0 ps-3">@foreach (session('peringatan_keluar') as $p)<li>{{ $p }}</li>@endforeach</ul>
                </div>
            @endif
            @if (! $aktif && $kepeg?->jenis_keluar)
                {{-- Pegawai sudah keluar lewat "Proses Keluar": ringkasan + pembatalan. --}}
                <div class="alert alert-secondary d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
                    <div class="small">
                        <strong><i class="bi bi-box-arrow-right me-1"></i>Keluar per {{ $kepeg->tanggal_keluar?->translatedFormat('d F Y') ?? '-' }}</strong>
                        · {{ $kepeg->jenisKeluarLabel() }}
                        @if ($kepeg->alasan_keluar) · {{ $kepeg->alasan_keluar }} @endif
                        <span class="d-block text-muted">Diproses oleh {{ $kepeg->pemrosesKeluar?->name ?? '-' }}. Akunnya kini akun pelamar; untuk kembali bekerja harus melamar lagi.</span>
                    </div>
                    @can('kelola-pegawai')
                        @if ($kepeg->keluar_snapshot)
                            <form action="{{ route('admin.pegawai.keluar.batal', $pegawai) }}" method="POST" class="flex-shrink-0"
                                data-confirm="Batalkan proses keluar {{ $pegawai->nama_lengkap }}? Status, akun, penugasan, dan pengajuan dikembalikan seperti sebelum keluar.">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Batalkan Keluar</button>
                            </form>
                        @endif
                    @endcan
                </div>
            @endif

            <ul class="nav pd-tabs" id="detailTab" role="tablist">
                @foreach ([
                    'pribadi' => ['bi-person', 'Data Pribadi'],
                    'kepegawaian' => ['bi-briefcase', 'Kepegawaian'],
                    'penugasan' => ['bi-diagram-3', 'Penugasan & Approval'],
                    'kontak' => ['bi-telephone', 'Kontak & BPJS'],
                    'dokumen' => ['bi-folder2-open', 'Dokumen'],
                ] as $key => [$icon, $label])
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link {{ $loop->first ? 'active' : '' }}" id="tab-{{ $key }}-btn" data-bs-toggle="tab"
                            data-bs-target="#tab-{{ $key }}" type="button" role="tab" aria-controls="tab-{{ $key }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            <i class="bi {{ $icon }}"></i> {{ $label }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <section class="panel">
                <div class="tab-content">

                    <div class="tab-pane fade show active" id="tab-pribadi" role="tabpanel" aria-labelledby="tab-pribadi-btn">
                        <h2 class="pd-section-title"><i class="bi bi-person-lines-fill"></i> Identitas</h2>
                        @include('admin.pegawai._info-grid', ['items' => [
                            'Nama Lengkap' => $pegawai->nama_lengkap,
                            'Email' => $pegawai->email,
                            'NIK (No. KTP)' => $pegawai->nik,
                            'No. KK' => $pegawai->no_kk,
                            'No. NPWP' => $pegawai->npwp,
                            'Tempat, Tanggal Lahir' => collect([$pegawai->tempat_lahir, optional($pegawai->tanggal_lahir)->translatedFormat('d F Y')])->filter()->implode(', '),
                            'Jenis Kelamin' => $pegawai->jenis_kelamin,
                            'Agama' => $pegawai->agama,
                            'Golongan Darah' => $pegawai->golongan_darah,
                            'Status Pernikahan' => $pegawai->status_pernikahan,
                            'Jumlah Tanggungan' => $pegawai->jumlah_tanggungan,
                            'Nama Ibu Kandung' => $pegawai->nama_ibu_kandung,
                            'Pendidikan Terakhir' => $pegawai->pendidikan_terakhir,
                        ]])

                        <h2 class="pd-section-title mt-4"><i class="bi bi-geo-alt"></i> Alamat</h2>
                        @include('admin.pegawai._info-grid', ['items' => [
                            'Alamat KTP' => $pegawai->alamat_ktp,
                            'Alamat Domisili' => $pegawai->alamat_domisili ?: 'Sama dengan KTP',
                        ]])
                    </div>

                    <div class="tab-pane fade" id="tab-kepegawaian" role="tabpanel" aria-labelledby="tab-kepegawaian-btn">
                        <h2 class="pd-section-title"><i class="bi bi-briefcase"></i> Data Kepegawaian</h2>
                        @include('admin.pegawai._info-grid', ['items' => [
                            'NIP' => $pegawai->nip,
                            'Jabatan Utama' => $kepeg?->jabatan,
                            'Jabatan Tambahan' => $kepeg?->jabatan_tambahan,
                            'Bagian Utama' => $kepeg?->unit_kerja,
                            'Lokasi Kerja' => $kepeg?->lokasi_kerja,
                            'Tanggal Masuk' => optional($kepeg?->tanggal_masuk)->translatedFormat('d F Y'),
                            'Masa Kerja' => $kepeg?->masa_kerja,
                            'Status Rekrutmen' => $kepeg?->status_rekrutmen,
                            ...($kepeg?->status_rekrutmen === 'Tidak Aktif' ? [
                                'Tanggal Keluar' => optional($kepeg->tanggal_keluar)->translatedFormat('d F Y') ?? 'Tidak diketahui',
                                'Jenis Keluar' => $kepeg->jenisKeluarLabel(),
                                'Alasan Keluar' => $kepeg->alasan_keluar,
                            ] : []),
                            'Status Kerja' => $kepeg?->status_kerja,
                            'Tanggal Habis Kontrak' => optional($kepeg?->tanggal_habis_kontrak)->translatedFormat('d F Y'),
                        ]])
                    </div>

                    <div class="tab-pane fade" id="tab-penugasan" role="tabpanel" aria-labelledby="tab-penugasan-btn">
                        @if (session('peringatan_penugasan'))
                            <div class="alert alert-warning small d-flex gap-2">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <ul class="mb-0 ps-3">
                                    @foreach (session('peringatan_penugasan') as $p)
                                        <li>{{ $p }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
                        @section('galat-di-halaman', '1')
                        @if ($errors->any())
                            <div class="alert alert-danger small mb-3"><i class="bi bi-x-circle-fill me-1"></i>{{ $errors->first() }}</div>
                        @endif

                        @include('admin.pegawai._akun-login')
                        @if ($bolehAturAkun && $akun)
                            @push('modal-pegawai')
                                @include('admin.pegawai._modal-alur')
                            @endpush
                        @endif

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2" id="alur-approval">
                            <h2 class="pd-section-title mb-0">
                                <i class="bi bi-signpost-split"></i> Alur Approval
                                @if ($alurKhusus)<span class="pd-chip pd-chip-primary ms-1"><i class="bi bi-sliders"></i> Diatur khusus</span>@endif
                            </h2>
                            @if ($bolehAturAkun && $akun)
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAlurPegawai">
                                        <i class="bi bi-diagram-3"></i> Ubah Alur & Atasan
                                    </button>
                                    @if ($alurKhusus)
                                        <form action="{{ route('admin.pegawai.alur.destroy', $pegawai) }}" method="POST"
                                            data-confirm="Kembalikan alur persetujuan {{ $pegawai->nama_lengkap }} ke otomatis (mengikuti bagian & jabatan)?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-light btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Kembali Otomatis</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if ($alurKhusus)
                            <div class="alert alert-info small py-2 mb-2">
                                <i class="bi bi-info-circle me-1"></i>Alur pegawai ini diatur khusus oleh {{ $alurKhusus->pengubah?->name ?? 'SDM' }}
                                ({{ $alurKhusus->updated_at?->translatedFormat('d M Y H:i') }}) dan berlaku untuk cuti & lembur.
                                @if ($alurKhusus->catatan)<span class="d-block text-muted">Catatan: {{ $alurKhusus->catatan }}</span>@endif
                            </div>
                        @endif
                        @if ($unitsDisetujui)
                            <div class="d-flex flex-wrap align-items-center gap-1 small text-muted mb-2">
                                <span>Menyetujui pengajuan staf di:</span>
                                @foreach ($unitsDisetujui as $unit)
                                    <span class="pd-chip pd-chip-dark"><i class="bi bi-shield-check"></i> {{ $unit }}</span>
                                @endforeach
                            </div>
                        @endif
                        @unless ($alurKhusus)
                            <p class="text-muted small mb-3">
                                Cuti mengikuti bagian utama. Lembur dipilih per bagian dan di-approve kepala bagian tersebut;
                                bila pegawai ini kepala di bagian itu, atau bagiannya tidak punya kepala, approval naik ke GM.
                            </p>
                        @endunless

                        @if (! $akun)
                            <div class="pd-empty mb-4"><i class="bi bi-person-x"></i>Pegawai ini belum punya akun, jadi belum bisa mengajukan cuti/lembur.</div>
                        @elseif (! $alurApproval)
                            <div class="pd-empty mb-4"><i class="bi bi-slash-circle"></i>Akun pegawai ini tidak mengajukan cuti/lembur lewat sistem (bagian/jabatan dikecualikan).</div>
                        @else
                            <div class="row g-3 mb-4">
                                @foreach ($alurApproval as $alur)
                                    @php $kosong = collect($alur['tahap'])->contains(fn ($t) => $t['approvers']->isEmpty()); @endphp
                                    <div class="col-md-6 col-xl-4">
                                        <div class="pd-flow-card {{ $kosong ? 'has-warning' : '' }}">
                                            <div class="pd-flow-head">
                                                <span class="pd-chip {{ $alur['jenis'] === 'Cuti' ? 'pd-chip-primary' : 'pd-chip-dark' }}">
                                                    <i class="bi {{ $alur['jenis'] === 'Cuti' ? 'bi-calendar2-week' : 'bi-clock-history' }}"></i> {{ $alur['jenis'] }}
                                                </span>
                                                <span class="fw-semibold small">{{ $alur['unit'] ?? 'Bagian belum diisi' }}</span>
                                            </div>
                                            <ol class="pd-steps">
                                                @foreach ($alur['tahap'] as $tahap)
                                                    <li class="pd-step {{ $tahap['final'] ? 'is-final' : '' }} {{ $tahap['approvers']->isEmpty() ? 'is-empty' : '' }}">
                                                        <span class="pd-step-dot">
                                                            @if ($tahap['approvers']->isEmpty())<i class="bi bi-exclamation"></i>@elseif ($tahap['final'])<i class="bi bi-check2"></i>@else{{ $loop->iteration }}@endif
                                                        </span>
                                                        <div style="min-width: 0">
                                                            <div class="pd-step-label">
                                                                {{ match (true) {
                                                                    $tahap['level'] === \App\Support\PengajuanWorkflow::LEVEL_KEPALA_SDM => 'Kepala SDM',
                                                                    $tahap['level'] === \App\Support\PengajuanWorkflow::LEVEL_KEPALA_DIVISI && ($tahap['khusus'] ?? false) && $alurKhusus?->atasan_user_id => 'Atasan Langsung',
                                                                    default => $tahap['label'],
                                                                } }}
                                                                @if ($tahap['final'])<span class="pd-chip pd-chip-success ms-1">Final</span>@endif
                                                            </div>
                                                            <div class="pd-step-who">
                                                                @if ($tahap['approvers']->isEmpty())
                                                                    <span class="text-warning fw-semibold">Belum ada approver — pengajuan akan ditolak sistem.</span>
                                                                @else
                                                                    {{ $tahap['approvers']->pluck('name')->take(3)->implode(', ') }}
                                                                    @if ($tahap['approvers']->count() > 3)
                                                                        +{{ $tahap['approvers']->count() - 3 }} lainnya
                                                                    @endif
                                                                    @if ($tahap['level'] === \App\Support\PengajuanWorkflow::LEVEL_GM && $tahap['approvers']->count() > 1)
                                                                        <span class="d-block">GM lain bisa approve sebagai pengganti.</span>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ol>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h2 class="pd-section-title mb-0"><i class="bi bi-clock-history"></i> Riwayat Penugasan & Mutasi</h2>
                            @can('kelola-penugasan')
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('admin.penugasan.create', ['pegawai' => $pegawai, 'jenis' => 'mutasi']) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-arrow-left-right"></i> Mutasi
                                    </a>
                                    <a href="{{ route('admin.penugasan.create', ['pegawai' => $pegawai, 'jenis' => 'rangkap']) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-plus-lg"></i> Tambah Rangkap
                                    </a>
                                </div>
                            @endcan
                        </div>

                        @if ($pegawai->penugasans->isEmpty())
                            <div class="pd-empty"><i class="bi bi-inbox"></i>Belum ada riwayat penugasan.</div>
                        @else
                            <div class="table-responsive border rounded-3">
                                <table class="table pd-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Bagian</th>
                                            <th>Jabatan / Peran</th>
                                            <th>Periode</th>
                                            <th>Status</th>
                                            <th>SK / Keterangan</th>
                                            @can('kelola-penugasan')<th class="text-end">Aksi</th>@endcan
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pegawai->penugasans as $p)
                                            <tr class="{{ $p->isAktif() ? 'pd-row-active' : '' }}">
                                                <td>
                                                    <div class="fw-semibold">{{ $p->unit_kerja }}</div>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        <span class="pd-chip {{ $p->is_utama ? 'pd-chip-primary' : '' }}">{{ $p->is_utama ? 'Utama' : 'Rangkap' }}</span>
                                                        <span class="pd-chip pd-chip-muted">{{ $p->jenisLabel() }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    {{ $p->jabatan ?? '-' }}
                                                    @if ($p->isKepala())
                                                        <span class="pd-chip pd-chip-dark ms-1"><i class="bi bi-star-fill"></i> Kepala</span>
                                                    @endif
                                                </td>
                                                <td class="small text-nowrap">
                                                    {{ $p->tanggal_mulai->format('d-m-Y') }} &ndash; {{ $p->tanggal_selesai?->format('d-m-Y') ?? 'sekarang' }}
                                                </td>
                                                <td>
                                                    @php
                                                        $chip = match (true) {
                                                            $p->isAkanDatang() => 'pd-chip-warning',
                                                            $p->isAktif() => 'pd-chip-success',
                                                            default => 'pd-chip-muted',
                                                        };
                                                    @endphp
                                                    <span class="pd-chip {{ $chip }}">{{ $p->statusLabel() }}</span>
                                                </td>
                                                <td class="small">
                                                    <div>{{ $p->no_sk ?? '-' }}</div>
                                                    @if ($p->keterangan)<div class="text-muted">{{ $p->keterangan }}</div>@endif
                                                    <div class="text-muted">Dicatat: {{ $p->dibuatOleh?->name ?? 'sistem' }}</div>
                                                </td>
                                                @can('kelola-penugasan')
                                                    <td class="text-end">
                                                        @if (auth()->user()->isAdmin())
                                                            <a href="{{ route('admin.penugasan.edit', $p) }}" class="btn btn-link btn-sm p-0 me-2" title="Ubah langsung (Admin)"><i class="bi bi-pencil"></i> Ubah</a>
                                                        @endif
                                                        @if ($p->isAkanDatang())
                                                            <form action="{{ route('admin.penugasan.destroy', $p) }}" method="POST"
                                                                data-confirm="Batalkan {{ strtolower($p->jenisLabel()) }} terjadwal ke {{ $p->unit_kerja }}?">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                    <i class="bi bi-x-circle"></i> Batalkan
                                                                </button>
                                                            </form>
                                                        @elseif (! $p->is_utama && $p->isAktif())
                                                            <form action="{{ route('admin.penugasan.akhiri', $p) }}" method="POST"
                                                                class="input-group input-group-sm justify-content-end flex-nowrap ms-auto" style="max-width: 250px"
                                                                data-confirm="Akhiri rangkap tugas di {{ $p->unit_kerja }}?">
                                                                @csrf
                                                                <input type="date" name="tanggal_selesai" class="form-control" aria-label="Hari terakhir bertugas"
                                                                    value="{{ now()->format('Y-m-d') }}" min="{{ $p->tanggal_mulai->format('Y-m-d') }}" required
                                                                    title="Hari terakhir bertugas">
                                                                <button type="submit" class="btn btn-outline-danger">
                                                                    <i class="bi bi-box-arrow-right"></i> Akhiri
                                                                </button>
                                                            </form>
                                                        @endif
                                                        @if (! $p->isAkanDatang() && ($p->jenis !== \App\Models\Penugasan::JENIS_AWAL || auth()->user()->isAdmin()))
                                                            <form action="{{ route('admin.penugasan.destroy', $p) }}" method="POST" class="mt-1"
                                                                data-confirm="Hapus {{ strtolower($p->jenisLabel()) }} ke {{ $p->unit_kerja }} (koreksi salah input)?{{ $p->is_utama ? ' Pegawai dikembalikan ke bagian sebelumnya.' : '' }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-link btn-sm text-danger p-0">
                                                                    <i class="bi bi-trash"></i> Hapus
                                                                </button>
                                                            </form>
                                                        @elseif ($p->jenis === \App\Models\Penugasan::JENIS_AWAL && ! auth()->user()->isAdmin())
                                                            <span class="text-muted small">&mdash;</span>
                                                        @endif
                                                    </td>
                                                @endcan
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="tab-pane fade" id="tab-kontak" role="tabpanel" aria-labelledby="tab-kontak-btn">
                        <h2 class="pd-section-title"><i class="bi bi-telephone"></i> Kontak Darurat</h2>
                        @include('admin.pegawai._info-grid', ['items' => [
                            'Nama Kerabat' => optional($pegawai->kontakDarurat)->nama_kerabat,
                            'No. HP Kerabat' => optional($pegawai->kontakDarurat)->no_hp_kerabat,
                            'Hubungan' => optional($pegawai->kontakDarurat)->hubungan_kerabat,
                        ]])

                        <h2 class="pd-section-title mt-4"><i class="bi bi-card-checklist"></i> Data BPJS</h2>
                        @include('admin.pegawai._info-grid', ['items' => [
                            'No. BPJS Kesehatan' => optional($pegawai->bpjs)->no_bpjs_kesehatan,
                            'No. BPJS Ketenagakerjaan' => optional($pegawai->bpjs)->no_bpjs_ketenagakerjaan,
                            'Faskes Tingkat 1' => optional($pegawai->bpjs)->faskes_tingkat_1,
                        ]])
                    </div>

                    <div class="tab-pane fade" id="tab-dokumen" role="tabpanel" aria-labelledby="tab-dokumen-btn">
                        <h2 class="pd-section-title"><i class="bi bi-folder2-open"></i> Dokumen Pegawai</h2>
                        <div class="row g-3">
                            @foreach ([
                                'pas_foto' => 'Pas Foto',
                                'upload_ktp' => 'KTP',
                                'upload_kk' => 'Kartu Keluarga',
                                'upload_cv' => 'CV',
                                'upload_ijazah_terakhir' => 'Ijazah Terakhir',
                                'upload_buku_tabungan' => 'Buku Tabungan',
                            ] as $field => $label)
                                <div class="col-md-6">
                                    @include('partials.preview-file', [
                                        'path' => optional($pegawai->dokumen)->{$field},
                                        'label' => $label,
                                    ])
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </section>
        </div>
    </main>

    @include('partials.preview-file-modal')
    @stack('modal-pegawai')
    @if ($dampakKeluar)
        @include('admin.pegawai._modal-keluar')
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tab = new URLSearchParams(window.location.search).get('tab');
            const btn = tab && document.getElementById('tab-' + tab + '-btn');
            if (btn && window.bootstrap) {
                bootstrap.Tab.getOrCreateInstance(btn).show();
                // Kembali dari simpan pengaturan: gulir ke bagiannya (#alur-approval / #akun-login).
                const target = location.hash && document.querySelector(location.hash);
                if (target) setTimeout(() => target.scrollIntoView({ block: 'start' }), 150);
            }
        });
    </script>
@endsection
