{{--
    Layout PUBLIK (situs karier): daftar & detail lowongan, jadwal seleksi kandidat (tautan token).
    Bilah atas lengket (logo · Lowongan · akun) · isi selebar maks 1280px · kaki ringan.
    Section: title · content · styles (CSS/meta khusus halaman) · scripts. Stack: head.
    Semua gaya dari token ui.css (bagian "HALAMAN PUBLIK"); tema selalu terang.
--}}
@php
    $pengguna = Auth::user();
    $pelamar = $pengguna?->isPelamar();
@endphp
<!DOCTYPE html>
<html lang="id" data-theme="light" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Karir Yayasan Abdurrab — lowongan kerja terbuka dan pendaftaran pelamar.">
    @include('layouts.partials.favicon')
    @stack('head')
    <title>@yield('title', 'Karir') | HRRAB</title>

    <link rel="stylesheet" href="{{ asset('assets-admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets-admin/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    @include('layouts.partials.ui-css')

    @yield('styles')
</head>

<body class="pub-body">
    <a href="#konten-utama" class="skip-link">Lewati ke konten utama</a>

    <header class="pub-bar">
        <nav class="pub-wadah pub-bar-isi" aria-label="Navigasi situs karier">
            <a class="pub-brand" href="{{ route('lowongan.index') }}">
                <img src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="" width="36" height="36">
                <span>
                    <span class="pub-brand-nama">Karir HRRAB</span>
                    <span class="pub-brand-sub">Yayasan Abdurrab</span>
                </span>
            </a>

            <button class="app-icon-btn d-md-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#pubMenu"
                aria-controls="pubMenu" aria-expanded="false" aria-label="Buka/tutup menu">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <div class="collapse d-md-flex pub-menu" id="pubMenu">
                <a class="pub-tautan {{ request()->routeIs('lowongan.*') ? 'is-active' : '' }}" href="{{ route('lowongan.index') }}"
                    @if (request()->routeIs('lowongan.*')) aria-current="page" @endif>Lowongan</a>
                @if ($pelamar)
                    <a class="pub-tautan" href="{{ route('pelamar.lamaran.index') }}">Lamaran Saya</a>
                    <a class="pub-tautan" href="{{ route('pelamar.data-pelamar.edit') }}">Data Pelamar</a>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm" icon="bi-box-arrow-right" data-confirm="true" data-title="Keluar dari akun?"
                            data-text="Anda perlu masuk lagi untuk melanjutkan." data-confirm-text="Ya, Keluar" data-cancel-text="Batal">Keluar</x-button>
                    </form>
                @elseif ($pengguna)
                    <x-button :href="route($pengguna->dashboardRoute())" size="sm" icon="bi-grid">Buka Panel</x-button>
                @else
                    <x-button :href="route('pelamar.login')" variant="secondary" size="sm">Masuk</x-button>
                    <x-button :href="route('register')" size="sm">Daftar</x-button>
                @endif
            </div>
        </nav>
    </header>

    <main id="konten-utama" tabindex="-1">
        @yield('content')
    </main>

    <footer class="pub-kaki">
        <div class="pub-wadah">
            <div class="pub-kaki-atas">
                <div class="pub-kaki-brand">
                    <a class="pub-brand" href="{{ route('lowongan.index') }}">
                        <img src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="" width="36" height="36" loading="lazy" decoding="async">
                        <span>
                            <span class="pub-brand-nama">Karir HRRAB</span>
                            <span class="pub-brand-sub">Yayasan Abdurrab</span>
                        </span>
                    </a>
                    <p>Portal rekrutmen resmi Yayasan Abdurrab. Temukan posisi yang sesuai dan tumbuh bersama kami.</p>
                </div>
                <nav aria-label="Tautan pelamar">
                    <p class="pub-kaki-judul">Pelamar</p>
                    <ul>
                        <li><a href="{{ route('lowongan.index') }}">Lowongan Terbuka</a></li>
                        @if ($pelamar)
                            <li><a href="{{ route('pelamar.lamaran.index') }}">Lamaran Saya</a></li>
                            <li><a href="{{ route('pelamar.data-pelamar.edit') }}">Data Pelamar</a></li>
                        @else
                            <li><a href="{{ route('register') }}">Daftar Akun</a></li>
                            <li><a href="{{ route('pelamar.login') }}">Masuk Pelamar</a></li>
                        @endif
                    </ul>
                </nav>
            </div>
            <p class="pub-kaki-bawah">&copy; {{ date('Y') }} Yayasan Abdurrab &mdash; Human Resource Management System</p>
        </div>
    </footer>

    <script src="{{ asset('assets-admin/js/bootstrap.bundle.min.js') }}"></script>
    @include('layouts.partials.placeholder-dropdown')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('layouts.partials.sweetalert')
    @include('layouts.partials.tanpa-spasi')

    @yield('scripts')
</body>

</html>
