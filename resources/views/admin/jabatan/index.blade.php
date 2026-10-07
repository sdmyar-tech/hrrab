@extends('layouts.admin.app')

@section('content')
@php
    $total = $jabatans->count();
    $terpakai = $jabatans->filter(fn ($j) => ($dipakai[$j->nama] ?? 0) > 0)->count();
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-diagram-3"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem</p>
                    <h1 class="h3 mb-1">Master Jabatan</h1>
                    <p class="text-muted mb-0">Satu daftar untuk semua pilihan jabatan: data pegawai, mutasi, lowongan, formasi, dan kandidat.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.jabatan.export', 'eksporUkuran' => ''])
            </div>
        </div>

        <div class="row g-3 mb-3">
            @foreach ([['Total jabatan', $total, 'bi-diagram-3', 'semua'], ['Sedang dipakai', $terpakai, 'bi-person-check', 'dipakai'], ['Tidak dipakai', $total - $terpakai, 'bi-archive', 'kosong']] as [$judul, $angka, $ikonStat, $saring])
                <div class="col-4">
                    <button type="button" class="jb-stat w-100 {{ $saring === 'semua' ? 'aktif' : '' }}" data-saring="{{ $saring }}">
                        <i class="bi {{ $ikonStat }}" aria-hidden="true"></i>
                        <span><strong>{{ $angka }}</strong><small>{{ $judul }}</small></span>
                    </button>
                </div>
            @endforeach
        </div>

        <section class="panel p-0 overflow-hidden">
            {{-- Bilah alat: tambah cepat + cari langsung + urutkan --}}
            <div class="jb-bilah">
                <form action="{{ route('admin.jabatan.store') }}" method="POST" class="jb-tambah">
                    @csrf
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-plus-lg"></i></span>
                        <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" placeholder="Tambah jabatan baru, mis. STAFF IT"
                            value="{{ old('nama') }}" maxlength="150" required autocomplete="off">
                        <button type="submit" class="btn btn-primary">Tambah</button>
                    </div>
                    @error('nama')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </form>
                <div class="jb-cari">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" id="jb-cari" class="form-control" placeholder="Cari jabatan…" value="{{ request('q') }}" autocomplete="off">
                    </div>
                    <select id="jb-urut" class="form-select" aria-label="Urutkan">
                        <option value="nama">A – Z</option>
                        <option value="dipakai">Paling banyak dipakai</option>
                    </select>
                </div>
            </div>

            <div class="jb-info small text-muted px-3 py-2">
                Menampilkan <strong id="jb-tampil">{{ $total }}</strong> dari {{ $total }} jabatan.
                Nama otomatis huruf kapital · ubah nama ikut memperbarui semua data · hapus jabatan yang dipakai akan meminta jabatan pengganti.
            </div>

            <ul class="jb-daftar list-unstyled mb-0" id="jb-daftar">
                @forelse ($jabatans as $j)
                    @php $n = $dipakai[$j->nama] ?? 0; @endphp
                    <li class="jb-baris" data-nama="{{ $j->nama }}" data-dipakai="{{ $n }}">
                        <div class="jb-tampil">
                            <span class="jb-nama">{{ $j->nama }}</span>
                            @if ($n)
                                <a href="{{ route('admin.pegawai.index', ['q' => $j->nama]) }}" class="jb-pakai" title="Lihat pegawai dengan jabatan ini">
                                    <i class="bi bi-people"></i> {{ $n }} data
                                </a>
                            @else
                                <span class="jb-pakai kosong">tidak dipakai</span>
                            @endif
                            <span class="jb-aksi">
                                <button type="button" class="btn btn-outline-secondary btn-sm jb-ubah" title="Ubah nama">
                                    <i class="bi bi-pencil"></i><span class="d-none d-sm-inline">Ubah</span>
                                </button>
                                @if ($n)
                                    <button type="button" class="btn btn-outline-danger btn-sm" title="Hapus (pilih pengganti)"
                                        data-bs-toggle="modal" data-bs-target="#modalHapusJabatan"
                                        data-url="{{ route('admin.jabatan.destroy', $j) }}" data-id="{{ $j->id }}" data-nama="{{ $j->nama }}" data-dipakai="{{ $n }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @else
                                    <form action="{{ route('admin.jabatan.destroy', $j) }}" method="POST" class="d-inline" data-confirm="Hapus jabatan {{ $j->nama }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </span>
                        </div>

                        {{-- Ubah nama langsung di baris (Enter = simpan, Esc = batal) --}}
                        <form action="{{ route('admin.jabatan.update', $j) }}" method="POST" class="jb-form-ubah" hidden>
                            @csrf
                            @method('PUT')
                            <input type="text" name="nama" class="form-control form-control-sm" value="{{ $j->nama }}" maxlength="150" required>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i>Simpan</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm jb-batal">Batal</button>
                        </form>
                    </li>
                @empty
                    <li class="text-center text-muted py-5">Belum ada jabatan.</li>
                @endforelse
                <li class="jb-kosong text-center text-muted py-5" hidden>
                    <i class="bi bi-search d-block fs-3 mb-2"></i>Tidak ada jabatan yang cocok.
                </li>
            </ul>
        </section>
    </div>
</main>

<div class="modal fade" id="modalHapusJabatan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h5 class="modal-title">Hapus Jabatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small mb-3">
                    <strong data-nama></strong> masih dipakai di <strong data-dipakai></strong> data (pegawai, mutasi, lowongan, formasi, atau lamaran).
                </div>
                <label class="form-label">Pindahkan data tersebut ke jabatan</label>
                <input type="search" class="form-control form-control-sm mb-2" placeholder="Cari jabatan pengganti…" data-cari-pengganti autocomplete="off">
                <select name="pengganti_id" class="form-select" size="7" required>
                    @foreach ($semuaJabatan as $opsi)
                        <option value="{{ $opsi->id }}">{{ $opsi->nama }}</option>
                    @endforeach
                </select>
                <p class="small text-muted mt-2 mb-0">Setelah dipindahkan, jabatan lama dihapus dari daftar.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i>Pindahkan & Hapus</button>
            </div>
        </form>
    </div>
</div>

<style>
    .jb-stat { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; border: 1px solid var(--admin-border); border-radius: .85rem;
        background: var(--admin-surface); color: var(--admin-text); text-align: left; transition: border-color .15s, box-shadow .15s; }
    .jb-stat > i { width: 38px; height: 38px; display: inline-grid; place-items: center; border-radius: .6rem; background: var(--hr-primary-soft); color: var(--hr-primary); font-size: 1.05rem; flex: none; }
    .jb-stat strong { display: block; font-size: 1.25rem; line-height: 1.1; }
    .jb-stat small { color: var(--admin-muted); font-size: .78rem; }
    .jb-stat:hover { border-color: #e4b0b1; }
    .jb-stat.aktif { border-color: var(--hr-primary); box-shadow: inset 0 0 0 1px var(--hr-primary); }
    .jb-bilah { display: flex; flex-wrap: wrap; gap: .75rem; padding: 1rem; border-bottom: 1px solid var(--admin-border); background: var(--admin-surface-soft); }
    .jb-tambah { flex: 1 1 320px; margin: 0; }
    .jb-cari { flex: 1 1 320px; display: flex; gap: .5rem; }
    .jb-cari .input-group { flex: 1 1 auto; }
    .jb-cari .form-select { flex: 0 0 auto; width: auto; }
    .jb-info { border-bottom: 1px solid var(--admin-border); }
    .jb-daftar { max-height: calc(100vh - 340px); min-height: 240px; overflow-y: auto; }
    .jb-baris { border-bottom: 1px solid var(--admin-border); }
    .jb-baris:last-of-type { border-bottom: 0; }
    .jb-tampil { display: flex; align-items: center; gap: .75rem; padding: .55rem 1rem; min-height: 50px; }
    .jb-baris:hover { background: rgba(136, 15, 18, .03); }
    .jb-nama { flex: 1 1 auto; min-width: 0; font-weight: 600; font-size: .9rem; overflow-wrap: anywhere; }
    .jb-pakai { flex: none; font-size: .78rem; font-weight: 600; padding: .2rem .6rem; border-radius: 999px; background: var(--hr-primary-soft); color: var(--hr-primary-dark) !important; text-decoration: none; white-space: nowrap; }
    .jb-pakai:hover { background: var(--hr-primary); color: #fff !important; }
    .jb-pakai.kosong { background: transparent; color: var(--admin-muted) !important; font-weight: 500; }
    .jb-aksi { flex: none; display: inline-flex; gap: .35rem; }
    .jb-aksi form { margin: 0; }
    .jb-form-ubah { display: flex; gap: .4rem; align-items: center; padding: .5rem 1rem; background: var(--hr-primary-soft); }
    .jb-form-ubah[hidden], .jb-tampil[hidden] { display: none; }
    .jb-form-ubah input { flex: 1 1 auto; }
    #modalHapusJabatan select[size] { font-size: .875rem; }
    @media (max-width: 575.98px) {
        .jb-stat { flex-direction: column; text-align: center; gap: .35rem; padding: .7rem .4rem; }
        .jb-stat small { font-size: .7rem; }
        .jb-tampil { flex-wrap: wrap; }
        .jb-nama { flex-basis: 100%; }
        .jb-aksi { margin-left: auto; }
        .jb-daftar { max-height: none; }
        .jb-cari { flex-wrap: wrap; }
        .jb-cari .input-group, .jb-cari .form-select { flex: 1 1 100%; width: 100%; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const daftar = document.getElementById('jb-daftar');
        const baris = Array.from(daftar.querySelectorAll('.jb-baris'));
        const cari = document.getElementById('jb-cari');
        const urut = document.getElementById('jb-urut');
        const tampil = document.getElementById('jb-tampil');
        const kosong = daftar.querySelector('.jb-kosong');
        let saring = 'semua';

        // Saring, cari, dan urutkan langsung tanpa memuat ulang halaman.
        function perbarui() {
            const kata = cari.value.trim().toUpperCase();
            let n = 0;
            baris.forEach(function (li) {
                const dipakai = parseInt(li.dataset.dipakai, 10);
                const cocok = li.dataset.nama.includes(kata)
                    && (saring === 'semua' || (saring === 'dipakai' ? dipakai > 0 : dipakai === 0));
                li.hidden = ! cocok;
                n += cocok ? 1 : 0;
            });
            tampil.textContent = n;
            kosong.hidden = n > 0;
        }

        function urutkan() {
            baris.sort(function (a, b) {
                return urut.value === 'dipakai'
                    ? (b.dataset.dipakai - a.dataset.dipakai) || a.dataset.nama.localeCompare(b.dataset.nama)
                    : a.dataset.nama.localeCompare(b.dataset.nama);
            }).forEach(function (li) { daftar.insertBefore(li, kosong); });
        }

        cari.addEventListener('input', perbarui);
        urut.addEventListener('change', urutkan);
        document.querySelectorAll('.jb-stat').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.jb-stat').forEach(function (b) { b.classList.remove('aktif'); });
                btn.classList.add('aktif');
                saring = btn.dataset.saring;
                perbarui();
            });
        });

        // Ubah nama langsung di baris.
        baris.forEach(function (li) {
            const viewBaris = li.querySelector('.jb-tampil');
            const form = li.querySelector('.jb-form-ubah');
            const input = form.querySelector('input[name=nama]');
            const tutup = function () { form.hidden = true; viewBaris.hidden = false; input.value = li.dataset.nama; };
            li.querySelector('.jb-ubah').addEventListener('click', function () {
                viewBaris.hidden = true; form.hidden = false; input.focus(); input.select();
            });
            form.querySelector('.jb-batal').addEventListener('click', tutup);
            input.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutup(); });
        });

        // Modal hapus: cari pengganti & sembunyikan jabatan yang sedang dihapus.
        const modal = document.getElementById('modalHapusJabatan');
        const pilihan = modal.querySelector('[name=pengganti_id]');
        const cariPengganti = modal.querySelector('[data-cari-pengganti]');
        let idDihapus = null;
        function saringPengganti() {
            const kata = cariPengganti.value.trim().toUpperCase();
            Array.prototype.forEach.call(pilihan.options, function (o) {
                o.hidden = o.value === idDihapus || ! o.text.includes(kata);
            });
        }
        modal.addEventListener('show.bs.modal', function (e) {
            const tombol = e.relatedTarget;
            idDihapus = tombol.dataset.id;
            modal.querySelector('form').action = tombol.dataset.url;
            modal.querySelector('[data-nama]').textContent = tombol.dataset.nama;
            modal.querySelector('[data-dipakai]').textContent = tombol.dataset.dipakai;
            pilihan.value = '';
            cariPengganti.value = '';
            saringPengganti();
        });
        modal.addEventListener('shown.bs.modal', function () { cariPengganti.focus(); });
        cariPengganti.addEventListener('input', saringPengganti);

        if (cari.value) perbarui();
    });
</script>
@endsection
