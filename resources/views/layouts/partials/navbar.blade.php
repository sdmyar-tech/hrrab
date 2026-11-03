{{--
    Topbar panel: [menu] breadcrumb ··· [Cari menu] [tema] [lonceng] | [akun ▾]
    navAktif & navBeranda dititipkan layouts.partials.sidebar di atribut request; $lonceng diisi view composer (AppServiceProvider).
--}}
@php
    $pengguna = Auth::user();
    $navAktif = request()->attributes->get('navAktif');
    $navBeranda = request()->attributes->get('navBeranda');

    // Judul halaman: @section('title') atau isi <h1> (sama dengan layouts.partials.judul-halaman).
    // yieldContent sudah ter-escape (@section singkat memakai e()); di-decode agar {{ }} di bawah tidak menggandakan &amp;.
    if (! ($judulKini = trim(html_entity_decode($__env->yieldContent('title'), ENT_QUOTES)))) {
        preg_match('/<h1\b[^>]*>(.*?)<\/h1>/s', $__env->yieldContent('content'), $h1);
        $judulKini = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($h1[1] ?? ''), ENT_QUOTES)));
    }

    $beranda = $navBeranda ?? $pengguna?->dashboardRoute();
    $diBeranda = $beranda && request()->routeIs($beranda);
    $remah = [];
    if (! $diBeranda && $beranda) {
        $remah[] = ['label' => 'Beranda', 'url' => route($beranda), 'ikon' => 'bi-house'];
    }
    if ($navAktif) {
        if ($navAktif['bagian']) {
            $remah[] = ['label' => $navAktif['bagian']];
        }
        $diHalamanMenu = request()->routeIs($navAktif['route']);
        $remah[] = ['label' => $navAktif['label'], 'url' => $diHalamanMenu ? null : $navAktif['url']];
        if (! $diHalamanMenu && $judulKini && mb_strtolower($judulKini) !== mb_strtolower($navAktif['label'])) {
            $remah[] = ['label' => $judulKini];
        }
    } else {
        $remah[] = ['label' => $judulKini ?: 'Dashboard'];
    }
@endphp
<header class="app-topbar">
    <button class="app-icon-btn app-menu-btn" type="button" data-sidebar-toggle aria-controls="adminSidebar"
        aria-expanded="true" aria-label="Buka/tutup menu" title="Buka/tutup menu">
        <i class="bi bi-list d-lg-none" aria-hidden="true"></i>
        <i class="bi bi-layout-sidebar d-none d-lg-inline" aria-hidden="true"></i>
    </button>

    <x-breadcrumb class="app-remah" :items="$remah" />

    <div class="app-topbar-aksi">
        <button type="button" class="app-cari" data-bs-toggle="modal" data-bs-target="#paletMenu" aria-label="Cari menu (Ctrl+K)" title="Cari menu (Ctrl+K)">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="app-cari-teks">Cari menu…</span>
            <kbd>Ctrl K</kbd>
        </button>

        <button class="app-icon-btn" type="button" data-theme-toggle aria-label="Ganti mode tampilan" title="Ganti mode tampilan">
            <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
        </button>

        @if ($lonceng ?? null)
            @include('layouts.partials.lonceng')
        @endif

        <span class="app-pemisah" aria-hidden="true"></span>

        <div class="dropdown">
            <button class="app-akun-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                aria-label="Menu akun {{ $pengguna?->name }}">
                <x-avatar :src="$pengguna?->fotoUrl()" :name="$pengguna?->name" size="sm" />
                <span class="app-akun-teks d-none d-md-flex">
                    <span class="app-akun-nama">{{ $pengguna?->name }}</span>
                    <span class="app-akun-peran">{{ $pengguna?->roleLabel() }}</span>
                </span>
                <i class="bi bi-chevron-down small text-muted d-none d-md-inline" aria-hidden="true"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end app-akun-menu">
                <div class="app-akun-kepala">
                    <x-avatar :src="$pengguna?->fotoUrl()" :name="$pengguna?->name" size="md" />
                    <div class="min-w-0">
                        <div class="fw-semibold text-truncate">{{ $pengguna?->name }}</div>
                        <div class="small text-muted text-truncate">{{ $pengguna?->email }}</div>
                        <x-badge tone="neutral" class="mt-1">{{ $pengguna?->roleLabel() }}</x-badge>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person" aria-hidden="true"></i>Profil Saya</a>
                @if ($pengguna && ! $diBeranda)
                    <a class="dropdown-item" href="{{ route($pengguna->dashboardRoute()) }}"><i class="bi bi-house" aria-hidden="true"></i>{{ $pengguna->isPelamar() ? 'Lamaran Saya' : 'Dashboard' }}</a>
                @endif
                <div class="dropdown-divider"></div>
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger"
                        data-confirm="true"
                        data-title="Keluar dari akun?"
                        data-text="Anda perlu masuk lagi untuk melanjutkan."
                        data-confirm-text="Ya, Keluar"
                        data-cancel-text="Batal">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
