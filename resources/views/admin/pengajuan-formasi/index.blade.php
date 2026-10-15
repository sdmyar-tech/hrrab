@extends('layouts.admin.app')

@section('content')
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
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pengajuan-formasi.export', 'eksporUkuran' => ''])
                @if ($canSubmit)
                    <a href="{{ route('admin.pengajuan-formasi.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Ajukan Formasi
                    </a>
                @endif
            </div>
        </div>

        <section class="panel">
            <form action="{{ route('admin.pengajuan-formasi.index') }}" method="GET" id="filterFormasiForm" class="panel-filter px-3 pt-3 pb-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="searchInput" class="form-control border-start-0"
                                placeholder="Cari jabatan atau unit kerja..." value="{{ request('q') }}" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                            <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-auto">@include('partials.urut', ['urutForm' => 'filterFormasiForm'])</div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Diajukan Oleh</th>
                            <th>Unit Kerja</th>
                            <th>Jabatan</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Lowongan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengajuanFormasis as $item)
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block">{{ $item->diajukanOleh?->name }}</span>
                                    <small class="text-muted">{{ optional($item->created_at)->translatedFormat('d M Y') }}</small>
                                </td>
                                <td>{{ $item->unit_kerja }}</td>
                                <td>
                                    {{ $item->jabatan }}
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-1 btn-detail-formasi" data-detail="detail-formasi-{{ $item->id }}" title="Lihat detail">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                    @if ($item->jenis_kebutuhan)<small class="text-muted d-block">{{ $item->jenis_kebutuhan === 'penambahan' ? 'Penambahan' : 'Replacement' }}</small>@endif
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
                                <td>{{ $item->jumlahLabel() }}</td>
                                <td>
                                    <span class="badge {{ $item->badgeClass() }}">{{ $item->statusLabel() }}</span>
                                    @if ($item->butuhDuaPersetujuan())
                                        {{-- Penambahan: bertahap GM unit → Owner. Tahap berjalan ditandai jam pasir, tahap berikutnya abu. --}}
                                        @php
                                            $tahap = $item->isPending() ? \App\Support\RekrutmenAccess::tahapPersetujuan($item) : null;
                                            $langkah = array_filter([
                                                'gm' => \App\Support\RekrutmenAccess::perluGm($item) ? ['GM unit', $item->gm_disetujui_oleh, $item->gmPenyetuju] : null,
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
                                <td>
                                    @if ($item->lowongan)
                                        <a href="{{ route('admin.lowongan.edit', $item->lowongan) }}">{{ $item->lowongan->statusLabel() }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if (($item->isPending() || auth()->user()->isAdmin()) && \App\Support\RekrutmenAccess::canReviewFormasi(auth()->user(), $item))
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <form action="{{ route('admin.pengajuan-formasi.approve', $item) }}" method="POST"
                                                data-confirm="Setujui pengajuan formasi ini?">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="bi bi-check-lg"></i> Setujui
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.pengajuan-formasi.reject', $item) }}" method="POST"
                                                class="form-reject-formasi">
                                                @csrf
                                                <input type="hidden" name="catatan_admin" value="">
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-reject-formasi">
                                                    <i class="bi bi-x-lg"></i> Tolak
                                                </button>
                                            </form>
                                            @if (auth()->user()->isAdmin())
                                                @include('admin.editor-data._tombol', ['entitas' => 'formasi', 'id' => $item->id, 'teks' => '', 'hapus' => false])
                                                <form action="{{ route('admin.pengajuan-formasi.destroy', $item) }}" method="POST"
                                                    data-confirm="Hapus permanen pengajuan formasi {{ $item->jabatan }}?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    @elseif ($item->diajukan_oleh === auth()->id() && $item->isPending())
                                        <form action="{{ route('admin.pengajuan-formasi.batalkan', $item) }}" method="POST"
                                            data-confirm="Batalkan pengajuan formasi ini?">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-x-lg"></i> Batalkan
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Belum ada pengajuan formasi.
                                </td>
                            </tr>
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
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterFormasiForm');
        let debounceTimer;

        if (searchInput && filterForm) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    filterForm.submit();
                }, 500);
            });
        }

        document.querySelectorAll('.btn-reject-formasi').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = btn.closest('form');

                Swal.fire({
                    icon: 'question',
                    title: 'Tolak Pengajuan Formasi?',
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
