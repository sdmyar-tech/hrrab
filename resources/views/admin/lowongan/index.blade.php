@extends('layouts.admin.app')

@section('content')
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
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.lowongan.export', 'eksporUkuran' => ''])
                <a href="{{ route('admin.lowongan.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Buat Lowongan
                </a>
            </div>
        </div>

        <section class="panel">
            <form action="{{ route('admin.lowongan.index') }}" method="GET" id="filterLowonganForm" class="panel-filter px-3 pt-3 pb-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="searchInput" class="form-control border-start-0"
                                placeholder="Cari judul, jabatan, atau unit kerja..." value="{{ request('q') }}" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="dipublikasikan" {{ request('status') == 'dipublikasikan' ? 'selected' : '' }}>Dipublikasikan</option>
                            <option value="ditutup" {{ request('status') == 'ditutup' ? 'selected' : '' }}>Ditutup</option>
                        </select>
                    </div>
                    <div class="col-md-auto">@include('partials.urut', ['urutForm' => 'filterLowonganForm'])</div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Unit Kerja</th>
                            <th>Kebutuhan</th>
                            <th>Pelamar</th>
                            <th>Tanggal Tutup</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowongans as $item)
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block">{{ $item->judul }}</span>
                                    <small class="text-muted">{{ $item->jabatan }}</small>
                                </td>
                                <td>{{ $item->unit_kerja }}</td>
                                <td>{{ $item->jumlah_terisi }}/{{ $item->jumlah_dibutuhkan }} orang</td>
                                <td>{{ $item->lamarans_count }}</td>
                                <td>{{ optional($item->tanggal_tutup)->format('d M Y') ?? '-' }}</td>
                                <td><span class="badge {{ $item->status === 'dipublikasikan' ? 'text-bg-success' : ($item->status === 'ditutup' ? 'text-bg-secondary' : 'text-bg-warning') }}">{{ $item->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="{{ route('admin.lowongan.edit', $item) }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        @if ($item->status === 'draft')
                                            <form action="{{ route('admin.lowongan.publish', $item) }}" method="POST"
                                                data-confirm="Publikasikan lowongan ini ke publik?">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="bi bi-megaphone"></i> Publikasikan
                                                </button>
                                            </form>
                                        @elseif ($item->status === 'dipublikasikan')
                                            <form action="{{ route('admin.lowongan.close', $item) }}" method="POST"
                                                data-confirm="Tutup lowongan ini?">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-lock"></i> Tutup
                                                </button>
                                            </form>
                                        @else
                                            @include('admin.lowongan._reopen-button', ['lowongan' => $item, 'small' => true])
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Belum ada lowongan.
                                </td>
                            </tr>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterLowonganForm');
        let debounceTimer;

        if (searchInput && filterForm) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    filterForm.submit();
                }, 500);
            });
        }
    });
</script>
@endsection
