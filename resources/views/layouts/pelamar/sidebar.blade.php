        <aside class="admin-sidebar" id="adminSidebar" aria-label="Main navigation">
            <div class="sidebar-header">
                <a class="brand-mark" href="{{ route('pelamar.lamaran.index') }}" aria-label="Lamaran Saya">
                    <span class="brand-icon"><i class="bi bi-grid-1x2-fill" aria-hidden="true"></i></span>
                    <span class="brand-copy">
                        <span class="brand-title">Pelamar</span>
                        <span class="brand-subtitle">HRRAB</span>
                    </span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <a class="nav-link {{ request()->routeIs('lowongan.*') ? 'active' : '' }}"
                    href="{{ route('lowongan.index') }}">
                    <span class="nav-icon"><i class="bi bi-briefcase" aria-hidden="true"></i></span>
                    <span class="nav-text">Lowongan</span>
                </a>

                <a class="nav-link {{ request()->routeIs('pelamar.data-pelamar.*') ? 'active' : '' }}"
                    href="{{ route('pelamar.data-pelamar.edit') }}">
                    <span class="nav-icon"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
                    <span class="nav-text">Data Pelamar</span>
                </a>

                <a class="nav-link {{ request()->routeIs('pelamar.lamaran.*') ? 'active' : '' }}"
                    href="{{ route('pelamar.lamaran.index') }}">
                    <span class="nav-icon"><i class="bi bi-send" aria-hidden="true"></i></span>
                    <span class="nav-text">Lamaran Saya</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <span class="sidebar-footer-text">Pelamar login</span>
            </div>
        </aside>
