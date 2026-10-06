@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-diagram-3"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem</p>
                    <h1 class="h3 mb-1">Master Jabatan</h1>
                    <p class="text-muted mb-0">Daftar ini dipakai di semua pilihan jabatan (data pegawai, penugasan, lowongan, formasi, kandidat).</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['route' => 'admin.jabatan.export', 'ukuran' => ''])
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <section class="panel p-3">
                    <h2 class="h6 mb-3"><i class="bi bi-plus-circle me-1"></i>Tambah Jabatan</h2>
                    <form action="{{ route('admin.jabatan.store') }}" method="POST">
                        @csrf
                        <input type="text" name="nama" class="form-control mb-2 @error('nama') is-invalid @enderror"
                            placeholder="mis. STAFF IT" value="{{ old('nama') }}" maxlength="150" required>
                        @error('nama')<div class="invalid-feedback mb-2">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
                    </form>
                    <p class="small text-muted mt-3 mb-0">
                        Nama otomatis huruf kapital. Mengubah nama jabatan juga memperbarui data yang sudah memakainya.
                        Jabatan yang masih dipakai tidak bisa dihapus.
                    </p>
                </section>
            </div>

            <div class="col-lg-8">
                <section class="panel">
                    <form action="{{ route('admin.jabatan.index') }}" method="GET" class="panel-filter px-3 pt-3 pb-2 d-flex gap-2">
                        <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari jabatan..." value="{{ request('q') }}">
                        <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-search"></i></button>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:48px;">#</th>
                                    <th>Nama Jabatan</th>
                                    <th class="text-center">Dipakai</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($jabatans as $j)
                                    <tr>
                                        <td class="text-muted">{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $j->nama }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ ($dipakai[$j->nama] ?? 0) ? 'text-bg-primary' : 'text-bg-light border' }}">{{ $dipakai[$j->nama] ?? 0 }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="aksi-grup">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUbahJabatan"
                                                    data-url="{{ route('admin.jabatan.update', $j) }}" data-nama="{{ $j->nama }}">
                                                    <i class="bi bi-pencil"></i> Ubah
                                                </button>
                                                <form action="{{ route('admin.jabatan.destroy', $j) }}" method="POST" class="d-inline"
                                                    data-confirm="Hapus jabatan {{ $j->nama }}?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" @disabled($dipakai[$j->nama] ?? 0)
                                                        title="{{ ($dipakai[$j->nama] ?? 0) ? 'Masih dipakai' : 'Hapus' }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Tidak ada jabatan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalUbahJabatan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Ubah Nama Jabatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="text" name="nama" class="form-control" maxlength="150" required>
                <p class="small text-muted mt-2 mb-0">Semua pegawai, penugasan, lowongan & formasi yang memakai nama lama ikut diubah.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('modalUbahJabatan').addEventListener('show.bs.modal', function (e) {
        const tombol = e.relatedTarget;
        const form = this.querySelector('form');
        form.action = tombol.dataset.url;
        form.querySelector('[name=nama]').value = tombol.dataset.nama;
    });
</script>
@endsection
