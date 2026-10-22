@php
    $judulHalaman = match (true) {
        request()->routeIs('admin.pegawai.index') => ['Data Pegawai', 'Kelola data seluruh pegawai, informasi kepegawaian, dan dokumen terkait.'],
        request()->routeIs('admin.pegawai.create') => ['Tambah Pegawai Baru', 'Masukkan data pegawai baru ke dalam sistem kepegawaian.'],
        request()->routeIs('admin.pegawai.edit') => ['Edit Data Pegawai', 'Perbarui informasi detail dan dokumen pegawai yang sudah ada.'],
        default => ['Detail Pegawai', 'Profil lengkap, rincian kepegawaian, BPJS, dan dokumen.'],
    };
@endphp
<div class="page-heading">
    <div class="page-heading-copy">
        <span class="page-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
        <div>
            <p class="eyebrow mb-1">Manajemen SDM</p>
            <h1 class="h3 mb-1">{{ $judulHalaman[0] }}</h1>
            <p class="text-muted mb-0">{{ $judulHalaman[1] }}</p>
        </div>
    </div>
    <div class="heading-actions">
        @if (request()->routeIs('admin.pegawai.index'))
            @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pegawai.export'])
            @can('kelola-pegawai')
                <a href="{{ route('admin.pegawai.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-person-plus" aria-hidden="true"></i> Tambah Pegawai
                </a>
            @endcan
        @else
            <a href="{{ isset($pegawai) && $pegawai->exists ? route('admin.pegawai.show', $pegawai) : route('admin.pegawai.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali
            </a>
        @endif
    </div>
</div>
