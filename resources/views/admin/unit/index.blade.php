@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-buildings"></i></span>
                <div>
                    <p class="eyebrow mb-1">Kepegawaian</p>
                    <h1 class="h3 mb-1">Master Bagian &amp; Koordinator</h1>
                    <p class="text-muted mb-0">Daftar bagian dan koordinatornya. Koordinator dari bagian lain otomatis dicatat sebagai <strong>rangkap</strong> di Mutasi &amp; Rangkap.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.unit.export'])
                <a href="{{ route('admin.penugasan.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left-right"></i> Mutasi &amp; Rangkap</a>
            </div>
        </div>

        @if ($peringatan = session('peringatan'))
            <div class="alert alert-warning small">
                <i class="bi bi-info-circle me-1"></i>{{ implode(' ', $peringatan) }}
            </div>
        @endif
        {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
        @section('galat-di-halaman', '1')
        @if ($errors->any())
            <div class="alert alert-danger small">{{ $errors->first() }}</div>
        @endif

        <section class="panel p-0 overflow-hidden">
            <div class="un-bilah">
                @if ($bisaKelola)
                    <form action="{{ route('admin.unit.store') }}" method="POST" class="un-tambah">
                        @csrf
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-plus-lg"></i></span>
                            <input type="text" name="nama" class="form-control" placeholder="Bagian baru, contoh: LEGAL" aria-label="Nama bagian baru" maxlength="100" required autocomplete="off" value="{{ old('nama') }}">
                            <input type="text" name="keterangan" class="form-control d-none d-md-block" placeholder="Keterangan, contoh: Bagian hukum" aria-label="Keterangan bagian (opsional)" maxlength="255" value="{{ old('keterangan') }}">
                            <button type="submit" class="btn btn-primary">Tambah</button>
                        </div>
                    </form>
                @endif
                <div class="input-group un-cari">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input type="search" class="form-control" id="un-cari" placeholder="Cari bagian, contoh: SDM atau Siti" aria-label="Cari bagian menurut nama bagian atau koordinator" autocomplete="off" data-saring="#un-daftar">
                </div>
            </div>
            <p class="small text-muted mb-0 px-3 pt-3" aria-live="polite"><strong class="text-body" data-saring-jumlah="#un-daftar">{{ $units->count() }}</strong> dari {{ $units->count() }} bagian</p>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="kolom-no" style="width:56px">No</th>
                            <th>Bagian</th>
                            <th>Koordinator</th>
                            <th class="text-center">Pegawai</th>
                            <th class="text-center">Jabatan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="un-daftar">
                        @forelse ($units as $u)
                            @php
                                $koor = $kepala->get($u->nama, collect());
                                $gm = $gmKepala[$u->nama] ?? collect();
                                // Koordinator sekarang untuk modal: pegawai (penugasan kepala) + GM tanpa data pegawai.
                                $koorModal = $koor->map(fn ($p) => ['nilai' => 'pegawai:' . $p->data_pegawai_id, 'nama' => $p->dataPegawai?->nama_lengkap, 'ket' => $p->is_utama ? '' : ' (rangkap)'])
                                    ->concat($gm->map(fn ($g) => ['nilai' => 'gm:' . $g->id, 'nama' => $g->name, 'ket' => ' (GM)']))->values();
                            @endphp
                            <tr data-cari="{{ mb_strtolower($u->nama . ' ' . $koor->pluck('dataPegawai.nama_lengkap')->implode(' ') . ' ' . $gm->pluck('name')->implode(' ')) }}">
                                <td class="text-muted kolom-no">{{ $u->urutan }}</td>
                                <td class="kolom-penuh">
                                    <span class="fw-semibold d-block">{{ $u->nama }}</span>
                                    @if ($u->keterangan)<small class="text-muted">{{ $u->keterangan }}</small>@endif
                                </td>
                                <td class="kolom-penuh">
                                    @forelse ($koor as $p)
                                        <span class="un-koor">
                                            <i class="bi bi-person-badge"></i>{{ $p->dataPegawai?->nama_lengkap ?? '-' }}
                                            <span class="badge {{ $p->is_utama ? 'text-bg-light border' : 'text-bg-info' }}">{{ $p->is_utama ? 'Bagian utama' : 'Rangkap' }}</span>
                                        </span>
                                    @empty
                                        @if ($gm->isEmpty())<span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i>Belum ada koordinator</span>@endif
                                    @endforelse
                                    @foreach ($gm as $g)
                                        <span class="un-koor"><i class="bi bi-person-gear"></i>{{ $g->name }} <span class="badge text-bg-warning">GM</span></span>
                                    @endforeach
                                </td>
                                <td class="text-center">{{ $anggota[$u->nama] ?? 0 }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.jabatan.index', ['unit' => $u->nama]) }}" class="text-decoration-none" title="Lihat jabatan bagian ini">{{ $u->jabatans_count }}</a>
                                </td>
                                <td class="text-end un-aksi">
                                    <div class="d-inline-flex gap-1 justify-content-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalKoordinator"
                                            data-unit="{{ $u->nama }}" data-url="{{ route('admin.unit.koordinator', $u) }}"
                                            data-koor='@json($koorModal)'>
                                            <i class="bi bi-person-badge"></i> Koordinator
                                        </button>
                                        @if ($bisaKelola)
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUbahUnit"
                                                data-url="{{ route('admin.unit.update', $u) }}" data-nama="{{ $u->nama }}" data-urutan="{{ $u->urutan }}" data-keterangan="{{ $u->keterangan }}">
                                                <i class="bi bi-pencil"></i> Ubah
                                            </button>
                                            <form action="{{ route('admin.unit.destroy', $u) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="true" data-icon="warning" data-title="Hapus bagian?"
                                                    data-text="Bagian {{ $u->nama }} akan dihapus. Penghapusan hanya bisa bila bagian ini tidak dipakai data apa pun."
                                                    data-confirm-text="Ya, Hapus" data-confirm-color="#dc3545" data-cancel-text="Batal"><i class="bi bi-trash"></i> Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @include('partials.kosong-tabel', ['kosongKolom' => 6, 'kosongTeks' => 'Belum ada bagian.', 'kosongIkon' => 'bi-building'])
                        @endforelse
                        <tr data-saring-kosong="#un-daftar" hidden><td colspan="6">@include('partials.kosong', ['teks' => 'Tidak ada bagian yang cocok dengan pencarian ini.', 'ikonKosong' => 'bi-search'])</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<div class="modal fade" id="modalKoordinator" tabindex="-1" aria-labelledby="judulKoordinator" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="formKoordinator" data-confirm="Simpan koordinator bagian ini?">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="judulKoordinator">Koordinator <span data-unit></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="small mb-3" data-sekarang></div>
                <label class="form-label" for="un-cari-pegawai">Pilih koordinator baru <span class="text-muted fw-normal">(GM atau pegawai)</span></label>
                <input type="search" class="form-control form-control-sm mb-2" id="un-cari-pegawai" placeholder="Cari koordinator, contoh: Siti atau SMK" autocomplete="off">
                <select name="koordinator" class="form-select" size="9" required>
                    {{-- GM: yang punya data pegawai dicatat sebagai rangkap kepala; yang tidak, unit dicatat di akun GM. --}}
                    <optgroup label="GM">
                        @foreach ($gms as $g)
                            @php $unitGm = $g->dataPegawai?->kepegawaian?->unit_kerja; @endphp
                            <option value="{{ $g->data_pegawai_id ? 'pegawai:' . $g->data_pegawai_id : 'gm:' . $g->id }}" data-unit="{{ $unitGm }}" data-gm="1"
                                data-tanpa-pegawai="{{ $g->data_pegawai_id ? '' : '1' }}" data-divisi='@json(array_values((array) $g->divisi))'
                                data-cari="{{ mb_strtolower($g->name . ' gm ' . $unitGm . ' ' . implode(' ', (array) $g->divisi)) }}">
                                {{ $g->name }} — GM{{ $g->divisi ? ' (' . implode(', ', array_slice((array) $g->divisi, 0, 3)) . (count((array) $g->divisi) > 3 ? ', …' : '') . ')' : '' }}
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Pegawai">
                        @foreach ($pegawaiOpsi as $pg)
                            <option value="pegawai:{{ $pg->id }}" data-unit="{{ $pg->kepegawaian?->unit_kerja }}"
                                data-cari="{{ mb_strtolower($pg->nama_lengkap . ' ' . $pg->nip . ' ' . $pg->kepegawaian?->unit_kerja) }}">
                                {{ $pg->nama_lengkap }} — {{ $pg->kepegawaian?->jabatan ?? '-' }} ({{ $pg->kepegawaian?->unit_kerja ?? '-' }})
                            </option>
                        @endforeach
                    </optgroup>
                </select>
                <div class="form-text" data-info-rangkap>Pilih pegawai untuk melihat cara pencatatannya.</div>

                <label class="form-label mt-3" for="un-jabatan">Jabatan koordinator <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="text" name="jabatan" id="un-jabatan" class="form-control" list="un-jabatan-daftar" maxlength="150" autocomplete="off">
                <datalist id="un-jabatan-daftar"></datalist>
                <div class="form-text">Kosongkan: jabatan bagian utamanya tetap, atau "KEPALA &lt;BAGIAN&gt;" untuk rangkap.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" data-kosongkan hidden><i class="bi bi-person-x"></i> Kosongkan</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
            </div>
        </form>
        <form method="POST" id="formKosongkan" hidden data-confirm="Kosongkan koordinator bagian ini? Pengajuan pegawai bagian ini akan diteruskan ke GM.">
            @csrf
            <input type="hidden" name="koordinator" value="">
        </form>
    </div>
</div>

@if ($bisaKelola)
    <div class="modal fade" id="modalUbahUnit" tabindex="-1" aria-labelledby="judulUbahUnit" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content" data-confirm="Simpan perubahan bagian? Bila nama diganti, semua data yang memakai nama lama ikut diperbarui.">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="judulUbahUnit">Ubah Bagian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-8">
                            <label class="form-label">Nama bagian</label>
                            <input type="text" name="nama" class="form-control text-uppercase" maxlength="100" required>
                        </div>
                        <div class="col-4">
                            <label for="f-urutan" class="form-label">Urutan</label>
                            <input id="f-urutan" type="number" name="urutan" class="form-control" min="0" max="9999" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" name="keterangan" class="form-control" maxlength="255">
                        </div>
                    </div>
                    <p class="form-text mt-2 mb-0">Urutan menentukan posisi bagian di semua daftar pilihan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endif

<style>
    .un-bilah { display: flex; flex-wrap: wrap; gap: .75rem; padding: 1rem; border-bottom: 1px solid var(--admin-border); background: var(--admin-surface-soft); }
    .un-tambah { flex: 2 1 420px; margin: 0; }
    .un-cari { flex: 1 1 260px; }
    /* Nama koordinator dibungkus per kata (bukan per huruf) bila kolom sempit, badge ikut turun baris. */
.un-koor { display: inline-flex; flex-wrap: wrap; align-items: center; gap: .35rem; margin: .1rem .5rem .1rem 0; font-weight: 600; font-size: .86rem; max-width: 100%; overflow-wrap: normal; word-break: normal; }
    .un-koor > .bi { color: var(--hr-primary); }
    #modalKoordinator select[size] { font-size: .86rem; }
    /* Tombol aksi sebaris (tidak membungkus) agar ukurannya seragam; tabel bisa digeser di layar sempit. */
    .table tbody td.un-aksi { white-space: nowrap !important; width: 1%; }
    .un-aksi form { margin: 0; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Saring langsung ditangani global (main.js: data-saring).

        const jabatanPerUnit = @json($jabatanPerUnit);
        const modal = document.getElementById('modalKoordinator');
        const form = document.getElementById('formKoordinator');
        const formKosong = document.getElementById('formKosongkan');
        const pilih = form.querySelector('[name=koordinator]');
        const cariPg = document.getElementById('un-cari-pegawai');
        const info = form.querySelector('[data-info-rangkap]');
        const tombolKosong = form.querySelector('[data-kosongkan]');
        let unitAktif = '';

        function jelaskan() {
            const opsi = pilih.selectedOptions[0];
            form.jabatan.disabled = Boolean(opsi?.dataset.tanpaPegawai);
            if (!opsi) { info.textContent = 'Pilih pegawai untuk melihat cara pencatatannya.'; return; }
            if (opsi.dataset.tanpaPegawai) {
                const bidang = JSON.parse(opsi.dataset.divisi || '[]').includes(unitAktif);
                info.innerHTML = '<i class="bi bi-person-gear me-1"></i>GM tanpa data pegawai — bagian dicatat di akun GM (seperti di Kelola Akun).'
                    + (bidang ? '' : ' <span class="text-warning-emphasis">Bagian ini di luar bidang GM tersebut.</span>');
                return;
            }
            info.innerHTML = opsi.dataset.unit === unitAktif
                ? '<i class="bi bi-arrow-up-circle me-1"></i>Sudah di bagian ini — dinaikkan menjadi koordinator.'
                : '<i class="bi bi-diagram-2 me-1"></i>Bagian utamanya <strong></strong> — dicatat sebagai <strong>rangkap</strong> koordinator di ' + unitAktif + '.';
            const s = info.querySelector('strong');
            if (s && opsi.dataset.unit !== unitAktif) s.textContent = opsi.dataset.unit || '-';
        }
        function saring() {
            const kata = cariPg.value.trim().toLowerCase();
            Array.from(pilih.options).forEach((o) => { o.hidden = !o.dataset.cari.includes(kata); });
            // Grup tanpa hasil disembunyikan juga.
            pilih.querySelectorAll('optgroup').forEach((g) => { g.hidden = !Array.from(g.children).some((o) => !o.hidden); });
        }

        modal.addEventListener('show.bs.modal', (e) => {
            const t = e.relatedTarget;
            unitAktif = t.dataset.unit;
            form.action = formKosong.action = t.dataset.url;
            modal.querySelectorAll('[data-unit]').forEach((el) => { if (el.tagName === 'SPAN') el.textContent = unitAktif; });
            const koor = JSON.parse(t.dataset.koor || '[]');
            const sekarang = modal.querySelector('[data-sekarang]');
            sekarang.innerHTML = koor.length ? 'Koordinator sekarang: <strong></strong>' : '<span class="text-danger">Bagian ini belum punya koordinator.</span>';
            if (koor.length) sekarang.querySelector('strong').textContent = koor.map((k) => k.nama + k.ket).join(', ');
            tombolKosong.hidden = !koor.length;
            pilih.value = koor[0]?.nilai ?? '';
            cariPg.value = '';
            saring();
            const dl = document.getElementById('un-jabatan-daftar');
            dl.innerHTML = '';
            (jabatanPerUnit[unitAktif] || []).forEach((j) => dl.append(new Option(j)));
            form.jabatan.value = '';
            form.jabatan.placeholder = 'KEPALA ' + unitAktif;
            jelaskan();
        });
        modal.addEventListener('shown.bs.modal', () => cariPg.focus());
        cariPg.addEventListener('input', saring);
        pilih.addEventListener('change', jelaskan);
        tombolKosong.addEventListener('click', () => formKosong.requestSubmit());

        const modalUbah = document.getElementById('modalUbahUnit');
        modalUbah?.addEventListener('show.bs.modal', (e) => {
            const t = e.relatedTarget, f = modalUbah.querySelector('form');
            f.action = t.dataset.url;
            f.nama.value = t.dataset.nama;
            f.urutan.value = t.dataset.urutan;
            f.keterangan.value = t.dataset.keterangan || '';
        });
    });
</script>
@endsection
