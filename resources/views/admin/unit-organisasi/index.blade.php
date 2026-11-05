@extends('layouts.admin.app')

@section('title', 'Master Unit')

@section('content')
{{-- Galat isian ditampilkan di halaman ini (dan modalnya dibuka lagi); banner global tidak perlu. --}}
@section('galat-di-halaman', '1')
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Master Unit" eyebrow="Kepegawaian"
            subtitle="Unit membawahi beberapa bagian; unit pegawai terisi otomatis dari bagiannya. Tanpa koordinator; persetujuan tetap mengikuti Bagian.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.master-unit.export'])
                <x-button :href="route('admin.unit.index')" variant="secondary" icon="bi-buildings">Bagian &amp; Koordinator</x-button>
                <x-button icon="bi-plus-lg" data-bs-toggle="modal" data-bs-target="#modalTambahUnitOrg">Tambah Unit</x-button>
            </x-slot:actions>
        </x-page-header>

        @if ($errors->any())
            <x-alert tone="danger" title="Perubahan belum tersimpan" class="mb-3">{{ $errors->first() }}</x-alert>
        @endif

        <x-card :padding="false" aria-label="Daftar unit">
            <x-filter-bar target="#uo-daftar" search="Cari unit, contoh: TK" search-label="Cari unit menurut nama atau keterangan"
                :count="$unit->count()" unit="unit" />

            <x-table caption="Daftar unit" id="uo-daftar" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th class="kolom-no">No</th>
                        <th>Unit</th>
                        <th>Bagian</th>
                        <th class="text-end">Pegawai Aktif</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($unit as $u)
                    <tr data-cari="{{ mb_strtolower($u->nama . ' ' . $u->keterangan . ' ' . $u->bagians->pluck('nama')->implode(' ')) }}">
                        <td class="kolom-no text-muted hr-angka">{{ $u->urutan }}</td>
                        <td class="kolom-penuh">
                            <span class="hr-sel-judul">{{ $u->nama }}</span>
                            @if ($u->keterangan)<span class="hr-sel-sub">{{ $u->keterangan }}</span>@endif
                        </td>
                        <td>
                            <span class="jb-unit">
                                @forelse ($u->bagians as $b)
                                    <span class="jb-unit-chip">{{ $b->nama }}</span>
                                @empty
                                    <span class="jb-unit-chip kosong" title="Belum membawahi bagian — unit pegawai tidak terisi otomatis">belum ada bagian</span>
                                @endforelse
                            </span>
                        </td>
                        <td class="hr-angka">
                            @if ($n = $pegawai[$u->nama] ?? 0)
                                <a href="{{ route('admin.pegawai.index', ['unit_organisasi' => $u->nama]) }}" title="Lihat pegawai unit ini">{{ $n }}</a>
                            @else
                                0
                            @endif
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                <x-icon-button icon="bi-pencil" label="Ubah unit" data-bs-toggle="modal" data-bs-target="#modalUbahUnitOrg"
                                    data-url="{{ route('admin.master-unit.update', $u) }}" data-nama="{{ $u->nama }}" data-urutan="{{ $u->urutan }}" data-keterangan="{{ $u->keterangan }}"
                                    data-bagian-ids="{{ $u->bagians->pluck('id')->toJson() }}" data-unit-id="{{ $u->id }}" />
                                <form action="{{ route('admin.master-unit.destroy', $u) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <x-icon-button type="submit" icon="bi-trash" label="Hapus unit" tone="danger" data-confirm="true" data-icon="warning"
                                        data-title="Hapus unit?" data-text="Unit {{ $u->nama }} akan dihapus. Penghapusan hanya bisa bila unit ini tidak dipakai pegawai mana pun."
                                        data-confirm-text="Ya, Hapus" data-cancel-text="Batal" />
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="5" icon="bi-grid-3x3-gap" title="Belum ada unit">
                        Unit dipakai untuk mengelompokkan pegawai, misalnya TK, SD, atau SMK.
                        <x-slot:action><x-button icon="bi-plus-lg" data-bs-toggle="modal" data-bs-target="#modalTambahUnitOrg">Tambah Unit</x-button></x-slot:action>
                    </x-table-empty>
                @endforelse
                <x-table-empty :colspan="5" icon="bi-search" title="Tidak ada unit yang cocok" data-saring-kosong="#uo-daftar" hidden>
                    Coba kata kunci lain, misalnya nama atau keterangan unit.
                </x-table-empty>
            </x-table>
        </x-card>
    </div>
</main>

{{-- Tambah unit --}}
<x-modal id="modalTambahUnitOrg" title="Tambah Unit" icon="bi-grid-3x3-gap" size="lg" scrollable subtitle="Unit baru langsung muncul di pilihan unit data pegawai.">
    <form action="{{ route('admin.master-unit.store') }}" method="POST" id="formTambahUnitOrg">
        @csrf
        <input type="hidden" name="_form" value="tambah">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label required" for="uo-tambah-nama">Nama unit</label>
                <input type="text" name="nama" id="uo-tambah-nama" class="form-control text-uppercase" maxlength="100" required autocomplete="off"
                    placeholder="Tulis nama unit, contoh: TK" value="{{ old('_form') === 'tambah' ? old('nama') : '' }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="uo-tambah-ket">Keterangan <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" name="keterangan" id="uo-tambah-ket" class="form-control" maxlength="255"
                    placeholder="Tulis keterangan singkat, contoh: Taman kanak-kanak" value="{{ old('_form') === 'tambah' ? old('keterangan') : '' }}">
            </div>
            <div class="col-12">@include('admin.unit-organisasi._pilih-bagian', ['dipilih' => old('_form') === 'tambah' ? array_map('intval', old('bagian_ids', [])) : []])</div>
        </div>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formTambahUnitOrg" icon="bi-plus-lg">Tambah Unit</x-button>
    </x-slot:footer>
</x-modal>

{{-- Ubah unit --}}
<x-modal id="modalUbahUnitOrg" title="Ubah Unit" icon="bi-pencil-square" size="lg" scrollable>
    <form method="POST" id="formUbahUnitOrg" data-confirm="Simpan perubahan unit? Bila nama diganti, data pegawai yang memakai nama lama ikut diperbarui.">
        @csrf @method('PUT')
        <input type="hidden" name="_form" value="ubah">
        <input type="hidden" name="_url">
        <div class="row g-3">
            <div class="col-8">
                <label class="form-label required" for="uo-nama">Nama unit</label>
                <input type="text" name="nama" id="uo-nama" class="form-control text-uppercase" maxlength="100" required placeholder="Contoh: TK">
            </div>
            <div class="col-4">
                <label class="form-label required" for="uo-urutan">Urutan</label>
                <input type="number" name="urutan" id="uo-urutan" class="form-control" min="0" max="9999" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="uo-keterangan">Keterangan <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" name="keterangan" id="uo-keterangan" class="form-control" maxlength="255" placeholder="Tulis keterangan singkat, contoh: Taman kanak-kanak">
            </div>
            <div class="col-12">@include('admin.unit-organisasi._pilih-bagian', ['dipilih' => []])</div>
        </div>
        <p class="form-text mt-2 mb-0">Urutan menentukan posisi unit di semua daftar pilihan.</p>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formUbahUnitOrg" icon="bi-check-lg">Simpan</x-button>
    </x-slot:footer>
</x-modal>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Saring langsung ditangani global (main.js: data-saring).
        const modalUbah = document.getElementById('modalUbahUnitOrg');
        const formUbah = document.getElementById('formUbahUnitOrg');
        const isiUbah = (d) => {
            formUbah.action = formUbah._url.value = d.url;
            formUbah.nama.value = d.nama;
            formUbah.urutan.value = d.urutan;
            formUbah.keterangan.value = d.keterangan || '';
            // Centang bagian milik unit ini; keterangan "di unit lain" hanya untuk bagian unit lain.
            const milik = JSON.parse(d.bagianIds || '[]').map(String);
            formUbah.querySelectorAll('[name="bagian_ids[]"]').forEach((c) => {
                c.checked = milik.includes(c.value);
                const lain = c.closest('label').querySelector('[data-unit-lain]');
                if (lain) lain.hidden = String(c.dataset.unitId || '') === String(d.unitId || '');
            });
        };
        // Saring daftar bagian di kedua modal.
        document.querySelectorAll('[data-cari-bagian]').forEach((cari) => cari.addEventListener('input', () => {
            const kata = cari.value.trim().toLowerCase();
            cari.closest('[data-pilih-bagian]').querySelectorAll('[data-nama-bagian]').forEach((l) => { l.hidden = !!kata && !l.dataset.namaBagian.includes(kata); });
        }));
        modalUbah.addEventListener('show.bs.modal', (e) => { if (e.relatedTarget) isiUbah(e.relatedTarget.dataset); });

        // Galat validasi dari server → buka lagi modal yang sama dengan isian terakhir.
        @if ($errors->any() && old('_form') === 'tambah')
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTambahUnitOrg')).show();
        @elseif ($errors->any() && old('_form') === 'ubah' && old('_url'))
            isiUbah({{ \Illuminate\Support\Js::from(['url' => old('_url'), 'nama' => old('nama'), 'urutan' => old('urutan'), 'keterangan' => old('keterangan'), 'bagianIds' => json_encode(array_map('intval', old('bagian_ids', [])))]) }});
            bootstrap.Modal.getOrCreateInstance(modalUbah).show();
        @endif
    });
</script>
@endsection
