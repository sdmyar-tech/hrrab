@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-clipboard-check"></i></span>
                <div>
                    <p class="eyebrow mb-1">Kepegawaian</p>
                    <h1 class="h3 mb-1">Evaluasi Kontrak</h1>
                    <p class="text-muted mb-0">Sebulan sebelum kontrak habis, atasan mengisi rekomendasi & penilaian; Kepala SDM memutuskan.</p>
                </div>
            </div>
        </div>

        <section class="panel">
            <ul class="nav nav-tabs px-3 pt-3" role="tablist">
                @foreach (\App\Http\Controllers\Admin\EvaluasiKontrakController::TAB as $kode => $label)
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === $kode ? 'active' : '' }}" href="{{ route('admin.evaluasi-kontrak.index', ['tab' => $kode, 'q' => request('q')]) }}">
                            {{ $label }} <span class="badge {{ $kode === 'tugas' && $jumlahTab[$kode] ? 'text-bg-danger' : 'text-bg-light' }} ms-1">{{ $jumlahTab[$kode] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <form action="{{ route('admin.evaluasi-kontrak.index') }}" method="GET" class="panel-filter px-3 pt-3 pb-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="input-group input-group-sm" style="max-width: 360px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control border-start-0" placeholder="Cari nama atau unit…" value="{{ request('q') }}">
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Habis Kontrak</th>
                            <th>Status</th>
                            <th>Rekomendasi Atasan</th>
                            <th>Keputusan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftar as $e)
                            @php $sisa = $e->sisaHari(); @endphp
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block">{{ $e->dataPegawai?->nama_lengkap ?? '-' }}</span>
                                    <small class="text-muted">{{ $e->dataPegawai?->kepegawaian?->jabatan }} · {{ $e->unit_kerja }}</small>
                                </td>
                                <td>
                                    {{ $e->tanggal_habis_kontrak->translatedFormat('d M Y') }}
                                    @if ($e->status !== \App\Models\EvaluasiKontrak::STATUS_SELESAI)
                                        <small class="d-block {{ $sisa < 0 ? 'text-danger fw-semibold' : ($sisa <= 7 ? 'text-warning-emphasis fw-semibold' : 'text-muted') }}">
                                            {{ $sisa < 0 ? 'lewat ' . abs($sisa) . ' hari' : ($sisa === 0 ? 'hari ini' : $sisa . ' hari lagi') }}
                                        </small>
                                    @endif
                                </td>
                                <td><span class="badge {{ $e->badgeClass() }}">{{ $e->statusLabel() }}</span></td>
                                <td>
                                    @if ($e->rekomendasi)
                                        {{ \App\Models\EvaluasiKontrak::labelPilihan($e->rekomendasi, $e->lama_bulan) }}
                                        <small class="d-block text-muted">nilai rata-rata {{ $e->rataNilai() ?? '-' }}/5 · {{ $e->pengisi?->name }}</small>
                                    @else
                                        <span class="text-muted">Belum diisi</span>
                                    @endif
                                </td>
                                <td>{{ $e->keputusan ? \App\Models\EvaluasiKontrak::labelPilihan($e->keputusan, $e->keputusan_lama_bulan) : '-' }}</td>
                                <td class="text-end">
                                    @php $tugas = \App\Support\EvaluasiKontrakAlur::tugasUntuk(auth()->user(), $e); @endphp
                                    <a href="{{ route('admin.evaluasi-kontrak.show', $e) }}" class="btn btn-sm {{ $tugas ? 'btn-primary' : 'btn-outline-secondary' }}">
                                        <i class="bi {{ $tugas ? 'bi-pencil-square' : 'bi-eye' }} me-1"></i>{{ $tugas ? ($e->status === \App\Models\EvaluasiKontrak::STATUS_MENUNGGU_SDM ? 'Putuskan' : 'Isi evaluasi') : 'Lihat' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-clipboard-check fs-3 d-block mb-2"></i>
                                    {{ $tab === 'tugas' ? 'Tidak ada evaluasi yang menunggu tindakan Anda.' : 'Belum ada evaluasi kontrak di sini.' }}
                                    <small class="d-block mt-1">Evaluasi dibuat otomatis {{ \App\Models\EvaluasiKontrak::HARI_SEBELUM }} hari sebelum kontrak pegawai habis.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
@endsection
