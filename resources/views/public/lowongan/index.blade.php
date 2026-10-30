@extends('layouts.public.app')

@section('title', 'Lowongan Karir')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'unit_kerja']);
    // Chip bagian: pertahankan kata kunci & urutan, reset halaman.
    $urlBagian = fn (?string $unit) => route('lowongan.index', array_filter([
        'q' => request('q'), 'unit_kerja' => $unit, 'urut' => request('urut'),
    ]));
@endphp

{{-- ============ Hero: judul + kotak cari besar ============ --}}
<section class="pub-hero">
    <div class="pub-wadah">
        <x-badge tone="primary" icon="bi-stars">Karir di Yayasan Abdurrab</x-badge>
        <h1 class="pub-hero-judul">Bangun karir, tumbuh bersama kami.</h1>
        <p class="pub-hero-sub">Temukan posisi yang sesuai dengan keahlian Anda dan jadilah bagian dari keluarga besar Yayasan Abdurrab.</p>

        <form action="{{ route('lowongan.index') }}" method="GET" id="filterLowonganPublik" class="pub-cari" role="search" data-muat-lowongan>
            @if (request('unit_kerja'))<input type="hidden" name="unit_kerja" value="{{ request('unit_kerja') }}">@endif
            <label for="cari-lowongan" class="visually-hidden">Cari lowongan</label>
            <i class="bi bi-search pub-cari-ikon" aria-hidden="true"></i>
            <input type="search" id="cari-lowongan" name="q" value="{{ request('q') }}" class="pub-cari-isian"
                placeholder="Cari posisi, contoh: Guru" autocomplete="off">
            <x-button type="submit" size="lg" icon="bi-search" class="pub-cari-tombol">Cari</x-button>
        </form>

        <p class="pub-hero-statistik">
            <span><strong>{{ number_format($lowongans->total(), 0, ',', '.') }}</strong> lowongan {{ $adaFilter ? 'cocok' : 'terbuka' }}</span>
            <span aria-hidden="true">·</span>
            <span><strong>{{ $unitKerjaOptions->count() }}</strong> bagian membuka posisi</span>
        </p>
    </div>
</section>

<div class="pub-wadah pub-isi">
    {{-- ============ Filter chip bagian + urutan ============ --}}
    @if ($unitKerjaOptions->isNotEmpty())
        <nav class="pub-chips" aria-label="Saring menurut bagian" data-muat-lowongan>
            <x-chip :href="$urlBagian(null)" :active="! request('unit_kerja')" icon="bi-grid">Semua bagian</x-chip>
            @foreach ($unitKerjaOptions as $opt)
                <x-chip :href="$urlBagian($opt)" :active="request('unit_kerja') === $opt">{{ $opt }}</x-chip>
            @endforeach
        </nav>
    @endif

    <div class="hr-section-head mt-4">
        <div>
            <h2 class="hr-section-title">{{ $adaFilter ? 'Hasil pencarian' : 'Lowongan terbaru' }}</h2>
            <p class="hr-section-sub">
                @if ($adaFilter)
                    {{ $lowongans->total() }} lowongan
                    @if (request('q')) untuk “{{ request('q') }}”@endif
                    @if (request('unit_kerja')) di bagian {{ request('unit_kerja') }}@endif
                    · <a href="{{ route('lowongan.index') }}" class="fw-semibold">Hapus filter</a>
                @else
                    Diperbarui otomatis saat lowongan baru dibuka.
                @endif
            </p>
        </div>
        @include('partials.urut', ['urutForm' => 'filterLowonganPublik'])
    </div>

    {{-- ============ Grid kartu lowongan ============ --}}
    <div class="row g-3" data-daftar-lowongan>
        @forelse ($lowongans as $lowongan)
            @php
                $sisa = max(0, $lowongan->jumlah_dibutuhkan - $lowongan->jumlah_terisi);
                $baru = $lowongan->dipublikasikan_pada && $lowongan->dipublikasikan_pada->gt(now()->subDays(7));
                $hariTutup = $lowongan->tanggal_tutup ? (int) now()->startOfDay()->diffInDays($lowongan->tanggal_tutup, false) : null;
                $segeraTutup = ! is_null($hariTutup) && $hariTutup <= 7;
            @endphp
            <div class="col-md-6 col-xl-4">
                <x-card as="article" :href="route('lowongan.show', $lowongan)" :title="$lowongan->judul" title-tag="h3"
                    :subtitle="$lowongan->unit_kerja" class="h-100 pub-kartu-lowongan">
                    <x-slot:actions>
                        @if ($baru)<x-badge tone="success" dot>Baru</x-badge>@endif
                    </x-slot:actions>
                    <ul class="pub-meta">
                        <li><i class="bi bi-person-badge" aria-hidden="true"></i>{{ $lowongan->jabatan }}</li>
                        @if ($lowongan->lokasi_kerja)<li><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $lowongan->lokasi_kerja }}</li>@endif
                        @if ($lowongan->tipe_kerja)<li><i class="bi bi-clock" aria-hidden="true"></i>{{ $lowongan->tipe_kerja }}</li>@endif
                        <li><i class="bi bi-people" aria-hidden="true"></i>Butuh {{ $sisa }} orang</li>
                    </ul>
                    <x-slot:footer>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            @if (is_null($hariTutup))
                                <x-badge tone="neutral" icon="bi-calendar-check">Dibuka tanpa batas</x-badge>
                            @elseif ($segeraTutup)
                                <x-badge tone="warning" icon="bi-hourglass-split">{{ $hariTutup === 0 ? 'Tutup hari ini' : 'Tutup ' . $hariTutup . ' hari lagi' }}</x-badge>
                            @else
                                <x-badge tone="neutral" icon="bi-calendar-event">Tutup {{ $lowongan->tanggal_tutup->translatedFormat('d M Y') }}</x-badge>
                            @endif
                            <span class="pub-lihat">Lihat detail <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                        </div>
                    </x-slot:footer>
                </x-card>
            </div>
        @empty
            <div class="col-12">
                <x-card>
                    @if ($adaFilter)
                        <x-empty-state icon="bi-search" title="Lowongan tidak ditemukan">
                            Coba kata kunci lain atau pilih bagian yang berbeda.
                            <x-slot:action><x-button :href="route('lowongan.index')" variant="secondary" icon="bi-x-lg">Hapus filter</x-button></x-slot:action>
                        </x-empty-state>
                    @else
                        <x-empty-state icon="bi-briefcase" title="Belum ada lowongan dibuka">
                            Silakan cek kembali dalam waktu dekat. Daftarkan akun agar siap melamar saat lowongan dibuka.
                            @unless (Auth::check())
                                <x-slot:action><x-button :href="route('register')" icon="bi-person-plus">Daftar Akun Pelamar</x-button></x-slot:action>
                            @endunless
                        </x-empty-state>
                    @endif
                </x-card>
            </div>
        @endforelse
    </div>

    {{-- Kerangka pemuatan: tampil menggantikan daftar saat filter/cari dikirim. --}}
    <div class="row g-3" data-kerangka-lowongan hidden>
        @for ($i = 0; $i < 3; $i++)
            <div class="col-md-6 col-xl-4"><x-skeleton variant="card" /></div>
        @endfor
    </div>

    <x-pagination :paginator="$lowongans" class="mt-4" />

    {{-- Ajakan untuk tamu --}}
    @unless (Auth::check())
        <x-card class="pub-ajakan mt-5">
            <div class="pub-ajakan-isi">
                <span class="hr-stat-ikon hr-tone-primary"><i class="bi bi-person-plus" aria-hidden="true"></i></span>
                <div class="flex-grow-1">
                    <p class="hr-card-title mb-1">Siapkan profil sekali, lamar berkali-kali</p>
                    <p class="hr-card-subtitle mb-0">Daftar akun pelamar, lengkapi data diri, lalu lamar lowongan cukup dengan satu klik.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <x-button :href="route('pelamar.login')" variant="secondary">Masuk</x-button>
                    <x-button :href="route('register')" icon="bi-person-plus">Daftar Sekarang</x-button>
                </div>
            </div>
        </x-card>
    @endunless
</div>
@endsection

@section('scripts')
<script>
    // Umpan balik saat filter/cari dikirim: daftar diganti kerangka pemuatan sampai halaman baru tampil.
    document.addEventListener('DOMContentLoaded', function () {
        const daftar = document.querySelector('[data-daftar-lowongan]');
        const kerangka = document.querySelector('[data-kerangka-lowongan]');
        const muat = function () { if (daftar && kerangka) { daftar.hidden = true; kerangka.hidden = false; } };
        document.querySelectorAll('form[data-muat-lowongan]').forEach((f) => f.addEventListener('submit', muat));
        document.querySelectorAll('[data-muat-lowongan] a').forEach((a) => a.addEventListener('click', (e) => { if (!e.ctrlKey && !e.metaKey) muat(); }));
        document.querySelectorAll('select[name="urut"]').forEach((s) => s.addEventListener('change', muat));
        window.addEventListener('pageshow', function (e) { if (e.persisted && daftar) { daftar.hidden = false; kerangka.hidden = true; } });
    });
</script>
@endsection
