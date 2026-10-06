        @php
            $menu = [
                ['Lamaran Saya', 'bi-send', 'pelamar.lamaran.index', 'pelamar.lamaran.*', $hitungPelamar['lamaran'] ?? null],
                ['Data Pelamar', 'bi-person-vcard', 'pelamar.data-pelamar.edit', 'pelamar.data-pelamar.*', null],
                ['Notifikasi', 'bi-bell', 'notifikasi.index', 'notifikasi.*', $hitungPelamar['notifikasi'] ?? null],
                ['Profil Akun', 'bi-person-circle', 'profile', 'profile', null],
            ];
        @endphp
        <aside class="admin-sidebar" id="adminSidebar" aria-label="Main navigation">
            <div class="sidebar-header">
                <a class="brand-mark" href="{{ route('pelamar.lamaran.index') }}" aria-label="Lamaran Saya">
                    <img src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="logo" style="width: 32px; height: 32px; object-fit: contain;">
                    <span class="brand-copy">
                        <span class="brand-title">Pelamar</span>
                        <span class="brand-subtitle">HRRAB</span>
                    </span>
                </a>
            </div>

            <nav class="sidebar-nav">
                @foreach ($menu as [$label, $ikon, $route, $pola, $badge])
                    @php $aktif = request()->routeIs($pola); @endphp
                    <a class="nav-link {{ $aktif ? 'active' : '' }}" href="{{ route($route) }}" @if ($aktif) aria-current="page" @endif title="{{ $label }}">
                        <span class="nav-icon"><i class="bi {{ $ikon }}" aria-hidden="true"></i></span>
                        <span class="nav-text">{{ $label }}</span>
                        @if ($badge)
                            <span class="nav-badge">{{ $badge > 99 ? '99+' : $badge }}</span>
                        @endif
                    </a>
                @endforeach

                <div class="nav-section">Lowongan</div>
                <a class="nav-link" href="{{ route('lowongan.index') }}" title="Cari Lowongan">
                    <span class="nav-icon"><i class="bi bi-briefcase" aria-hidden="true"></i></span>
                    <span class="nav-text">Cari Lowongan</span>
                    <i class="bi bi-box-arrow-up-right ms-auto small opacity-75 nav-text" aria-hidden="true"></i>
                </a>
            </nav>

            <div class="sidebar-footer">
                <span class="sidebar-footer-text">Pelamar login</span>
            </div>
        </aside>
