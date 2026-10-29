@extends('layouts.admin.app')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'jenis_cuti', 'status', 'tanggal_dari', 'tanggal_sampai']);
@endphp
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
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pengajuan-cuti.export'])
            </div>
        </div>

        <section class="panel">
            @include('admin.partials.gm-bidang-tabs', ['route' => 'admin.pengajuan-cuti.index'])

            <form action="{{ route('admin.pengajuan-cuti.index') }}" method="GET" id="filterCutiForm" class="panel-filter px-3 pt-3 pb-2">
                @if ($bidangTabs)
                    <input type="hidden" name="bidang" value="{{ request('bidang', 'saya') }}">
                @endif
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6 col-xl-3">
                        @include('partials.daftar-cari', [
                            'cariTeks' => 'Cari pegawai, contoh: Siti',
                            'cariLabel' => 'Cari pengajuan cuti menurut nama atau NIP pegawai',
                        ])
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <select name="jenis_cuti" class="form-select form-select-sm" aria-label="Jenis cuti" onchange="this.form.submit()">
                            <option value="">Semua Jenis</option>
                            <option value="tahunan" {{ request('jenis_cuti') == 'tahunan' ? 'selected' : '' }}>Cuti Tahunan</option>
                            <option value="besar" {{ request('jenis_cuti') == 'besar' ? 'selected' : '' }}>Cuti Besar</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <select name="status" class="form-select form-select-sm" aria-label="Status pengajuan" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Dari</span>
                            <input type="date" name="tanggal_dari" class="form-control form-control-sm" aria-label="Tanggal mulai dari"
                                value="{{ request('tanggal_dari') }}" title="Tanggal mulai dari" onchange="this.form.submit()">
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">s/d</span>
                            <input type="date" name="tanggal_sampai" class="form-control form-control-sm" aria-label="Tanggal mulai sampai"
                                value="{{ request('tanggal_sampai') }}" title="Tanggal mulai sampai" onchange="this.form.submit()">
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl-auto">@include('partials.urut', ['urutForm' => 'filterCutiForm'])</div>
                </div>
                @include('partials.daftar-ringkas', [
                    'ringkasJumlah' => $pengajuanCutis->total(), 'ringkasSatuan' => 'pengajuan',
                    'ringkasFilter' => $adaFilter, 'ringkasReset' => route('admin.pengajuan-cuti.index', $bidangTabs ? ['bidang' => request('bidang', 'saya')] : []),
                ])
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Jenis Cuti</th>
                            <th>Tanggal</th>
                            {{-- Disembunyikan di tablet agar kolom aksi tidak terjepit; tetap ada di desktop & kartu HP. --}}
                            <th class="d-md-none d-lg-table-cell">Pengganti Tugas</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengajuanCutis as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.pengajuan-cuti.show', $item) }}" class="nama-tautan d-block">{{ $item->dataPegawai?->nama_lengkap ?? '-' }}</a>
                                    <small class="text-muted">{{ $item->dataPegawai?->kepegawaian?->jabatan ?? '-' }}@if ($item->unit_kerja) &middot; {{ $item->unit_kerja }}@endif</small>
                                </td>
                                <td>
                                    {{ $item->jenisLabel() }}
                                    @if ($item->kategoriBesarLabel())
                                        <small class="text-muted d-block">{{ $item->kategoriBesarLabel() }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{-- Satu hari cukup satu tanggal; lama cuti di bawahnya. --}}
                                    <span class="text-nowrap">{{ $item->tanggal_mulai->translatedFormat('d M Y') }}</span>
                                    @unless ($item->tanggal_mulai->isSameDay($item->tanggal_selesai))
                                        &ndash; <span class="text-nowrap">{{ $item->tanggal_selesai->translatedFormat('d M Y') }}</span>
                                    @endunless
                                    <small class="text-muted d-block">{{ $item->lama_cuti }} hari</small>
                                </td>
                                <td class="d-md-none d-lg-table-cell">{{ $item->pengganti_nama ?? '-' }}</td>
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
                                <td class="text-end kolom-aksi">
                                    {{-- Tahap Kepala Bagian: pilih pengganti tugas dulu, lalu Setujui. --}}
                                    @include('admin.partials.aksi-pengajuan', [
                                        'detail' => route('admin.pengajuan-cuti.show', $item),
                                        'setujui' => route('admin.pengajuan-cuti.approve', $item->id),
                                        'tolak' => route('admin.pengajuan-cuti.reject', $item->id),
                                        'kelasTolak' => 'btn-reject-cuti',
                                        'judulSetujui' => 'Setujui Pengajuan Cuti?',
                                        'teksSetujui' => 'Pengajuan cuti ' . $item->dataPegawai?->nama_lengkap . ' akan disetujui pada tahap ini.',
                                        ...($item->current_level === \App\Support\PengajuanWorkflow::LEVEL_KEPALA_DIVISI ? ['pengganti' => $item->substituteOptions()] : []),
                                    ])
                                </td>
                            </tr>
                        {{-- Rincian untuk penyetuju: tampil sebelum tombol Setujui/Tolak diklik. --}}
                        @if ($item->isPending() && \App\Support\PengajuanWorkflow::canAct(auth()->user(), $item))
                            @include('admin.pengajuan._ringkasan-baris', ['r' => \App\Support\RingkasanPengajuan::cuti($item), 'kolom' => 6])
                        @endif
                        @empty
                            @include('partials.kosong-tabel', [
                                'kosongKolom' => 6,
                                'kosongTeks' => $adaFilter ? 'Tidak ada pengajuan cuti yang cocok dengan pencarian/filter ini.' : 'Belum ada pengajuan cuti.',
                                'kosongIkon' => $adaFilter ? 'bi-search' : 'bi-calendar2-check',
                                'kosongReset' => $adaFilter ? route('admin.pengajuan-cuti.index') : null,
                            ])
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

<style>
    /* Kolom aksi cukup lebar untuk dropdown pengganti + tombol (tidak terjepit). */
    /* Mengalahkan aturan global .table td (min-width: 0; kolom terakhir width: 1%). */
    .table tbody td.kolom-aksi { width: 200px; min-width: 200px; white-space: normal !important; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Cari otomatis ditangani global (main.js: data-cari-otomatis).
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
                    icon: 'warning',
                    title: 'Tolak pengajuan cuti?',
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
