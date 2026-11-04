@extends('layouts.admin.app')

@section('title', 'Pengajuan Lembur')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'status', 'tanggal_dari', 'tanggal_sampai']);
    $reset = route('admin.pengajuan-lembur.index', $bidangTabs ? ['bidang' => request('bidang', 'saya')] : []);
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Pengajuan Lembur" eyebrow="Data Pengajuan" subtitle="Tinjau, setujui, atau tolak pengajuan lembur pegawai.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pengajuan-lembur.export'])
            </x-slot:actions>
        </x-page-header>

        @include('admin.pengajuan._stat-status', ['hitung' => $hitungStatus, 'route' => 'admin.pengajuan-lembur.index', 'satuan' => 'pengajuan'])
        @include('admin.partials.gm-bidang-tabs', ['route' => 'admin.pengajuan-lembur.index', 'bagian' => 'peringatan'])

        <x-card :padding="false" aria-label="Daftar pengajuan lembur">
            <x-filter-bar :action="route('admin.pengajuan-lembur.index')" id="filterLemburForm"
                search="Cari pegawai, contoh: Siti" search-label="Cari pengajuan lembur menurut nama atau NIP pegawai"
                :count="$pengajuanLemburs->total()" unit="pengajuan" :filtered="$adaFilter" :reset="$reset">
                <select name="status" class="form-select form-select-sm" aria-label="Saring status" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="pending" @selected(request('status') == 'pending')>Menunggu</option>
                    <option value="approved" @selected(request('status') == 'approved')>Disetujui</option>
                    <option value="rejected" @selected(request('status') == 'rejected')>Ditolak</option>
                </select>
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Tanggal'])
                @include('partials.urut', ['urutForm' => 'filterLemburForm'])
                @if ($bidangTabs)
                    <x-slot:hidden><input type="hidden" name="bidang" value="{{ request('bidang', 'saya') }}"></x-slot:hidden>
                    <x-slot:chips>@include('admin.partials.gm-bidang-tabs', ['route' => 'admin.pengajuan-lembur.index', 'bagian' => 'chip'])</x-slot:chips>
                @endif
            </x-filter-bar>

            <x-table caption="Daftar pengajuan lembur" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Pegawai</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        {{-- Disembunyikan di tablet agar kolom aksi tidak terjepit; tetap ada di desktop & kartu HP. --}}
                        <th class="d-md-none d-lg-table-cell">Uraian Tugas</th>
                        <th>Status</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($pengajuanLemburs as $item)
                    @php
                        $nama = $item->dataPegawai?->nama_lengkap ?? '-';
                        $jam = substr((string) $item->jam_mulai, 0, 5) . '–' . substr((string) $item->jam_selesai, 0, 5);
                        $konteks = [
                            ['Pegawai', $nama . ($item->unit_kerja ? ' · ' . $item->unit_kerja : '')],
                            ['Tanggal', optional($item->tanggal)->translatedFormat('l, d M Y')],
                            ['Waktu', $jam . ' (' . $item->jumlah_jam . ' jam)'],
                            ['Uraian tugas', $item->uraian_tugas],
                            ['Tahap', $item->currentLevelLabel()],
                        ];
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.pengajuan-lembur.show', $item) }}" class="hr-sel-judul">{{ $nama }}</a>
                            <span class="hr-sel-sub"><span class="angka-utuh">{{ $item->dataPegawai?->nip ?? '-' }}</span>@if ($item->unit_kerja) &middot; {{ $item->unit_kerja }}@endif</span>
                        </td>
                        <td>
                            <span class="text-nowrap">{{ optional($item->tanggal)->translatedFormat('d M Y') }}</span>
                            <span class="hr-sel-sub">{{ $item->hari }}</span>
                        </td>
                        <td>
                            <span class="text-nowrap">{{ $jam }}</span>
                            <span class="hr-sel-sub">{{ $item->jumlah_jam }} jam</span>
                        </td>
                        <td class="d-md-none d-lg-table-cell">{{ \Illuminate\Support\Str::limit($item->uraian_tugas, 60) }}</td>
                        <td>
                            <x-status-badge :badge="$item->badgeClass()" dot>{{ $item->statusLabel() }}</x-status-badge>
                            @if ($item->isPending())<span class="hr-sel-sub mt-1">Tahap: {{ $item->currentLevelLabel() }}</span>@endif
                            @include('admin.partials.gm-pengganti-badge', ['pengajuan' => $item])
                            @if ($item->status === 'rejected' && $item->catatan_admin)<span class="hr-sel-sub mt-1">{{ $item->catatan_admin }}</span>@endif
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            @include('admin.pengajuan._aksi-ikon', [
                                'detail' => route('admin.pengajuan-lembur.show', $item),
                                'setujui' => route('admin.pengajuan-lembur.approve', $item->id),
                                'tolak' => route('admin.pengajuan-lembur.reject', $item->id),
                                'judul' => 'pengajuan lembur',
                                'konteks' => $konteks,
                            ])
                        </td>
                    </tr>
                    {{-- Rincian untuk penyetuju: tampil sebelum tombol Setujui/Tolak diklik. --}}
                    @if ($item->isPending() && \App\Support\PengajuanWorkflow::canAct(auth()->user(), $item))
                        @include('admin.pengajuan._ringkasan-baris', ['r' => \App\Support\RingkasanPengajuan::lembur($item), 'kolom' => 6])
                    @endif
                @empty
                    <x-table-empty :colspan="6" :icon="$adaFilter ? 'bi-search' : 'bi-clock-history'"
                        :title="$adaFilter ? 'Tidak ada pengajuan lembur yang cocok' : 'Belum ada pengajuan lembur'">
                        {{ $adaFilter ? 'Coba kata kunci lain atau reset filter.' : 'Pengajuan lembur pegawai yang perlu ditinjau akan tampil di sini.' }}
                        @if ($adaFilter)
                            <x-slot:action><x-button :href="$reset" variant="secondary" icon="bi-x-circle">Reset filter</x-button></x-slot:action>
                        @endif
                    </x-table-empty>
                @endforelse
            </x-table>

            @if ($pengajuanLemburs->hasPages())
                <x-slot:footer><x-pagination :paginator="$pengajuanLemburs" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>

@include('admin.pengajuan._persetujuan')
@endsection
