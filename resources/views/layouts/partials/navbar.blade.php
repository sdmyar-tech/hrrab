            <nav class="navbar admin-navbar navbar-expand bg-white">
                <div class="container-fluid px-3 px-lg-4">
                    <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="adminSidebar"
                        aria-expanded="true" aria-label="Buka/tutup menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <div class="navbar-actions ms-auto">
                        <button class="icon-button theme-toggle" type="button" data-theme-toggle
                            aria-label="Ganti mode tampilan" title="Ganti mode tampilan">
                            <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
                        </button>

                        @if ($lonceng ?? null)
                            @include('layouts.partials.lonceng')
                        @endif

                        <div class="dropdown">
                            <button class="profile-button dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                aria-expanded="false" aria-label="Menu akun {{ Auth::user()->name ?? 'User' }}">
                                <img class="avatar-img avatar-sm" style="object-fit: cover;" src="{{ Auth::user()?->fotoUrl() ?? asset('assets-admin/images/avatar/avatar-fallback.jpg') }}"
                                    alt="{{ Auth::user()->name ?? 'User' }}">
                                <span class="profile-name d-none d-sm-inline">{{ Auth::user()->name ?? 'User' }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('profile') }}">Profil Saya</a></li>
                                <li><span class="dropdown-item-text px-3 py-2 text-muted small">{{ Auth::user()?->roleLabel() ?? 'User' }}</span></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="dropdown-item text-danger"
                                            data-confirm="true"
                                            data-title="Keluar dari akun?"
                                            data-text="Anda perlu masuk lagi untuk melanjutkan."
                                            data-confirm-text="Ya, Keluar"
                                            data-cancel-text="Batal">
                                            Keluar
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
