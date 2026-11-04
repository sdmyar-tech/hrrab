@extends('layouts.admin.app')

@section('title', 'Pengajuan Cuti')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'jenis_cuti', 'status', 'tanggal_dari', 'tanggal_sampai']);
    $reset = route('admin.pengajuan-cuti.index', $bidangTabs ? ['bidang' => request('bidang', 'saya')] : []);
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Pengajuan Cuti" eyebrow="Data Pengajuan" subtitle="Tinjau, setujui, atau tolak pengajuan cuti pegawai.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pengajuan-cuti.export'])
            </x-slot:actions>
        </x-page-header>

        @include('admin.pengajuan._stat-status', ['hitung' => $hitungStatus, 'route' => 'admin.pengajuan-cuti.index', 'satuan' => 'pengajuan'])
        @include('admin.partials.gm-bidang-tabs', ['route' => 'admin.pengajuan-cuti.index', 'bagian' => 'peringatan'])

        <x-card :padding="false" aria-label="Daftar pengajuan cuti">
            <x-filter-bar :action="route('admin.pengajuan-cuti.index')" id="filterCutiForm"
                search="Cari pegawai, contoh: Siti" search-label="Cari pengajuan cuti menurut nama atau NIP pegawai"
                :count="$pengajuanCutis->total()" unit="pengajuan" :filtered="$adaFilter" :reset="$reset">
                <select name="status" class="form-select form-select-sm" aria-label="Saring status" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="pending" @selected(request('status') == 'pending')>Menunggu</option>
                    <option value="approved" @selected(request('status') == 'approved')>Disetujui</option>
                    <option value="rejected" @selected(request('status') == 'rejected')>Ditolak</option>
                </select>
                <select name="jenis_cuti" class="form-select form-select-sm" aria-label="Saring jenis cuti" onchange="this.form.submit()">
                    <option value="">Semua jenis</option>
                    <option value="tahunan" @selected(request('jenis_cuti') == 'tahunan')>Cuti Tahunan</option>
                    <option value="besar" @selected(request('jenis_cuti') == 'besar')>Cuti Besar</option>
                </select>
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Tgl mulai'])
                @include('partials.urut', ['urutForm' => 'filterCutiForm'])
                @if ($bidangTabs)
                    <x-slot:hidden><input type="hidden" name="bidang" value="{{ request('bidang', 'saya') }}"></x-slot:hidden>
                    <x-slot:chips>@include('admin.partials.gm-bidang-tabs', ['route' => 'admin.pengajuan-cuti.index', 'bagian' => 'chip'])</x-slot:chips>
                @endif
            </x-filter-bar>

            <x-table caption="Daftar pengajuan cuti" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Pegawai</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal</th>
                        {{-- Disembunyikan di tablet agar kolom aksi tidak terjepit; tetap ada di desktop & kartu HP. --}}
                        <th class="d-md-none d-lg-table-cell">Pengganti Tugas</th>
                        <th>Status</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($pengajuanCutis as $item)
                    @php
                        $nama = $item->dataPegawai?->nama_lengkap ?? '-';
                        $tanggal = $item->tanggal_mulai->translatedFormat('d M Y') . ($item->tanggal_mulai->isSameDay($item->tanggal_selesai) ? '' : ' – ' . $item->tanggal_selesai->translatedFormat('d M Y'));
                        $konteks = [
                            ['Pegawai', $nama . ($item->unit_kerja ? ' · ' . $item->unit_kerja : '')],
                            ['Jenis', $item->jenisLabel() . ($item->kategoriBesarLabel() ? ' · ' . $item->kategoriBesarLabel() : '')],
                            ['Tanggal', $tanggal . ' (' . $item->lama_cuti . ' hari)'],
                            ['Keterangan', $item->keterangan],
                            ['Tahap', $item->currentLevelLabel()],
                        ];
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.pengajuan-cuti.show', $item) }}" class="hr-sel-judul">{{ $nama }}</a>
                            <span class="hr-sel-sub">{{ collect([$item->dataPegawai?->kepegawaian?->jabatan, $item->unit_kerja])->filter()->implode(' · ') ?: '-' }}</span>
                        </td>
                        <td>
                            <span class="d-block">{{ $item->jenisLabel() }}</span>
                            @if ($item->kategoriBesarLabel())<span class="hr-sel-sub">{{ $item->kategoriBesarLabel() }}</span>@endif
                        </td>
                        <td>
                            <span class="text-nowrap">{{ $item->tanggal_mulai->translatedFormat('d M Y') }}</span>
                            @unless ($item->tanggal_mulai->isSameDay($item->tanggal_selesai))
                                &ndash; <span class="text-nowrap">{{ $item->tanggal_selesai->translatedFormat('d M Y') }}</span>
                            @endunless
                            <span class="hr-sel-sub">{{ $item->lama_cuti }} hari</span>
                        </td>
                        <td class="d-md-none d-lg-table-cell">{{ $item->pengganti_nama ?? '-' }}</td>
                        <td>
                            <x-status-badge :badge="$item->badgeClass()" dot>{{ $item->statusLabel() }}</x-status-badge>
                            @if ($item->isPending())<span class="hr-sel-sub mt-1">Tahap: {{ $item->currentLevelLabel() }}</span>@endif
                            @include('admin.partials.gm-pengganti-badge', ['pengajuan' => $item])
                            @if ($item->status === 'rejected' && $item->catatan_admin)<span class="hr-sel-sub mt-1">{{ $item->catatan_admin }}</span>@endif
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            @include('admin.pengajuan._aksi-ikon', [
                                'detail' => route('admin.pengajuan-cuti.show', $item),
                                'setujui' => route('admin.pengajuan-cuti.approve', $item->id),
                                'tolak' => route('admin.pengajuan-cuti.reject', $item->id),
                                'judul' => 'pengajuan cuti',
                                'konteks' => $konteks,
                                ...($item->perluPengganti() ? ['pengganti' => $item->substituteOptions()] : []),
                            ])
                        </td>
                    </tr>
                    {{-- Rincian untuk penyetuju: tampil sebelum tombol Setujui/Tolak diklik. --}}
                    @if ($item->isPending() && \App\Support\PengajuanWorkflow::canAct(auth()->user(), $item))
                        @include('admin.pengajuan._ringkasan-baris', ['r' => \App\Support\RingkasanPengajuan::cuti($item), 'kolom' => 6])
                    @endif
                @empty
                    <x-table-empty :colspan="6" :icon="$adaFilter ? 'bi-search' : 'bi-calendar2-check'"
                        :title="$adaFilter ? 'Tidak ada pengajuan cuti yang cocok' : 'Belum ada pengajuan cuti'">
                        {{ $adaFilter ? 'Coba kata kunci lain atau reset filter.' : 'Pengajuan cuti pegawai yang perlu ditinjau akan tampil di sini.' }}
                        @if ($adaFilter)
                            <x-slot:action><x-button :href="$reset" variant="secondary" icon="bi-x-circle">Reset filter</x-button></x-slot:action>
                        @endif
                    </x-table-empty>
                @endforelse
            </x-table>

            @if ($pengajuanCutis->hasPages())
                <x-slot:footer><x-pagination :paginator="$pengajuanCutis" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>

@include('admin.pengajuan._persetujuan')
@endsection
