@extends('layouts.admin.app')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'status', 'jalur', 'tanggal_dari', 'tanggal_sampai']);
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-people"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">Lamaran Masuk</h1>
                    <p class="text-muted mb-0">Verifikasi, interview, kontrak, hingga keputusan akhir.</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.lamaran.export'])
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.lamaran.export-jadwal', 'eksporTeks' => 'Ekspor Jadwal'])
                @if ($canReview)
                    <a href="{{ route('admin.lamaran.internal.create') }}" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i> Tambah Kandidat Langsung
                    </a>
                @endif
            </div>
        </div>

        <section class="panel" aria-label="Daftar lamaran">
            <form action="{{ route('admin.lamaran.index') }}" method="GET" id="filterLamaranForm" class="panel-filter px-3 pt-3 pb-2" role="search">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6 col-xl-4">
                        @include('partials.daftar-cari', [
                            'cariTeks' => 'Cari pelamar, contoh: Budi Santoso',
                            'cariLabel' => 'Cari lamaran menurut nama atau email pelamar',
                        ])
                    </div>
                    <div class="col-6 col-md-3 col-xl-3">
                        <select name="status" class="form-select form-select-sm" aria-label="Status lamaran" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="menunggu_verifikasi" {{ request('status') == 'menunggu_verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                            <option value="proses" {{ request('status') == 'proses' ? 'selected' : '' }}>Proses</option>
                            <option value="diterima" {{ request('status') == 'diterima' ? 'selected' : '' }}>Diterima</option>
                            <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                            <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan Pelamar</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <select name="jalur" class="form-select form-select-sm" aria-label="Jalur lamaran" onchange="this.form.submit()">
                            <option value="">Semua Jalur</option>
                            <option value="umum" {{ request('jalur') == 'umum' ? 'selected' : '' }}>Jalur Umum (Lowongan)</option>
                            <option value="internal" {{ request('jalur') == 'internal' ? 'selected' : '' }}>Jalur Langsung</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">@include('partials.filter-tanggal', ['tanggalLabel' => 'Masuk'])</div>
                    <div class="col-12 col-md-auto">@include('partials.urut', ['urutForm' => 'filterLamaranForm'])</div>
                </div>
                @include('partials.daftar-ringkas', [
                    'ringkasJumlah' => $lamarans->total(), 'ringkasSatuan' => 'lamaran',
                    'ringkasFilter' => $adaFilter, 'ringkasReset' => route('admin.lamaran.index'),
                ])
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kandidat</th>
                            <th>Posisi</th>
                            <th>Tanggal Masuk</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lamarans as $item)
                            @php
                                $sudahKaryawan = $item->dataPelamar?->user?->role === \App\Models\User::ROLE_STAFF;
                                $riwayat = $sudahKaryawan
                                    ? collect()
                                    : ($item->dataPelamar?->lamarans->where('id', '!=', $item->id) ?? collect());
                            @endphp
                            <tr>
                                <td>
                                    <div class="table-media align-items-start">
                                        <img class="product-thumb rounded-circle" loading="lazy" alt=""
                                            src="{{ $item->dataPelamar?->foto_path ? \App\Support\Berkas::url($item->dataPelamar->foto_path) : asset('assets-admin/images/avatar/avatar-fallback.jpg') }}">
                                        <div class="min-w-0 flex-grow-1">
                                            <a href="{{ route('admin.lamaran.show', $item) }}" class="nama-tautan d-block">{{ $item->namaKandidat() }}</a>
                                            <small class="text-muted d-block">{{ $item->emailKandidat() }}</small>
                                            @if ($item->isJalurInternal())
                                                <span class="badge text-bg-light border mt-1">
                                                    <i class="bi bi-person-check me-1"></i>Jalur Langsung{{ $item->isKandidatPegawai() ? ' · Pegawai Yayasan' : '' }}
                                                </span>
                                            @endif

                                            @if ($riwayat->isNotEmpty())
                                                <button type="button" class="btn btn-link btn-sm p-0 mt-1 d-block text-decoration-none"
                                                    data-bs-toggle="collapse" data-bs-target="#riwayat-{{ $item->id }}"
                                                    aria-expanded="false" aria-controls="riwayat-{{ $item->id }}">
                                                    <i class="bi bi-clock-history me-1"></i>{{ $riwayat->count() }}x lamaran sebelumnya
                                                    <i class="bi bi-chevron-down small"></i>
                                                </button>
                                                <div class="collapse mt-2" id="riwayat-{{ $item->id }}">
                                                    <div class="border rounded p-2" style="font-size:.8rem; background: var(--admin-surface-soft);">
                                                        @foreach ($riwayat as $r)
                                                            <div class="d-flex justify-content-between align-items-start gap-2 py-1 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                                                <div>
                                                                    <span class="d-block">{{ $r->lowongan?->judul }}</span>
                                                                    @if ($r->catatan_admin)
                                                                        <span class="text-muted fst-italic">{{ $r->catatan_admin }}</span>
                                                                    @endif
                                                                </div>
                                                                <span class="badge {{ $r->badgeClass() }} flex-shrink-0">{{ $r->statusLabel() }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{ $item->judulPosisi() }}
                                    <small class="text-muted d-block">
                                        {{ $item->unitKerjaTujuan() }}{{ $item->lowongan ? '' : ' · tanpa lowongan' }}
                                    </small>
                                </td>
                                <td class="text-nowrap">{{ optional($item->created_at)->translatedFormat('d M Y') }}</td>
                                <td>
                                    {{-- Status + tahap dalam satu kolom: tahap hanya relevan saat lamaran sedang diproses. --}}
                                    <span class="badge {{ $item->badgeClass() }}">{{ $item->statusLabel() }}</span>
                                    @if ($item->isProses())
                                        <small class="text-muted d-block mt-1">{{ $item->tahapLabel() }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.lamaran.show', $item) }}" class="btn btn-light btn-sm">
                                        <i class="bi bi-eye" aria-hidden="true"></i>Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            @include('partials.kosong-tabel', [
                                'kosongKolom' => 5,
                                'kosongTeks' => $adaFilter ? 'Tidak ada lamaran yang cocok dengan pencarian/filter ini.' : 'Belum ada lamaran masuk.',
                                'kosongIkon' => $adaFilter ? 'bi-search' : 'bi-inbox',
                                'kosongReset' => $adaFilter ? route('admin.lamaran.index') : null,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($lamarans->hasPages())
                <div class="panel-footer px-3 py-2 border-top">
                    {{ $lamarans->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>
</main>
@endsection
