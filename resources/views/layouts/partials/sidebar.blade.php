{{--
    Sidebar bersama untuk semua panel. Variabel:
    $menu    = [['bagian' => ?string, 'item' => [[label, ikon, route, polaAktif, tampil, badge, (opsional) badgePeringatan, (opsional) eksternal]]]]
    $beranda = nama route tujuan logo
    Semua bagian selalu terbuka (tanpa dropdown): judul bagian kecil + daftar menu, supaya tugas langsung terlihat.
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
            @php $tampil = array_values(array_filter($grup['item'], fn ($i) => $i[4])); @endphp
            @continue(! $tampil)

            @if ($grup['bagian'])
                <div class="nav-bagian" role="group" aria-label="{{ $grup['bagian'] }}">
                    <div class="nav-bagian-judul">{{ $grup['bagian'] }}</div>
                    @foreach ($tampil as $item)
                        @include('layouts.partials.sidebar-link', ['item' => $item])
                    @endforeach
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
