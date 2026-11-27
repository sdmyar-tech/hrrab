@extends('layouts.admin.app')

@section('title', 'Riwayat Kontrak')

@section('content')
@php
    $adaFilter = request()->filled('q') || request()->filled('unit_kerja') || request('status') === 'semua';
    $paramFilter = request()->only(['q', 'unit_kerja', 'status']);
    $ikonTab = ['semua' => 'bi-list-ul', 'belum' => 'bi-hourglass-split', 'sudah' => 'bi-check2-circle'];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Riwayat Kontrak" eyebrow="Kepegawaian"
            subtitle="Catat kontrak tiap pegawai: Masa Training, PKWT 1, PKWT 2, sampai PKWTT. Dipakai untuk menghitung “kontrak ke-berapa” di Evaluasi Kontrak.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.riwayat-kontrak.export', 'eksporParam' => $paramFilter])
            </x-slot:actions>
        </x-page-header>

        <x-card :padding="false" aria-label="Daftar riwayat kontrak pegawai">
            <x-filter-bar :action="route('admin.riwayat-kontrak.index')" search="Cari nama atau NIP, contoh: Siti"
                search-label="Cari pegawai menurut nama atau NIP" :count="$pegawais->total()" unit="pegawai" :filtered="$adaFilter"
                :reset="route('admin.riwayat-kontrak.index', ['tab' => $tab])">
                <select name="unit_kerja" class="form-select form-select-sm" aria-label="Saring bagian" onchange="this.form.submit()">
                    <option value="">Semua bagian</option>
                    @foreach ($unitOptions as $u)<option value="{{ $u }}" @selected(request('unit_kerja') === $u)>{{ $u }}</option>@endforeach
                </select>
                <select name="status" class="form-select form-select-sm" aria-label="Saring status pegawai" onchange="this.form.submit()">
                    <option value="Aktif" @selected(request('status', 'Aktif') !== 'semua')>Pegawai aktif</option>
                    <option value="semua" @selected(request('status') === 'semua')>Termasuk tidak aktif</option>
                </select>
                <x-slot:hidden><input type="hidden" name="tab" value="{{ $tab }}"></x-slot:hidden>
                <x-slot:chips>
                    @foreach (\App\Http\Controllers\Admin\RiwayatKontrakController::TAB as $kode => $label)
                        <x-chip :href="route('admin.riwayat-kontrak.index', ['tab' => $kode] + $paramFilter)" :active="$tab === $kode"
                            :icon="$ikonTab[$kode]" :count="$jumlahTab[$kode]">{{ $label }}</x-chip>
                    @endforeach
                </x-slot:chips>
            </x-filter-bar>

            <x-table caption="Daftar riwayat kontrak pegawai" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Pegawai</th>
                        <th>Status Kerja</th>
                        <th>Riwayat Kontrak</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($pegawais as $p)
                    @php $k = $p->kepegawaian; @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.riwayat-kontrak.show', $p) }}" class="hr-sel-judul">{{ $p->nama_lengkap }}</a>
                            <span class="hr-sel-sub">{{ collect([$p->nip, $k?->jabatan, $k?->unit_kerja])->filter()->implode(' · ') }}</span>
                        </td>
                        <td>
                            {{ $k?->status_kerja ?? '-' }}
                            @if ($k?->tanggal_masuk)<span class="hr-sel-sub">masuk {{ $k->tanggal_masuk->translatedFormat('d M Y') }}</span>@endif
                            @if ($k?->tanggal_habis_kontrak)<span class="hr-sel-sub">habis {{ $k->tanggal_habis_kontrak->translatedFormat('d M Y') }}</span>@endif
                        </td>
                        <td>
                            @if ($p->kontrak_count)
                                <x-badge tone="info">{{ $p->kontrak_count }} catatan</x-badge>
                            @else
                                <x-badge tone="warning" icon="bi-exclamation-circle">Belum ada</x-badge>
                            @endif
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                <x-icon-button :icon="$bolehUbah ? 'bi-pencil-square' : 'bi-eye'" :tone="$bolehUbah && ! $p->kontrak_count ? 'primary' : null"
                                    :href="route('admin.riwayat-kontrak.show', $p)" :label="$bolehUbah ? ($p->kontrak_count ? 'Lihat / ubah riwayat' : 'Isi riwayat kontrak') : 'Lihat riwayat'" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="4" :icon="$adaFilter ? 'bi-search' : 'bi-journal-bookmark'"
                        :title="$adaFilter ? 'Tidak ada pegawai yang cocok' : 'Belum ada pegawai'">
                        @if ($adaFilter)
                            <x-slot:action><x-button :href="route('admin.riwayat-kontrak.index', ['tab' => $tab])" variant="secondary" icon="bi-x-circle">Reset filter</x-button></x-slot:action>
                        @endif
                    </x-table-empty>
                @endforelse
            </x-table>

            @if ($pegawais->hasPages())
                <x-slot:footer><x-pagination :paginator="$pegawais" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>
@endsection
