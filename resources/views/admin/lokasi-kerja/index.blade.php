@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-geo-alt"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem</p>
                    <h1 class="h3 mb-1">Master Lokasi Kerja</h1>
                    <p class="text-muted mb-0">Tempat/area kerja pegawai. Daftar ini menjadi pilihan lokasi kerja di data pegawai, lowongan, dan kandidat langsung.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.lokasi-kerja.export'])
            </div>
        </div>

        {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
        @section('galat-di-halaman', '1')
        @if ($errors->any())
            <div class="alert alert-danger small">{{ $errors->first() }}</div>
        @endif

        <section class="panel p-0 overflow-hidden">
            <div class="lk-bilah">
                <form action="{{ route('admin.lokasi-kerja.store') }}" method="POST" class="lk-tambah">
                    @csrf
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-plus-lg"></i></span>
                        <input type="text" name="nama" class="form-control" placeholder="Tambah lokasi baru, mis. KAMPUS JL. RIAU" maxlength="100" required autocomplete="off" value="{{ old('nama') }}">
                        <input type="text" name="keterangan" class="form-control d-none d-md-block" placeholder="Keterangan / alamat (opsional)" maxlength="255" value="{{ old('keterangan') }}">
                        <button type="submit" class="btn btn-primary">Tambah</button>
                    </div>
                </form>
                <div class="input-group lk-cari">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" id="lk-cari" placeholder="Cari lokasi…" autocomplete="off">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:56px">No</th>
                            <th>Lokasi Kerja</th>
                            <th class="text-center">Pegawai Aktif</th>
                            <th class="text-center">Lowongan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="lk-daftar">
                        @forelse ($lokasi as $l)
                            <tr data-cari="{{ mb_strtolower($l->nama . ' ' . $l->keterangan) }}">
                                <td class="text-muted">{{ $l->urutan }}</td>
                                <td>
                                    <span class="fw-semibold d-block">{{ $l->nama }}</span>
                                    @if ($l->keterangan)<small class="text-muted">{{ $l->keterangan }}</small>@endif
                                </td>
                                <td class="text-center">{{ $pegawai[$l->nama] ?? 0 }}</td>
                                <td class="text-center">{{ $lowongan[$l->nama] ?? 0 }}</td>
                                <td class="text-end lk-aksi">
                                    <div class="d-inline-flex gap-1 justify-content-end">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUbahLokasi"
                                            data-url="{{ route('admin.lokasi-kerja.update', $l) }}" data-nama="{{ $l->nama }}" data-urutan="{{ $l->urutan }}" data-keterangan="{{ $l->keterangan }}">
                                            <i class="bi bi-pencil"></i> Ubah
                                        </button>
                                        <form action="{{ route('admin.lokasi-kerja.destroy', $l) }}" method="POST" data-confirm="Hapus lokasi kerja {{ $l->nama }}? (Hanya bisa bila tidak dipakai data apa pun.)">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-geo-alt fs-3 d-block mb-2"></i>
                                    Belum ada lokasi kerja.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<div class="modal fade" id="modalUbahLokasi" tabindex="-1" aria-labelledby="judulUbahLokasi" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" data-confirm="Simpan perubahan lokasi kerja? Bila nama diganti, semua data yang memakai nama lama ikut diperbarui.">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="judulUbahLokasi">Ubah Lokasi Kerja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-8">
                        <label class="form-label">Nama lokasi</label>
                        <input type="text" name="nama" class="form-control text-uppercase" maxlength="100" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="urutan" class="form-control" min="0" max="9999" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="keterangan" class="form-control" maxlength="255">
                    </div>
                </div>
                <p class="form-text mt-2 mb-0">Urutan menentukan posisi lokasi di semua daftar pilihan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<style>
    .lk-bilah { display: flex; flex-wrap: wrap; gap: .75rem; padding: 1rem; border-bottom: 1px solid var(--admin-border); background: var(--admin-surface-soft); }
    .lk-tambah { flex: 2 1 420px; margin: 0; }
    .lk-cari { flex: 1 1 260px; }
    .table tbody td.lk-aksi { white-space: nowrap !important; width: 1%; }
    .lk-aksi form { margin: 0; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cari = document.getElementById('lk-cari');
        cari.addEventListener('input', () => {
            const kata = cari.value.trim().toLowerCase();
            document.querySelectorAll('#lk-daftar tr[data-cari]').forEach((tr) => { tr.hidden = !tr.dataset.cari.includes(kata); });
        });

        const modalUbah = document.getElementById('modalUbahLokasi');
        modalUbah.addEventListener('show.bs.modal', (e) => {
            const t = e.relatedTarget, f = modalUbah.querySelector('form');
            f.action = t.dataset.url;
            f.nama.value = t.dataset.nama;
            f.urutan.value = t.dataset.urutan;
            f.keterangan.value = t.dataset.keterangan || '';
        });
    });
</script>
@endsection
