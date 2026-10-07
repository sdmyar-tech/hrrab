@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-arrow-left-right"></i></span>
                <div>
                    <p class="eyebrow mb-1">Kepegawaian</p>
                    <h1 class="h3 mb-1">Mutasi & Rangkap Tugas</h1>
                    <p class="text-muted mb-0">Riwayat perpindahan unit dan rangkap tugas pegawai. Mutasi/rangkap baru dibuat dari halaman detail pegawai.</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.penugasan.export', 'eksporUkuran' => ''])
                <a href="{{ route('admin.pegawai.index') }}" class="btn btn-primary">
                    <i class="bi bi-people me-1"></i> Pilih Pegawai
                </a>
            </div>
        </div>

        @if ($unitsTanpaKepala)
            <div class="alert alert-warning d-flex gap-2 align-items-start">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div>
                    <strong>Unit tanpa kepala aktif:</strong> {{ implode(', ', $unitsTanpaKepala) }}.
                    <div class="small">Pengajuan cuti/lembur staf di unit ini tidak punya approver tahap Kepala Bagian. Tunjuk kepala lewat Mutasi/Rangkap (peran Kepala) atau Kelola User.</div>
                </div>
            </div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="panel p-3 h-100">
                    <small class="text-muted d-block">Rangkap aktif</small>
                    <span class="h4 mb-0">{{ $jumlahRangkapAktif }}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="panel p-3 h-100">
                    <small class="text-muted d-block">Terjadwal (belum berlaku)</small>
                    <span class="h4 mb-0">{{ $jumlahTerjadwal }}</span>
                </div>
            </div>
        </div>

        <section class="panel">
            <form action="{{ route('admin.penugasan.index') }}" method="GET" class="panel-filter px-3 pt-3 pb-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari nama, NIP, atau unit..." value="{{ request('q') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <select name="jenis" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Jenis</option>
                            <option value="mutasi" @selected(request('jenis') === 'mutasi')>Mutasi</option>
                            <option value="rangkap" @selected(request('jenis') === 'rangkap')>Rangkap</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="berlaku" @selected(request('status') === 'berlaku')>Berlaku</option>
                            <option value="terjadwal" @selected(request('status') === 'terjadwal')>Terjadwal</option>
                            <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
                        </select>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Jenis</th>
                            <th>Unit</th>
                            <th>Jabatan / Peran</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th>SK / Dicatat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($penugasans as $p)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.pegawai.show', ['pegawai' => $p->data_pegawai_id, 'tab' => 'penugasan']) }}" class="fw-semibold d-block">
                                        {{ $p->dataPegawai?->nama_lengkap ?? '-' }}
                                    </a>
                                    <small class="text-muted">Unit utama: {{ $p->dataPegawai?->kepegawaian?->unit_kerja ?? '-' }}</small>
                                </td>
                                <td><span class="badge {{ $p->jenis === 'mutasi' ? 'text-bg-primary' : 'text-bg-info' }}">{{ $p->jenisLabel() }}</span></td>
                                <td>{{ $p->unit_kerja }}</td>
                                <td>
                                    {{ $p->jabatan ?? '-' }}
                                    @if ($p->isKepala())<span class="badge text-bg-dark ms-1">Kepala</span>@endif
                                </td>
                                <td class="small">
                                    {{ $p->tanggal_mulai->format('d-m-Y') }} &ndash; {{ $p->tanggal_selesai?->format('d-m-Y') ?? 'sekarang' }}
                                </td>
                                <td><span class="badge {{ $p->statusBadge() }}">{{ $p->statusLabel() }}</span></td>
                                <td class="small">
                                    {{ $p->no_sk ?? '-' }}
                                    <span class="text-muted d-block">{{ $p->dibuatOleh?->name ?? 'sistem' }}</span>
                                </td>
                                <td class="text-end">
                                    @if (auth()->user()->isAdmin())
                                        <a href="{{ route('admin.penugasan.edit', $p) }}" class="btn btn-outline-secondary btn-sm" title="Ubah langsung (Admin)"><i class="bi bi-pencil"></i></a>
                                    @endif
                                    <form action="{{ route('admin.penugasan.destroy', $p) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="kembali" value="daftar">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus"
                                            data-confirm="true" data-icon="warning" data-confirm-color="#dc3545" data-confirm-text="Ya, hapus"
                                            data-title="Hapus {{ strtolower($p->jenisLabel()) }}?"
                                            data-text="{{ $p->jenisLabel() }} {{ $p->dataPegawai?->nama_lengkap }} ke {{ $p->unit_kerja }} akan dihapus.{{ $p->is_utama ? ' Pegawai dikembalikan ke unit sebelumnya.' : '' }}">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Belum ada mutasi atau rangkap tugas.
                                </td>
                            </tr>
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
