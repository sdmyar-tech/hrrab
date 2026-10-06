@extends('layouts.public.app')

@section('title', 'Lowongan Karir')

@section('content')
<section class="public-hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <p class="hero-eyebrow mb-2">Karir di Yayasan Abdurrab</p>
                <h1 class="display-5 mb-3">Bangun karir, tumbuh bersama kami.</h1>
                <p class="lead mb-4" style="opacity:.9; max-width:560px;">
                    Temukan posisi yang sesuai dengan keahlian Anda dan jadilah bagian dari keluarga besar Yayasan Abdurrab.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="stat-pill"><i class="bi bi-briefcase me-1"></i> {{ $lowongans->total() }} lowongan terbuka</span>
                    <span class="stat-pill"><i class="bi bi-diagram-3 me-1"></i> {{ $unitKerjaOptions->count() }} unit kerja</span>
                </div>
                @unless (Auth::user()?->isPelamar())
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="{{ route('register') }}" class="btn btn-light fw-semibold px-4" style="color:var(--brand-dark);">Daftar Sekarang</a>
                        <a href="{{ route('pelamar.login') }}" class="btn btn-outline-light fw-semibold px-4">Masuk</a>
                    </div>
                @endunless
            </div>
            <div class="col-lg-4 d-none d-lg-flex justify-content-end">
                <img class="hero-logo" src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="Logo Yayasan Abdurrab"
                    style="width:180px; height:180px; border-radius:40px; padding:18px;">
            </div>
        </div>
    </div>
</section>

<div class="container pb-4">
    <form action="{{ route('lowongan.index') }}" method="GET" class="search-card mb-4">
        <div class="row g-2">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white" style="border-color:#e4d3d3; border-radius:10px 0 0 10px;"><i class="bi bi-search text-muted"></i></span>
                    <input type="search" name="q" class="form-control border-start-0" style="border-radius:0 10px 10px 0;"
                        placeholder="Cari posisi, jabatan, atau unit kerja..." value="{{ request('q') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="unit_kerja" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Unit Kerja</option>
                    @foreach ($unitKerjaOptions as $opt)
                        <option value="{{ $opt }}" {{ request('unit_kerja') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-brand" style="border-radius:10px;"><i class="bi bi-search me-1"></i> Cari</button>
            </div>
        </div>
    </form>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">
            @if (request()->hasAny(['q', 'unit_kerja']))
                Hasil pencarian <span class="text-muted fw-normal">({{ $lowongans->total() }})</span>
            @else
                Lowongan Terbaru
            @endif
        </h2>
        @if (request()->hasAny(['q', 'unit_kerja']))
            <a href="{{ route('lowongan.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Reset filter</a>
        @endif
    </div>

    <div class="row g-4">
        @forelse ($lowongans as $lowongan)
            @php
                $sisa = max(0, $lowongan->jumlah_dibutuhkan - $lowongan->jumlah_terisi);
                $baru = $lowongan->dipublikasikan_pada && $lowongan->dipublikasikan_pada->gt(now()->subDays(7));
                $segeraTutup = $lowongan->tanggal_tutup && $lowongan->tanggal_tutup->between(now()->startOfDay(), now()->addDays(7));
            @endphp
            <div class="col-md-6 col-lg-4">
                <a href="{{ route('lowongan.show', $lowongan) }}" class="text-decoration-none text-reset">
                    <div class="card job-card h-100 bg-white">
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="job-icon"><i class="bi bi-briefcase-fill"></i></span>
                                <div class="d-flex gap-1">
                                    @if ($baru)
                                        <span class="badge badge-baru">Baru</span>
                                    @endif
                                    @if ($segeraTutup)
                                        <span class="badge badge-segera">Segera tutup</span>
                                    @endif
                                </div>
                            </div>

                            <span class="badge badge-kategori align-self-start mb-2">{{ $lowongan->unit_kerja }}</span>
                            <h3 class="h5 fw-bold mb-3">{{ $lowongan->judul }}</h3>

                            <ul class="list-unstyled text-muted small job-meta mb-3">
                                <li><i class="bi bi-person-badge"></i> {{ $lowongan->jabatan }}</li>
                                @if ($lowongan->lokasi_kerja)
                                    <li><i class="bi bi-geo-alt"></i> {{ $lowongan->lokasi_kerja }}</li>
                                @endif
                                @if ($lowongan->tipe_kerja)
                                    <li><i class="bi bi-clock"></i> {{ $lowongan->tipe_kerja }}</li>
                                @endif
                                <li><i class="bi bi-people"></i> Butuh {{ $sisa }} orang</li>
                            </ul>

                            <div class="job-footer d-flex justify-content-between align-items-center small">
                                <span class="text-muted">
                                    @if ($lowongan->tanggal_tutup)
                                        <i class="bi bi-calendar-event me-1"></i> Tutup {{ $lowongan->tanggal_tutup->translatedFormat('d M Y') }}
                                    @else
                                        <i class="bi bi-calendar-check me-1"></i> Dibuka
                                    @endif
                                </span>
                                <span class="fw-semibold" style="color:var(--brand);">Detail <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state text-center">
                    <img src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="" class="mb-3">
                    <h3 class="h5 fw-bold">
                        {{ request()->hasAny(['q', 'unit_kerja']) ? 'Lowongan tidak ditemukan' : 'Belum ada lowongan dibuka' }}
                    </h3>
                    <p class="text-muted mb-0">
                        @if (request()->hasAny(['q', 'unit_kerja']))
                            Coba kata kunci atau unit kerja lain.
                        @else
                            Silakan cek kembali dalam waktu dekat. Daftarkan akun agar siap melamar saat lowongan dibuka.
                        @endif
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    @if ($lowongans->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $lowongans->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
