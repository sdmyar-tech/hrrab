@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-door-open"></i></span>
                <div>
                    <p class="eyebrow mb-1">Manajemen SDM</p>
                    <h1 class="h3 mb-1">Izin Keluar Jam Kerja</h1>
                    <p class="text-muted mb-0">Setujui atau tolak izin keluar. Setelah persetujuan akhir, pegawai mendapat tiket untuk ditunjukkan ke security.</p>
                </div>
            </div>
        </div>

        <section class="panel">
            @include('admin.partials.gm-bidang-tabs', ['route' => 'admin.izin-keluar.index'])

            <form action="{{ route('admin.izin-keluar.index') }}" method="GET" id="filterIzinKeluarForm" class="panel-filter px-3 pt-3 pb-2">
                @if ($bidangTabs)
                    <input type="hidden" name="bidang" value="{{ request('bidang', 'saya') }}">
                @endif
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="searchInput" class="form-control border-start-0"
                                placeholder="Cari nama, NIP, kode tiket..." value="{{ request('q') }}" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" @selected(request('status') == 'pending')>Menunggu</option>
                            <option value="approved" @selected(request('status') == 'approved')>Disetujui</option>
                            <option value="rejected" @selected(request('status') == 'rejected')>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Dari</span>
                            <input type="date" name="tanggal_dari" class="form-control form-control-sm" aria-label="Tanggal dari"
                                value="{{ request('tanggal_dari') }}" onchange="this.form.submit()">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">s/d</span>
                            <input type="date" name="tanggal_sampai" class="form-control form-control-sm" aria-label="Tanggal sampai"
                                value="{{ request('tanggal_sampai') }}" onchange="this.form.submit()">
                        </div>
                    </div>
                    <div class="col-md-auto">@include('partials.urut', ['urutForm' => 'filterIzinKeluarForm'])</div>
                    @if (request()->hasAny(['q', 'status', 'tanggal_dari', 'tanggal_sampai']))
                        <div class="col-md-1">
                            <a href="{{ route('admin.izin-keluar.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset filter"><i class="bi bi-x-lg"></i></a>
                        </div>
                    @endif
                    <div class="col-auto ms-auto text-end">
                        @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.izin-keluar.export'])
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Keperluan</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($izinKeluars as $item)
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block">{{ $item->dataPegawai?->nama_lengkap ?? '-' }}</span>
                                    <small class="text-muted">{{ $item->dataPegawai?->nip ?? '-' }}@if ($item->unit_kerja) &middot; {{ $item->unit_kerja }}@endif</small>
                                </td>
                                <td>
                                    {{ $item->tanggal->translatedFormat('d M Y') }}
                                    <small class="text-muted d-block">{{ $item->tanggal->translatedFormat('l') }}</small>
                                </td>
                                <td>{{ $item->jamLabel() }}</td>
                                <td>
                                    {{ $item->keperluanLabel() }}
                                    <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($item->alasan, 60) }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $item->badgeClass() }}">{{ $item->statusLabel() }}</span>
                                    @if ($item->isPending())
                                        <small class="text-muted d-block mt-1">Tahap: {{ $item->currentLevelLabel() }}</small>
                                    @elseif ($item->kode_tiket)
                                        <small class="text-muted d-block mt-1 font-monospace">{{ $item->kode_tiket }}</small>
                                    @endif
                                    @include('admin.partials.gm-pengganti-badge', ['pengajuan' => $item])
                                    @if ($item->status === 'rejected' && $item->catatan_admin)
                                        <small class="text-muted d-block mt-1">{{ $item->catatan_admin }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @include('admin.partials.aksi-pengajuan', [
                                        'detail' => route('admin.izin-keluar.show', $item),
                                        'setujui' => route('admin.izin-keluar.approve', $item),
                                        'tolak' => route('admin.izin-keluar.reject', $item),
                                        'kelasTolak' => 'btn-tolak-izin',
                                        'judulSetujui' => 'Setujui Izin Keluar?',
                                        'teksSetujui' => 'Izin keluar ' . $item->dataPegawai?->nama_lengkap . ' pada ' . $item->tanggal->translatedFormat('d M Y') . ' (' . $item->jamLabel() . ') akan disetujui.',
                                        'tambahan' => $item->isApproved() ? [[route('pegawai.izin-keluar.tiket', $item), 'Tiket', 'bi-ticket-perforated', 'btn-outline-success']] : [],
                                    ])
                                </td>
                            </tr>
                        {{-- Rincian untuk penyetuju: tampil sebelum tombol Setujui/Tolak diklik. --}}
                        @if ($item->isPending() && \App\Support\PengajuanWorkflow::canAct(auth()->user(), $item))
                            @include('admin.pengajuan._ringkasan-baris', ['r' => \App\Support\RingkasanPengajuan::izinKeluar($item), 'kolom' => 6])
                        @endif
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Belum ada izin keluar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($izinKeluars->hasPages())
                <div class="panel-footer px-3 py-2 border-top">
                    {{ $izinKeluars->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>
</main>

@include('admin.izin-keluar._skrip-tolak')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterIzinKeluarForm');
        let jeda;
        searchInput?.addEventListener('input', function () {
            clearTimeout(jeda);
            jeda = setTimeout(() => filterForm.submit(), 500);
        });
    });
</script>
@endsection
