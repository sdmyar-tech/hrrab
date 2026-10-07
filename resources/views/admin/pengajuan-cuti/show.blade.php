@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <a href="{{ route('admin.pengajuan-cuti.index') }}" class="text-decoration-none text-muted small d-inline-block mb-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Pengajuan Cuti
        </a>

        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-calendar2-check"></i></span>
                <div>
                    <p class="eyebrow mb-1">Manajemen SDM</p>
                    <h1 class="h3 mb-1">Detail Pengajuan Cuti</h1>
                    <p class="text-muted mb-0">{{ $pengajuanCuti->dataPegawai?->nama_lengkap }}</p>
                </div>
            </div>
            <div class="heading-actions">
                <span class="badge fs-6 {{ $pengajuanCuti->badgeClass() }}">{{ $pengajuanCuti->statusLabel() }}</span>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="panel p-4 mb-3">
                    <h2 class="h6 fw-bold mb-3">Data Pegawai</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Nama Lengkap</dt>
                        <dd class="col-7">{{ $pengajuanCuti->dataPegawai?->nama_lengkap ?? '-' }}</dd>
                        <dt class="col-5 text-muted">NIP</dt>
                        <dd class="col-7">{{ $pengajuanCuti->dataPegawai?->nip ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Jabatan</dt>
                        <dd class="col-7">{{ $pengajuanCuti->dataPegawai?->kepegawaian?->jabatan ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Unit Kerja</dt>
                        <dd class="col-7">{{ $pengajuanCuti->unit_kerja ?? $pengajuanCuti->dataPegawai?->kepegawaian?->unit_kerja ?? '-' }}</dd>
                    </dl>
                </div>

                <div class="panel p-4">
                    <h2 class="h6 fw-bold mb-3">Detail Pengajuan</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Jenis Cuti</dt>
                        <dd class="col-7">
                            {{ $pengajuanCuti->jenisLabel() }}
                            @if ($pengajuanCuti->kategoriBesarLabel())
                                <br><span class="text-muted">{{ $pengajuanCuti->kategoriBesarLabel() }}</span>
                            @endif
                        </dd>
                        <dt class="col-5 text-muted">Tanggal</dt>
                        <dd class="col-7">{{ $pengajuanCuti->tanggal_mulai->format('d M Y') }} &ndash; {{ $pengajuanCuti->tanggal_selesai->format('d M Y') }}</dd>
                        <dt class="col-5 text-muted">Lama Cuti</dt>
                        <dd class="col-7">{{ $pengajuanCuti->lama_cuti }} hari</dd>
                        <dt class="col-5 text-muted">Sisa Cuti Tahunan</dt>
                        <dd class="col-7">{{ \App\Models\PengajuanCuti::sisaCutiTahunan($pengajuanCuti->dataPegawai, $pengajuanCuti->tanggal_mulai->year) }} / {{ \App\Models\PengajuanCuti::ANNUAL_QUOTA }} hari</dd>
                        <dt class="col-5 text-muted">Jumlah Pengajuan Tahun Ini</dt>
                        <dd class="col-7">{{ \App\Models\PengajuanCuti::jumlahPengajuanTahunIni($pengajuanCuti->dataPegawai, $pengajuanCuti->tanggal_mulai->year) }} / {{ \App\Models\PengajuanCuti::MAX_PENGAJUAN_PER_TAHUN }} kali</dd>
                        <dt class="col-5 text-muted">Pengganti Tugas</dt>
                        <dd class="col-7">{{ $pengajuanCuti->pengganti_nama ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Keterangan</dt>
                        <dd class="col-7">{{ $pengajuanCuti->keterangan ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Tanggal Pengajuan</dt>
                        <dd class="col-7">{{ optional($pengajuanCuti->created_at)->format('d M Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="panel p-4 mb-3">
                    <h2 class="h6 fw-bold mb-3">Riwayat Persetujuan</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Tahap Saat Ini</dt>
                        <dd class="col-7">{{ $pengajuanCuti->isPending() ? $pengajuanCuti->currentLevelLabel() : '-' }}</dd>
                        <dt class="col-5 text-muted">Disetujui Kepala Bagian</dt>
                        <dd class="col-7">
                            @if ($pengajuanCuti->kepala_approved_by)
                                {{ $pengajuanCuti->kepalaApprovedBy?->name }}
                                <br><span class="text-muted">{{ optional($pengajuanCuti->kepala_approved_at)->format('d M Y H:i') }}</span>
                            @else
                                <span class="text-muted">Belum</span>
                            @endif
                        </dd>
                        <dt class="col-5 text-muted">Disetujui GM</dt>
                        <dd class="col-7">
                            @if ($pengajuanCuti->gm_approved_by)
                                {{ $pengajuanCuti->gmApprovedBy?->name }}
                                @if ($pengajuanCuti->gm_pengganti)
                                    <span class="badge text-bg-warning">GM pengganti</span>
                                @endif
                                <br><span class="text-muted">{{ optional($pengajuanCuti->gm_approved_at)->format('d M Y H:i') }}</span>
                            @else
                                <span class="text-muted">Belum</span>
                            @endif
                        </dd>
                        <dt class="col-5 text-muted">Disetujui Akhir (Kepala SDM/GM)</dt>
                        <dd class="col-7">
                            @if ($pengajuanCuti->approved_by)
                                {{ $pengajuanCuti->approvedBy?->name }}
                                <br><span class="text-muted">{{ optional($pengajuanCuti->approved_at)->format('d M Y H:i') }}</span>
                            @else
                                <span class="text-muted">Belum</span>
                            @endif
                        </dd>
                        @if ($pengajuanCuti->status === 'rejected' && $pengajuanCuti->catatan_admin)
                            <dt class="col-5 text-muted">Alasan Penolakan</dt>
                            <dd class="col-7">{{ $pengajuanCuti->catatan_admin }}</dd>
                        @endif
                    </dl>
                </div>

                @if ($pengajuanCuti->isPending())
                    @if (\App\Support\PengajuanWorkflow::canAct(auth()->user(), $pengajuanCuti))
                        <div class="panel p-4">
                            <h2 class="h6 fw-bold mb-3">Aksi</h2>
                            @include('admin.partials.gm-pengganti-badge', ['pengajuan' => $pengajuanCuti])

                            @if ($pengajuanCuti->current_level === \App\Support\PengajuanWorkflow::LEVEL_KEPALA_DIVISI)
                                <form action="{{ route('admin.pengajuan-cuti.approve', $pengajuanCuti->id) }}" method="POST" class="form-approve-kepala mb-2">
                                    @csrf
                                    <label class="form-label small mb-1">Pengganti Tugas</label>
                                    <div class="input-group">
                                        <select name="pengganti_nama" class="form-select" required>
                                            <option value="">Pilih pengganti...</option>
                                            @foreach ($pengajuanCuti->substituteOptions() as $nama)
                                                <option value="{{ $nama }}">{{ $nama }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-success" title="Setujui &amp; teruskan ke Kepala SDM">
                                            <i class="bi bi-check-lg"></i> Setujui
                                        </button>
                                    </div>
                                </form>
                            @else
                                <form action="{{ route('admin.pengajuan-cuti.approve', $pengajuanCuti->id) }}" method="POST" class="mb-2">
                                    @csrf
                                    <button type="submit" class="btn btn-success"
                                        data-confirm="true"
                                        data-icon="question"
                                        data-title="Setujui Pengajuan Cuti?"
                                        data-text="Pengajuan cuti {{ $pengajuanCuti->dataPegawai?->nama_lengkap }} akan disetujui pada tahap ini."
                                        data-confirm-text="Ya, Setujui"
                                        data-confirm-color="#198754"
                                        data-cancel-text="Batal">
                                        <i class="bi bi-check-lg"></i> Setujui
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('admin.pengajuan-cuti.reject', $pengajuanCuti->id) }}" method="POST" class="form-reject-cuti">
                                @csrf
                                <input type="hidden" name="catatan_admin" value="">
                                <button type="button" class="btn btn-outline-danger btn-reject-cuti">
                                    <i class="bi bi-x-lg"></i> Tolak
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-secondary small mb-0">Menunggu persetujuan: {{ $pengajuanCuti->currentLevelLabel() }}</div>
                    @endif
                @endif

                @can('kelola-sistem')
                    @include('admin.pengajuan-cuti._ubah-admin')
                @endcan
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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
