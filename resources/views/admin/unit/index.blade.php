@extends('layouts.admin.app')

@section('title', 'Bagian & Koordinator')

@section('content')
{{-- Galat isian ditampilkan di halaman ini (dan modalnya dibuka lagi); banner global tidak perlu. --}}
@section('galat-di-halaman', '1')
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Bagian & Koordinator" eyebrow="Kepegawaian"
            subtitle="Daftar bagian dan koordinatornya. Koordinator dari bagian lain otomatis dicatat sebagai rangkap di Mutasi & Rangkap.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.unit.export'])
                <x-button :href="route('admin.penugasan.index')" variant="secondary" icon="bi-arrow-left-right">Mutasi &amp; Rangkap</x-button>
                @if ($bisaKelola)
                    <x-button icon="bi-plus-lg" data-bs-toggle="modal" data-bs-target="#modalTambahUnit">Tambah Bagian</x-button>
                @endif
            </x-slot:actions>
        </x-page-header>

        @if ($peringatan = session('peringatan'))
            <x-alert tone="warning" class="mb-3">{{ implode(' ', $peringatan) }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert tone="danger" title="Perubahan belum tersimpan" class="mb-3">{{ $errors->first() }}</x-alert>
        @endif

        <x-card :padding="false" aria-label="Daftar bagian">
            <x-filter-bar target="#un-daftar" search="Cari bagian, contoh: SDM atau Siti" search-label="Cari bagian menurut nama bagian atau koordinator"
                :count="$units->count()" unit="bagian" />

            <x-table caption="Daftar bagian dan koordinator" id="un-daftar" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th class="kolom-no">No</th>
                        <th>Bagian</th>
                        <th>Koordinator</th>
                        <th class="text-end">Pegawai</th>
                        <th class="text-end">Jabatan</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($units as $u)
                    @php
                        $koor = $kepala->get($u->nama, collect());
                        $gm = $gmKepala[$u->nama] ?? collect();
                        // Koordinator sekarang untuk modal: pegawai (penugasan kepala) + GM tanpa data pegawai.
                        $koorModal = $koor->map(fn ($p) => ['nilai' => 'pegawai:' . $p->data_pegawai_id, 'nama' => $p->dataPegawai?->nama_lengkap, 'ket' => $p->is_utama ? '' : ' (rangkap)'])
                            ->concat($gm->map(fn ($g) => ['nilai' => 'gm:' . $g->id, 'nama' => $g->name, 'ket' => ' (GM)']))->values();
                    @endphp
                    <tr data-cari="{{ mb_strtolower($u->nama . ' ' . $koor->pluck('dataPegawai.nama_lengkap')->implode(' ') . ' ' . $gm->pluck('name')->implode(' ')) }}">
                        <td class="kolom-no text-muted hr-angka">{{ $u->urutan }}</td>
                        <td class="kolom-penuh">
                            <span class="hr-sel-judul">{{ $u->nama }}</span>
                            @if ($u->keterangan)<span class="hr-sel-sub">{{ $u->keterangan }}</span>@endif
                        </td>
                        <td class="kolom-penuh">
                            <div class="un-koor-daftar">
                                @forelse ($koor as $p)
                                    <span class="un-koor">
                                        <i class="bi bi-person-badge" aria-hidden="true"></i>{{ $p->dataPegawai?->nama_lengkap ?? '-' }}
                                        <x-badge :tone="$p->is_utama ? 'neutral' : 'info'">{{ $p->is_utama ? 'Bagian utama' : 'Rangkap' }}</x-badge>
                                    </span>
                                @empty
                                    @if ($gm->isEmpty())
                                        <x-badge tone="danger" icon="bi-exclamation-circle">Belum ada koordinator</x-badge>
                                    @endif
                                @endforelse
                                @foreach ($gm as $g)
                                    <span class="un-koor"><i class="bi bi-person-gear" aria-hidden="true"></i>{{ $g->name }} <x-badge tone="warning">GM</x-badge></span>
                                @endforeach
                            </div>
                        </td>
                        <td class="hr-angka">{{ $anggota[$u->nama] ?? 0 }}</td>
                        <td class="hr-angka">
                            <a href="{{ route('admin.jabatan.index', ['unit' => $u->nama]) }}" title="Lihat jabatan bagian ini">{{ $u->jabatans_count }}</a>
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                <x-icon-button icon="bi-person-badge" label="Atur koordinator" tone="primary" data-bs-toggle="modal" data-bs-target="#modalKoordinator"
                                    data-unit="{{ $u->nama }}" data-url="{{ route('admin.unit.koordinator', $u) }}" data-koor='@json($koorModal)' />
                                @if ($bisaKelola)
                                    <x-icon-button icon="bi-pencil" label="Ubah bagian" data-bs-toggle="modal" data-bs-target="#modalUbahUnit"
                                        data-url="{{ route('admin.unit.update', $u) }}" data-nama="{{ $u->nama }}" data-urutan="{{ $u->urutan }}" data-keterangan="{{ $u->keterangan }}" />
                                    <form action="{{ route('admin.unit.destroy', $u) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <x-icon-button type="submit" icon="bi-trash" label="Hapus bagian" tone="danger" data-confirm="true" data-icon="warning"
                                            data-title="Hapus bagian?" data-text="Bagian {{ $u->nama }} akan dihapus. Penghapusan hanya bisa bila bagian ini tidak dipakai data apa pun."
                                            data-confirm-text="Ya, Hapus" data-cancel-text="Batal" />
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="6" icon="bi-buildings" title="Belum ada bagian">
                        Bagian dipakai untuk mengelompokkan pegawai dan menentukan koordinator penyetuju.
                        @if ($bisaKelola)
                            <x-slot:action><x-button icon="bi-plus-lg" data-bs-toggle="modal" data-bs-target="#modalTambahUnit">Tambah Bagian</x-button></x-slot:action>
                        @endif
                    </x-table-empty>
                @endforelse
                <x-table-empty :colspan="6" icon="bi-search" title="Tidak ada bagian yang cocok" data-saring-kosong="#un-daftar" hidden>
                    Coba kata kunci lain, misalnya nama bagian atau nama koordinatornya.
                </x-table-empty>
            </x-table>
        </x-card>
    </div>
</main>

{{-- Atur koordinator --}}
<x-modal id="modalKoordinator" title="Koordinator" icon="bi-person-badge" size="lg">
    <form method="POST" id="formKoordinator" data-confirm="Simpan koordinator bagian ini?">
        @csrf
        <div class="small mb-3" data-sekarang></div>
        <label class="form-label" for="un-cari-pegawai">Pilih koordinator baru <span class="hr-field-opsional">(dari Data Pegawai)</span></label>
        <input type="search" class="form-control form-control-sm mb-2" id="un-cari-pegawai" placeholder="Cari koordinator, contoh: Siti atau SMK" autocomplete="off">
        <select name="koordinator" class="form-select un-pilih-pegawai" size="9" required aria-label="Pilih koordinator baru">
            <option value="" disabled hidden>Pilih pegawai…</option>
            @foreach ($pegawaiOpsi as $pg)
                @php $gmPg = in_array($pg->id, $gmPegawaiIds, true); @endphp
                <option value="pegawai:{{ $pg->id }}" data-unit="{{ $pg->kepegawaian?->unit_kerja }}" @if ($gmPg) data-gm="1" @endif
                    data-cari="{{ mb_strtolower($pg->nama_lengkap . ' ' . $pg->nip . ' ' . $pg->kepegawaian?->unit_kerja . ($gmPg ? ' gm' : '')) }}">
                    {{ $pg->nama_lengkap }}{{ $gmPg ? ' · GM' : '' }} — {{ $pg->kepegawaian?->jabatan ?? '-' }} ({{ $pg->kepegawaian?->unit_kerja ?? '-' }})
                </option>
            @endforeach
        </select>
        <div class="form-text" data-info-rangkap>Pilih pegawai untuk melihat cara pencatatannya.</div>

        <label class="form-label mt-3" for="un-jabatan">Jabatan koordinator <span class="hr-field-opsional">(opsional)</span></label>
        <input type="text" name="jabatan" id="un-jabatan" class="form-control" list="un-jabatan-daftar" maxlength="150" autocomplete="off">
        <datalist id="un-jabatan-daftar"></datalist>
        <div class="form-text">Kosongkan: jabatan bagian utamanya tetap, atau "KEPALA &lt;BAGIAN&gt;" untuk rangkap.</div>
    </form>
    <form method="POST" id="formKosongkan" hidden data-confirm="Kosongkan koordinator bagian ini? Pengajuan pegawai bagian ini akan diteruskan ke GM.">
        @csrf
        <input type="hidden" name="koordinator" value="">
    </form>
    <x-slot:footer>
        <x-button variant="danger-outline" icon="bi-person-x" class="me-auto" data-kosongkan hidden>Kosongkan</x-button>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formKoordinator" icon="bi-check-lg">Simpan</x-button>
    </x-slot:footer>
</x-modal>

@if ($bisaKelola)
    {{-- Tambah bagian --}}
    <x-modal id="modalTambahUnit" title="Tambah Bagian" icon="bi-buildings" subtitle="Bagian baru langsung muncul di semua pilihan bagian.">
        <form action="{{ route('admin.unit.store') }}" method="POST" id="formTambahUnit">
            @csrf
            <input type="hidden" name="_form" value="tambah">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label required" for="un-tambah-nama">Nama bagian</label>
                    <input type="text" name="nama" id="un-tambah-nama" class="form-control text-uppercase" maxlength="100" required autocomplete="off"
                        placeholder="Tulis nama bagian, contoh: LEGAL" value="{{ old('_form') === 'tambah' ? old('nama') : '' }}">
                </div>
                <div class="col-12">
                    <label class="form-label" for="un-tambah-ket">Keterangan <span class="hr-field-opsional">(opsional)</span></label>
                    <input type="text" name="keterangan" id="un-tambah-ket" class="form-control" maxlength="255"
                        placeholder="Tulis keterangan singkat, contoh: Bagian hukum" value="{{ old('_form') === 'tambah' ? old('keterangan') : '' }}">
                </div>
            </div>
        </form>
        <x-slot:footer>
            <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
            <x-button type="submit" form="formTambahUnit" icon="bi-plus-lg">Tambah Bagian</x-button>
        </x-slot:footer>
    </x-modal>

    {{-- Ubah bagian --}}
    <x-modal id="modalUbahUnit" title="Ubah Bagian" icon="bi-pencil-square">
        <form method="POST" id="formUbahUnit" data-confirm="Simpan perubahan bagian? Bila nama diganti, semua data yang memakai nama lama ikut diperbarui.">
            @csrf @method('PUT')
            <input type="hidden" name="_form" value="ubah">
            <input type="hidden" name="_url">
            <div class="row g-3">
                <div class="col-8">
                    <label class="form-label required" for="un-ubah-nama">Nama bagian</label>
                    <input type="text" name="nama" id="un-ubah-nama" class="form-control text-uppercase" maxlength="100" required placeholder="Contoh: LEGAL">
                </div>
                <div class="col-4">
                    <label class="form-label required" for="un-ubah-urutan">Urutan</label>
                    <input type="number" name="urutan" id="un-ubah-urutan" class="form-control" min="0" max="9999" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="un-ubah-ket">Keterangan <span class="hr-field-opsional">(opsional)</span></label>
                    <input type="text" name="keterangan" id="un-ubah-ket" class="form-control" maxlength="255" placeholder="Tulis keterangan singkat, contoh: Bagian hukum">
                </div>
            </div>
            <p class="form-text mt-2 mb-0">Urutan menentukan posisi bagian di semua daftar pilihan.</p>
        </form>
        <x-slot:footer>
            <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
            <x-button type="submit" form="formUbahUnit" icon="bi-check-lg">Simpan</x-button>
        </x-slot:footer>
    </x-modal>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Saring langsung ditangani global (main.js: data-saring).
        const jabatanPerUnit = @json($jabatanPerUnit);
        const modal = document.getElementById('modalKoordinator');
        const judul = document.getElementById('modalKoordinator-judul');
        const form = document.getElementById('formKoordinator');
        const formKosong = document.getElementById('formKosongkan');
        const pilih = form.querySelector('[name=koordinator]');
        const cariPg = document.getElementById('un-cari-pegawai');
        const info = form.querySelector('[data-info-rangkap]');
        const tombolKosong = modal.querySelector('[data-kosongkan]');
        let unitAktif = '';

        function jelaskan() {
            // Opsi kosong (belum memilih) jangan dianggap pegawai dari bagian lain.
            const opsi = pilih.selectedOptions[0]?.value ? pilih.selectedOptions[0] : null;
            form.jabatan.disabled = Boolean(opsi?.dataset.tanpaPegawai);
            if (!opsi) { info.textContent = 'Pilih pegawai untuk melihat cara pencatatannya.'; return; }
            if (opsi.dataset.tanpaPegawai) {
                const bidang = JSON.parse(opsi.dataset.divisi || '[]').includes(unitAktif);
                info.innerHTML = '<i class="bi bi-person-gear me-1"></i>GM tanpa data pegawai — bagian dicatat di akun GM (seperti di Kelola Akun).'
                    + (bidang ? '' : ' <span class="text-warning-emphasis">Bagian ini di luar bidang GM tersebut.</span>');
                return;
            }
            const samaBagian = (opsi.dataset.unit || '').trim().toUpperCase() === (unitAktif || '').trim().toUpperCase();
            info.innerHTML = samaBagian
                ? '<i class="bi bi-arrow-up-circle me-1"></i>Sudah di bagian ini — dinaikkan menjadi koordinator.'
                : '<i class="bi bi-diagram-2 me-1"></i>Bagian utamanya <strong></strong> — dicatat sebagai <strong>rangkap</strong> koordinator di ' + unitAktif + '.';
            const s = info.querySelector('strong');
            if (s && !samaBagian) s.textContent = opsi.dataset.unit || '-';
        }
        function saring() {
            const kata = cariPg.value.trim().toLowerCase();
            Array.from(pilih.options).forEach((o) => { o.hidden = !o.dataset.cari?.includes(kata); });
        }

        modal.addEventListener('show.bs.modal', (e) => {
            const t = e.relatedTarget;
            unitAktif = t.dataset.unit;
            form.action = formKosong.action = t.dataset.url;
            judul.textContent = 'Koordinator ' + unitAktif;
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
            form.jabatan.placeholder = 'Contoh: KEPALA ' + unitAktif;
            jelaskan();
        });
        modal.addEventListener('shown.bs.modal', () => cariPg.focus());
        cariPg.addEventListener('input', saring);
        pilih.addEventListener('change', jelaskan);
        tombolKosong.addEventListener('click', () => formKosong.requestSubmit());

        const modalUbah = document.getElementById('modalUbahUnit');
        const formUbah = document.getElementById('formUbahUnit');
        const isiUbah = (d) => {
            formUbah.action = formUbah._url.value = d.url;
            formUbah.nama.value = d.nama;
            formUbah.urutan.value = d.urutan;
            formUbah.keterangan.value = d.keterangan || '';
        };
        modalUbah?.addEventListener('show.bs.modal', (e) => { if (e.relatedTarget) isiUbah(e.relatedTarget.dataset); });

        // Galat validasi dari server → buka lagi modal yang sama dengan isian terakhir.
        @if ($errors->any() && old('_form') === 'tambah')
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTambahUnit')).show();
        @elseif ($errors->any() && old('_form') === 'ubah' && old('_url'))
            isiUbah({{ \Illuminate\Support\Js::from(['url' => old('_url'), 'nama' => old('nama'), 'urutan' => old('urutan'), 'keterangan' => old('keterangan')]) }});
            bootstrap.Modal.getOrCreateInstance(modalUbah).show();
        @endif
    });
</script>
@endsection
