{{--
    Sidebar bersama untuk semua panel. Variabel:
    $menu    = [['bagian' => ?string, 'item' => [[label, ikon, route, polaAktif, tampil, badge, (opsional) badgePeringatan, (opsional) eksternal]]]]
    $beranda = nama route tujuan logo
--}}
@php $pengguna = Auth::user(); @endphp
<aside class="admin-sidebar" id="adminSidebar" aria-label="Navigasi utama">
    <div class="sidebar-header">
        <a class="brand-mark" href="{{ route($beranda) }}" aria-label="Beranda HRRAB">
            <img src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="" class="brand-logo">
            <span class="brand-copy">
                <span class="brand-title">HRRAB</span>
                <span class="brand-subtitle">Yayasan Abdurrab</span>
            </span>
        </a>
    </div>

    <nav class="sidebar-nav">
        @foreach ($menu as $grup)
            @php
                $tampil = array_values(array_filter($grup['item'], fn ($i) => $i[4]));
                // Bagian bernama dengan ≥ 2 menu → dropdown; menu tunggal cukup tampil sebagai link biasa.
                $dropdown = $grup['bagian'] && count($tampil) > 1;
            @endphp
            @continue(! $tampil)

            @if ($dropdown)
                @php
                    $kunci = \Illuminate\Support\Str::slug($grup['bagian']);
                    $grupAktif = collect($tampil)->contains(fn ($i) => ! ($i[7] ?? false) && request()->routeIs($i[3]));
                    $totalBadge = collect($tampil)->sum(fn ($i) => (int) $i[5]);
                    $adaPeringatan = collect($tampil)->contains(fn ($i) => $i[5] && ($i[6] ?? false));
                @endphp
                <div class="nav-grup {{ $grupAktif ? 'terbuka berisi-aktif' : '' }}" data-nav-grup="{{ $kunci }}">
                    <button type="button" class="nav-link nav-grup-toggle" aria-expanded="{{ $grupAktif ? 'true' : 'false' }}"
                        aria-controls="navsub-{{ $kunci }}" title="{{ $grup['bagian'] }}">
                        <span class="nav-icon"><i class="bi {{ $grup['ikon'] ?? 'bi-folder2' }}" aria-hidden="true"></i></span>
                        <span class="nav-text">{{ $grup['bagian'] }}</span>
                        @if ($totalBadge)
                            <span class="nav-badge nav-badge-grup {{ $adaPeringatan ? 'is-warning' : '' }}" aria-label="{{ $totalBadge }} perlu perhatian">{{ $totalBadge > 99 ? '99+' : $totalBadge }}</span>
                        @endif
                        <i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="nav-sub" id="navsub-{{ $kunci }}">
                        <div class="nav-sub-isi">
                            @foreach ($tampil as $item)
                                @include('layouts.partials.sidebar-link', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                @foreach ($tampil as $item)
                    @include('layouts.partials.sidebar-link', ['item' => $item])
                @endforeach
            @endif
        @endforeach
    </nav>

    <div class="sidebar-footer sidebar-akun">
        <a href="{{ route('profile') }}" class="akun-info" title="Profil {{ $pengguna?->name }}">
            <img src="{{ $pengguna?->fotoUrl() }}" alt="" class="akun-foto">
            <span class="akun-teks">
                <span class="akun-nama">{{ $pengguna?->name }}</span>
                <span class="akun-role">{{ $pengguna?->roleLabel() }}</span>
            </span>
        </a>
        <form action="{{ route('logout') }}" method="POST" class="akun-keluar">
            @csrf
            <button type="submit" class="btn-akun-keluar js-confirm" title="Keluar" aria-label="Keluar"
                data-confirm="true" data-title="Logout" data-text="Apakah Anda yakin ingin keluar dari akun ini?"
                data-confirm-text="Ya, logout" data-cancel-text="Batal">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            </button>
        </form>
    </div>
</aside>
