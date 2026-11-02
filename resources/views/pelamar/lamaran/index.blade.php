@extends('layouts.pelamar.app')

@section('title', 'Lamaran Saya')

@section('content')
@use('App\Models\Lamaran')
@php
    $toneStatus = [
        Lamaran::STATUS_MENUNGGU_VERIFIKASI => 'warning', Lamaran::STATUS_PROSES => 'info',
        Lamaran::STATUS_DITERIMA => 'success', Lamaran::STATUS_DITOLAK => 'danger', Lamaran::STATUS_DIBATALKAN => 'neutral',
    ];
    // Kelompok filter (semua lamaran sudah dimuat → disaring langsung di halaman).
    $kelompok = fn (Lamaran $l) => match ($l->status) {
        Lamaran::STATUS_DITERIMA => 'diterima',
        Lamaran::STATUS_DITOLAK => 'ditolak',
        Lamaran::STATUS_DIBATALKAN => 'dibatalkan',
        default => 'proses',
    };
    $jumlah = $lamarans->countBy($kelompok);
    $filter = [
        ['semua', 'Semua', 'bi-grid', $lamarans->count()],
        ['proses', 'Diproses', 'bi-hourglass-split', $jumlah['proses'] ?? 0],
        ['diterima', 'Diterima', 'bi-check-circle', $jumlah['diterima'] ?? 0],
        ['ditolak', 'Ditolak', 'bi-x-circle', $jumlah['ditolak'] ?? 0],
        ['dibatalkan', 'Dibatalkan', 'bi-slash-circle', $jumlah['dibatalkan'] ?? 0],
    ];
    $urutanTahap = [Lamaran::TAHAP_VERIFIKASI, Lamaran::TAHAP_INTERVIEW, Lamaran::TAHAP_SCREENING, Lamaran::TAHAP_KONTRAK, Lamaran::TAHAP_SELESAI];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Lamaran Saya" eyebrow="Pelamar" subtitle="Pantau status setiap lamaran dan jadwal seleksi Anda.">
            <x-badge tone="neutral" icon="bi-send">{{ $lamarans->count() }} lamaran</x-badge>
            @if ($jumlah['proses'] ?? 0)<x-badge tone="info" dot>{{ $jumlah['proses'] }} sedang diproses</x-badge>@endif
            <x-slot:actions>
                @if ($lamarans->isNotEmpty())
                    @include('partials.tombol-ekspor', ['eksporRoute' => 'pelamar.lamaran.export', 'eksporTeks' => 'Ekspor'])
                @endif
                <x-button :href="route('lowongan.index')" icon="bi-briefcase">Cari Lowongan</x-button>
            </x-slot:actions>
        </x-page-header>

        @if ($lamarans->isEmpty())
            <x-card>
                <x-empty-state icon="bi-send" title="Belum ada lamaran">
                    Lamaran yang Anda kirim akan tampil di sini lengkap dengan status dan jadwal seleksinya.
                    <x-slot:action>
                        <x-button :href="route('pelamar.data-pelamar.edit')" variant="secondary" icon="bi-person-vcard">Lengkapi Data</x-button>
                        <x-button :href="route('lowongan.index')" icon="bi-briefcase">Lihat Lowongan</x-button>
                    </x-slot:action>
                </x-empty-state>
            </x-card>
        @else
            {{-- ============ Filter status + urutan ============ --}}
            <div class="lamaran-filter">
                <div class="pub-chips mb-0" role="group" aria-label="Saring menurut status">
                    @foreach ($filter as [$kunci, $label, $ikon, $n])
                        <x-chip :icon="$ikon" :count="$n" :active="$kunci === 'semua'" data-saring-status="{{ $kunci }}">{{ $label }}</x-chip>
                    @endforeach
                </div>
                @if ($urutOpsi) @include('partials.urut') @endif
            </div>

            {{-- ============ Daftar kartu lamaran ============ --}}
            <div class="d-grid gap-3" id="daftar-lamaran">
                @foreach ($lamarans as $lamaran)
                    @php
                        $judul = $lamaran->lowongan?->judul ?? $lamaran->jabatanTujuan();
                        $posisiTahap = array_search($lamaran->tahap ?? Lamaran::TAHAP_VERIFIKASI, $urutanTahap, true) ?: 0;
                    @endphp
                    <x-card as="article" class="lamaran-kartu hr-card-hover" data-status="{{ $kelompok($lamaran) }}">
                        <div class="lamaran-kartu-isi">
                            <span class="hr-stat-ikon hr-tone-{{ $toneStatus[$lamaran->status] ?? 'neutral' }}"><i class="bi bi-briefcase" aria-hidden="true"></i></span>
                            <div class="min-w-0 flex-grow-1">
                                <h2 class="lamaran-judul">
                                    <a href="{{ route('pelamar.lamaran.show', $lamaran) }}" class="stretched-link">{{ $judul }}</a>
                                </h2>
                                <p class="lamaran-sub">{{ $lamaran->lowongan?->unit_kerja ?? $lamaran->unitKerjaTujuan() }} · Dikirim {{ $lamaran->created_at->translatedFormat('d M Y') }}</p>
                                @if ($lamaran->isProses() || $lamaran->status === Lamaran::STATUS_MENUNGGU_VERIFIKASI)
                                    {{-- Penunjuk tahap ringkas: verifikasi → interview → screening → kontrak → selesai --}}
                                    <div class="lamaran-tahap" aria-label="Tahap {{ $posisiTahap + 1 }} dari 5: {{ $lamaran->tahapLabel() }}">
                                        @foreach ($urutanTahap as $i => $t)
                                            <span class="{{ $i < $posisiTahap ? 'is-lewat' : ($i === $posisiTahap ? 'is-kini' : '') }}"></span>
                                        @endforeach
                                        <small>{{ $lamaran->tahapLabel() }}</small>
                                    </div>
                                @endif
                                @if ($lamaran->catatan_admin)
                                    <p class="lamaran-catatan"><i class="bi bi-chat-left-text" aria-hidden="true"></i>{{ \Illuminate\Support\Str::limit($lamaran->catatan_admin, 140) }}</p>
                                @endif
                            </div>
                            <div class="lamaran-kartu-aksi">
                                <x-badge :tone="$toneStatus[$lamaran->status] ?? 'neutral'">{{ $lamaran->statusLabel() }}</x-badge>
                                <div class="d-flex gap-1">
                                    @if (! $lamaran->isSelesai())
                                        <form action="{{ route('pelamar.lamaran.tolak', $lamaran) }}" method="POST" class="m-0 position-relative" style="z-index: 2;">
                                            @csrf
                                            <x-button type="submit" variant="ghost" size="sm" icon="bi-x-lg" class="text-danger"
                                                data-confirm="true" data-icon="warning" data-title="Batalkan lamaran?"
                                                data-text="Lamaran untuk {{ $judul }} akan dibatalkan dan tidak diproses lagi."
                                                data-confirm-text="Ya, Batalkan" data-cancel-text="Kembali">Batalkan</x-button>
                                        </form>
                                    @endif
                                    <x-button :href="route('pelamar.lamaran.show', $lamaran)" variant="secondary" size="sm" icon-right="bi-chevron-right" class="position-relative" style="z-index: 2;">Detail</x-button>
                                </div>
                            </div>
                        </div>
                    </x-card>
                @endforeach
                <x-card data-saring-kosong-status hidden>
                    <x-empty-state icon="bi-funnel" title="Tidak ada lamaran dengan status ini" compact>
                        Pilih status lain atau tampilkan semua lamaran.
                    </x-empty-state>
                </x-card>
            </div>
        @endif
    </div>
</main>

@include('partials.saring-status')
@endsection
