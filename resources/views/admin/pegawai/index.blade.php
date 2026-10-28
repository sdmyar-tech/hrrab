@extends(Auth::user()->panelLayout())

@section('content')
    <main class="dashboard-content">
        <div class="container-fluid px-3 px-lg-4 py-4">

            @include('layouts.admin.header')

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="h5 mb-1 section-title">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            <span>Data Pegawai</span>
                        </h2>
                        <p class="text-muted mb-0">Daftar seluruh pegawai terdaftar beserta informasi kepegawaian.</p>
                    </div>
                </div>

                <form action="{{ route('admin.pegawai.index') }}" method="GET" id="filterPegawaiForm"
                    class="panel-filter px-3 pt-3 pb-2">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                <input type="search" name="q" id="searchInput" class="form-control border-start-0"
                                    placeholder="Cari nama, NIP, email, atau jabatan..." value="{{ request('q') }}"
                                    autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="unit_kerja" class="form-select form-select-sm"
                                onchange="document.getElementById('filterPegawaiForm').submit()">
                                <option value="">Semua Unit Kerja</option>
                                @foreach ($unitKerjaOptions as $unit)
                                    <option value="{{ $unit }}"
                                        {{ request('unit_kerja') == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status_rekrutmen" class="form-select form-select-sm"
                                onchange="document.getElementById('filterPegawaiForm').submit()">
                                <option value="">Semua Status</option>
                                <option value="Aktif" {{ request('status_rekrutmen') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="Tidak Aktif" {{ request('status_rekrutmen') == 'Tidak Aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-auto">@include('partials.urut', ['urutForm' => 'filterPegawaiForm'])</div>
                        <div class="col-auto text-end">
                            @if (request()->hasAny(['q', 'unit_kerja', 'status_rekrutmen']) || $saringanDashboard)
                                <a href="{{ route('admin.pegawai.index') }}" class="btn btn-outline-secondary btn-sm"
                                    title="Reset filter">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Filter yang datang dari klik grafik dashboard: tetap terbawa saat mencari, bisa dilepas satu per satu. --}}
                    @if ($saringanDashboard)
                        @foreach (array_keys($saringanDashboard) as $kunci)
                            <input type="hidden" name="{{ $kunci }}" value="{{ request($kunci) }}">
                        @endforeach
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2" data-saringan-dashboard>
                            <span class="small text-muted"><i class="bi bi-funnel me-1"></i>Dari dashboard:</span>
                            @foreach ($saringanDashboard as $kunci => $nilai)
                                <a href="{{ request()->fullUrlWithoutQuery([$kunci, 'page']) }}" class="badge text-bg-info text-decoration-none" title="Lepas filter ini">
                                    {{ \App\Support\KelompokPegawai::LABEL[$kunci] }}: {{ $nilai }} <i class="bi bi-x-lg ms-1"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if (request()->hasAny(['q', 'unit_kerja', 'status_rekrutmen']) || $saringanDashboard)
                        <div class="mt-2">
                            <small class="text-muted">
                                Menampilkan {{ $pegawais->total() }} hasil
                                @if (request('q'))
                                    untuk "<strong>{{ request('q') }}</strong>"
                                @endif
                            </small>
                        </div>
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="pegawaiTable">
                        <thead>
                            <tr>
                                <th>NIP / Nama</th>
                                <th>Jabatan</th>
                                <th>Status Kepegawaian</th>
                                <th>No. Telepon / BPJS</th>
                                <th>Tanggal Masuk</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pegawais as $pegawai)
                                <tr>
                                    <td>
                                        <div class="table-media">
                                            <img class="product-thumb rounded-circle"
                                                src="{{ optional($pegawai->dokumen)->pas_foto ? \App\Support\Berkas::url($pegawai->dokumen->pas_foto) : asset('assets-admin/images/avatar/avatar-fallback.jpg') }}"
                                                alt="{{ $pegawai->nama_lengkap }}">
                                            <div>
                                                <span class="fw-semibold d-block">{{ $pegawai->nama_lengkap }}</span>
                                                <small class="text-muted">{{ $pegawai->nip ?? '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $pegawai->kepegawaian->jabatan ?? '-' }}
                                        @if ($pegawai->kepegawaian?->unit_kerja)
                                            <small class="text-muted d-block">{{ $pegawai->kepegawaian->unit_kerja }}</small>
                                        @endif
                                        @foreach ($pegawai->penugasanAktif->where('is_utama', false) as $rangkap)
                                            <span class="badge text-bg-info" title="Rangkap tugas">Rangkap {{ $rangkap->unit_kerja }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @php
                                            $status = $pegawai->kepegawaian->status_rekrutmen ?? 'Non-Aktif';
                                            $badgeClass = match (strtolower($status)) {
                                                'tetap', 'aktif' => 'text-bg-success',
                                                'kontrak' => 'text-bg-info',
                                                'probation', 'magang' => 'text-bg-warning',
                                                default => 'text-bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span>
                                    </td>
                                    <td>
                                        <div>{{ $pegawai->no_hp ?? '-' }}</div>
                                        <small class="text-muted">BPJS:
                                            {{ optional($pegawai->bpjs)->no_bpjs_kesehatan ?? '-' }}</small>
                                    </td>
                                    <td>{{ optional(optional($pegawai->kepegawaian)->tanggal_masuk)->translatedFormat('d M Y') ?? '-' }}</td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                Aksi
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.pegawai.show', $pegawai->id) }}">
    <i class="bi bi-eye me-2"></i>Detail
</a>
                                                </li>
                                                @can('kelola-pegawai')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('admin.pegawai.edit', $pegawai->id) }}">
                                                            <i class="bi bi-pencil me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('admin.pegawai.destroy', $pegawai->id) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger"
                                                                data-confirm="true"
                                                                data-icon="warning"
                                                                data-title="Hapus Data Pegawai?"
                                                                data-text="Data {{ $pegawai->nama_lengkap }} akan dihapus permanen beserta dokumen terkait."
                                                                data-confirm-text="Ya, Hapus"
                                                                data-confirm-color="#dc3545"
                                                                data-cancel-text="Batal">
                                                                <i class="bi bi-trash me-2"></i>Hapus
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endcan
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        @if (request()->hasAny(['q', 'unit_kerja', 'status_rekrutmen']))
                                            Tidak ada pegawai yang cocok dengan pencarian/filter ini.
                                        @else
                                            Belum ada data pegawai.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($pegawais->hasPages())
                    <div class="panel-footer px-3 py-2 border-top">
                        {{ $pegawais->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const filterForm = document.getElementById('filterPegawaiForm');
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
