@extends('layouts.admin.app')

@section('title', 'Pengajuan Formasi')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'status', 'tanggal_dari', 'tanggal_sampai']);
    $isAdmin = auth()->user()->isAdmin();
    $reset = route('admin.pengajuan-formasi.index');
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Pengajuan Formasi" eyebrow="Rekrutmen" subtitle="Permintaan kekurangan pegawai dari Kepala/Koordinator Bagian.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pengajuan-formasi.export'])
                @if ($canSubmit)
                    <x-button :href="route('admin.pengajuan-formasi.create')" icon="bi-plus-lg">Buat Pengajuan</x-button>
                @endif
            </x-slot:actions>
        </x-page-header>

        <x-card :padding="false" aria-label="Daftar pengajuan formasi">
            <x-filter-bar :action="route('admin.pengajuan-formasi.index')" id="filterFormasiForm"
                search="Cari formasi, contoh: Guru SMK" search-label="Cari pengajuan formasi menurut jabatan atau bagian"
                :count="$pengajuanFormasis->total()" unit="pengajuan" :filtered="$adaFilter" :reset="$reset">
                <select name="status" class="form-select form-select-sm" aria-label="Saring status" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="pending" @selected(request('status') == 'pending')>Menunggu</option>
                    <option value="approved" @selected(request('status') == 'approved')>Disetujui</option>
                    <option value="rejected" @selected(request('status') == 'rejected')>Ditolak</option>
                    <option value="dibatalkan" @selected(request('status') == 'dibatalkan')>Dibatalkan</option>
                </select>
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Diajukan'])
                @include('partials.urut', ['urutForm' => 'filterFormasiForm'])
            </x-filter-bar>

            <x-table caption="Daftar pengajuan formasi" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Posisi</th>
                        <th>Diajukan Oleh</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        {{-- Disembunyikan di tablet agar kolom aksi tidak terjepit; tetap ada di desktop & kartu HP. --}}
                        <th class="d-md-none d-lg-table-cell">Lowongan</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($pengajuanFormasis as $item)
                    @php
                        $bisaReview = ($item->isPending() || $isAdmin) && \App\Support\RekrutmenAccess::canReviewFormasi(auth()->user(), $item);
                        $tahapBerjalan = $item->isPending() ? \App\Support\RekrutmenAccess::tahapPersetujuan($item) : null;
                        $konteks = [
                            ['Posisi', $item->jabatan . ' · ' . $item->unit_kerja],
                            ['Kebutuhan', $item->jenisLabel()],
                            ['Jumlah', $item->jumlahLabel()],
                            ['Dibutuhkan', optional($item->target_waktu)->translatedFormat('d M Y')],
                            ['Diajukan oleh', trim(($item->diajukanOleh?->name ?? '') . ' · ' . optional($item->created_at)->translatedFormat('d M Y'), ' ·')],
                            ['Tahap', $tahapBerjalan === 'gm' ? 'GM bagian' : ($tahapBerjalan === 'owner' ? 'Owner' : null)],
                        ];
                        $formAksi = [];
                        if ($bisaReview && $isAdmin) {
                            $formAksi[] = [route('admin.pengajuan-formasi.destroy', $item), 'Hapus permanen (Admin)', 'bi-trash', 'danger', 'DELETE',
                                'Hapus pengajuan formasi?', 'Pengajuan formasi ' . $item->jabatan . ' akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.', 'Hapus'];
                        } elseif (! $bisaReview && $item->diajukan_oleh === auth()->id() && $item->isPending()) {
                            $formAksi[] = [route('admin.pengajuan-formasi.batalkan', $item), 'Batalkan pengajuan', 'bi-x-circle', 'danger', 'POST',
                                'Batalkan pengajuan formasi?', 'Pengajuan formasi ' . $item->jabatan . ' akan dibatalkan dan tidak diproses lagi.', 'Batalkan'];
                        }
                    @endphp
                    <tr>
                        <td>
                            <span class="hr-sel-judul">{{ $item->jabatan }}</span>
                            <span class="hr-sel-sub">{{ $item->unit_kerja }}@if ($item->jenis_kebutuhan) &middot; {{ $item->jenis_kebutuhan === 'penambahan' ? 'Penambahan' : 'Pengganti' }}@endif</span>
                            {{-- Rincian lengkap untuk dialog "Lihat detail". --}}
                            <template id="detail-formasi-{{ $item->id }}">
                                <dl class="hr-konteks text-start">
                                    @foreach ([
                                        'Kebutuhan karena' => $item->jenisLabel(),
                                        'Jumlah' => $item->jumlahLabel(),
                                        'Tanggal dibutuhkan' => optional($item->target_waktu)->translatedFormat('d M Y'),
                                        'Minimal pendidikan' => $item->min_pendidikan,
                                        'Minimal usia' => $item->min_usia ? $item->min_usia . ' tahun' : null,
                                        'Kemampuan / keahlian' => $item->kualifikasi,
                                        'Atasan langsung' => $item->atasan_langsung,
                                        'Tugas & tanggung jawab' => $item->tugas,
                                        'Keterangan' => $item->diganti || $item->slot_tambahan ? null : $item->alasan,
                                    ] as $label => $nilai)
                                        @if (filled($nilai))<dt>{{ $label }}</dt><dd>{{ $nilai }}</dd>@endif
                                    @endforeach
                                </dl>
                                @if ($item->diganti || $item->slot_tambahan)
                                    <div class="text-start mt-3">
                                        <div class="small fw-semibold mb-1">{{ $item->diganti ? 'Pegawai yang digantikan' : 'Posisi baru' }}</div>
                                        <div class="bagan-deret">
                                            @foreach ($item->diganti ?? [] as $o)
                                                <span class="bagan-node {{ $o['aktif'] ? 'aktif' : 'nonaktif' }} diganti"><i class="bi {{ $o['aktif'] ? 'bi-person-fill' : 'bi-person-dash' }}"></i><span class="nm">{{ $o['nama'] }} · {{ $o['jabatan'] }}</span><span class="bagan-lencana"><i class="bi bi-arrow-repeat"></i></span></span>
                                            @endforeach
                                            @foreach ($item->slot_tambahan ?? [] as $i => $ket)
                                                <span class="bagan-node kosong"><i class="bi bi-person-plus"></i><span class="nm">{{ $ket ?: 'Posisi baru #' . ($i + 1) }}</span><span class="bagan-lencana"><i class="bi bi-plus-lg"></i></span></span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </template>
                        </td>
                        <td>
                            <span class="d-block">{{ $item->diajukanOleh?->name ?? '-' }}</span>
                            <span class="hr-sel-sub">{{ optional($item->created_at)->translatedFormat('d M Y') }}</span>
                        </td>
                        <td class="text-nowrap">{{ $item->jumlahLabel() }}</td>
                        <td>
                            <x-status-badge :badge="$item->badgeClass()" dot>{{ $item->statusLabel() }}</x-status-badge>
                            @if ($item->butuhDuaPersetujuan())
                                {{-- Penambahan: bertahap GM bagian → Owner. Selesai = hijau, berjalan = kuning, berikutnya = abu. --}}
                                <span class="hr-sel-badge">
                                    @foreach (array_filter([
                                        'gm' => \App\Support\RekrutmenAccess::perluGm($item) ? ['GM bagian', $item->gm_disetujui_oleh, $item->gmPenyetuju] : null,
                                        'owner' => ['Owner', $item->owner_disetujui_oleh, $item->ownerPenyetuju],
                                    ]) as $kode => [$label, $sudah, $oleh])
                                        <x-badge :tone="$sudah ? 'success' : ($tahapBerjalan === $kode ? 'warning' : 'neutral')"
                                            :icon="$sudah ? 'bi-check-circle-fill' : ($tahapBerjalan === $kode ? 'bi-hourglass-split' : 'bi-circle')">{{ $label }}{{ $oleh ? ': ' . $oleh->name : '' }}</x-badge>
                                    @endforeach
                                </span>
                            @endif
                            @if ($item->status === 'rejected' && $item->catatan_admin)<span class="hr-sel-sub mt-1">{{ $item->catatan_admin }}</span>@endif
                        </td>
                        <td class="d-md-none d-lg-table-cell">
                            @if ($item->lowongan)
                                <a href="{{ route('admin.lowongan.edit', $item->lowongan) }}">{{ $item->lowongan->statusLabel() }}</a>
                            @else
                                <span class="text-muted">Belum ada</span>
                            @endif
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            @include('admin.pengajuan._aksi-ikon', [
                                'detailData' => 'detail-formasi-' . $item->id,
                                'setujui' => route('admin.pengajuan-formasi.approve', $item),
                                'tolak' => route('admin.pengajuan-formasi.reject', $item),
                                'judul' => 'pengajuan formasi',
                                'konteks' => $konteks,
                                'bisaProses' => $bisaReview,
                                'tambahan' => $bisaReview && $isAdmin ? [[route('admin.data.edit', ['formasi', $item->id, 'kembali' => request()->fullUrl()]), 'Ubah semua kolom (Admin)', 'bi-shield-lock']] : [],
                                'formAksi' => $formAksi,
                                'slotTambahan' => $isAdmin ? 2 : 1,
                            ])
                        </td>
                    </tr>
                    {{-- Rincian untuk penyetuju: tampil sebelum tombol Setujui/Tolak diklik. --}}
                    @if ($item->isPending() && \App\Support\RekrutmenAccess::canReviewFormasi(auth()->user(), $item))
                        @include('admin.pengajuan._ringkasan-baris', ['r' => \App\Support\RingkasanPengajuan::formasi($item), 'kolom' => 6])
                    @endif
                @empty
                    <x-table-empty :colspan="6" :icon="$adaFilter ? 'bi-search' : 'bi-person-plus'"
                        :title="$adaFilter ? 'Tidak ada pengajuan formasi yang cocok' : 'Belum ada pengajuan formasi'">
                        {{ $adaFilter ? 'Coba kata kunci lain atau reset filter.' : 'Permintaan tambahan atau pengganti pegawai dari bagian akan tampil di sini.' }}
                        @if ($adaFilter)
                            <x-slot:action><x-button :href="$reset" variant="secondary" icon="bi-x-circle">Reset filter</x-button></x-slot:action>
                        @elseif ($canSubmit)
                            <x-slot:action><x-button :href="route('admin.pengajuan-formasi.create')" icon="bi-plus-lg">Buat Pengajuan</x-button></x-slot:action>
                        @endif
                    </x-table-empty>
                @endforelse
            </x-table>

            @if ($pengajuanFormasis->hasPages())
                <x-slot:footer><x-pagination :paginator="$pengajuanFormasis" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>

@include('admin.pengajuan._persetujuan')
@include('admin.pengajuan-formasi._bagan-gaya')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Dialog detail formasi (isi dari <template> di baris).
        document.querySelectorAll('[data-detail]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                Swal.fire({
                    title: 'Detail Pengajuan Formasi',
                    html: document.getElementById(btn.dataset.detail).innerHTML,
                    confirmButtonColor: '#880f12',
                    confirmButtonText: 'Tutup',
                    width: 560,
                });
            });
        });
    });
</script>
@endsection
