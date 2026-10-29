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
                @if ($urutOpsi) @include('partials.urut') @endif
                @include('partials.tombol-ekspor', ['eksporRoute' => 'pelamar.lamaran.export', 'eksporTeks' => 'Ekspor Lamaran & Jadwal'])
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
                            <th>Catatan Admin</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lamarans as $lamaran)
                            <tr>
                                <td>
                                    <a href="{{ route('pelamar.lamaran.show', $lamaran) }}" class="nama-tautan d-block">{{ $lamaran->lowongan?->judul }}</a>
                                    <small class="text-muted">{{ $lamaran->lowongan?->unit_kerja }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $lamaran->badgeClass() }}">{{ $lamaran->statusLabel() }}</span>
                                    @if ($lamaran->isProses())<small class="text-muted d-block mt-1">{{ $lamaran->tahapLabel() }}</small>@endif
                                </td>
                                <td>{{ $lamaran->catatan_admin ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('pelamar.lamaran.show', $lamaran) }}" class="btn btn-light btn-sm">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                    @if (! $lamaran->isSelesai())
                                        <form action="{{ route('pelamar.lamaran.tolak', $lamaran) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                                data-confirm="true" data-icon="warning" data-title="Batalkan lamaran?"
                                                data-text="Lamaran untuk {{ $lamaran->lowongan?->judul }} akan dibatalkan dan tidak diproses lagi."
                                                data-confirm-text="Ya, Batalkan" data-confirm-color="#dc3545" data-cancel-text="Kembali">
                                                <i class="bi bi-x-lg"></i> Batalkan
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            @include('partials.kosong-tabel', ['kosongKolom' => 4, 'kosongTeks' => 'Anda belum melamar lowongan apa pun.', 'kosongIkon' => 'bi-briefcase'])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
