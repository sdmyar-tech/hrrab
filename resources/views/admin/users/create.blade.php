@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-plus"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem</p>
                    <h1 class="h3 mb-1">Tambah Akun</h1>
                    <p class="text-muted mb-0">Buat akun baru untuk masuk ke HRRAB. Pilih dari Data Pegawai agar nama, email & NIP terisi dan akun langsung tertaut.</p>
                </div>
            </div>
            <div class="heading-actions">
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i>Kembali</a>
            </div>
        </div>

        <form data-fokus-awal action="{{ route('admin.users.store') }}" method="POST" data-confirm="Tambah akun user baru ini?">
            @csrf

            <div class="row g-3 align-items-start">
                <div class="col-lg-5">
                    <section class="panel">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-lock"></i>Data Akun</h2>
                        @include('admin.users._akun', ['akun' => null, 'wajibPassword' => true])

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check-lg"></i>Simpan</button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </section>
                </div>
                <div class="col-lg-7">
                    @include('admin.users._divisi', ['selectedDivisi' => old('divisi', []), 'selectedKepalaUnit' => old('kepala_unit')])
                </div>
            </div>
        </form>
    </div>
</main>
@endsection
