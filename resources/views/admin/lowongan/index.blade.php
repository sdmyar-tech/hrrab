@extends('layouts.admin.app')

@section('title', 'Lowongan Rekrutmen')

@section('content')
@php
    $pengguna = auth()->user();
    $isAdmin = $pengguna->can('sistem.editor_data'); // tautan Editor Data
    $adaFilter = request()->hasAny(['q', 'status', 'tanggal_dari', 'tanggal_sampai']);
    $statusAktif = (string) request('status');
    $param = request()->except(['status', 'page']);
    // Label, warna (hijau dibuka · merah ditutup · abu draf), dan ikon per status lowongan.
    $statusInfo = [
        \App\Models\Lowongan::STATUS_DRAFT => ['Draf', 'neutral'],
        \App\Models\Lowongan::STATUS_DIPUBLIKASIKAN => ['Dipublikasikan', 'success'],
        \App\Models\Lowongan::STATUS_DITUTUP => ['Ditutup', 'danger'],
    ];
    // Kartu angka: klik = saring status (saringan lain tetap). Kartu pelamar membuka Lamaran Masuk.
    $kartu = [
        ['', 'Total lowongan', $hitungStatus['semua'], 'bi-briefcase', 'primary', $hitungStatus['draft'] . ' masih draf'],
        ['dipublikasikan', 'Aktif', $hitungStatus['dipublikasikan'], 'bi-megaphone', 'success', 'Dipublikasikan, menerima lamaran'],
        ['ditutup', 'Ditutup', $hitungStatus['ditutup'], 'bi-lock', 'danger', 'Tidak menerima lamaran baru'],
    ];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Lowongan Rekrutmen" eyebrow="Rekrutmen" subtitle="Kelola & publikasikan lowongan yang terbit dari pengajuan formasi.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.lowongan.export'])
                <x-button :href="route('admin.lowongan.create')" icon="bi-plus-lg">Buat Lowongan</x-button>
            </x-slot:actions>
        </x-page-header>

        <section class="row g-3 mb-4" aria-label="Ringkasan lowongan">
            @foreach ($kartu as [$status, $label, $angka, $ikon, $tone, $meta])
                <div class="col-6 col-xl-3">
                    <x-stat-card :label="$label" :value="$angka" :icon="$ikon" :tone="$tone" :meta="$meta"
                        :href="route('admin.lowongan.index', $status === '' ? $param : $param + ['status' => $status])"
                        @class(['hr-stat-aktif' => $statusAktif === $status]) />
                </div>
            @endforeach
            <div class="col-6 col-xl-3">
                <x-stat-card label="Total pelamar" :value="$hitungStatus['pelamar']" icon="bi-people" tone="info"
                    meta="Di lowongan yang tampil · buka Lamaran Masuk" :href="route('admin.lamaran.index')" />
            </div>
        </section>

        <x-card :padding="false" aria-label="Daftar lowongan">
            <x-filter-bar :action="route('admin.lowongan.index')" id="filterLowonganForm"
                search="Cari lowongan, contoh: Guru Matematika" search-label="Cari lowongan menurut judul, jabatan, atau bagian"
                :count="$lowongans->total()" unit="lowongan" :filtered="$adaFilter" :reset="route('admin.lowongan.index')">
                <select name="status" class="form-select form-select-sm" aria-label="Saring status lowongan" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    @foreach ($statusInfo as $nilai => [$labelOpsi])
                        <option value="{{ $nilai }}" @selected($statusAktif === $nilai)>{{ $labelOpsi }}</option>
                    @endforeach
                </select>
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Tutup'])
                @include('partials.urut', ['urutForm' => 'filterLowonganForm'])
            </x-filter-bar>

            <x-table caption="Daftar lowongan" id="tabelLowongan" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Lowongan</th>
                        <th>Pelamar</th>
                        {{-- Disembunyikan di tablet (768–1199) agar kolom aksi tidak terpotong; tanggal tetap tampil di bawah status. --}}
                        <th class="d-md-none d-xl-table-cell">Tanggal Tutup</th>
                        <th>Status</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($lowongans as $item)
                    @php
                        [$labelStatus, $toneStatus] = $statusInfo[$item->status] ?? $statusInfo[\App\Models\Lowongan::STATUS_DRAFT];
                        $tutup = $item->tanggal_tutup;
                        $sisaHari = $tutup ? (int) now()->startOfDay()->diffInDays($tutup, false) : null;
                        $ditutup = $item->status === \App\Models\Lowongan::STATUS_DITUTUP;
                        $lengkap = $item->deskripsi && $item->kualifikasi;
                        $nama = $item->judul . ' (' . $item->unit_kerja . ')';
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.lowongan.edit', $item) }}" class="hr-sel-judul">{{ $item->judul }}</a>
                            <span class="hr-sel-sub">{{ $item->jabatan }} · {{ $item->unit_kerja }}</span>
                            @if ($item->tipe_kerja || $item->lokasi_kerja || $item->pengajuanFormasi)
                                <span class="hr-sel-badge">
                                    @if ($item->tipe_kerja)<x-badge tone="neutral" icon="bi-clock">{{ $item->tipe_kerja }}</x-badge>@endif
                                    @if ($item->lokasi_kerja)<x-badge tone="neutral" icon="bi-geo-alt">{{ $item->lokasi_kerja }}</x-badge>@endif
                                    @if ($item->pengajuanFormasi)<x-badge tone="info" icon="bi-diagram-3" title="Terbit dari pengajuan formasi">Formasi #{{ $item->pengajuanFormasi->id }}</x-badge>@endif
                                </span>
                            @endif
                        </td>
                        <td>
                            {{-- Jumlah pelamar langsung membuka Lamaran Masuk yang tersaring ke lowongan ini. --}}
                            <a href="{{ route('admin.lamaran.index', ['lowongan_id' => $item->id]) }}" class="rk-tautan-angka text-nowrap"
                                title="Lihat pelamar lowongan ini">{{ $item->lamarans_count }} pelamar</a>
                            <span class="hr-sel-sub text-nowrap">Terisi {{ $item->jumlah_terisi }}/{{ $item->jumlah_dibutuhkan }} orang</span>
                        </td>
                        <td class="d-md-none d-xl-table-cell">
                            @if ($tutup)
                                <span class="d-block text-nowrap">{{ $tutup->translatedFormat('d M Y') }}</span>
                                @if ($item->status === \App\Models\Lowongan::STATUS_DIPUBLIKASIKAN && $sisaHari !== null && $sisaHari >= 0 && $sisaHari <= 7)
                                    <span class="hr-sel-badge"><x-badge tone="warning" icon="bi-hourglass-split">{{ $sisaHari === 0 ? 'Tutup hari ini' : $sisaHari . ' hari lagi' }}</x-badge></span>
                                @endif
                            @else
                                <span class="text-muted">Tanpa batas</span>
                            @endif
                        </td>
                        <td>
                            <x-badge :tone="$toneStatus" dot>{{ $labelStatus }}</x-badge>
                            @if ($ditutup && $item->isKuotaPenuh())
                                <span class="hr-sel-sub mt-1"><i class="bi bi-check2-all" aria-hidden="true"></i> Kuota terpenuhi</span>
                            @elseif ($item->status === \App\Models\Lowongan::STATUS_DRAFT && ! $lengkap)
                                <span class="hr-sel-sub mt-1">Deskripsi/kualifikasi belum lengkap</span>
                            @endif
                            <span class="hr-sel-sub d-none d-md-block d-xl-none">{{ $tutup ? 'Tutup ' . $tutup->translatedFormat('d M Y') : 'Tanpa batas tutup' }}</span>
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            {{-- Lihat · Ubah · ubah status (Publikasikan / Tutup / Buka kembali) · Admin: aksi lain. Pengganti menjaga ikon sejajar. --}}
                            <div class="hr-aksi-baris">
                                @if ($item->status === \App\Models\Lowongan::STATUS_DRAFT)
                                    <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                @else
                                    <x-icon-button icon="bi-eye" label="Lihat di halaman karier (tab baru)" :href="route('lowongan.show', $item)" target="_blank" rel="noopener" />
                                @endif
                                <x-icon-button icon="bi-pencil" label="Ubah lowongan" :href="route('admin.lowongan.edit', $item)" />

                                @if ($item->status === \App\Models\Lowongan::STATUS_DRAFT && $lengkap)
                                    <form action="{{ route('admin.lowongan.publish', $item) }}" method="POST">
                                        @csrf
                                        <x-icon-button type="submit" icon="bi-megaphone" tone="success" label="Publikasikan"
                                            data-confirm="true" data-title="Publikasikan lowongan?" data-confirm-text="Ya, Publikasikan" data-cancel-text="Batal"
                                            :data-text="$nama . ' akan tampil di halaman karier dan mulai menerima lamaran.'" />
                                    </form>
                                @elseif ($item->status === \App\Models\Lowongan::STATUS_DRAFT)
                                    {{-- Backend menolak publikasi tanpa deskripsi & kualifikasi → arahkan ke form agar dilengkapi dulu. --}}
                                    <x-icon-button icon="bi-megaphone" tone="warning" label="Lengkapi deskripsi & kualifikasi untuk publikasi"
                                        :href="route('admin.lowongan.edit', $item) . '#bagian-deskripsi'" />
                                @elseif ($item->status === \App\Models\Lowongan::STATUS_DIPUBLIKASIKAN)
                                    <form action="{{ route('admin.lowongan.close', $item) }}" method="POST">
                                        @csrf
                                        <x-icon-button type="submit" icon="bi-lock" tone="danger" label="Tutup lowongan"
                                            data-confirm="true" data-icon="warning" data-title="Tutup lowongan?" data-confirm-text="Ya, Tutup" data-cancel-text="Batal"
                                            :data-text="$nama . ' tidak lagi menerima lamaran baru. ' . $item->lamarans_count . ' pelamar yang sudah masuk tetap bisa diproses.'" />
                                    </form>
                                @else
                                    @include('admin.lowongan._reopen-button', ['lowongan' => $item, 'ikon' => true])
                                @endif

                                @if ($isAdmin)
                                    <x-dropdown class="rk-aksi-lain">
                                        <x-slot:trigger>
                                            <x-icon-button icon="bi-three-dots-vertical" label="Aksi lain (Admin)" data-bs-toggle="dropdown" aria-expanded="false" />
                                        </x-slot:trigger>
                                        <a class="dropdown-item" href="{{ route('admin.data.edit', ['lowongan', $item->id, 'kembali' => request()->fullUrl()]) }}">
                                            <i class="bi bi-shield-lock me-2" aria-hidden="true"></i>Ubah semua kolom (Admin)
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('admin.data.destroy', ['lowongan', $item->id]) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="kembali" value="{{ request()->fullUrl() }}">
                                            <button type="submit" class="dropdown-item text-danger"
                                                data-confirm="true" data-icon="warning" data-title="Hapus lowongan permanen?"
                                                data-text="Lowongan {{ $nama }} akan dihapus permanen. Tindakan ini tidak bisa dibatalkan."
                                                data-confirm-text="Ya, Hapus" data-cancel-text="Batal">
                                                <i class="bi bi-trash me-2" aria-hidden="true"></i>Hapus permanen (Admin)
                                            </button>
                                        </form>
                                    </x-dropdown>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="5" :icon="$adaFilter ? 'bi-search' : 'bi-briefcase'"
                        :title="$adaFilter ? 'Tidak ada lowongan yang cocok' : 'Belum ada lowongan'">
                        {{ $adaFilter ? 'Coba kata kunci lain atau reset filter untuk melihat semua lowongan.' : 'Lowongan terbit otomatis dari pengajuan formasi yang disetujui, atau buat sendiri.' }}
                        <x-slot:action>
                            @if ($adaFilter)
                                <x-button :href="route('admin.lowongan.index')" variant="secondary" icon="bi-x-circle">Reset filter</x-button>
                            @else
                                <x-button :href="route('admin.lowongan.create')" icon="bi-plus-lg">Buat Lowongan</x-button>
                            @endif
                        </x-slot:action>
                    </x-table-empty>
                @endforelse
            </x-table>

            @if ($lowongans->hasPages())
                <x-slot:footer><x-pagination :paginator="$lowongans" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>
@endsection
