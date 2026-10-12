@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-calendar2-check"></i></span>
                <div>
                    <p class="eyebrow mb-1">Manajemen SDM</p>
                    <h1 class="h3 mb-1">Pengajuan Cuti</h1>
                    <p class="text-muted mb-0">Tinjau, setujui, atau tolak pengajuan cuti pegawai.</p>
                </div>
            </div>
        </div>

        <section class="panel">
            @include('admin.partials.gm-bidang-tabs', ['route' => 'admin.pengajuan-cuti.index'])

            <form action="{{ route('admin.pengajuan-cuti.index') }}" method="GET" id="filterCutiForm" class="panel-filter px-3 pt-3 pb-2">
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
                    <div class="col-md-2">
                        <select name="jenis_cuti" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Jenis</option>
                            <option value="tahunan" {{ request('jenis_cuti') == 'tahunan' ? 'selected' : '' }}>Cuti Tahunan</option>
                            <option value="besar" {{ request('jenis_cuti') == 'besar' ? 'selected' : '' }}>Cuti Besar</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="tanggal_dari" class="form-control form-control-sm"
                            value="{{ request('tanggal_dari') }}" title="Tanggal mulai dari"
                            onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="tanggal_sampai" class="form-control form-control-sm"
                            value="{{ request('tanggal_sampai') }}" title="Tanggal mulai sampai"
                            onchange="this.form.submit()">
                    </div>
                    <div class="col-md-auto">@include('partials.urut', ['urutForm' => 'filterCutiForm'])</div>
                    @if (request()->hasAny(['q', 'jenis_cuti', 'status', 'tanggal_dari', 'tanggal_sampai']))
                        <div class="col-md-1">
                            <a href="{{ route('admin.pengajuan-cuti.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset filter">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        </div>
                    @endif
                    <div class="col-auto ms-auto text-end">
                        @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pengajuan-cuti.export'])
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Jenis Cuti</th>
                            <th>Tanggal</th>
                            <th>Lama</th>
                            <th>Pengganti Tugas</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengajuanCutis as $item)
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block">{{ $item->dataPegawai?->nama_lengkap ?? '-' }}</span>
                                    <small class="text-muted">{{ $item->dataPegawai?->kepegawaian?->jabatan ?? '-' }}@if ($item->unit_kerja) &middot; {{ $item->unit_kerja }}@endif</small>
                                </td>
                                <td>
                                    {{ $item->jenisLabel() }}
                                    @if ($item->kategoriBesarLabel())
                                        <small class="text-muted d-block">{{ $item->kategoriBesarLabel() }}</small>
                                    @endif
                                </td>
                                <td>{{ $item->tanggal_mulai->translatedFormat('d M Y') }} - {{ $item->tanggal_selesai->translatedFormat('d M Y') }}</td>
                                <td>{{ $item->lama_cuti }} hari</td>
                                <td>{{ $item->pengganti_nama ?? '-' }}</td>
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
                                    <a href="{{ route('admin.pengajuan-cuti.show', $item) }}" class="btn btn-outline-secondary btn-sm mb-1">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                    @if ($item->isPending())
                                        @if (\App\Support\PengajuanWorkflow::canAct(auth()->user(), $item))
                                            @if ($item->current_level === \App\Support\PengajuanWorkflow::LEVEL_KEPALA_DIVISI)
                                                <form action="{{ route('admin.pengajuan-cuti.approve', $item->id) }}" method="POST" class="form-approve-kepala mb-1">
                                                    @csrf
                                                    <div class="input-group input-group-sm">
                                                        <select name="pengganti_nama" class="form-select form-select-sm" required>
                                                            <option value="">Pilih pengganti...</option>
                                                            @foreach ($item->substituteOptions() as $nama)
                                                                <option value="{{ $nama }}">{{ $nama }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="btn btn-success btn-sm" title="Setujui &amp; teruskan ke Kepala SDM">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                    </div>
                                                </form>
                                                <form action="{{ route('admin.pengajuan-cuti.reject', $item->id) }}" method="POST" class="form-reject-cuti">
                                                    @csrf
                                                    <input type="hidden" name="catatan_admin" value="">
                                                    <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-reject-cuti">
                                                        <i class="bi bi-x-lg"></i> Tolak
                                                    </button>
                                                </form>
                                            @else
                                                <div class="d-flex gap-1 justify-content-end">
                                                    <form action="{{ route('admin.pengajuan-cuti.approve', $item->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm"
                                                            data-confirm="true"
                                                            data-icon="question"
                                                            data-title="Setujui Pengajuan Cuti?"
                                                            data-text="Pengajuan cuti {{ $item->dataPegawai?->nama_lengkap }} akan disetujui pada tahap ini."
                                                            data-confirm-text="Ya, Setujui"
                                                            data-confirm-color="#198754"
                                                            data-cancel-text="Batal">
                                                            <i class="bi bi-check-lg"></i> Setujui
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.pengajuan-cuti.reject', $item->id) }}" method="POST" class="form-reject-cuti">
                                                        @csrf
                                                        <input type="hidden" name="catatan_admin" value="">
                                                        <button type="button" class="btn btn-outline-danger btn-sm btn-reject-cuti">
                                                            <i class="bi bi-x-lg"></i> Tolak
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted small">Menunggu: {{ $item->currentLevelLabel() }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">Sudah diproses</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Belum ada pengajuan cuti.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pengajuanCutis->hasPages())
                <div class="panel-footer px-3 py-2 border-top">
                    {{ $pengajuanCutis->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterCutiForm');
        let debounceTimer;

        if (searchInput && filterForm) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    filterForm.submit();
                }, 500);
            });
        }

        document.querySelectorAll('.form-approve-kepala').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (form.dataset.confirmed === '1') return;

                if (!form.checkValidity()) {
                    form.reportValidity();
                    e.preventDefault();
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    icon: 'question',
                    title: 'Setujui & Teruskan ke Tahap Berikutnya?',
                    text: 'Pengganti tugas yang dipilih akan final dan tidak perlu persetujuan lagi.',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Setujui',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.dataset.confirmed = '1';
                        form.submit();
                    }
                });
            });
        });

        document.querySelectorAll('.btn-reject-cuti').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = btn.closest('form');

                Swal.fire({
                    icon: 'question',
                    title: 'Tolak Pengajuan Cuti?',
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
