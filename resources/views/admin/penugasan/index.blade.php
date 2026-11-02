@extends('layouts.admin.app')

@section('title', 'Mutasi & Rangkap Tugas')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'jenis', 'status', 'tanggal_dari', 'tanggal_sampai']);
    $isAdmin = auth()->user()->isAdmin();
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Mutasi & Rangkap Tugas" eyebrow="Kepegawaian"
            subtitle="Riwayat perpindahan bagian dan rangkap tugas pegawai. Mutasi atau rangkap baru dibuat dari halaman ini.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.penugasan.export'])
                <x-button :href="route('admin.penugasan.tambah')" icon="bi-plus-lg">Tambah Mutasi / Rangkap</x-button>
            </x-slot:actions>
        </x-page-header>

        @if ($unitsTanpaKepala)
            <x-alert tone="warning" title="Bagian tanpa kepala aktif: {{ implode(', ', $unitsTanpaKepala) }}" class="mb-3">
                Pengajuan cuti/lembur staf di bagian ini tidak punya penyetuju tahap Kepala Bagian. Tunjuk kepala lewat Mutasi/Rangkap (peran Kepala) atau Kelola Akun.
            </x-alert>
        @endif

        {{-- Kartu angka sekaligus pintasan filter (satu klik ke daftar yang dimaksud). --}}
        <section class="row g-3 mb-4" aria-label="Ringkasan penugasan">
            <div class="col-6 col-lg-3">
                <x-stat-card label="Rangkap aktif" :value="$jumlahRangkapAktif" icon="bi-diagram-2" tone="info"
                    :href="route('admin.penugasan.index', ['jenis' => 'rangkap', 'status' => 'berlaku'])" />
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Terjadwal (belum berlaku)" :value="$jumlahTerjadwal" icon="bi-calendar-event" tone="warning"
                    :href="route('admin.penugasan.index', ['status' => 'terjadwal'])" />
            </div>
        </section>

        <x-card :padding="false" aria-label="Daftar mutasi dan rangkap tugas">
            <x-filter-bar :action="route('admin.penugasan.index')" id="filterPenugasanForm"
                search="Cari pegawai atau bagian, contoh: Siti atau SMK" search-label="Cari mutasi/rangkap menurut nama pegawai, NIP, atau bagian"
                :count="$penugasans->total()" unit="data" :filtered="$adaFilter" :reset="route('admin.penugasan.index')">
                <select name="jenis" class="form-select form-select-sm" aria-label="Saring jenis" onchange="this.form.submit()">
                    <option value="">Semua jenis</option>
                    <option value="mutasi" @selected(request('jenis') === 'mutasi')>Mutasi</option>
                    <option value="rangkap" @selected(request('jenis') === 'rangkap')>Rangkap</option>
                </select>
                <select name="status" class="form-select form-select-sm" aria-label="Saring status" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="berlaku" @selected(request('status') === 'berlaku')>Berlaku</option>
                    <option value="terjadwal" @selected(request('status') === 'terjadwal')>Terjadwal</option>
                    <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
                </select>
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Mulai'])
                @include('partials.urut', ['urutForm' => 'filterPenugasanForm'])
            </x-filter-bar>

            <x-table caption="Daftar mutasi dan rangkap tugas" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Pegawai</th>
                        <th>Penugasan</th>
                        <th>Periode</th>
                        {{-- Disembunyikan di tablet agar kolom utama tidak berdesakan. --}}
                        <th class="d-md-none d-lg-table-cell">SK / Dicatat</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($penugasans as $p)
                    @php $urlPegawai = route('admin.pegawai.show', ['pegawai' => $p->data_pegawai_id, 'tab' => 'penugasan']); @endphp
                    <tr>
                        <td>
                            <a href="{{ $urlPegawai }}" class="hr-sel-judul">{{ $p->dataPegawai?->nama_lengkap ?? '-' }}</a>
                            <span class="hr-sel-sub">Bagian utama: {{ $p->dataPegawai?->kepegawaian?->unit_kerja ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="d-flex flex-wrap align-items-center gap-2">
                                <x-badge :tone="$p->jenis === 'mutasi' ? 'primary' : 'info'">{{ $p->jenisLabel() }}</x-badge>
                                <span class="fw-semibold">{{ $p->unit_kerja }}</span>
                            </span>
                            <span class="hr-sel-sub mt-1">
                                {{ $p->jabatan ?? '-' }}
                                @if ($p->isKepala())<x-badge tone="neutral" class="ms-1">Kepala</x-badge>@endif
                            </span>
                        </td>
                        <td>
                            <span class="text-nowrap">{{ $p->tanggal_mulai->translatedFormat('d M Y') }}</span> &ndash;
                            <span class="text-nowrap">{{ $p->tanggal_selesai?->translatedFormat('d M Y') ?? 'sekarang' }}</span>
                            <span class="hr-sel-badge"><x-status-badge :badge="$p->statusBadge()" dot>{{ $p->statusLabel() }}</x-status-badge></span>
                        </td>
                        <td class="d-md-none d-lg-table-cell">
                            {{ $p->no_sk ?? 'Tanpa SK' }}
                            <span class="hr-sel-sub">oleh {{ $p->dibuatOleh?->name ?? 'sistem' }}</span>
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                <x-icon-button icon="bi-person-vcard" label="Lihat pegawai" :href="$urlPegawai" />
                                @if ($isAdmin)
                                    <x-icon-button icon="bi-pencil" label="Ubah langsung (Admin)" :href="route('admin.penugasan.edit', $p)" />
                                @endif
                                <form action="{{ route('admin.penugasan.destroy', $p) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="kembali" value="daftar">
                                    <x-icon-button type="submit" icon="bi-trash" label="Hapus" tone="danger"
                                        data-confirm="true" data-icon="warning" data-confirm-text="Ya, Hapus" data-cancel-text="Batal"
                                        data-title="Hapus {{ strtolower($p->jenisLabel()) }}?"
                                        data-text="{{ $p->jenisLabel() }} {{ $p->dataPegawai?->nama_lengkap }} ke {{ $p->unit_kerja }} akan dihapus.{{ $p->is_utama ? ' Pegawai dikembalikan ke bagian sebelumnya.' : '' }}" />
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="5" :icon="$adaFilter ? 'bi-search' : 'bi-arrow-left-right'"
                        :title="$adaFilter ? 'Tidak ada mutasi/rangkap yang cocok' : 'Belum ada mutasi atau rangkap tugas'">
                        {{ $adaFilter ? 'Coba kata kunci lain atau reset filter.' : 'Catat perpindahan bagian atau rangkap tugas pegawai di sini.' }}
                        <x-slot:action>
                            @if ($adaFilter)
                                <x-button :href="route('admin.penugasan.index')" variant="secondary" icon="bi-x-circle">Reset filter</x-button>
                            @else
                                <x-button :href="route('admin.penugasan.tambah')" icon="bi-plus-lg">Tambah Mutasi / Rangkap</x-button>
                            @endif
                        </x-slot:action>
                    </x-table-empty>
                @endforelse
            </x-table>

            @if ($penugasans->hasPages())
                <x-slot:footer><x-pagination :paginator="$penugasans" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>
@endsection
