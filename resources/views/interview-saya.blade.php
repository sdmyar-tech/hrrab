@extends($layout)

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-calendar2-week"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">Jadwal Interview Saya</h1>
                    <p class="text-muted mb-0">Jadwal wawancara kandidat yang ditugaskan kepada Anda.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'interview.saya.export', 'eksporTeks' => 'Ekspor Jadwal', 'eksporUkuran' => ''])
            </div>
        </div>

        <section class="panel">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kandidat</th>
                            <th>Lowongan</th>
                            <th>Jadwal</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                            <th>Hasil</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jadwals as $jadwal)
                            <tr>
                                <td>{{ $jadwal->lamaran?->namaKandidat() }}</td>
                                <td>{{ $jadwal->lamaran?->judulPosisi() }}</td>
                                <td>
                                    {{ $jadwal->tahapLabel() }}<br>
                                    <small class="text-muted">{{ $jadwal->tanggal->format('d M Y') }}, {{ substr($jadwal->waktu_mulai, 0, 5) }}</small>
                                </td>
                                <td>@if ($jadwal->lokasi)@include('partials.lokasi', ['lokasi' => $jadwal->lokasi])@else - @endif</td>
                                <td><span class="badge {{ $jadwal->status === 'dibatalkan' ? 'text-bg-danger' : ($jadwal->status === 'selesai' ? 'text-bg-secondary' : 'text-bg-info') }}">{{ $jadwal->statusLabel() }}</span></td>
                                <td><span class="badge {{ $jadwal->hasilBadgeClass() }}">{{ $jadwal->hasilLabel() }}</span></td>
                                <td class="text-end">
                                    @if ($jadwal->isTerjadwal() && $jadwal->lamaran)
                                        <a href="{{ route('admin.lamaran.show', $jadwal->lamaran) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil-square me-1"></i> Isi Hasil
                                        </a>
                                    @elseif ($jadwal->lamaran)
                                        <a href="{{ route('admin.lamaran.show', $jadwal->lamaran) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Belum ada jadwal interview yang ditugaskan kepada Anda.
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
