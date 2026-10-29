@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-gear"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem</p>
                    <h1 class="h3 mb-1">Ubah Akun</h1>
                    <p class="text-muted mb-0">{{ $user->email }}</p>
                </div>
            </div>
            <div class="heading-actions">
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i>Kembali</a>
            </div>
        </div>

        <form action="{{ route('admin.users.update', $user) }}" method="POST" data-confirm="Simpan perubahan akun user ini?">
            @csrf
            @method('PUT')

            <div class="row g-3 align-items-start">
                <div class="col-lg-5">
                    <section class="panel">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-lock"></i>Data Akun</h2>
                        @include('admin.users._akun', ['akun' => $user, 'wajibPassword' => false])

                        @if ($user->dataPegawai || $user->dataPelamar)
                            <div class="alert alert-light border small mt-3 mb-0">
                                <i class="bi bi-link-45deg me-1"></i>Terhubung dengan
                                {{ $user->dataPegawai ? 'pegawai' : 'pelamar' }}
                                <strong>{{ $user->dataPegawai?->nama_lengkap ?? $user->dataPelamar?->nama_lengkap }}</strong>.
                                Tautan dikelola otomatis lewat NIP & proses rekrutmen.
                            </div>
                        @endif

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-save"></i>Simpan Perubahan</button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </section>
                </div>
                <div class="col-lg-7">
                    @include('admin.users._divisi', ['selectedDivisi' => old('divisi', $user->divisi ?? []), 'selectedKepalaUnit' => old('kepala_unit', $user->unitsDipimpin())])
                </div>
            </div>
        </form>
    </div>
</main>
@endsection
