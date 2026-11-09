@extends('layouts.admin.app')

@section('title', 'Izin')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'status', 'jenis', 'tanggal_dari', 'tanggal_sampai']);
    $reset = route('admin.izin-keluar.index', $bidangTabs ? ['bidang' => request('bidang', 'saya')] : []);
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Izin" eyebrow="Data Pengajuan"
            subtitle="Setujui atau tolak izin pegawai. Izin beberapa jam mendapat tiket untuk security; izin tidak masuk tercatat di absensi.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.izin-keluar.export'])
            </x-slot:actions>
        </x-page-header>

        @include('admin.pengajuan._stat-status', ['hitung' => $hitungStatus, 'route' => 'admin.izin-keluar.index', 'satuan' => 'izin'])
        @include('admin.partials.gm-bidang-tabs', ['route' => 'admin.izin-keluar.index', 'bagian' => 'peringatan'])

        <x-card :padding="false" aria-label="Daftar izin">
            <x-filter-bar :action="route('admin.izin-keluar.index')" id="filterIzinKeluarForm"
                search="Cari pegawai atau tiket, contoh: Siti" search-label="Cari izin menurut nama, NIP, atau kode tiket"
                :count="$izinKeluars->total()" unit="izin" :filtered="$adaFilter" :reset="$reset">
                <select name="status" class="form-select form-select-sm" aria-label="Saring status" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="pending" @selected(request('status') == 'pending')>Menunggu</option>
                    <option value="approved" @selected(request('status') == 'approved')>Disetujui</option>
                    <option value="rejected" @selected(request('status') == 'rejected')>Ditolak</option>
                </select>
                <select name="jenis" class="form-select form-select-sm" aria-label="Saring jenis izin" onchange="this.form.submit()">
                    <option value="">Semua jenis</option>
                    @foreach (\App\Models\PengajuanIzinKeluar::JENIS as $kode => $label)
                        <option value="{{ $kode }}" @selected(request('jenis') === $kode)>{{ $label }}</option>
                    @endforeach
                </select>
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Tanggal'])
                @include('partials.urut', ['urutForm' => 'filterIzinKeluarForm'])
                @if ($bidangTabs)
                    <x-slot:hidden><input type="hidden" name="bidang" value="{{ request('bidang', 'saya') }}"></x-slot:hidden>
                    <x-slot:chips>@include('admin.partials.gm-bidang-tabs', ['route' => 'admin.izin-keluar.index', 'bagian' => 'chip'])</x-slot:chips>
                @endif
            </x-filter-bar>

            <x-table caption="Daftar izin" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Pegawai</th>
                        <th>Waktu</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($izinKeluars as $item)
                    @php
                        $nama = $item->dataPegawai?->nama_lengkap ?? '-';
                        $konteks = [
                            ['Pegawai', $nama . ($item->unit_kerja ? ' · ' . $item->unit_kerja : '')],
                            ['Waktu', $item->tanggalLabel() . ' · ' . $item->jamLabel()],
                            ['Keperluan', $item->keperluanLabel()],
                            ['Alasan', $item->alasan],
                            ['Tahap', $item->currentLevelLabel()],
                        ];
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.izin-keluar.show', $item) }}" class="hr-sel-judul">{{ $nama }}</a>
                            <span class="hr-sel-sub"><span class="angka-utuh">{{ $item->dataPegawai?->nip ?? '-' }}</span>@if ($item->unit_kerja) &middot; {{ $item->unit_kerja }}@endif</span>
                        </td>
                        <td>
                            <span class="text-nowrap">{{ $item->tanggalLabel('d M Y') }}</span>
                            <span class="hr-sel-sub">@unless ($item->isHarian()){{ $item->tanggal->translatedFormat('l') }} &middot; @endunless{{ $item->jamLabel() }}</span>
                        </td>
                        <td>
                            <span class="d-block">{{ $item->keperluanLabel() }}</span>
                            <span class="hr-sel-sub">{{ \Illuminate\Support\Str::limit($item->alasan, 60) }}</span>
                        </td>
                        <td>
                            <x-status-badge :badge="$item->badgeClass()" dot>{{ $item->statusLabel() }}</x-status-badge>
                            @if ($item->isPending())
                                <span class="hr-sel-sub mt-1">Tahap: {{ $item->currentLevelLabel() }}</span>
                            @elseif ($item->kode_tiket)
                                <span class="hr-sel-sub mt-1 font-monospace">{{ $item->kode_tiket }}</span>
                            @endif
                            @include('admin.partials.gm-pengganti-badge', ['pengajuan' => $item])
                            @if ($item->status === 'rejected' && $item->catatan_admin)<span class="hr-sel-sub mt-1">{{ $item->catatan_admin }}</span>@endif
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            @include('admin.pengajuan._aksi-ikon', [
                                'detail' => route('admin.izin-keluar.show', $item),
                                'setujui' => route('admin.izin-keluar.approve', $item),
                                'tolak' => route('admin.izin-keluar.reject', $item),
                                'judul' => 'izin',
                                'konteks' => $konteks,
                                'tambahan' => $item->punyaTiket() ? [[route('pegawai.izin-keluar.tiket', $item), 'Lihat tiket', 'bi-ticket-perforated']] : [],
                                'slotTambahan' => 1,
                            ])
                        </td>
                    </tr>
                    {{-- Rincian untuk penyetuju: tampil sebelum tombol Setujui/Tolak diklik. --}}
                    @if ($item->isPending() && \App\Support\PengajuanWorkflow::canAct(auth()->user(), $item))
                        @include('admin.pengajuan._ringkasan-baris', ['r' => \App\Support\RingkasanPengajuan::izinKeluar($item), 'kolom' => 5])
                    @endif
                @empty
                    <x-table-empty :colspan="5" :icon="$adaFilter ? 'bi-search' : 'bi-door-open'"
                        :title="$adaFilter ? 'Tidak ada izin yang cocok' : 'Belum ada izin'">
                        {{ $adaFilter ? 'Coba kata kunci lain atau reset filter.' : 'Izin pegawai yang perlu ditinjau akan tampil di sini.' }}
                        @if ($adaFilter)
                            <x-slot:action><x-button :href="$reset" variant="secondary" icon="bi-x-circle">Reset filter</x-button></x-slot:action>
                        @endif
                    </x-table-empty>
                @endforelse
            </x-table>

            @if ($izinKeluars->hasPages())
                <x-slot:footer><x-pagination :paginator="$izinKeluars" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>

@include('admin.pengajuan._persetujuan')
@endsection
