@extends($layout)

@section('title', 'Jadwal Interview Saya')

@section('content')
@php
    // Kelompok filter: semua jadwal sudah dimuat → disaring langsung di halaman.
    $kelompok = fn ($j) => $j->status === 'dibatalkan' ? 'dibatalkan' : ($j->isTerjadwal() ? 'terjadwal' : 'selesai');
    $jumlah = $jadwals->countBy($kelompok);
    $filter = [
        ['semua', 'Semua', 'bi-grid', $jadwals->count()],
        ['terjadwal', 'Terjadwal', 'bi-calendar-event', $jumlah['terjadwal'] ?? 0],
        ['selesai', 'Selesai', 'bi-check2-circle', $jumlah['selesai'] ?? 0],
        ['dibatalkan', 'Dibatalkan', 'bi-x-circle', $jumlah['dibatalkan'] ?? 0],
    ];
    $toneStatus = ['terjadwal' => 'info', 'selesai' => 'neutral', 'dibatalkan' => 'danger'];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Jadwal Interview Saya" eyebrow="Rekrutmen" subtitle="Jadwal wawancara kandidat yang ditugaskan kepada Anda.">
            @if ($jumlah['terjadwal'] ?? 0)<x-badge tone="info" dot>{{ $jumlah['terjadwal'] }} terjadwal</x-badge>@endif
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'interview.saya.export', 'eksporTeks' => 'Ekspor Jadwal'])
            </x-slot:actions>
        </x-page-header>

        @if ($jadwals->isEmpty())
            <x-card>
                <x-empty-state icon="bi-calendar2-week" title="Belum ada jadwal interview">Jadwal wawancara yang ditugaskan kepada Anda akan tampil di sini.</x-empty-state>
            </x-card>
        @else
            <div class="lamaran-filter">
                <div class="pub-chips mb-0" role="group" aria-label="Saring menurut status">
                    @foreach ($filter as [$kunci, $label, $ikon, $n])
                        <x-chip :icon="$ikon" :count="$n" :active="$kunci === 'semua'" data-saring-status="{{ $kunci }}">{{ $label }}</x-chip>
                    @endforeach
                </div>
                @include('partials.urut')
            </div>

            <div class="d-grid gap-3" id="daftar-lamaran">
                @foreach ($jadwals as $jadwal)
                    @php $k = $kelompok($jadwal); $hariIni = $jadwal->tanggal->isToday(); @endphp
                    <x-card as="article" class="lamaran-kartu" data-status="{{ $k }}">
                        <div class="lamaran-kartu-isi">
                            <span class="dash-tanggal {{ $hariIni && $k === 'terjadwal' ? 'is-hari-ini' : '' }}">
                                <span>{{ $jadwal->tanggal->translatedFormat('M') }}</span>
                                <strong>{{ $jadwal->tanggal->format('d') }}</strong>
                            </span>
                            <div class="min-w-0 flex-grow-1">
                                <h2 class="lamaran-judul">{{ $jadwal->lamaran?->namaKandidat() ?? 'Kandidat' }}</h2>
                                <p class="lamaran-sub">{{ $jadwal->lamaran?->judulPosisi() }} · {{ $jadwal->tahapLabel() }}</p>
                                <ul class="pub-rincian mt-2">
                                    <li><i class="bi bi-clock" aria-hidden="true"></i>{{ $jadwal->tanggal->translatedFormat('l, d M Y') }}, {{ substr($jadwal->waktu_mulai, 0, 5) }} WIB @if ($hariIni && $k === 'terjadwal')<x-badge tone="danger" dot class="ms-1">Hari ini</x-badge>@endif</li>
                                    @if ($jadwal->lokasi)<li>@include('partials.lokasi', ['lokasi' => $jadwal->lokasi])</li>@endif
                                </ul>
                            </div>
                            <div class="lamaran-kartu-aksi">
                                <div class="d-flex flex-wrap justify-content-end gap-1">
                                    <x-badge :tone="$toneStatus[$k]">{{ $jadwal->statusLabel() }}</x-badge>
                                    <x-status-badge :badge="$jadwal->hasilBadgeClass()">{{ $jadwal->hasilLabel() }}</x-status-badge>
                                </div>
                                @if ($jadwal->lamaran)
                                    @if ($jadwal->isTerjadwal())
                                        <x-button :href="route('admin.lamaran.show', $jadwal->lamaran)" size="sm" icon="bi-pencil-square">Isi Hasil</x-button>
                                    @else
                                        <x-button :href="route('admin.lamaran.show', $jadwal->lamaran)" variant="secondary" size="sm" icon-right="bi-chevron-right">Detail</x-button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </x-card>
                @endforeach
                <x-card data-saring-kosong-status hidden>
                    <x-empty-state icon="bi-funnel" title="Tidak ada jadwal dengan status ini" compact>Pilih status lain atau tampilkan semua jadwal.</x-empty-state>
                </x-card>
            </div>
        @endif
    </div>
</main>
@include('partials.saring-status')
@endsection
