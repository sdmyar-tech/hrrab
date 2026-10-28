@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-people"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">Lamaran Masuk</h1>
                    <p class="text-muted mb-0">Verifikasi, interview, kontrak, hingga keputusan akhir.</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.lamaran.export', 'eksporUkuran' => ''])
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.lamaran.export-jadwal', 'eksporTeks' => 'Ekspor Jadwal', 'eksporIkon' => 'bi-calendar-week', 'eksporUkuran' => ''])
                @if ($canReview)
                    <a href="{{ route('admin.lamaran.internal.create') }}" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i> Tambah Kandidat Langsung
                    </a>
                @endif
            </div>
        </div>

        <section class="panel">
            <form action="{{ route('admin.lamaran.index') }}" method="GET" id="filterLamaranForm" class="panel-filter px-3 pt-3 pb-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="searchInput" class="form-control border-start-0"
                                placeholder="Cari nama atau email pelamar..." value="{{ request('q') }}" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="menunggu_verifikasi" {{ request('status') == 'menunggu_verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                            <option value="proses" {{ request('status') == 'proses' ? 'selected' : '' }}>Proses</option>
                            <option value="diterima" {{ request('status') == 'diterima' ? 'selected' : '' }}>Diterima</option>
                            <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                            <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan Pelamar</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="jalur" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Jalur</option>
                            <option value="umum" {{ request('jalur') == 'umum' ? 'selected' : '' }}>Jalur Umum (Lowongan)</option>
                            <option value="internal" {{ request('jalur') == 'internal' ? 'selected' : '' }}>Jalur Langsung</option>
                        </select>
                    </div>
                    <div class="col-md-auto">@include('partials.urut', ['urutForm' => 'filterLamaranForm'])</div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kandidat</th>
                            <th>Posisi</th>
                            <th>Tanggal Masuk</th>
                            <th>Status</th>
                            <th>Tahap</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lamarans as $item)
                            @php
                                $sudahKaryawan = $item->dataPelamar?->user?->role === \App\Models\User::ROLE_STAFF;
                                $riwayat = $sudahKaryawan
                                    ? collect()
                                    : ($item->dataPelamar?->lamarans->where('id', '!=', $item->id) ?? collect());
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-start gap-2">
                                        @if ($item->dataPelamar?->foto_path)
                                            <img src="{{ \App\Support\Berkas::url($item->dataPelamar->foto_path) }}"
                                                alt="Pas Foto {{ $item->dataPelamar?->nama_lengkap }}"
                                                class="rounded flex-shrink-0" style="width:40px;height:40px;object-fit:cover;">
                                        @endif
                                        <div class="flex-grow-1">
                                            <span class="fw-semibold d-block">{{ $item->namaKandidat() }}</span>
                                            <small class="text-muted d-block">{{ $item->emailKandidat() }}</small>
                                            @if ($item->isJalurInternal())
                                                <span class="badge text-bg-light border mt-1">
                                                    <i class="bi bi-person-check me-1"></i>Jalur Langsung{{ $item->isKandidatPegawai() ? ' · Pegawai Yayasan' : '' }}
                                                </span>
                                            @endif

                                            @if ($riwayat->isNotEmpty())
                                                <button type="button" class="btn btn-link btn-sm p-0 mt-1 text-decoration-none"
                                                    data-bs-toggle="collapse" data-bs-target="#riwayat-{{ $item->id }}"
                                                    aria-expanded="false" aria-controls="riwayat-{{ $item->id }}">
                                                    <i class="bi bi-clock-history me-1"></i>{{ $riwayat->count() }}x lamaran sebelumnya
                                                    <i class="bi bi-chevron-down small"></i>
                                                </button>
                                                <div class="collapse mt-2" id="riwayat-{{ $item->id }}">
                                                    <div class="border rounded p-2 bg-light" style="font-size:.8rem;">
                                                        @foreach ($riwayat as $r)
                                                            <div class="d-flex justify-content-between align-items-start gap-2 py-1 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                                                <div>
                                                                    <span class="d-block">{{ $r->lowongan?->judul }}</span>
                                                                    @if ($r->catatan_admin)
                                                                        <span class="text-muted fst-italic">{{ $r->catatan_admin }}</span>
                                                                    @endif
                                                                </div>
                                                                <span class="badge {{ $r->badgeClass() }} flex-shrink-0">{{ $r->statusLabel() }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{ $item->judulPosisi() }}
                                    <small class="text-muted d-block">
                                        {{ $item->unitKerjaTujuan() }}{{ $item->lowongan ? '' : ' · tanpa lowongan' }}
                                    </small>
                                </td>
                                <td>{{ optional($item->created_at)->translatedFormat('d M Y') }}</td>
                                <td><span class="badge {{ $item->badgeClass() }}">{{ $item->statusLabel() }}</span></td>
                                <td>{{ $item->isProses() ? $item->tahapLabel() : '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.lamaran.show', $item) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Belum ada lamaran masuk.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($lamarans->hasPages())
                <div class="panel-footer px-3 py-2 border-top">
                    {{ $lamarans->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterLamaranForm');
        let debounceTimer;

        if (searchInput && filterForm) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    filterForm.submit();
                }, 500);
            });
        }
    });
</script>
@endsection
