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
            <div class="heading-actions d-flex flex-wrap gap-2 align-items-center">
                <span class="badge fs-6 {{ $pengajuanCuti->badgeClass() }}">{{ $pengajuanCuti->statusLabel() }}</span>
                @include('admin.editor-data._tombol', ['entitas' => 'cuti', 'id' => $pengajuanCuti->id, 'teks' => 'Ubah semua kolom', 'kembaliKe' => route('admin.pengajuan-cuti.index')])
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="panel p-4">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-clipboard-data me-1"></i>Ringkasan Pengajuan</h2>
                    @include('admin.pengajuan._ringkasan', ['r' => \App\Support\RingkasanPengajuan::cuti($pengajuanCuti)])
                </div>
            </div>

            <div class="col-lg-5">
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
