        @php
            $u = Auth::user();
            $menu = [
                ['bagian' => null, 'item' => [
                    ['Dashboard', 'bi-speedometer2', 'pegawai.dashboard', 'pegawai.dashboard', true, null],
                ]],
                ['bagian' => 'Pengajuan', 'item' => [
                    ['Pengajuan Cuti', 'bi-calendar2-check', 'pegawai.pengajuan-cuti', 'pegawai.pengajuan-cuti*', $u?->can('ajukan-cuti'), null],
                    ['Pengajuan Lembur', 'bi-clock-history', 'pegawai.pengajuan-lembur', 'pegawai.pengajuan-lembur*', $u?->can('ajukan-lembur'), null],
                ]],
                ['bagian' => 'Tugas SDM', 'item' => [
                    ['Kelola Data Pegawai', 'bi-people', 'admin.pegawai.index', 'admin.pegawai.*', $u?->can('kelola-pegawai'), null],
                ]],
                ['bagian' => 'Informasi', 'item' => [
                    ['Pengumuman', 'bi-megaphone', 'pengumuman.index', 'pengumuman.*', true, $hitung['pengumuman'] ?? null],
                    ['Notifikasi', 'bi-bell', 'notifikasi.index', 'notifikasi.*', true, $hitung['notifikasi'] ?? null],
                ]],
                ['bagian' => 'Data Saya', 'item' => [
                    ['Data Pegawai', 'bi-person-vcard', 'pegawai.data-pegawai', 'pegawai.data-pegawai', true, null],
                    ['Pengkinian Data', 'bi-pencil-square', 'pegawai.pengkinian-data', 'pegawai.pengkinian-data', true, $hitung['data_kurang'] ?? null],
                ]],
            ];
        @endphp
        <aside class="admin-sidebar" id="adminSidebar" aria-label="Main navigation">
            <div class="sidebar-header">
                <a class="brand-mark" href="{{ route('pegawai.dashboard') }}" aria-label="Dashboard">
                    <img src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="logo" style="width: 32px; height: 32px; object-fit: contain;">
                    <span class="brand-copy">
                        <span class="brand-title">{{ $u?->roleLabel() ?? 'User' }}</span>
                        <span class="brand-subtitle">HRRAB</span>
                    </span>
                </a>
            </div>

            <nav class="sidebar-nav">
                @foreach ($menu as $grup)
                    @php $tampil = array_filter($grup['item'], fn ($i) => $i[4]); @endphp
                    @continue(! $tampil)
                    @if ($grup['bagian'])
                        <div class="nav-section">{{ $grup['bagian'] }}</div>
                    @endif
                    @foreach ($tampil as [$label, $ikon, $route, $pola, $_, $badge])
                        @php $aktif = request()->routeIs($pola); @endphp
                        <a class="nav-link {{ $aktif ? 'active' : '' }}" href="{{ route($route) }}" @if ($aktif) aria-current="page" @endif title="{{ $label }}">
                            <span class="nav-icon"><i class="bi {{ $ikon }}" aria-hidden="true"></i></span>
                            <span class="nav-text">{{ $label }}</span>
                            @if ($badge)
                                <span class="nav-badge {{ $route === 'pegawai.pengkinian-data' ? 'is-warning' : '' }}"
                                    aria-label="{{ $route === 'pegawai.pengkinian-data' ? $badge . ' isian wajib kosong' : $badge . ' belum dibaca' }}">{{ $badge > 99 ? '99+' : $badge }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="sidebar-footer">
                <span class="sidebar-footer-text">{{ $u?->roleLabel() ?? 'User' }} login</span>
            </div>
        </aside>
