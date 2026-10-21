@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <a href="{{ route('admin.izin-keluar.index') }}" class="text-decoration-none text-muted small d-inline-block mb-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Izin Keluar
        </a>

        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-door-open"></i></span>
                <div>
                    <p class="eyebrow mb-1">Manajemen SDM</p>
                    <h1 class="h3 mb-1">Detail Izin Keluar</h1>
                    <p class="text-muted mb-0">{{ $izinKeluar->dataPegawai?->nama_lengkap }}</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2 align-items-center">
                <span class="badge fs-6 {{ $izinKeluar->badgeClass() }}">{{ $izinKeluar->statusLabel() }}</span>
                @if ($izinKeluar->isApproved())
                    <a href="{{ route('pegawai.izin-keluar.tiket', $izinKeluar) }}" class="btn btn-success btn-sm"><i class="bi bi-ticket-perforated me-1"></i>Lihat Tiket</a>
                @endif
                @include('admin.editor-data._tombol', ['entitas' => 'izin_keluar', 'id' => $izinKeluar->id, 'teks' => 'Ubah semua kolom', 'kembaliKe' => route('admin.izin-keluar.index')])
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="panel p-4">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-clipboard-data me-1"></i>Ringkasan Pengajuan</h2>
                    @include('admin.pengajuan._ringkasan', ['r' => \App\Support\RingkasanPengajuan::izinKeluar($izinKeluar)])
                </div>
            </div>

            <div class="col-lg-5">
                @if ($izinKeluar->isPending())
                    @if (\App\Support\PengajuanWorkflow::canAct(auth()->user(), $izinKeluar))
                        <div class="panel p-4">
                            <h2 class="h6 fw-bold mb-3">Aksi</h2>
                            @include('admin.partials.gm-pengganti-badge', ['pengajuan' => $izinKeluar])
                            <form action="{{ route('admin.izin-keluar.approve', $izinKeluar) }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-success w-100"
                                    data-confirm="true" data-icon="question" data-title="Setujui Izin Keluar?"
                                    data-text="Izin keluar {{ $izinKeluar->dataPegawai?->nama_lengkap }} pada {{ $izinKeluar->tanggal->translatedFormat('d M Y') }} ({{ $izinKeluar->jamLabel() }}) akan disetujui."
                                    data-confirm-text="Ya, Setujui" data-confirm-color="#198754" data-cancel-text="Batal">
                                    <i class="bi bi-check-lg"></i> Setujui
                                </button>
                            </form>
                            <form action="{{ route('admin.izin-keluar.reject', $izinKeluar) }}" method="POST">
                                @csrf
                                <input type="hidden" name="catatan_admin" value="">
                                <button type="button" class="btn btn-outline-danger w-100 btn-tolak-izin"><i class="bi bi-x-lg"></i> Tolak</button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-secondary small mb-0">Menunggu persetujuan: {{ $izinKeluar->currentLevelLabel() }}</div>
                    @endif
                @endif

                @can('kelola-sistem')
                    @include('admin.izin-keluar._ubah-admin')
                @endcan
            </div>
        </div>
    </div>
</main>

@include('admin.izin-keluar._skrip-tolak')
@endsection
