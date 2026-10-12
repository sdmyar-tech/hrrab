@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-plus"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">Pengajuan Formasi</h1>
                    <p class="text-muted mb-0">Permintaan kekurangan pegawai dari Kepala/Koordinator Divisi.</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pengajuan-formasi.export', 'eksporUkuran' => ''])
                @if ($canSubmit)
                    <a href="{{ route('admin.pengajuan-formasi.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Ajukan Formasi
                    </a>
                @endif
            </div>
        </div>

        <section class="panel">
            <form action="{{ route('admin.pengajuan-formasi.index') }}" method="GET" id="filterFormasiForm" class="panel-filter px-3 pt-3 pb-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="searchInput" class="form-control border-start-0"
                                placeholder="Cari jabatan atau unit kerja..." value="{{ request('q') }}" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                            <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-auto">@include('partials.urut', ['urutForm' => 'filterFormasiForm'])</div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Diajukan Oleh</th>
                            <th>Unit Kerja</th>
                            <th>Jabatan</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Lowongan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengajuanFormasis as $item)
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block">{{ $item->diajukanOleh?->name }}</span>
                                    <small class="text-muted">{{ optional($item->created_at)->translatedFormat('d M Y') }}</small>
                                </td>
                                <td>{{ $item->unit_kerja }}</td>
                                <td>
                                    {{ $item->jabatan }}
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-1 btn-detail-formasi"
                                        data-kualifikasi="{{ $item->kualifikasi }}"
                                        data-alasan="{{ $item->alasan }}"
                                        data-target="{{ optional($item->target_waktu)->translatedFormat('d M Y') ?? '-' }}"
                                        title="Lihat detail">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                </td>
                                <td>{{ $item->jumlah_dibutuhkan }} orang</td>
                                <td>
                                    <span class="badge {{ $item->badgeClass() }}">{{ $item->statusLabel() }}</span>
                                    @if ($item->status === 'rejected' && $item->catatan_admin)
                                        <small class="text-muted d-block mt-1">{{ $item->catatan_admin }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->lowongan)
                                        <a href="{{ route('admin.lowongan.edit', $item->lowongan) }}">{{ $item->lowongan->statusLabel() }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if (($item->isPending() || auth()->user()->isAdmin()) && \App\Support\RekrutmenAccess::canReviewFormasi(auth()->user(), $item))
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <form action="{{ route('admin.pengajuan-formasi.approve', $item) }}" method="POST"
                                                data-confirm="Setujui pengajuan formasi ini?">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="bi bi-check-lg"></i> Setujui
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.pengajuan-formasi.reject', $item) }}" method="POST"
                                                class="form-reject-formasi">
                                                @csrf
                                                <input type="hidden" name="catatan_admin" value="">
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-reject-formasi">
                                                    <i class="bi bi-x-lg"></i> Tolak
                                                </button>
                                            </form>
                                            @if (auth()->user()->isAdmin())
                                                @include('admin.editor-data._tombol', ['entitas' => 'formasi', 'id' => $item->id, 'teks' => '', 'hapus' => false])
                                                <form action="{{ route('admin.pengajuan-formasi.destroy', $item) }}" method="POST"
                                                    data-confirm="Hapus permanen pengajuan formasi {{ $item->jabatan }}?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    @elseif ($item->diajukan_oleh === auth()->id() && $item->isPending())
                                        <form action="{{ route('admin.pengajuan-formasi.batalkan', $item) }}" method="POST"
                                            data-confirm="Batalkan pengajuan formasi ini?">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-x-lg"></i> Batalkan
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Belum ada pengajuan formasi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pengajuanFormasis->hasPages())
                <div class="panel-footer px-3 py-2 border-top">
                    {{ $pengajuanFormasis->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterFormasiForm');
        let debounceTimer;

        if (searchInput && filterForm) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    filterForm.submit();
                }, 500);
            });
        }

        document.querySelectorAll('.btn-reject-formasi').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = btn.closest('form');

                Swal.fire({
                    icon: 'question',
                    title: 'Tolak Pengajuan Formasi?',
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

        document.querySelectorAll('.btn-detail-formasi').forEach(function (btn) {
            btn.addEventListener('click', function () {
                Swal.fire({
                    icon: 'info',
                    title: 'Detail Pengajuan Formasi',
                    html: '<div class="text-start">'
                        + '<p class="mb-1"><strong>Target Waktu:</strong> ' + (btn.dataset.target || '-') + '</p>'
                        + '<p class="mb-1"><strong>Kualifikasi:</strong><br>' + btn.dataset.kualifikasi + '</p>'
                        + '<p class="mb-0"><strong>Alasan:</strong><br>' + btn.dataset.alasan + '</p>'
                        + '</div>',
                    confirmButtonColor: '#880f12',
                    confirmButtonText: 'Tutup',
                });
            });
        });
    });
</script>
@endsection
