@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        @include('partials.kembali', ['ke' => route('admin.lowongan.index'), 'label' => 'Lowongan'])
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-briefcase"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">Ubah Lowongan</h1>
                    <p class="text-muted mb-0">
                        Status saat ini:
                        <span class="badge {{ $lowongan->status === 'dipublikasikan' ? 'text-bg-success' : ($lowongan->status === 'ditutup' ? 'text-bg-secondary' : 'text-bg-warning') }}">
                            {{ $lowongan->statusLabel() }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <section class="panel p-4">
            <form action="{{ route('admin.lowongan.update', $lowongan) }}" method="POST" data-confirm="Simpan perubahan lowongan ini?">
                @csrf
                @method('PUT')

                @include('admin.lowongan._form')

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" name="submit_action" value="save" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>

                    @if ($lowongan->status === 'draft')
                        <button type="submit" name="submit_action" value="publish" class="btn btn-success">
                            <i class="bi bi-megaphone me-1"></i> Simpan &amp; Publikasikan
                        </button>
                    @elseif ($lowongan->status === 'dipublikasikan')
                        <button type="submit" form="close-form" class="btn btn-outline-danger">
                            <i class="bi bi-lock me-1"></i> Tutup Lowongan
                        </button>
                    @endif

                    <a href="{{ route('admin.lowongan.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </form>

            <form id="close-form" action="{{ route('admin.lowongan.close', $lowongan) }}" method="POST" class="d-none" data-confirm="Tutup lowongan ini?">
                @csrf
            </form>

            @if ($lowongan->status === \App\Models\Lowongan::STATUS_DITUTUP)
                <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top">
                    <span class="text-muted small">Lowongan ini sudah ditutup.</span>
                    @include('admin.lowongan._reopen-button', ['lowongan' => $lowongan])
                </div>
            @endif
        </section>
    </div>
</main>
@endsection
