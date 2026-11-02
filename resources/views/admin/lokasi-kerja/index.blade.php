@extends('layouts.admin.app')

@section('title', 'Master Lokasi Kerja')

@section('content')
{{-- Galat isian ditampilkan di halaman ini (dan modalnya dibuka lagi); banner global tidak perlu. --}}
@section('galat-di-halaman', '1')
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Master Lokasi Kerja" eyebrow="Sistem"
            subtitle="Tempat atau area kerja pegawai. Daftar ini menjadi pilihan lokasi kerja di data pegawai, lowongan, dan kandidat langsung.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.lokasi-kerja.export'])
                <x-button icon="bi-plus-lg" data-bs-toggle="modal" data-bs-target="#modalTambahLokasi">Tambah Lokasi</x-button>
            </x-slot:actions>
        </x-page-header>

        @if ($errors->any())
            <x-alert tone="danger" title="Perubahan belum tersimpan" class="mb-3">{{ $errors->first() }}</x-alert>
        @endif

        <x-card :padding="false" aria-label="Daftar lokasi kerja">
            <x-filter-bar target="#lk-daftar" search="Cari lokasi, contoh: Kampus" search-label="Cari lokasi kerja menurut nama atau alamat"
                :count="$lokasi->count()" unit="lokasi" />

            <x-table caption="Daftar lokasi kerja" id="lk-daftar" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th class="kolom-no">No</th>
                        <th>Lokasi Kerja</th>
                        <th class="text-end">Pegawai Aktif</th>
                        <th class="text-end">Lowongan</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($lokasi as $l)
                    <tr data-cari="{{ mb_strtolower($l->nama . ' ' . $l->keterangan) }}">
                        <td class="kolom-no text-muted hr-angka">{{ $l->urutan }}</td>
                        <td class="kolom-penuh">
                            <span class="hr-sel-judul">{{ $l->nama }}</span>
                            @if ($l->keterangan)<span class="hr-sel-sub">{{ $l->keterangan }}</span>@endif
                        </td>
                        <td class="hr-angka">{{ $pegawai[$l->nama] ?? 0 }}</td>
                        <td class="hr-angka">{{ $lowongan[$l->nama] ?? 0 }}</td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                <x-icon-button icon="bi-pencil" label="Ubah lokasi" data-bs-toggle="modal" data-bs-target="#modalUbahLokasi"
                                    data-url="{{ route('admin.lokasi-kerja.update', $l) }}" data-nama="{{ $l->nama }}" data-urutan="{{ $l->urutan }}" data-keterangan="{{ $l->keterangan }}" />
                                <form action="{{ route('admin.lokasi-kerja.destroy', $l) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <x-icon-button type="submit" icon="bi-trash" label="Hapus lokasi" tone="danger" data-confirm="true" data-icon="warning"
                                        data-title="Hapus lokasi kerja?" data-text="Lokasi {{ $l->nama }} akan dihapus. Penghapusan hanya bisa bila lokasi ini tidak dipakai data apa pun."
                                        data-confirm-text="Ya, Hapus" data-cancel-text="Batal" />
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="5" icon="bi-geo-alt" title="Belum ada lokasi kerja">
                        Lokasi kerja menjadi pilihan di data pegawai, lowongan, dan kandidat langsung.
                        <x-slot:action><x-button icon="bi-plus-lg" data-bs-toggle="modal" data-bs-target="#modalTambahLokasi">Tambah Lokasi</x-button></x-slot:action>
                    </x-table-empty>
                @endforelse
                <x-table-empty :colspan="5" icon="bi-search" title="Tidak ada lokasi yang cocok" data-saring-kosong="#lk-daftar" hidden>
                    Coba kata kunci lain, misalnya nama atau alamat lokasi.
                </x-table-empty>
            </x-table>
        </x-card>
    </div>
</main>

{{-- Tambah lokasi --}}
<x-modal id="modalTambahLokasi" title="Tambah Lokasi Kerja" icon="bi-geo-alt" subtitle="Lokasi baru langsung muncul di semua pilihan lokasi kerja.">
    <form action="{{ route('admin.lokasi-kerja.store') }}" method="POST" id="formTambahLokasi">
        @csrf
        <input type="hidden" name="_form" value="tambah">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label required" for="lk-tambah-nama">Nama lokasi</label>
                <input type="text" name="nama" id="lk-tambah-nama" class="form-control text-uppercase" maxlength="100" required autocomplete="off"
                    placeholder="Tulis nama lokasi, contoh: SMK" value="{{ old('_form') === 'tambah' ? old('nama') : '' }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="lk-tambah-ket">Alamat / keterangan <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" name="keterangan" id="lk-tambah-ket" class="form-control" maxlength="255"
                    placeholder="Tulis alamat singkat, contoh: Jl. Riau 73" value="{{ old('_form') === 'tambah' ? old('keterangan') : '' }}">
            </div>
        </div>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formTambahLokasi" icon="bi-plus-lg">Tambah Lokasi</x-button>
    </x-slot:footer>
</x-modal>

{{-- Ubah lokasi --}}
<x-modal id="modalUbahLokasi" title="Ubah Lokasi Kerja" icon="bi-pencil-square">
    <form method="POST" id="formUbahLokasi" data-confirm="Simpan perubahan lokasi kerja? Bila nama diganti, semua data yang memakai nama lama ikut diperbarui.">
        @csrf @method('PUT')
        <input type="hidden" name="_form" value="ubah">
        <input type="hidden" name="_url">
        <div class="row g-3">
            <div class="col-8">
                <label class="form-label required" for="lk-nama">Nama lokasi</label>
                <input type="text" name="nama" id="lk-nama" class="form-control text-uppercase" maxlength="100" required placeholder="Contoh: SMK">
            </div>
            <div class="col-4">
                <label class="form-label required" for="lk-urutan">Urutan</label>
                <input type="number" name="urutan" id="lk-urutan" class="form-control" min="0" max="9999" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="lk-keterangan">Alamat / keterangan <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" name="keterangan" id="lk-keterangan" class="form-control" maxlength="255" placeholder="Tulis alamat singkat, contoh: Jl. Riau 73">
            </div>
        </div>
        <p class="form-text mt-2 mb-0">Urutan menentukan posisi lokasi di semua daftar pilihan.</p>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formUbahLokasi" icon="bi-check-lg">Simpan</x-button>
    </x-slot:footer>
</x-modal>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Saring langsung ditangani global (main.js: data-saring).
        const modalUbah = document.getElementById('modalUbahLokasi');
        const formUbah = document.getElementById('formUbahLokasi');
        const isiUbah = (d) => {
            formUbah.action = formUbah._url.value = d.url;
            formUbah.nama.value = d.nama;
            formUbah.urutan.value = d.urutan;
            formUbah.keterangan.value = d.keterangan || '';
        };
        modalUbah.addEventListener('show.bs.modal', (e) => { if (e.relatedTarget) isiUbah(e.relatedTarget.dataset); });

        // Galat validasi dari server → buka lagi modal yang sama dengan isian terakhir.
        @if ($errors->any() && old('_form') === 'tambah')
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTambahLokasi')).show();
        @elseif ($errors->any() && old('_form') === 'ubah' && old('_url'))
            isiUbah({{ \Illuminate\Support\Js::from(['url' => old('_url'), 'nama' => old('nama'), 'urutan' => old('urutan'), 'keterangan' => old('keterangan')]) }});
            bootstrap.Modal.getOrCreateInstance(modalUbah).show();
        @endif
    });
</script>
@endsection
