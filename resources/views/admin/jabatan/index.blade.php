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
                <a href="{{ route('admin.jabatan.hak-akses') }}" class="btn btn-outline-secondary"><i class="bi bi-person-badge"></i> Hak Akses Jabatan</a>
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
            <div class="jb-bilah">
                <form action="{{ route('admin.jabatan.store') }}" method="POST" class="jb-tambah">
                    @csrf
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-plus-lg"></i></span>
                        <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" placeholder="Tambah jabatan baru, mis. STAFF LEGAL"
                            value="{{ $errors->ubah->any() ? '' : old('nama') }}" maxlength="150" required autocomplete="off">
                        {{-- Jabatan dibuat langsung di unitnya (unit lain bisa ditambah lewat Ubah). --}}
                        <select name="unit_id" class="form-select jb-unit-tambah" aria-label="Unit jabatan">
                            <option value="">-- Pilih unit --</option>
                            <option value="">Tanpa unit</option>
                            @foreach ($units as $un)
                                <option value="{{ $un->id }}" @selected(old('unit_id') == $un->id || (! old('unit_id') && request('unit') === $un->nama))>{{ $un->nama }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary">Tambah</button>
                    </div>
                    @error('nama')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </form>
                <div class="jb-cari">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" id="jb-cari" class="form-control" placeholder="Cari jabatan…" value="{{ request('q') }}" autocomplete="off">
                    </div>
                    <select id="jb-saring-unit" class="form-select" aria-label="Saring per unit">
                        <option value="">Semua unit</option>
                        <option value="-">Belum ada unit</option>
                        @foreach ($units as $un)
                            <option value="{{ $un->nama }}" @selected(request('unit') === $un->nama)>{{ $un->nama }}</option>
                        @endforeach
                    </select>
                    <select id="jb-urut" class="form-select" aria-label="Urutkan">
                        <option value="nama">A – Z</option>
                        <option value="dipakai">Paling banyak dipakai</option>
                    </select>
                </div>
            </div>

            <div class="jb-info small text-muted px-3 py-2">
                Menampilkan <strong id="jb-tampil">{{ $total }}</strong> dari {{ $total }} jabatan.
                Klik nama atau <strong>Ubah</strong> untuk mengganti nama (semua data ikut diperbarui) atau <strong>unitnya</strong> · jabatan hanya muncul di pilihan unit yang ditautkan · hapus jabatan yang dipakai akan meminta jabatan pengganti.
            </div>

            <ul class="jb-daftar list-unstyled mb-0" id="jb-daftar">
                @forelse ($jabatans as $j)
                    @php $n = $dipakai[$j->nama] ?? 0; @endphp
                    <li class="jb-baris {{ session('jabatan_disorot') === $j->id ? 'disorot' : '' }}" id="jabatan-{{ $j->id }}" data-nama="{{ $j->nama }}" data-dipakai="{{ $n }}"
                        data-id="{{ $j->id }}" data-url="{{ route('admin.jabatan.update', $j) }}" data-url-hapus="{{ route('admin.jabatan.destroy', $j) }}"
                        data-rincian='@json($rincian[$j->nama] ?? (object) [])'
                        data-units='@json($j->units->pluck('nama'))' data-unit-ids='@json($j->units->pluck('id'))'>
                        <div class="jb-tampil">
                            <span class="jb-nama-wadah">
                                <button type="button" class="jb-nama" data-ubah-jabatan title="Klik untuk mengubah nama / unit">{{ $j->nama }}</button>
                                <span class="jb-unit">
                                    @forelse ($j->units as $un)
                                        <span class="jb-unit-chip">{{ $un->nama }}</span>
                                    @empty
                                        <span class="jb-unit-chip kosong" title="Belum ditautkan ke unit — tidak muncul di daftar jabatan unit mana pun">tanpa unit</span>
                                    @endforelse
                                </span>
                            </span>
                            @if ($n)
                                <a href="{{ route('admin.pegawai.index', ['q' => $j->nama]) }}" class="jb-pakai" title="Lihat pegawai dengan jabatan ini">
                                    <i class="bi bi-people"></i> {{ $n }} data
                                </a>
                            @else
                                <span class="jb-pakai kosong">tidak dipakai</span>
                            @endif
                            <span class="jb-aksi">
                                <button type="button" class="btn btn-outline-secondary btn-sm jb-ubah" title="Ubah nama" data-ubah-jabatan>
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

<div class="modal fade" id="modalUbahJabatan" tabindex="-1" aria-labelledby="judulUbahJabatan" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="formUbahJabatan" autocomplete="off">
            @csrf
            @method('PUT')
            <input type="hidden" name="jabatan_id">
            <input type="hidden" name="kembali_q">
            <div class="modal-header">
                <h5 class="modal-title" id="judulUbahJabatan"><i class="bi bi-pencil-square me-1"></i>Ubah Jabatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="jb-lama mb-3">
                    <span class="small text-muted d-block">Nama sekarang</span>
                    <strong data-nama-lama></strong>
                </div>

                <label class="form-label" for="jb-nama-baru">Nama baru</label>
                <input type="text" name="nama" id="jb-nama-baru" class="form-control form-control-lg @error('nama', 'ubah') is-invalid @enderror" maxlength="150" required>
                <div class="d-flex justify-content-between gap-2 mt-1">
                    <span class="form-text m-0" data-pesan-nama>Otomatis huruf kapital. Spasi berlebih dirapikan.</span>
                    <span class="form-text m-0 text-nowrap" data-hitung-huruf></span>
                </div>
                @error('nama', 'ubah')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                {{-- Unit tempat jabatan ini berada: menentukan jabatan yang muncul saat unit dipilih (pegawai, mutasi, formasi, lowongan). --}}
                <input type="hidden" name="unit_dikirim" value="1">
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0">Unit</label>
                        <span class="form-text m-0" data-jumlah-unit></span>
                    </div>
                    <input type="search" class="form-control form-control-sm mb-2" placeholder="Cari unit…" data-cari-unit autocomplete="off">
                    <div class="jb-pilih-unit" data-pilih-unit>
                        @foreach ($units as $un)
                            <label class="form-check" data-nama-unit="{{ $un->nama }}">
                                <input class="form-check-input" type="checkbox" name="unit_ids[]" value="{{ $un->id }}">
                                <span class="form-check-label">{{ $un->nama }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Nama sama dengan jabatan lain → tawarkan penggabungan. --}}
                <div class="alert alert-warning small mt-3 mb-0" data-kembar hidden>
                    <i class="bi bi-exclamation-triangle me-1"></i>Jabatan <strong data-nama-kembar></strong> sudah ada.
                    Bila keduanya sama, <strong>gabungkan</strong>: semua data dipindahkan ke jabatan itu lalu jabatan ini dihapus.
                    <button type="button" class="btn btn-warning btn-sm mt-2 w-100" data-gabungkan><i class="bi bi-intersect"></i> Gabungkan ke <span data-nama-kembar></span></button>
                </div>

                <div class="jb-dampak mt-3" data-dampak></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" data-simpan><i class="bi bi-check-lg"></i>Simpan Perubahan</button>
            </div>
        </form>
        <form method="POST" id="formGabungJabatan" hidden>
            @csrf
            @method('DELETE')
            <input type="hidden" name="pengganti_id">
        </form>
    </div>
</div>

<style>
    .jb-lama { padding: .6rem .8rem; border-radius: 10px; background: var(--admin-surface-soft); border: 1px solid var(--admin-border); }
    #jb-nama-baru { text-transform: uppercase; font-weight: 600; letter-spacing: .01em; }
    .jb-dampak { font-size: .8125rem; padding: .7rem .85rem; border-radius: 10px; border: 1px dashed var(--admin-border); }
    .jb-dampak.ada { border-style: solid; border-color: #e3c3c4; background: var(--hr-primary-soft); }
    .jb-dampak ul { list-style: none; padding: 0; margin: .4rem 0 0; display: flex; flex-wrap: wrap; gap: .35rem; }
    .jb-dampak li { padding: .15rem .55rem; border-radius: 999px; background: var(--admin-surface); border: 1px solid var(--admin-border); font-weight: 600; }
    button.jb-nama { border: 0; background: none; padding: 0; text-align: left; color: inherit; cursor: pointer; }
    button.jb-nama:hover { color: var(--hr-primary); text-decoration: underline; text-underline-offset: 3px; }
    .jb-baris.disorot { animation: jb-sorot 2.4s ease-out; }
    @keyframes jb-sorot { 0%, 40% { background: #fff3d6; } 100% { background: transparent; } }
    .jb-stat { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; border: 1px solid var(--admin-border); border-radius: .85rem;
        background: var(--admin-surface); color: var(--admin-text); text-align: left; transition: border-color .15s, box-shadow .15s; }
    .jb-stat > i { width: 38px; height: 38px; display: inline-grid; place-items: center; border-radius: .6rem; background: var(--hr-primary-soft); color: var(--hr-primary); font-size: 1.05rem; flex: none; }
    .jb-stat strong { display: block; font-size: 1.25rem; line-height: 1.1; }
    .jb-stat small { color: var(--admin-muted); font-size: .78rem; }
    .jb-stat:hover { border-color: #e4b0b1; }
    .jb-stat.aktif { border-color: var(--hr-primary); box-shadow: inset 0 0 0 1px var(--hr-primary); }
    .jb-bilah { display: flex; flex-wrap: wrap; gap: .75rem; padding: 1rem; border-bottom: 1px solid var(--admin-border); background: var(--admin-surface-soft); }
    .jb-tambah { flex: 1 1 460px; margin: 0; }
    .jb-tambah input[name=nama] { min-width: 180px; }
    .jb-cari { flex: 1 1 460px; display: flex; flex-wrap: wrap; gap: .5rem; }
    .jb-cari .input-group { flex: 1 1 200px; }
    .jb-cari .form-select { flex: 0 1 auto; width: auto; max-width: 220px; }
    .jb-info { border-bottom: 1px solid var(--admin-border); }
    .jb-daftar { max-height: calc(100vh - 340px); min-height: 240px; overflow-y: auto; }
    .jb-baris { border-bottom: 1px solid var(--admin-border); }
    .jb-baris:last-of-type { border-bottom: 0; }
    .jb-tampil { display: flex; align-items: center; gap: .75rem; padding: .55rem 1rem; min-height: 50px; }
    .jb-baris:hover { background: rgba(136, 15, 18, .03); }
    .jb-nama-wadah { flex: 1 1 auto; min-width: 0; display: flex; flex-wrap: wrap; align-items: center; gap: .3rem .6rem; }
    .jb-nama { min-width: 0; font-weight: 600; font-size: .9rem; overflow-wrap: anywhere; }
    .jb-unit { display: inline-flex; flex-wrap: wrap; gap: .25rem; }
    .jb-unit-chip { font-size: .7rem; font-weight: 700; letter-spacing: .02em; padding: .12rem .5rem; border-radius: 999px; background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; }
    .jb-unit-chip.kosong { background: transparent; color: var(--admin-muted); border-style: dashed; border-color: var(--admin-border); font-weight: 500; }
    .jb-unit-tambah { flex: 0 0 auto; max-width: 190px; }
    .jb-pilih-unit { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .15rem .75rem; max-height: 190px; overflow-y: auto;
        padding: .5rem .75rem; border: 1px solid var(--admin-border); border-radius: 10px; }
    .jb-pilih-unit .form-check { margin: 0; font-size: .85rem; }
    html[data-theme="dark"] .jb-unit-chip:not(.kosong) { background: rgba(99, 102, 241, .15); color: #c7d2fe; border-color: rgba(99, 102, 241, .35); }
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
        const saringUnit = document.getElementById('jb-saring-unit');
        let saring = 'semua';

        // Saring, cari, dan urutkan langsung tanpa memuat ulang halaman.
        function perbarui() {
            const kata = cari.value.trim().toUpperCase();
            let n = 0;
            baris.forEach(function (li) {
                const dipakai = parseInt(li.dataset.dipakai, 10);
                const units = JSON.parse(li.dataset.units || '[]');
                const cocokUnit = !saringUnit.value || (saringUnit.value === '-' ? units.length === 0 : units.includes(saringUnit.value));
                const cocok = li.dataset.nama.includes(kata) && cocokUnit
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
        saringUnit.addEventListener('change', perbarui);
        urut.addEventListener('change', urutkan);
        document.querySelectorAll('.jb-stat').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.jb-stat').forEach(function (b) { b.classList.remove('aktif'); });
                btn.classList.add('aktif');
                saring = btn.dataset.saring;
                perbarui();
            });
        });

        const modalUbah = document.getElementById('modalUbahJabatan');
        const formUbah = document.getElementById('formUbahJabatan');
        const formGabung = document.getElementById('formGabungJabatan');
        const inputNama = formUbah.querySelector('[name=nama]');
        const tombolSimpan = formUbah.querySelector('[data-simpan]');
        const pesanNama = formUbah.querySelector('[data-pesan-nama]');
        const kotakKembar = formUbah.querySelector('[data-kembar]');
        const kotakDampak = formUbah.querySelector('[data-dampak]');
        const centangUnit = Array.from(formUbah.querySelectorAll('[name="unit_ids[]"]'));
        const cariUnit = formUbah.querySelector('[data-cari-unit]');
        let unitAwal = '';
        const unitDipilih = () => centangUnit.filter((c) => c.checked).map((c) => c.value).sort().join(',');
        // Peta nama → id semua jabatan untuk cek nama kembar tanpa memuat ulang.
        const semua = new Map(baris.map((li) => [li.dataset.nama, li.dataset.id]));
        const rapikan = (v) => v.replace(/\s+/g, ' ').trim().toUpperCase();
        let aktif = null;

        function bukaUbah(li, namaAwal) {
            aktif = li;
            formUbah.action = li.dataset.url;
            formUbah.jabatan_id.value = li.dataset.id;
            formUbah.kembali_q.value = cari.value;
            modalUbah.querySelector('[data-nama-lama]').textContent = li.dataset.nama;
            inputNama.value = namaAwal ?? li.dataset.nama;
            // Unit jabatan: centang sesuai data, ingat keadaan awal untuk mendeteksi perubahan.
            const idUnit = JSON.parse(li.dataset.unitIds || '[]').map(String);
            centangUnit.forEach((c) => { c.checked = idUnit.includes(c.value); });
            unitAwal = unitDipilih();
            cariUnit.value = '';
            centangUnit.forEach((c) => { c.closest('label').hidden = false; });
            // Rincian dampak: data apa saja yang ikut berganti nama.
            const rincian = JSON.parse(li.dataset.rincian || '{}');
            const total = Object.values(rincian).reduce((a, b) => a + b, 0);
            kotakDampak.classList.toggle('ada', total > 0);
            kotakDampak.innerHTML = total
                ? '<i class="bi bi-arrow-repeat me-1"></i>Nama baru otomatis dipakai di <strong>' + total + ' data</strong>:<ul></ul>'
                : '<i class="bi bi-info-circle me-1"></i>Jabatan ini belum dipakai di data mana pun.';
            Object.entries(rincian).forEach(([label, n]) => {
                const item = document.createElement('li');
                item.textContent = label + ' ' + n;
                kotakDampak.querySelector('ul').appendChild(item);
            });
            periksa();
            bootstrap.Modal.getOrCreateInstance(modalUbah).show();
        }

        function periksa() {
            const nama = rapikan(inputNama.value);
            const lama = aktif.dataset.nama;
            const idKembar = semua.get(nama);
            const kembar = Boolean(nama && nama !== lama && idKembar && idKembar !== aktif.dataset.id);
            kotakKembar.hidden = !kembar;
            kotakKembar.querySelectorAll('[data-nama-kembar]').forEach((e) => { e.textContent = nama; });
            formGabung.pengganti_id.value = kembar ? idKembar : '';
            formGabung.action = aktif.dataset.urlHapus;
            const unitBerubah = unitDipilih() !== unitAwal;
            formUbah.querySelector('[data-jumlah-unit]').textContent = centangUnit.filter((c) => c.checked).length + ' unit dipilih';
            tombolSimpan.disabled = !nama || kembar || (nama === lama && !unitBerubah);
            pesanNama.textContent = !nama ? 'Nama jabatan wajib diisi.'
                : nama === lama ? (unitBerubah ? 'Nama tetap; unit akan diperbarui.' : 'Belum ada perubahan.')
                : kembar ? 'Nama ini sudah dipakai jabatan lain.'
                : 'Akan disimpan sebagai: ' + nama;
            pesanNama.classList.toggle('text-danger', !nama || kembar);
            formUbah.querySelector('[data-hitung-huruf]').textContent = nama.length + '/150';
        }

        document.querySelectorAll('[data-ubah-jabatan]').forEach((b) => b.addEventListener('click', () => bukaUbah(b.closest('.jb-baris'))));
        inputNama.addEventListener('input', periksa);
        centangUnit.forEach((c) => c.addEventListener('change', periksa));
        cariUnit.addEventListener('input', () => {
            const kata = cariUnit.value.trim().toUpperCase();
            centangUnit.forEach((c) => { c.closest('label').hidden = !c.closest('label').dataset.namaUnit.includes(kata); });
        });
        modalUbah.addEventListener('shown.bs.modal', () => { inputNama.focus(); inputNama.select(); });
        formUbah.addEventListener('submit', () => { inputNama.value = rapikan(inputNama.value); });
        kotakKembar.querySelector('[data-gabungkan]').addEventListener('click', function () {
            const tujuan = rapikan(inputNama.value);
            Swal.fire({
                icon: 'warning', title: 'Gabungkan jabatan?',
                html: 'Semua data <strong></strong> dipindahkan ke <strong></strong>, lalu jabatan lama dihapus.',
                didOpen: (el) => { const s = el.querySelectorAll('strong'); s[0].textContent = aktif.dataset.nama; s[1].textContent = tujuan; },
                showCancelButton: true, confirmButtonText: 'Ya, gabungkan', cancelButtonText: 'Batal', reverseButtons: true, confirmButtonColor: '#8b1215',
            }).then((r) => { if (r.isConfirmed) formGabung.submit(); });
        });

        // Galat validasi dari server → buka lagi modal untuk jabatan yang sama dengan isian terakhir.
        @if ($errors->ubah->any() && old('jabatan_id'))
            (function () {
                const li = document.getElementById('jabatan-{{ (int) old('jabatan_id') }}');
                if (li) bukaUbah(li, @json(old('nama')));
            })();
        @endif

        // Kembali dari simpan: gulir ke baris yang disorot.
        const disorot = daftar.querySelector('.jb-baris.disorot');
        if (disorot) disorot.scrollIntoView({ block: 'center' });

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

        if (cari.value || saringUnit.value) perbarui();
    });
</script>
@endsection
