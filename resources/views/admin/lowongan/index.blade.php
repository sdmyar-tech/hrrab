@extends('layouts.admin.app')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'status', 'tanggal_dari', 'tanggal_sampai']);
    $isAdmin = auth()->user()->isAdmin();
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-briefcase"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">Lowongan Rekrutmen</h1>
                    <p class="text-muted mb-0">Kelola & publikasikan lowongan yang terbit dari pengajuan formasi.</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.lowongan.export'])
                <a href="{{ route('admin.lowongan.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Buat Lowongan
                </a>
            </div>
        </div>

        <section class="panel" aria-label="Daftar lowongan">
            <form action="{{ route('admin.lowongan.index') }}" method="GET" id="filterLowonganForm" class="panel-filter px-3 pt-3 pb-2" role="search">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6 col-xl-4">
                        @include('partials.daftar-cari', [
                            'cariTeks' => 'Cari lowongan, contoh: Guru Matematika',
                            'cariLabel' => 'Cari lowongan menurut judul, jabatan, atau bagian',
                        ])
                    </div>
                    <div class="col-12 col-md-3">
                        <select name="status" class="form-select form-select-sm" aria-label="Status lowongan" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="dipublikasikan" {{ request('status') == 'dipublikasikan' ? 'selected' : '' }}>Dipublikasikan</option>
                            <option value="ditutup" {{ request('status') == 'ditutup' ? 'selected' : '' }}>Ditutup</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">@include('partials.filter-tanggal', ['tanggalLabel' => 'Tutup'])</div>
                    <div class="col-12 col-md-auto">@include('partials.urut', ['urutForm' => 'filterLowonganForm'])</div>
                </div>
                @include('partials.daftar-ringkas', [
                    'ringkasJumlah' => $lowongans->total(), 'ringkasSatuan' => 'lowongan',
                    'ringkasFilter' => $adaFilter, 'ringkasReset' => route('admin.lowongan.index'),
                ])
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Lowongan</th>
                            <th>Pelamar</th>
                            <th>Tanggal Tutup</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowongans as $item)
                            @php
                                $bisaDitutup = $item->status === 'dipublikasikan';
                                $adaMenu = $bisaDitutup || $isAdmin;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.lowongan.edit', $item) }}" class="nama-tautan d-block">{{ $item->judul }}</a>
                                    <small class="text-muted">{{ $item->jabatan }} · {{ $item->unit_kerja }}</small>
                                </td>
                                <td>
                                    {{ $item->lamarans_count }} pelamar
                                    <small class="text-muted d-block">Terisi {{ $item->jumlah_terisi }}/{{ $item->jumlah_dibutuhkan }} orang</small>
                                </td>
                                <td class="text-nowrap">{{ optional($item->tanggal_tutup)->translatedFormat('d M Y') ?? '-' }}</td>
                                <td><span class="badge {{ $item->status === 'dipublikasikan' ? 'text-bg-success' : ($item->status === 'ditutup' ? 'text-bg-secondary' : 'text-bg-warning') }}">{{ $item->statusLabel() }}</span></td>
                                <td class="text-end">
                                    {{-- Ubah + aksi utama sesuai status terlihat; Tutup & aksi khusus Admin di menu ⋮. --}}
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <a href="{{ route('admin.lowongan.edit', $item) }}" class="btn btn-light btn-sm">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>Ubah
                                        </a>
                                        @if ($item->status === 'draft')
                                            <form action="{{ route('admin.lowongan.publish', $item) }}" method="POST" class="m-0"
                                                data-confirm="Publikasikan lowongan {{ $item->judul }}? Lowongan akan tampil di halaman publik.">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="bi bi-megaphone" aria-hidden="true"></i>Publikasikan
                                                </button>
                                            </form>
                                        @elseif ($item->status !== 'dipublikasikan')
                                            @include('admin.lowongan._reopen-button', ['lowongan' => $item, 'small' => true])
                                        @endif
                                        @if ($adaMenu)
                                            <div class="dropdown">
                                                @include('partials.tombol-aksi-lain', ['aksiUntuk' => $item->judul])
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if ($bisaDitutup)
                                                        <li>
                                                            <form action="{{ route('admin.lowongan.close', $item) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item text-danger"
                                                                    data-confirm="true" data-icon="warning" data-title="Tutup lowongan?"
                                                                    data-text="Lowongan {{ $item->judul }} tidak lagi menerima lamaran baru."
                                                                    data-confirm-text="Ya, Tutup" data-confirm-color="#dc3545" data-cancel-text="Batal">
                                                                    <i class="bi bi-lock me-2"></i>Tutup Lowongan
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                    @if ($isAdmin)
                                                        @if ($bisaDitutup)<li><hr class="dropdown-divider"></li>@endif
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('admin.data.edit', ['lowongan', $item->id, 'kembali' => request()->fullUrl()]) }}">
                                                                <i class="bi bi-shield-lock me-2"></i>Ubah Semua Kolom (Admin)
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('admin.data.destroy', ['lowongan', $item->id]) }}" method="POST">
                                                                @csrf @method('DELETE')
                                                                <input type="hidden" name="kembali" value="{{ request()->fullUrl() }}">
                                                                <button type="submit" class="dropdown-item text-danger"
                                                                    data-confirm="true" data-icon="warning" data-title="Hapus lowongan permanen?"
                                                                    data-text="Lowongan {{ $item->judul }} akan dihapus permanen. Tindakan ini tidak bisa dibatalkan."
                                                                    data-confirm-text="Ya, Hapus" data-confirm-color="#dc3545" data-cancel-text="Batal">
                                                                    <i class="bi bi-trash me-2"></i>Hapus Permanen (Admin)
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        @else
                                            <span class="btn-ikon" aria-hidden="true"></span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @include('partials.kosong-tabel', [
                                'kosongKolom' => 5,
                                'kosongTeks' => $adaFilter ? 'Tidak ada lowongan yang cocok dengan pencarian/filter ini.' : 'Belum ada lowongan.',
                                'kosongIkon' => $adaFilter ? 'bi-search' : 'bi-briefcase',
                                'kosongReset' => $adaFilter ? route('admin.lowongan.index') : null,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($lowongans->hasPages())
                <div class="panel-footer px-3 py-2 border-top">
                    {{ $lowongans->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>
</main>
@endsection
