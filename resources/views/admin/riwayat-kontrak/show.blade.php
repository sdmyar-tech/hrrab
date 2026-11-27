@extends('layouts.admin.app')

@section('title', 'Riwayat Kontrak ' . $pegawai->nama_lengkap)

@section('content')
@php
    use App\Models\KontrakPegawai as KP;
    $k = $pegawai->kepegawaian;
    $saran = collect(KP::JENIS)->first(fn ($j) => in_array($j, KP::TUNGGAL, true) && ! $kontrak->contains('jenis', $j)) ?? 'Lainnya';
    // Baris isian: kiriman sebelumnya (bila gagal simpan) atau satu baris kosong dengan jenis yang disarankan.
    $barisForm = old('baris', [['jenis' => $saran]]);
    $bulan = fn ($m, $s) => $m && $s && $s->gte($m) ? max(1, (int) round($m->diffInDays($s->copy()->addDay()) / 30.4375)) : null;
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="$pegawai->nama_lengkap" eyebrow="Riwayat Kontrak"
            :subtitle="collect([$pegawai->nip, $k?->jabatan, $k?->unit_kerja])->filter()->implode(' · ') ?: null"
            :back="route('admin.riwayat-kontrak.index')" back-label="Riwayat Kontrak">
            <x-slot:actions>
                <x-button variant="secondary" icon="bi-person-vcard" :href="route('admin.pegawai.show', $pegawai)">Data Pegawai</x-button>
            </x-slot:actions>
        </x-page-header>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3"><x-stat-card label="Dikontrak (PKWT)" :value="$jumlahPkwt . ' kali'" icon="bi-journal-text" tone="info" meta="Hanya PKWT 1, 2, …" /></div>
            <div class="col-6 col-xl-3"><x-stat-card label="Sedang berlaku" :value="$berlaku?->jenis ?? '-'" icon="bi-check2-circle" :tone="$berlaku ? 'success' : 'neutral'"
                :meta="$berlaku ? ($berlaku->tanggal_selesai ? 'sampai ' . $berlaku->tanggal_selesai->translatedFormat('d M Y') : 'tanpa batas waktu') : 'tidak ada catatan yang berlaku'" /></div>
            <div class="col-6 col-xl-3"><x-stat-card label="Masuk" :value="$k?->tanggal_masuk?->translatedFormat('d M Y') ?? '-'" icon="bi-box-arrow-in-right" tone="neutral" :meta="$k?->status_kerja ?? ''" /></div>
            <div class="col-6 col-xl-3"><x-stat-card label="Habis kontrak (data pegawai)" :value="$k?->tanggal_habis_kontrak?->translatedFormat('d M Y') ?? '-'" icon="bi-calendar-x" tone="warning" meta="Dari Data Pegawai" /></div>
        </div>

        <div class="row g-4">
            <div class="{{ $bolehUbah ? 'col-xl-7' : 'col-12' }}">
                <x-card :padding="false" title="Riwayat Tercatat" icon="bi-journal-bookmark" :subtitle="$kontrak->count() . ' catatan, urut dari yang paling awal'">
                    <x-table caption="Riwayat kontrak {{ $pegawai->nama_lengkap }}" striped scroll>
                        <x-slot:head>
                            <tr>
                                <th>Jenis</th>
                                <th>Periode</th>
                                <th>Keterangan</th>
                                @if ($bolehUbah)<th class="hr-kolom-aksi">Aksi</th>@endif
                            </tr>
                        </x-slot:head>
                        @forelse ($kontrak as $r)
                            <tr>
                                <td>
                                    <x-badge :tone="$r->warna()">{{ $r->jenis }}</x-badge>
                                    @if ($r->berlaku())<span class="hr-sel-badge"><x-badge tone="success" dot>Berlaku</x-badge></span>@endif
                                </td>
                                <td>
                                    <span class="text-nowrap">{{ $r->tanggal_mulai->translatedFormat('d M Y') }} – {{ $r->tanggal_selesai?->translatedFormat('d M Y') ?? 'sekarang' }}</span>
                                    @if ($b = $bulan($r->tanggal_mulai, $r->tanggal_selesai))<span class="hr-sel-sub">± {{ $b }} bulan</span>@endif
                                </td>
                                <td>
                                    {{ $r->keterangan ?: '-' }}
                                    @if ($r->nomor_kontrak)<span class="hr-sel-sub">No. {{ $r->nomor_kontrak }}</span>@endif
                                </td>
                                @if ($bolehUbah)
                                    <td class="text-end hr-kolom-aksi">
                                        <div class="hr-aksi-baris">
                                            <x-icon-button icon="bi-pencil" label="Ubah" data-bs-toggle="modal" data-bs-target="#modalUbahKontrak"
                                                data-url="{{ route('admin.riwayat-kontrak.update', [$pegawai, $r]) }}"
                                                data-jenis="{{ $r->jenis }}" data-mulai="{{ $r->tanggal_mulai->toDateString() }}"
                                                data-selesai="{{ $r->tanggal_selesai?->toDateString() }}" data-nomor="{{ $r->nomor_kontrak }}" data-keterangan="{{ $r->keterangan }}" />
                                            <form method="POST" action="{{ route('admin.riwayat-kontrak.destroy', [$pegawai, $r]) }}">
                                                @csrf @method('DELETE')
                                                <x-icon-button type="submit" icon="bi-trash" label="Hapus" tone="danger" data-confirm="true"
                                                    data-title="Hapus riwayat {{ $r->jenis }}?" data-text="Catatan ini dihapus dari riwayat kontrak {{ $pegawai->nama_lengkap }}." data-confirm-text="Ya, Hapus" />
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <x-table-empty :colspan="$bolehUbah ? 4 : 3" icon="bi-journal-bookmark" title="Belum ada riwayat kontrak">
                                @if ($bolehUbah)Isi lewat formulir di samping, mis. Masa Training → PKWT 1 → PKWTT.@endif
                            </x-table-empty>
                        @endforelse
                    </x-table>
                </x-card>
            </div>

            @if ($bolehUbah)
                <div class="col-xl-5">
                    <x-card title="Tambah Riwayat" icon="bi-plus-circle" subtitle="Boleh beberapa baris sekaligus, mis. Masa Training, PKWT 1, PKWT 2.">
                        <form action="{{ route('admin.riwayat-kontrak.store', $pegawai) }}" method="POST" id="formTambahKontrak">
                            @csrf
                            <div id="daftarBaris" class="d-grid gap-3">
                                @foreach ($barisForm as $i => $b)
                                    @include('admin.riwayat-kontrak._baris', ['i' => $i, 'b' => $b])
                                @endforeach
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <x-button variant="outline" type="button" icon="bi-plus-lg" id="tambahBaris">Tambah baris</x-button>
                                <x-button type="submit" icon="bi-check-lg" class="ms-auto">Simpan Riwayat</x-button>
                            </div>
                            <p class="form-text mt-2 mb-0">
                                Tanggal selesai boleh dikosongkan hanya untuk PKWTT / Lainnya. Jenis PKWT hanya boleh satu kali per nomor.
                            </p>
                        </form>
                    </x-card>
                </div>
            @endif
        </div>
    </div>
</main>

@if ($bolehUbah)
    <x-modal id="modalUbahKontrak" title="Ubah Riwayat Kontrak" icon="bi-pencil-square">
        <form method="POST" id="formUbahKontrak" data-confirm="Simpan perubahan riwayat kontrak ini?">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label required" for="uk-jenis">Jenis</label>
                    <select name="jenis" id="uk-jenis" class="form-select" required>
                        @foreach (KP::JENIS as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label required" for="uk-mulai">Mulai</label>
                    <input type="date" name="tanggal_mulai" id="uk-mulai" class="form-control" required>
                </div>
                <div class="col-6">
                    <label class="form-label" for="uk-selesai">Selesai <span class="hr-field-opsional">(kosong bila PKWTT)</span></label>
                    <input type="date" name="tanggal_selesai" id="uk-selesai" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label" for="uk-nomor">Nomor kontrak <span class="hr-field-opsional">(opsional)</span></label>
                    <input type="text" name="nomor_kontrak" id="uk-nomor" class="form-control" maxlength="100" placeholder="Contoh: 012/SDM/PKWT/2025">
                </div>
                <div class="col-12">
                    <label class="form-label" for="uk-ket">Keterangan <span class="hr-field-opsional">(opsional)</span></label>
                    <input type="text" name="keterangan" id="uk-ket" class="form-control" maxlength="500" placeholder="Contoh: Kontrak pertama setelah lulus training">
                </div>
            </div>
        </form>
        <x-slot:footer>
            <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
            <x-button type="submit" form="formUbahKontrak" icon="bi-check-lg">Simpan</x-button>
        </x-slot:footer>
    </x-modal>

    {{-- Cetakan baris baru (diklon tombol "Tambah baris"); indeks diganti __I__. --}}
    <template id="cetakanBaris">
        @include('admin.riwayat-kontrak._baris', ['i' => '__I__', 'b' => ['jenis' => '']])
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const daftar = document.getElementById('daftarBaris');
            const cetakan = document.getElementById('cetakanBaris');
            let indeks = daftar.children.length + 100;

            document.getElementById('tambahBaris').addEventListener('click', function () {
                const baris = cetakan.content.firstElementChild.cloneNode(true);
                baris.innerHTML = baris.innerHTML.replaceAll('__I__', indeks++);
                daftar.append(baris);
                baris.querySelector('select').focus();
            });
            daftar.addEventListener('click', function (e) {
                const hapus = e.target.closest('[data-hapus-baris]');
                if (hapus && daftar.children.length > 1) hapus.closest('[data-baris]').remove();
            });

            const modal = document.getElementById('modalUbahKontrak');
            const form = document.getElementById('formUbahKontrak');
            modal.addEventListener('show.bs.modal', function (e) {
                const d = e.relatedTarget?.dataset;
                if (!d) return;
                form.action = d.url;
                form.jenis.value = d.jenis;
                form.tanggal_mulai.value = d.mulai;
                form.tanggal_selesai.value = d.selesai || '';
                form.nomor_kontrak.value = d.nomor || '';
                form.keterangan.value = d.keterangan || '';
            });
        });
    </script>
@endif
@endsection
