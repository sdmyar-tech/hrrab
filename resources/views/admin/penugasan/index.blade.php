@extends('layouts.admin.app')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'jenis', 'status']);
    $isAdmin = auth()->user()->isAdmin();
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-arrow-left-right"></i></span>
                <div>
                    <p class="eyebrow mb-1">Kepegawaian</p>
                    <h1 class="h3 mb-1">Mutasi & Rangkap Tugas</h1>
                    <p class="text-muted mb-0">Riwayat perpindahan unit dan rangkap tugas pegawai. Mutasi/rangkap baru dibuat langsung dari halaman ini.</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.penugasan.export'])
                <a href="{{ route('admin.penugasan.tambah') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Mutasi / Rangkap
                </a>
            </div>
        </div>

        @if ($unitsTanpaKepala)
            <div class="alert alert-warning d-flex gap-2 align-items-start">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div>
                    <strong>Unit tanpa kepala aktif:</strong> {{ implode(', ', $unitsTanpaKepala) }}.
                    <div class="small">Pengajuan cuti/lembur staf di unit ini tidak punya approver tahap Kepala Bagian. Tunjuk kepala lewat Mutasi/Rangkap (peran Kepala) atau Kelola Akun.</div>
                </div>
            </div>
        @endif

        {{-- Kartu angka sekaligus pintasan filter (satu klik ke daftar yang dimaksud). --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.penugasan.index', ['jenis' => 'rangkap', 'status' => 'berlaku']) }}" class="panel p-3 h-100 d-block text-reset text-decoration-none">
                    <small class="text-muted d-block">Rangkap aktif</small>
                    <span class="h4 mb-0">{{ $jumlahRangkapAktif }}</span>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.penugasan.index', ['status' => 'terjadwal']) }}" class="panel p-3 h-100 d-block text-reset text-decoration-none">
                    <small class="text-muted d-block">Terjadwal (belum berlaku)</small>
                    <span class="h4 mb-0">{{ $jumlahTerjadwal }}</span>
                </a>
            </div>
        </div>

        <section class="panel" aria-label="Daftar mutasi dan rangkap tugas">
            <form action="{{ route('admin.penugasan.index') }}" method="GET" id="filterPenugasanForm" class="panel-filter px-3 pt-3 pb-2" role="search">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6 col-xl-4">
                        @include('partials.daftar-cari', [
                            'cariTeks' => 'Cari pegawai atau unit, contoh: Siti atau SMK',
                            'cariLabel' => 'Cari mutasi/rangkap menurut nama pegawai, NIP, atau unit',
                        ])
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <select name="jenis" class="form-select form-select-sm" aria-label="Jenis" onchange="this.form.submit()">
                            <option value="">Semua Jenis</option>
                            <option value="mutasi" @selected(request('jenis') === 'mutasi')>Mutasi</option>
                            <option value="rangkap" @selected(request('jenis') === 'rangkap')>Rangkap</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <select name="status" class="form-select form-select-sm" aria-label="Status" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="berlaku" @selected(request('status') === 'berlaku')>Berlaku</option>
                            <option value="terjadwal" @selected(request('status') === 'terjadwal')>Terjadwal</option>
                            <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">@include('partials.urut', ['urutForm' => 'filterPenugasanForm'])</div>
                </div>
                @include('partials.daftar-ringkas', [
                    'ringkasJumlah' => $penugasans->total(), 'ringkasSatuan' => 'data',
                    'ringkasFilter' => $adaFilter, 'ringkasReset' => route('admin.penugasan.index'),
                ])
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Penugasan</th>
                            <th>Periode</th>
                            {{-- Disembunyikan di tablet agar kolom utama tidak berdesakan. --}}
                            <th class="d-md-none d-lg-table-cell">SK / Dicatat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($penugasans as $p)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.pegawai.show', ['pegawai' => $p->data_pegawai_id, 'tab' => 'penugasan']) }}" class="nama-tautan d-block">
                                        {{ $p->dataPegawai?->nama_lengkap ?? '-' }}
                                    </a>
                                    <small class="text-muted">Unit utama: {{ $p->dataPegawai?->kepegawaian?->unit_kerja ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $p->jenis === 'mutasi' ? 'text-bg-primary' : 'text-bg-info' }} me-1">{{ $p->jenisLabel() }}</span>{{ $p->unit_kerja }}
                                    <small class="text-muted d-block mt-1">
                                        {{ $p->jabatan ?? '-' }}
                                        @if ($p->isKepala())<span class="badge text-bg-dark ms-1">Kepala</span>@endif
                                    </small>
                                </td>
                                <td>
                                    <span class="text-nowrap">{{ $p->tanggal_mulai->translatedFormat('d M Y') }}</span> &ndash;
                                    <span class="text-nowrap">{{ $p->tanggal_selesai?->translatedFormat('d M Y') ?? 'sekarang' }}</span>
                                    <span class="badge {{ $p->statusBadge() }} d-table mt-1">{{ $p->statusLabel() }}</span>
                                </td>
                                <td class="d-md-none d-lg-table-cell">
                                    {{ $p->no_sk ?? 'Tanpa SK' }}
                                    <small class="text-muted d-block">oleh {{ $p->dibuatOleh?->name ?? 'sistem' }}</small>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        @if ($isAdmin)
                                            <a href="{{ route('admin.penugasan.edit', $p) }}" class="btn btn-light btn-sm" title="Ubah langsung (Admin)">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>Ubah
                                            </a>
                                        @endif
                                        <div class="dropdown">
                                            @include('partials.tombol-aksi-lain', ['aksiUntuk' => ($p->dataPegawai?->nama_lengkap ?? '') . ' ' . $p->jenisLabel()])
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.pegawai.show', ['pegawai' => $p->data_pegawai_id, 'tab' => 'penugasan']) }}">
                                                        <i class="bi bi-person-vcard me-2"></i>Lihat Pegawai
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('admin.penugasan.destroy', $p) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="kembali" value="daftar">
                                                        <button type="submit" class="dropdown-item text-danger"
                                                            data-confirm="true" data-icon="warning" data-confirm-color="#dc3545" data-confirm-text="Ya, Hapus" data-cancel-text="Batal"
                                                            data-title="Hapus {{ strtolower($p->jenisLabel()) }}?"
                                                            data-text="{{ $p->jenisLabel() }} {{ $p->dataPegawai?->nama_lengkap }} ke {{ $p->unit_kerja }} akan dihapus.{{ $p->is_utama ? ' Pegawai dikembalikan ke unit sebelumnya.' : '' }}">
                                                            <i class="bi bi-trash me-2"></i>Hapus
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @include('partials.kosong-tabel', [
                                'kosongKolom' => 5,
                                'kosongTeks' => $adaFilter ? 'Tidak ada mutasi/rangkap yang cocok dengan pencarian/filter ini.' : 'Belum ada mutasi atau rangkap tugas.',
                                'kosongIkon' => $adaFilter ? 'bi-search' : 'bi-arrow-left-right',
                                'kosongReset' => $adaFilter ? route('admin.penugasan.index') : null,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($penugasans->hasPages())
                <div class="panel-footer px-3 py-2 border-top">
                    {{ $penugasans->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>
</main>
@endsection
