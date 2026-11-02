@extends('layouts.admin.app')

@section('title', 'Evaluasi Kontrak')

@section('content')
@php
    $adaFilter = request()->filled('q') || \App\Support\FilterTanggal::aktif(request());
    $jumlahDaftar = method_exists($daftar, 'total') ? $daftar->total() : $daftar->count();
    $paramFilter = request()->only(['q', 'tanggal_dari', 'tanggal_sampai']);
    $ikonTab = ['tugas' => 'bi-hourglass-split', 'menunggu' => 'bi-arrow-repeat', 'selesai' => 'bi-check2-circle', 'semua' => 'bi-list-ul'];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Evaluasi Kontrak" eyebrow="Kepegawaian"
            subtitle="Sebulan sebelum kontrak habis, atasan mengisi rekomendasi dan penilaian; Kepala SDM memutuskan.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.evaluasi-kontrak.export', 'eksporParam' => ['tab' => $tab]])
            </x-slot:actions>
        </x-page-header>

        <x-card :padding="false" aria-label="Daftar evaluasi kontrak">
            <x-filter-bar :action="route('admin.evaluasi-kontrak.index')"
                search="Cari pegawai, contoh: Siti atau SMK" search-label="Cari evaluasi kontrak menurut nama pegawai atau bagian"
                :count="$jumlahDaftar" unit="evaluasi" :filtered="$adaFilter" :reset="route('admin.evaluasi-kontrak.index', ['tab' => $tab])">
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Habis kontrak'])
                <x-slot:hidden><input type="hidden" name="tab" value="{{ $tab }}"></x-slot:hidden>
                <x-slot:chips>
                    @foreach (\App\Http\Controllers\Admin\EvaluasiKontrakController::TAB as $kode => $label)
                        <x-chip :href="route('admin.evaluasi-kontrak.index', ['tab' => $kode] + $paramFilter)" :active="$tab === $kode"
                            :icon="$ikonTab[$kode] ?? null" :count="$jumlahTab[$kode]">{{ $label }}</x-chip>
                    @endforeach
                </x-slot:chips>
            </x-filter-bar>

            <x-table caption="Daftar evaluasi kontrak" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Pegawai</th>
                        <th>Habis Kontrak</th>
                        <th>Status</th>
                        <th>Penilaian</th>
                        <th>Keputusan</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($daftar as $e)
                    @php
                        $sisa = $e->sisaHari();
                        $tugas = \App\Support\EvaluasiKontrakAlur::tugasUntuk(auth()->user(), $e);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.evaluasi-kontrak.show', $e) }}" class="hr-sel-judul">{{ $e->dataPegawai?->nama_lengkap ?? '-' }}</a>
                            <span class="hr-sel-sub">{{ collect([$e->dataPegawai?->kepegawaian?->jabatan, $e->unit_kerja])->filter()->implode(' · ') }}</span>
                        </td>
                        <td>
                            <span class="text-nowrap">{{ $e->tanggal_habis_kontrak->translatedFormat('d M Y') }}</span>
                            @if ($e->status !== \App\Models\EvaluasiKontrak::STATUS_SELESAI)
                                <span class="hr-sel-badge">
                                    {{-- Mendesak: lewat = merah, ≤ 7 hari = kuning. --}}
                                    <x-badge :tone="$sisa < 0 ? 'danger' : ($sisa <= 7 ? 'warning' : 'neutral')">
                                        {{ $sisa < 0 ? 'lewat ' . abs($sisa) . ' hari' : ($sisa === 0 ? 'hari ini' : $sisa . ' hari lagi') }}
                                    </x-badge>
                                </span>
                            @endif
                        </td>
                        <td><x-status-badge :badge="$e->badgeClass()" dot>{{ $e->statusLabel() }}</x-status-badge></td>
                        <td>
                            @if ($e->total_nilai !== null)
                                <span class="d-block">{{ \App\Models\EvaluasiKontrak::predikat($e->total_nilai) }}</span>
                                <span class="hr-sel-sub">total {{ $e->total_nilai }} · {{ $e->pengisi?->name }}</span>
                            @else
                                <span class="text-muted">Belum diisi</span>
                            @endif
                        </td>
                        <td>
                            {{ $e->keputusan ? $e->labelKeputusan() : '-' }}
                            @if ($e->labelTtd())<span class="hr-sel-sub"><i class="bi bi-pen me-1" aria-hidden="true"></i>TTD {{ $e->labelTtd() }}</span>@endif
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                {{-- Ada tugas untuk Anda → ikon utama (marun); selain itu cukup lihat detail. --}}
                                @if ($tugas)
                                    <x-icon-button icon="bi-pencil-square" tone="primary" :href="route('admin.evaluasi-kontrak.show', $e)"
                                        :label="$e->status === \App\Models\EvaluasiKontrak::STATUS_MENUNGGU_SDM ? 'Putuskan' : 'Beri nilai'" />
                                @else
                                    <x-icon-button icon="bi-eye" label="Lihat detail" :href="route('admin.evaluasi-kontrak.show', $e)" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="6" :icon="$adaFilter ? 'bi-search' : ($tab === 'tugas' ? 'bi-check2-circle' : 'bi-clipboard-check')"
                        :title="$adaFilter ? 'Tidak ada evaluasi yang cocok' : ($tab === 'tugas' ? 'Tidak ada yang menunggu tindakan Anda' : 'Belum ada evaluasi kontrak di sini')">
                        Evaluasi dibuat otomatis {{ \App\Models\EvaluasiKontrak::HARI_SEBELUM }} hari sebelum kontrak pegawai habis.
                        @if ($adaFilter)
                            <x-slot:action><x-button :href="route('admin.evaluasi-kontrak.index', ['tab' => $tab])" variant="secondary" icon="bi-x-circle">Reset filter</x-button></x-slot:action>
                        @elseif ($tab !== 'semua')
                            <x-slot:action><x-button :href="route('admin.evaluasi-kontrak.index', ['tab' => 'semua'])" variant="secondary" icon="bi-list-ul">Lihat semua evaluasi</x-button></x-slot:action>
                        @endif
                    </x-table-empty>
                @endforelse
            </x-table>

            @if (method_exists($daftar, 'hasPages') && $daftar->hasPages())
                <x-slot:footer><x-pagination :paginator="$daftar" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>
@endsection
