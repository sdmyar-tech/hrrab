@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-clock-history"></i></span>
                <div>
                    <p class="eyebrow mb-1">Manajemen SDM</p>
                    <h1 class="h3 mb-1">Pengajuan Lembur</h1>
                    <p class="text-muted mb-0">Tinjau, setujui, atau tolak pengajuan lembur pegawai.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pengajuan-lembur.export'])
            </div>
        </div>

        <section class="panel">
            @include('admin.partials.gm-bidang-tabs', ['route' => 'admin.pengajuan-lembur.index'])

            <form action="{{ route('admin.pengajuan-lembur.index') }}" method="GET" id="filterLemburForm" class="panel-filter px-3 pt-3 pb-2">
                @if ($bidangTabs)
                    <input type="hidden" name="bidang" value="{{ request('bidang', 'saya') }}">
                @endif
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="searchInput" class="form-control border-start-0"
                                placeholder="Cari nama atau NIP..." value="{{ request('q') }}" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Dari</span>
                            <input type="date" name="tanggal_dari" class="form-control form-control-sm" aria-label="Tanggal dari"
                                value="{{ request('tanggal_dari') }}" title="Tanggal dari" onchange="this.form.submit()">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">s/d</span>
                            <input type="date" name="tanggal_sampai" class="form-control form-control-sm" aria-label="Tanggal sampai"
                                value="{{ request('tanggal_sampai') }}" title="Tanggal sampai" onchange="this.form.submit()">
                        </div>
                    </div>
                    <div class="col-md-auto">@include('partials.urut', ['urutForm' => 'filterLemburForm'])</div>
                    @if (request()->hasAny(['q', 'status', 'tanggal_dari', 'tanggal_sampai']))
                        <div class="col-md-1">
                            <a href="{{ route('admin.pengajuan-lembur.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset filter">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Tanggal</th>
                            <th>Uraian Tugas</th>
                            <th>Jam</th>
                            <th>Jml Jam</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengajuanLemburs as $item)
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block">{{ $item->dataPegawai?->nama_lengkap ?? '-' }}</span>
                                    <small class="text-muted">{{ $item->dataPegawai?->nip ?? '-' }}@if ($item->unit_kerja) &middot; {{ $item->unit_kerja }}@endif</small>
                                </td>
                                <td>
                                    {{ optional($item->tanggal)->translatedFormat('d M Y') }}
                                    <small class="text-muted d-block">{{ $item->hari }}</small>
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($item->uraian_tugas, 60) }}</td>
                                <td>{{ substr((string) $item->jam_mulai, 0, 5) }} - {{ substr((string) $item->jam_selesai, 0, 5) }}</td>
                                <td>{{ $item->jumlah_jam }} jam</td>
                                <td>
                                    <span class="badge {{ $item->badgeClass() }}">{{ $item->statusLabel() }}</span>
                                    @if ($item->isPending())
                                        <small class="text-muted d-block mt-1">Tahap: {{ $item->currentLevelLabel() }}</small>
                                    @endif
                                    @include('admin.partials.gm-pengganti-badge', ['pengajuan' => $item])
                                    @if ($item->status === 'rejected' && $item->catatan_admin)
                                        <small class="text-muted d-block mt-1">{{ $item->catatan_admin }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @include('admin.partials.aksi-pengajuan', [
                                        'detail' => route('admin.pengajuan-lembur.show', $item),
                                        'setujui' => route('admin.pengajuan-lembur.approve', $item->id),
                                        'tolak' => route('admin.pengajuan-lembur.reject', $item->id),
                                        'kelasTolak' => 'btn-reject-lembur',
                                        'judulSetujui' => 'Setujui Pengajuan Lembur?',
                                        'teksSetujui' => 'Pengajuan lembur ' . $item->dataPegawai?->nama_lengkap . ' pada ' . optional($item->tanggal)->translatedFormat('d M Y') . ' akan disetujui.',
                                    ])
                                </td>
                            </tr>
                        {{-- Rincian untuk penyetuju: tampil sebelum tombol Setujui/Tolak diklik. --}}
                        @if ($item->isPending() && \App\Support\PengajuanWorkflow::canAct(auth()->user(), $item))
                            @include('admin.pengajuan._ringkasan-baris', ['r' => \App\Support\RingkasanPengajuan::lembur($item), 'kolom' => 7])
                        @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Belum ada pengajuan lembur.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pengajuanLemburs->hasPages())
                <div class="panel-footer px-3 py-2 border-top">
                    {{ $pengajuanLemburs->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterLemburForm');
        let debounceTimer;

        if (searchInput && filterForm) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    filterForm.submit();
                }, 500);
            });
        }

        document.querySelectorAll('.btn-reject-lembur').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = btn.closest('form');

                Swal.fire({
                    icon: 'question',
                    title: 'Tolak Pengajuan Lembur?',
                    input: 'textarea',
                    inputLabel: 'Alasan penolakan',
                    inputPlaceholder: 'Tuliskan alasan penolakan...',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Tolak',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    preConfirm: function (value) {
                        if (!value || !value.trim()) {
                            Swal.showValidationMessage('Alasan penolakan wajib diisi.');
                        }
                        return value;
                    },
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.querySelector('[name="catatan_admin"]').value = result.value;
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endsection
