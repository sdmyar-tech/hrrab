@extends('layouts.public.app')

@section('title', $lowongan->judul)

@section('content')
@php
    $sisa = max(0, $lowongan->jumlah_dibutuhkan - $lowongan->jumlah_terisi);
    $terbuka = $lowongan->isMenerimaLamaran();
    $hariTutup = $lowongan->tanggal_tutup ? (int) now()->startOfDay()->diffInDays($lowongan->tanggal_tutup, false) : null;
    $authUser = Auth::user();
    $pelamar = $authUser?->isPelamar();
    $perluLengkap = $pelamar && ! $authUser->dataPelamar?->isLengkap();
    $bisaLamar = $terbuka && $pelamar && ! $perluLengkap && ! $sudahMelamar;
    $teksBagikan = 'Lowongan ' . $lowongan->judul . ' di Yayasan Abdurrab';
@endphp

<div class="pub-wadah pub-isi pub-detail">
    <x-breadcrumb class="pub-remah" :items="[
        ['label' => 'Lowongan', 'url' => route('lowongan.index'), 'ikon' => 'bi-briefcase'],
        ['label' => $lowongan->unit_kerja, 'url' => route('lowongan.index', ['unit_kerja' => $lowongan->unit_kerja])],
        ['label' => $lowongan->judul],
    ]" />

    {{-- ============ Kepala: badge + judul besar ============ --}}
    <header class="pub-detail-kepala">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <x-badge tone="primary" icon="bi-diagram-3">{{ $lowongan->unit_kerja }}</x-badge>
            @if ($lowongan->tipe_kerja)<x-badge tone="neutral" icon="bi-clock">{{ $lowongan->tipe_kerja }}</x-badge>@endif
            @if ($lowongan->lokasi_kerja)<x-badge tone="neutral" icon="bi-geo-alt">{{ $lowongan->lokasi_kerja }}</x-badge>@endif
            @if (! $terbuka)<x-badge tone="neutral" icon="bi-lock">Sudah ditutup</x-badge>@endif
        </div>
        <h1 class="hr-page-title">{{ $lowongan->judul }}</h1>
        <p class="hr-page-subtitle">Yayasan Abdurrab · {{ $lowongan->jabatan }}</p>
    </header>

    <div class="row g-4 align-items-start">
        {{-- ============ Kiri: isi lowongan ============ --}}
        <div class="col-lg-8">
            <x-card class="pub-artikel">
                @if ($lowongan->deskripsi)
                    <section class="pub-bagian">
                        <h2 class="pub-bagian-judul"><i class="bi bi-file-text" aria-hidden="true"></i>Deskripsi Pekerjaan</h2>
                        <div class="pub-teks">{{ $lowongan->deskripsi }}</div>
                    </section>
                @endif
                @if ($lowongan->kualifikasi)
                    <section class="pub-bagian">
                        <h2 class="pub-bagian-judul"><i class="bi bi-patch-check" aria-hidden="true"></i>Kualifikasi</h2>
                        <div class="pub-teks">{{ $lowongan->kualifikasi }}</div>
                    </section>
                @endif
                @if (! $lowongan->deskripsi && ! $lowongan->kualifikasi)
                    <x-empty-state icon="bi-file-text" title="Rincian menyusul" compact>Detail pekerjaan akan dijelaskan pada tahap seleksi.</x-empty-state>
                @endif
                <section class="pub-bagian">
                    <h2 class="pub-bagian-judul"><i class="bi bi-signpost-split" aria-hidden="true"></i>Alur Melamar</h2>
                    <ol class="pub-langkah">
                        <li><strong>Daftar & lengkapi data pelamar</strong><span>Satu profil dipakai untuk semua lamaran.</span></li>
                        <li><strong>Kirim lamaran</strong><span>Tekan "Lamar Sekarang" di halaman ini.</span></li>
                        <li><strong>Verifikasi & interview</strong><span>Status dan jadwal dapat dipantau di menu Lamaran Saya.</span></li>
                    </ol>
                </section>
            </x-card>
        </div>

        {{-- ============ Kanan: kartu lamar (lengket) ============ --}}
        <aside class="col-lg-4 pub-samping" aria-label="Lamar lowongan ini">
            <x-card class="pub-kartu-lamar">
                <dl class="dash-dl mb-4">
                    @if ($lowongan->tanggal_tutup)
                        <div><dt><i class="bi bi-calendar-event me-1" aria-hidden="true"></i>Batas lamaran</dt>
                            <dd>{{ $lowongan->tanggal_tutup->translatedFormat('d M Y') }}
                                @if ($terbuka && ! is_null($hariTutup) && $hariTutup <= 7)
                                    <x-badge tone="warning" class="ms-1">{{ $hariTutup === 0 ? 'hari ini' : $hariTutup . ' hari lagi' }}</x-badge>
                                @endif
                            </dd></div>
                    @endif
                    <div><dt><i class="bi bi-person-badge me-1" aria-hidden="true"></i>Jabatan</dt><dd>{{ $lowongan->jabatan }}</dd></div>
                    @if ($lowongan->lokasi_kerja)<div><dt><i class="bi bi-geo-alt me-1" aria-hidden="true"></i>Lokasi</dt><dd>{{ $lowongan->lokasi_kerja }}</dd></div>@endif
                    @if ($lowongan->tipe_kerja)<div><dt><i class="bi bi-clock me-1" aria-hidden="true"></i>Tipe kerja</dt><dd>{{ $lowongan->tipe_kerja }}</dd></div>@endif
                    <div><dt><i class="bi bi-people me-1" aria-hidden="true"></i>Kebutuhan</dt><dd>{{ $sisa }} orang</dd></div>
                    @if ($lowongan->dipublikasikan_pada)
                        <div><dt><i class="bi bi-megaphone me-1" aria-hidden="true"></i>Dibuka</dt><dd>{{ $lowongan->dipublikasikan_pada->translatedFormat('d M Y') }}</dd></div>
                    @endif
                </dl>

                @if (! $terbuka)
                    <x-alert tone="info" icon="bi-lock-fill" class="mb-0">Lowongan ini sudah tidak menerima lamaran.</x-alert>
                @elseif ($pelamar)
                    @if ($perluLengkap)
                        <x-button :href="route('pelamar.data-pelamar.edit')" size="lg" block icon="bi-person-lines-fill">Lengkapi Data untuk Melamar</x-button>
                    @elseif ($sudahMelamar)
                        <x-alert tone="success" title="Anda sudah melamar" class="mb-0">
                            <a href="{{ route('pelamar.lamaran.index') }}" class="alert-link">Lihat status lamaran</a>
                        </x-alert>
                    @else
                        <form action="{{ route('pelamar.lamaran.apply', $lowongan) }}" method="POST" id="formLamar" data-confirm="Lamar lowongan ini sekarang?">
                            @csrf
                            <x-button type="submit" size="lg" block icon="bi-send">Lamar Sekarang</x-button>
                        </form>
                    @endif
                @else
                    <div class="d-grid gap-2">
                        <x-button :href="route('register')" size="lg" block icon="bi-person-plus">Daftar untuk Melamar</x-button>
                        <x-button :href="route('pelamar.login')" variant="secondary" block>Sudah punya akun? Masuk</x-button>
                    </div>
                @endif

                <x-slot:footer>
                    <div class="pub-bagikan">
                        <span class="small text-muted">Bagikan</span>
                        <div class="d-flex gap-1">
                            <a class="app-icon-btn" href="https://wa.me/?text={{ urlencode($teksBagikan . ' ' . url()->current()) }}" target="_blank" rel="noopener" title="Bagikan lewat WhatsApp" aria-label="Bagikan lewat WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
                            <button type="button" class="app-icon-btn" data-salin-tautan="{{ url()->current() }}" title="Salin tautan" aria-label="Salin tautan lowongan"><i class="bi bi-link-45deg" aria-hidden="true"></i></button>
                            <button type="button" class="app-icon-btn" data-bagikan hidden title="Bagikan" aria-label="Bagikan lowongan"><i class="bi bi-share" aria-hidden="true"></i></button>
                        </div>
                    </div>
                </x-slot:footer>
            </x-card>
        </aside>
    </div>
</div>

{{-- ============ HP: tombol aksi lengket di bawah ============ --}}
@if ($terbuka && ! $sudahMelamar)
    <div class="pub-cta-bawah" role="region" aria-label="Aksi cepat">
        <div class="min-w-0">
            <p class="pub-cta-judul">{{ $lowongan->judul }}</p>
            @if ($lowongan->tanggal_tutup)<p class="pub-cta-sub">Tutup {{ $lowongan->tanggal_tutup->translatedFormat('d M Y') }}</p>@endif
        </div>
        @if ($bisaLamar)
            <x-button type="submit" form="formLamar" icon="bi-send">Lamar</x-button>
        @elseif ($perluLengkap)
            <x-button :href="route('pelamar.data-pelamar.edit')" icon="bi-person-lines-fill">Lengkapi Data</x-button>
        @elseif (! $pelamar)
            <x-button :href="route('register')" icon="bi-person-plus">Daftar & Lamar</x-button>
        @endif
    </div>
@endif
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const salin = document.querySelector('[data-salin-tautan]');
        salin?.addEventListener('click', async function () {
            try {
                await navigator.clipboard.writeText(salin.dataset.salinTautan);
                window.hrToast && hrToast('success', 'Tautan lowongan disalin.');
            } catch (e) {
                window.hrToast && hrToast('error', 'Gagal menyalin. Salin alamat dari bilah browser.');
            }
        });
        const bagikan = document.querySelector('[data-bagikan]');
        if (bagikan && navigator.share) {
            bagikan.hidden = false;
            bagikan.addEventListener('click', () => navigator.share({ title: document.title, url: location.href }).catch(() => {}));
        }
    });
</script>
@endsection
