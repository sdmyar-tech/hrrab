@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <a href="{{ route('admin.pengajuan-lembur.index') }}" class="text-decoration-none text-muted small d-inline-block mb-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Pengajuan Lembur
        </a>

        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-clock-history"></i></span>
                <div>
                    <p class="eyebrow mb-1">Manajemen SDM</p>
                    <h1 class="h3 mb-1">Detail Pengajuan Lembur</h1>
                    <p class="text-muted mb-0">{{ $pengajuanLembur->dataPegawai?->nama_lengkap }}</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2 align-items-center">
                <span class="badge fs-6 {{ $pengajuanLembur->badgeClass() }}">{{ $pengajuanLembur->statusLabel() }}</span>
                @include('admin.editor-data._tombol', ['entitas' => 'lembur', 'id' => $pengajuanLembur->id, 'teks' => 'Ubah semua kolom', 'kembaliKe' => route('admin.pengajuan-lembur.index')])
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="panel p-4 mb-3">
                    <h2 class="h6 fw-bold mb-3">Data Pegawai</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Nama Lengkap</dt>
                        <dd class="col-7">{{ $pengajuanLembur->dataPegawai?->nama_lengkap ?? '-' }}</dd>
                        <dt class="col-5 text-muted">NIP</dt>
                        <dd class="col-7">{{ $pengajuanLembur->dataPegawai?->nip ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Jabatan</dt>
                        <dd class="col-7">{{ $pengajuanLembur->dataPegawai?->kepegawaian?->jabatan ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Unit Kerja</dt>
                        <dd class="col-7">{{ $pengajuanLembur->unit_kerja ?? $pengajuanLembur->dataPegawai?->kepegawaian?->unit_kerja ?? '-' }}</dd>
                    </dl>
                </div>

                <div class="panel p-4">
                    <h2 class="h6 fw-bold mb-3">Detail Pengajuan</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Tanggal</dt>
                        <dd class="col-7">{{ $pengajuanLembur->tanggal->format('d M Y') }} <span class="text-muted">({{ $pengajuanLembur->hari }})</span></dd>
                        <dt class="col-5 text-muted">Jam</dt>
                        <dd class="col-7">{{ $pengajuanLembur->jam_mulai }} &ndash; {{ $pengajuanLembur->jam_selesai }}</dd>
                        <dt class="col-5 text-muted">Jumlah Jam</dt>
                        <dd class="col-7">{{ $pengajuanLembur->jumlah_jam }} jam</dd>
                        <dt class="col-5 text-muted">Uraian Tugas</dt>
                        <dd class="col-7" style="white-space:pre-line;">{{ $pengajuanLembur->uraian_tugas }}</dd>
                        <dt class="col-5 text-muted">Tanggal Pengajuan</dt>
                        <dd class="col-7">{{ optional($pengajuanLembur->created_at)->format('d M Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="panel p-4 mb-3">
                    <h2 class="h6 fw-bold mb-3">Riwayat Persetujuan</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Tahap Saat Ini</dt>
                        <dd class="col-7">{{ $pengajuanLembur->isPending() ? $pengajuanLembur->currentLevelLabel() : '-' }}</dd>
                        <dt class="col-5 text-muted">Disetujui Kepala Bagian</dt>
                        <dd class="col-7">
                            @if ($pengajuanLembur->kepala_approved_by)
                                {{ $pengajuanLembur->kepalaApprovedBy?->name }}
                                <br><span class="text-muted">{{ optional($pengajuanLembur->kepala_approved_at)->format('d M Y H:i') }}</span>
                            @else
                                <span class="text-muted">Belum</span>
                            @endif
                        </dd>
                        <dt class="col-5 text-muted">Disetujui GM</dt>
                        <dd class="col-7">
                            @if ($pengajuanLembur->gm_approved_by)
                                {{ $pengajuanLembur->gmApprovedBy?->name }}
                                @if ($pengajuanLembur->gm_pengganti)
                                    <span class="badge text-bg-warning">GM pengganti</span>
                                @endif
                                <br><span class="text-muted">{{ optional($pengajuanLembur->gm_approved_at)->format('d M Y H:i') }}</span>
                            @else
                                <span class="text-muted">Belum</span>
                            @endif
                        </dd>
                        <dt class="col-5 text-muted">Disetujui Akhir (Kepala SDM/GM)</dt>
                        <dd class="col-7">
                            @if ($pengajuanLembur->approved_by)
                                {{ $pengajuanLembur->approvedBy?->name }}
                                <br><span class="text-muted">{{ optional($pengajuanLembur->approved_at)->format('d M Y H:i') }}</span>
                            @else
                                <span class="text-muted">Belum</span>
                            @endif
                        </dd>
                        @if ($pengajuanLembur->status === 'rejected' && $pengajuanLembur->catatan_admin)
                            <dt class="col-5 text-muted">Alasan Penolakan</dt>
                            <dd class="col-7">{{ $pengajuanLembur->catatan_admin }}</dd>
                        @endif
                    </dl>
                </div>

                @if ($pengajuanLembur->isPending())
                    @if (\App\Support\PengajuanWorkflow::canAct(auth()->user(), $pengajuanLembur))
                        <div class="panel p-4">
                            <h2 class="h6 fw-bold mb-3">Aksi</h2>
                            @include('admin.partials.gm-pengganti-badge', ['pengajuan' => $pengajuanLembur])

                            <form action="{{ route('admin.pengajuan-lembur.approve', $pengajuanLembur->id) }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-success"
                                    data-confirm="true"
                                    data-icon="question"
                                    data-title="Setujui Pengajuan Lembur?"
                                    data-text="Pengajuan lembur {{ $pengajuanLembur->dataPegawai?->nama_lengkap }} pada {{ optional($pengajuanLembur->tanggal)->format('d M Y') }} akan disetujui."
                                    data-confirm-text="Ya, Setujui"
                                    data-confirm-color="#198754"
                                    data-cancel-text="Batal">
                                    <i class="bi bi-check-lg"></i> Setujui
                                </button>
                            </form>

                            <form action="{{ route('admin.pengajuan-lembur.reject', $pengajuanLembur->id) }}" method="POST" class="form-reject-lembur">
                                @csrf
                                <input type="hidden" name="catatan_admin" value="">
                                <button type="button" class="btn btn-outline-danger btn-reject-lembur">
                                    <i class="bi bi-x-lg"></i> Tolak
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-secondary small mb-0">Menunggu persetujuan: {{ $pengajuanLembur->currentLevelLabel() }}</div>
                    @endif
                @endif

                @can('kelola-sistem')
                    @include('admin.pengajuan-lembur._ubah-admin')
                @endcan
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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
