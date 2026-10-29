@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-grid-3x3-gap"></i></span>
                <div>
                    <p class="eyebrow mb-1">Kepegawaian</p>
                    <h1 class="h3 mb-1">Master Unit</h1>
                    <p class="text-muted mb-0">Daftar unit untuk mengelompokkan pegawai. Tanpa koordinator; persetujuan cuti, lembur, dan izin tetap mengikuti Bagian.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.master-unit.export'])
                <a href="{{ route('admin.unit.index') }}" class="btn btn-outline-secondary"><i class="bi bi-buildings"></i> Bagian &amp; Koordinator</a>
            </div>
        </div>

        {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
        @section('galat-di-halaman', '1')
        @if ($errors->any())
            <div class="alert alert-danger small">{{ $errors->first() }}</div>
        @endif

        <section class="panel p-0 overflow-hidden">
            <div class="uo-bilah">
                <form action="{{ route('admin.master-unit.store') }}" method="POST" class="uo-tambah">
                    @csrf
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
                        <input type="text" name="nama" class="form-control" placeholder="Unit baru, contoh: TK" aria-label="Nama unit baru" maxlength="100" required autocomplete="off" value="{{ old('nama') }}">
                        <input type="text" name="keterangan" class="form-control d-none d-md-block" placeholder="Keterangan, contoh: Taman kanak-kanak" aria-label="Keterangan unit (opsional)" maxlength="255" value="{{ old('keterangan') }}">
                        <button type="submit" class="btn btn-primary">Tambah</button>
                    </div>
                </form>
                <div class="input-group uo-cari">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input type="search" class="form-control" id="uo-cari" placeholder="Cari unit, contoh: TK" aria-label="Cari unit menurut nama atau keterangan" autocomplete="off" data-saring="#uo-daftar">
                </div>
            </div>
            <p class="small text-muted mb-0 px-3 pt-3" aria-live="polite"><strong class="text-body" data-saring-jumlah="#uo-daftar">{{ $unit->count() }}</strong> dari {{ $unit->count() }} unit</p>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="kolom-no" style="width:56px">No</th>
                            <th>Unit</th>
                            <th class="text-center">Pegawai Aktif</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="uo-daftar">
                        @forelse ($unit as $u)
                            <tr data-cari="{{ mb_strtolower($u->nama . ' ' . $u->keterangan) }}">
                                <td class="text-muted kolom-no">{{ $u->urutan }}</td>
                                <td class="kolom-penuh">
                                    <span class="fw-semibold d-block">{{ $u->nama }}</span>
                                    @if ($u->keterangan)<small class="text-muted">{{ $u->keterangan }}</small>@endif
                                </td>
                                <td class="text-center">
                                    @if ($n = $pegawai[$u->nama] ?? 0)
                                        <a href="{{ route('admin.pegawai.index', ['unit_organisasi' => $u->nama]) }}" title="Lihat pegawai unit ini">{{ $n }}</a>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td class="text-end uo-aksi">
                                    <div class="d-inline-flex gap-1 justify-content-end">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUbahUnitOrg"
                                            data-url="{{ route('admin.master-unit.update', $u) }}" data-nama="{{ $u->nama }}" data-urutan="{{ $u->urutan }}" data-keterangan="{{ $u->keterangan }}">
                                            <i class="bi bi-pencil"></i> Ubah
                                        </button>
                                        <form action="{{ route('admin.master-unit.destroy', $u) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="true" data-icon="warning" data-title="Hapus unit?"
                                                data-text="Unit {{ $u->nama }} akan dihapus. Penghapusan hanya bisa bila unit ini tidak dipakai pegawai mana pun."
                                                data-confirm-text="Ya, Hapus" data-confirm-color="#dc3545" data-cancel-text="Batal"><i class="bi bi-trash"></i> Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @include('partials.kosong-tabel', ['kosongKolom' => 4, 'kosongTeks' => 'Belum ada unit. Tambahkan lewat isian di atas.', 'kosongIkon' => 'bi-grid-3x3-gap'])
                        @endforelse
                        <tr data-saring-kosong="#uo-daftar" hidden><td colspan="4">@include('partials.kosong', ['teks' => 'Tidak ada unit yang cocok dengan pencarian ini.', 'ikonKosong' => 'bi-search'])</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<div class="modal fade" id="modalUbahUnitOrg" tabindex="-1" aria-labelledby="judulUbahUnitOrg" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" data-confirm="Simpan perubahan unit? Bila nama diganti, data pegawai yang memakai nama lama ikut diperbarui.">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="judulUbahUnitOrg">Ubah Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-8">
                        <label class="form-label" for="uo-nama">Nama unit</label>
                        <input type="text" name="nama" id="uo-nama" class="form-control text-uppercase" maxlength="100" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="uo-urutan">Urutan</label>
                        <input type="number" name="urutan" id="uo-urutan" class="form-control" min="0" max="9999" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="uo-keterangan">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="keterangan" id="uo-keterangan" class="form-control" maxlength="255" placeholder="Contoh: Taman kanak-kanak">
                    </div>
                </div>
                <p class="form-text mt-2 mb-0">Urutan menentukan posisi unit di semua daftar pilihan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<style>
    .uo-bilah { display: flex; flex-wrap: wrap; gap: .75rem; padding: 1rem; border-bottom: 1px solid var(--admin-border); background: var(--admin-surface-soft); }
    .uo-tambah { flex: 2 1 420px; margin: 0; }
    .uo-cari { flex: 1 1 260px; }
    .table tbody td.uo-aksi { white-space: nowrap !important; width: 1%; }
    .uo-aksi form { margin: 0; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Saring langsung ditangani global (main.js: data-saring).
        const modalUbah = document.getElementById('modalUbahUnitOrg');
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
