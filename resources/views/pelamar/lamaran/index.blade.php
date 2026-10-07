@extends('layouts.pelamar.app')

@section('content')
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-send"></i></span>
                <div>
                    <div class="eyebrow">Pelamar</div>
                    <h1 class="mb-0">Lamaran Saya</h1>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'pelamar.lamaran.export', 'eksporTeks' => 'Ekspor Lamaran & Jadwal', 'eksporUkuran' => ''])
                <a href="{{ route('lowongan.index') }}" class="btn btn-primary">
                    <i class="bi bi-briefcase me-1"></i> Cari Lowongan
                </a>
            </div>
        </div>

        <div class="panel">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Lowongan</th>
                            <th>Status</th>
                            <th>Tahap</th>
                            <th>Catatan Admin</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lamarans as $lamaran)
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block">{{ $lamaran->lowongan?->judul }}</span>
                                    <small class="text-muted">{{ $lamaran->lowongan?->unit_kerja }}</small>
                                </td>
                                <td><span class="badge {{ $lamaran->badgeClass() }}">{{ $lamaran->statusLabel() }}</span></td>
                                <td>{{ $lamaran->isProses() ? $lamaran->tahapLabel() : '-' }}</td>
                                <td>{{ $lamaran->catatan_admin ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('pelamar.lamaran.show', $lamaran) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                    @if (! $lamaran->isSelesai())
                                        <form action="{{ route('pelamar.lamaran.tolak', $lamaran) }}" method="POST"
                                            data-confirm="Batalkan lamaran ini?" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-x-lg"></i> Batalkan
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Anda belum melamar lowongan apapun.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
