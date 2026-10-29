@extends('layouts.admin.app')

@section('content')
@php
    $adaFilter = request()->filled('q') || \App\Support\FilterTanggal::aktif(request());
    $jumlahDaftar = method_exists($daftar, 'total') ? $daftar->total() : $daftar->count();
@endphp
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
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.evaluasi-kontrak.export', 'eksporParam' => ['tab' => $tab]])
            </div>
        </div>

        <section class="panel">
            <ul class="nav nav-tabs" role="tablist">
                @foreach (\App\Http\Controllers\Admin\EvaluasiKontrakController::TAB as $kode => $label)
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === $kode ? 'active' : '' }}" href="{{ route('admin.evaluasi-kontrak.index', ['tab' => $kode] + request()->only(['q', 'tanggal_dari', 'tanggal_sampai'])) }}">
                            {{-- Tugas yang menunggu = kuning (aturan warna status). --}}
                            {{ $label }} <span class="badge {{ $kode === 'tugas' && $jumlahTab[$kode] ? 'text-bg-warning' : 'text-bg-light' }} ms-1">{{ $jumlahTab[$kode] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <form action="{{ route('admin.evaluasi-kontrak.index') }}" method="GET" class="panel-filter px-3 pt-3 pb-2 mt-3" role="search">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="row g-2">
                    <div class="col-12 col-md-6 col-xl-4">
                        @include('partials.daftar-cari', [
                            'cariTeks' => 'Cari pegawai, contoh: Siti atau SMK',
                            'cariLabel' => 'Cari evaluasi kontrak menurut nama pegawai atau unit',
                        ])
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">@include('partials.filter-tanggal', ['tanggalLabel' => 'Habis kontrak'])</div>
                </div>
                @include('partials.daftar-ringkas', [
                    'ringkasJumlah' => $jumlahDaftar, 'ringkasSatuan' => 'evaluasi',
                    'ringkasFilter' => $adaFilter, 'ringkasReset' => route('admin.evaluasi-kontrak.index', ['tab' => $tab]),
                ])
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Habis Kontrak</th>
                            <th>Status</th>
                            <th>Penilaian</th>
                            <th>Keputusan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftar as $e)
                            @php $sisa = $e->sisaHari(); @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.evaluasi-kontrak.show', $e) }}" class="nama-tautan d-block">{{ $e->dataPegawai?->nama_lengkap ?? '-' }}</a>
                                    <small class="text-muted">{{ $e->dataPegawai?->kepegawaian?->jabatan }} · {{ $e->unit_kerja }}</small>
                                </td>
                                <td>
                                    <span class="text-nowrap">{{ $e->tanggal_habis_kontrak->translatedFormat('d M Y') }}</span>
                                    @if ($e->status !== \App\Models\EvaluasiKontrak::STATUS_SELESAI)
                                        <small class="d-block {{ $sisa < 0 ? 'text-danger fw-semibold' : ($sisa <= 7 ? 'text-warning-emphasis fw-semibold' : 'text-muted') }}">
                                            {{ $sisa < 0 ? 'lewat ' . abs($sisa) . ' hari' : ($sisa === 0 ? 'hari ini' : $sisa . ' hari lagi') }}
                                        </small>
                                    @endif
                                </td>
                                <td><span class="badge {{ $e->badgeClass() }}">{{ $e->statusLabel() }}</span></td>
                                <td>
                                    @if ($e->total_nilai !== null)
                                        {{ \App\Models\EvaluasiKontrak::predikat($e->total_nilai) }}
                                        <small class="d-block text-muted">total {{ $e->total_nilai }} · {{ $e->pengisi?->name }}</small>
                                    @else
                                        <span class="text-muted">Belum diisi</span>
                                    @endif
                                </td>
                                <td>{{ $e->keputusan ? $e->labelKeputusan() : '-' }}@if ($e->labelTtd())<small class="d-block text-muted"><i class="bi bi-pen me-1"></i>TTD {{ $e->labelTtd() }}</small>@endif</td>
                                <td class="text-end">
                                    @php $tugas = \App\Support\EvaluasiKontrakAlur::tugasUntuk(auth()->user(), $e); @endphp
                                    {{-- Ada tugas untuk Anda → tombol utama (menonjol); selain itu cukup Detail (ringan). --}}
                                    <a href="{{ route('admin.evaluasi-kontrak.show', $e) }}" class="btn btn-sm {{ $tugas ? 'btn-primary' : 'btn-light' }}">
                                        <i class="bi {{ $tugas ? 'bi-pencil-square' : 'bi-eye' }}" aria-hidden="true"></i>{{ $tugas ? ($e->status === \App\Models\EvaluasiKontrak::STATUS_MENUNGGU_SDM ? 'Putuskan' : 'Beri Nilai') : 'Detail' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            @include('partials.kosong-tabel', [
                                'kosongKolom' => 6,
                                'kosongTeks' => $adaFilter ? 'Tidak ada evaluasi yang cocok dengan pencarian ini.'
                                    : ($tab === 'tugas' ? 'Tidak ada evaluasi yang menunggu tindakan Anda.' : 'Belum ada evaluasi kontrak di sini.')
                                        . ' Evaluasi dibuat otomatis ' . \App\Models\EvaluasiKontrak::HARI_SEBELUM . ' hari sebelum kontrak pegawai habis.',
                                'kosongIkon' => $adaFilter ? 'bi-search' : 'bi-clipboard-check',
                                'kosongReset' => $adaFilter ? route('admin.evaluasi-kontrak.index', ['tab' => $tab]) : null,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
@endsection
