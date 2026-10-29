@extends('layouts.admin.app')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'status', 'tanggal_dari', 'tanggal_sampai']);
    $isAdmin = auth()->user()->isAdmin();
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-plus"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">Pengajuan Formasi</h1>
                    <p class="text-muted mb-0">Permintaan kekurangan pegawai dari Kepala/Koordinator Divisi.</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pengajuan-formasi.export'])
                @if ($canSubmit)
                    <a href="{{ route('admin.pengajuan-formasi.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Ajukan Formasi
                    </a>
                @endif
            </div>
        </div>

        <section class="panel">
            <form action="{{ route('admin.pengajuan-formasi.index') }}" method="GET" id="filterFormasiForm" class="panel-filter px-3 pt-3 pb-2" role="search">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6 col-xl-4">
                        @include('partials.daftar-cari', [
                            'cariTeks' => 'Cari formasi, contoh: Guru SMK',
                            'cariLabel' => 'Cari pengajuan formasi menurut jabatan atau bagian',
                        ])
                    </div>
                    <div class="col-12 col-md-3">
                        <select name="status" class="form-select form-select-sm" aria-label="Status pengajuan" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                            <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">@include('partials.filter-tanggal', ['tanggalLabel' => 'Diajukan'])</div>
                    <div class="col-12 col-md-auto">@include('partials.urut', ['urutForm' => 'filterFormasiForm'])</div>
                </div>
                @include('partials.daftar-ringkas', [
                    'ringkasJumlah' => $pengajuanFormasis->total(), 'ringkasSatuan' => 'pengajuan',
                    'ringkasFilter' => $adaFilter, 'ringkasReset' => route('admin.pengajuan-formasi.index'),
                ])
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Posisi</th>
                            <th>Diajukan Oleh</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            {{-- Disembunyikan di tablet agar kolom aksi tidak terjepit; tetap ada di desktop & kartu HP. --}}
                            <th class="d-md-none d-lg-table-cell">Lowongan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengajuanFormasis as $item)
                            <tr>
                                <td>
                                    {{-- Judul baris = posisi yang diminta; detail lengkap lewat tombol info (satu klik). --}}
                                    <span class="fw-semibold">{{ $item->jabatan }}</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-1 btn-detail-formasi" data-detail="detail-formasi-{{ $item->id }}"
                                        title="Lihat detail" aria-label="Lihat detail formasi {{ $item->jabatan }}">
                                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                                    </button>
                                    <small class="text-muted d-block">{{ $item->unit_kerja }}@if ($item->jenis_kebutuhan) &middot; {{ $item->jenis_kebutuhan === 'penambahan' ? 'Penambahan' : 'Pengganti' }}@endif</small>
                                    <template id="detail-formasi-{{ $item->id }}">
                                        <dl class="detail-formasi">
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
                                            <div class="detail-formasi-bagan">
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
                                    {{ $item->diajukanOleh?->name }}
                                    <small class="text-muted d-block">{{ optional($item->created_at)->translatedFormat('d M Y') }}</small>
                                </td>
                                <td>{{ $item->jumlahLabel() }}</td>
                                <td>
                                    <span class="badge {{ $item->badgeClass() }}">{{ $item->statusLabel() }}</span>
                                    @if ($item->butuhDuaPersetujuan())
                                        {{-- Penambahan: bertahap GM unit → Owner. Tahap berjalan ditandai jam pasir, tahap berikutnya abu. --}}
                                        @php
                                            $tahap = $item->isPending() ? \App\Support\RekrutmenAccess::tahapPersetujuan($item) : null;
                                            $langkah = array_filter([
                                                'gm' => \App\Support\RekrutmenAccess::perluGm($item) ? ['GM bagian', $item->gm_disetujui_oleh, $item->gmPenyetuju] : null,
                                                'owner' => ['Owner', $item->owner_disetujui_oleh, $item->ownerPenyetuju],
                                            ]);
                                        @endphp
                                        <ol class="tahap-setuju small mt-1">
                                            @foreach ($langkah as $kode => [$label, $sudah, $oleh])
                                                <li class="{{ $sudah ? 'text-success' : ($tahap === $kode ? 'text-warning-emphasis fw-semibold' : 'text-muted') }}">
                                                    <i class="bi {{ $sudah ? 'bi-check-circle-fill' : ($tahap === $kode ? 'bi-hourglass-split' : 'bi-circle') }} me-1"></i>{{ $label }}{{ $oleh ? ': ' . $oleh->name : '' }}
                                                </li>
                                            @endforeach
                                        </ol>
                                    @endif
                                    @if ($item->status === 'rejected' && $item->catatan_admin)
                                        <small class="text-muted d-block mt-1">{{ $item->catatan_admin }}</small>
                                    @endif
                                </td>
                                <td class="d-md-none d-lg-table-cell">
                                    @if ($item->lowongan)
                                        <a href="{{ route('admin.lowongan.edit', $item->lowongan) }}">{{ $item->lowongan->statusLabel() }}</a>
                                    @else
                                        <span class="text-muted">Belum ada</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{-- Tumpukan sama lebar seperti daftar cuti/lembur/izin keluar (.aksi-pengajuan di hrrab.css).
                                         Setujui menonjol; Tolak ringan; aksi khusus Admin dikumpulkan di menu Lainnya. --}}
                                    <div class="aksi-pengajuan">
                                        @if (($item->isPending() || $isAdmin) && \App\Support\RekrutmenAccess::canReviewFormasi(auth()->user(), $item))
                                            <form action="{{ route('admin.pengajuan-formasi.approve', $item) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm"
                                                    data-confirm="true" data-icon="question" data-title="Setujui pengajuan formasi?"
                                                    data-text="Formasi {{ $item->jabatan }} ({{ $item->unit_kerja }}) akan disetujui pada tahap ini."
                                                    data-confirm-text="Ya, Setujui" data-confirm-color="#198754" data-cancel-text="Batal">
                                                    <i class="bi bi-check-lg"></i> Setujui
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.pengajuan-formasi.reject', $item) }}" method="POST" class="form-reject-formasi">
                                                @csrf
                                                <input type="hidden" name="catatan_admin" value="">
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-reject-formasi"><i class="bi bi-x-lg"></i> Tolak</button>
                                            </form>
                                            @if ($isAdmin)
                                                <div class="dropdown">
                                                    <button class="btn btn-light btn-sm dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                                        aria-label="Aksi lain untuk formasi {{ $item->jabatan }}">Lainnya</button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('admin.data.edit', ['formasi', $item->id, 'kembali' => request()->fullUrl()]) }}">
                                                                <i class="bi bi-shield-lock me-2"></i>Ubah Semua Kolom (Admin)
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('admin.pengajuan-formasi.destroy', $item) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger"
                                                                    data-confirm="true" data-icon="warning" data-title="Hapus pengajuan formasi?"
                                                                    data-text="Pengajuan formasi {{ $item->jabatan }} akan dihapus permanen. Tindakan ini tidak bisa dibatalkan."
                                                                    data-confirm-text="Ya, Hapus" data-confirm-color="#dc3545" data-cancel-text="Batal">
                                                                    <i class="bi bi-trash me-2"></i>Hapus Permanen (Admin)
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            @endif
                                        @elseif ($item->diajukan_oleh === auth()->id() && $item->isPending())
                                            <form action="{{ route('admin.pengajuan-formasi.batalkan', $item) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-secondary btn-sm"
                                                    data-confirm="true" data-icon="warning" data-title="Batalkan pengajuan formasi?"
                                                    data-text="Pengajuan formasi {{ $item->jabatan }} akan dibatalkan dan tidak diproses lagi."
                                                    data-confirm-text="Ya, Batalkan" data-confirm-color="#dc3545" data-cancel-text="Kembali">
                                                    <i class="bi bi-x-lg"></i> Batalkan
                                                </button>
                                            </form>
                                        @else
                                            {{-- Tidak ada aksi untuk Anda; status & tahap tampil di kolom Status. --}}
                                            <span class="text-muted small text-center">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        {{-- Rincian untuk penyetuju: tampil sebelum tombol Setujui/Tolak diklik. --}}
                        @if ($item->isPending() && \App\Support\RekrutmenAccess::canReviewFormasi(auth()->user(), $item))
                            @include('admin.pengajuan._ringkasan-baris', ['r' => \App\Support\RingkasanPengajuan::formasi($item), 'kolom' => 6])
                        @endif
                        @empty
                            @include('partials.kosong-tabel', [
                                'kosongKolom' => 6,
                                'kosongTeks' => $adaFilter ? 'Tidak ada pengajuan formasi yang cocok dengan pencarian/filter ini.' : 'Belum ada pengajuan formasi.',
                                'kosongIkon' => $adaFilter ? 'bi-search' : 'bi-person-plus',
                                'kosongReset' => $adaFilter ? route('admin.pengajuan-formasi.index') : null,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pengajuanFormasis->hasPages())
                <div class="panel-footer px-3 py-2 border-top">
                    {{ $pengajuanFormasis->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Cari otomatis ditangani global (main.js: data-cari-otomatis).
        document.querySelectorAll('.btn-reject-formasi').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = btn.closest('form');

                Swal.fire({
                    icon: 'warning',
                    title: 'Tolak pengajuan formasi?',
                    input: 'textarea',
                    inputLabel: 'Alasan penolakan',
                    inputPlaceholder: 'Tuliskan alasan penolakan...',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Tolak',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    preConfirm: function (value) {
                        if (!value || !value.trim()) {
                            Swal.showValidationMessage('Alasan penolakan wajib diisi.');
                        }
                        return value;
                    },
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.querySelector('[name="catatan_admin"]').value = result.value;
                        form.submit();
                    }
                });
            });
        });

        document.querySelectorAll('.btn-detail-formasi').forEach(function (btn) {
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
@include('admin.pengajuan-formasi._bagan-gaya')
<style>
    .detail-formasi-bagan { margin-top: .9rem; text-align: left; }
    .tahap-setuju { list-style: none; padding: 0; margin: .25rem 0 0; display: grid; gap: .15rem; counter-reset: tahap; }
    .tahap-setuju li { white-space: nowrap; }
    .detail-formasi { text-align: left; margin: 0; font-size: .875rem; }
    .detail-formasi dt { font-size: .75rem; color: var(--admin-muted); font-weight: 600; margin-top: .55rem; }
    .detail-formasi dd { margin: 0; white-space: pre-line; color: var(--admin-text); }
</style>
@endsection
