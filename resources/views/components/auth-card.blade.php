{{--
    Kartu halaman tamu (masuk staf, masuk pelamar, daftar, ganti kata sandi): logo · eyebrow · judul · subjudul ·
    pesan galat/peringatan sesi (sebagai x-alert, bukan dialog) · isi · kaki.
    Halaman yang memakainya wajib @section('pakai-banner', '1') dan @section('body-class', 'auth-body').
    <x-auth-card eyebrow="Portal Staf" eyebrow-icon="bi-shield-lock" title="Masuk ke akun Anda" subtitle="..." wide>
        ...form...
        <x-slot:footer>...</x-slot:footer>
    </x-auth-card>
    wide: kartu 560px (form 2 kolom), selain itu 420px.
--}}
@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'eyebrowIcon' => null,
    'wide' => false,
])
<main class="auth-wrap">
    <div {{ $attributes->merge(['class' => 'auth-kartu' . ($wide ? ' auth-kartu-lebar' : '')]) }}>
        <a href="{{ route('lowongan.index') }}" class="auth-brand" aria-label="HRRAB — Yayasan Abdurrab">
            <img src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="" width="40" height="40">
            <span>
                <span class="auth-brand-nama">HRRAB</span>
                <span class="auth-brand-sub">Sistem Informasi SDM · Yayasan Abdurrab</span>
            </span>
        </a>

        @if ($eyebrow)
            <x-badge tone="primary" :icon="$eyebrowIcon" class="mb-3">{{ $eyebrow }}</x-badge>
        @endif
        <h1 class="auth-judul">{{ $title }}</h1>
        @if ($subtitle)<p class="auth-sub">{{ $subtitle }}</p>@endif

        @if (session('error'))
            <x-alert tone="danger" class="mb-4">{{ session('error') }}</x-alert>
        @endif
        @if (session('warning'))
            <x-alert tone="warning" class="mb-4">{{ session('warning') }}</x-alert>
        @endif

        {{ $slot }}

        @isset($footer)
            <div class="auth-kaki">{{ $footer }}</div>
        @endisset
    </div>
    <p class="auth-catatan">&copy; {{ now()->year }} Yayasan Abdurrab · HRRAB</p>
</main>
