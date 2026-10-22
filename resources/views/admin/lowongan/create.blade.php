@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-briefcase"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">Buat Lowongan</h1>
                </div>
            </div>
        </div>

        <section class="panel p-4">
            <form action="{{ route('admin.lowongan.store') }}" method="POST" data-confirm="Simpan lowongan ini sebagai draft?">
                @csrf

                @include('admin.lowongan._form')

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan sebagai Draft
                    </button>
                    <a href="{{ route('admin.lowongan.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </section>
    </div>
</main>
@endsection
