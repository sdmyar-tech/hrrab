            @php
                $u = Auth::user();
                $menu = [
                    ['bagian' => null, 'item' => [
                        ['Dashboard', 'bi-speedometer2', 'admin.dashboard', 'admin.dashboard', true, null],
                    ]],
                    ['bagian' => 'Persetujuan & Tugas', 'item' => [
                        ['Persetujuan Cuti', 'bi-calendar2-check', 'admin.pengajuan-cuti.index', 'admin.pengajuan-cuti.*', $u->can('lihat-laporan'), $hitung['cuti'] ?? null],
                        ['Persetujuan Lembur', 'bi-clock-history', 'admin.pengajuan-lembur.index', 'admin.pengajuan-lembur.*', $u->can('lihat-laporan'), $hitung['lembur'] ?? null],
                        ['Jadwal Interview Saya', 'bi-calendar2-week', 'interview.saya', 'interview.saya', $u->bisaMenginterview(), $hitung['interview'] ?? null],
                    ]],
                    ['bagian' => 'Pengajuan Saya', 'item' => [
                        ['Ajukan Cuti', 'bi-calendar-plus', 'pegawai.pengajuan-cuti', 'pegawai.pengajuan-cuti*', $u->can('ajukan-cuti'), null],
                        ['Ajukan Lembur', 'bi-stopwatch', 'pegawai.pengajuan-lembur', 'pegawai.pengajuan-lembur*', $u->can('ajukan-lembur'), null],
                    ]],
                    ['bagian' => 'Kepegawaian', 'item' => [
                        ['Data Pegawai', 'bi-people', 'admin.pegawai.index', 'admin.pegawai.*', $u->can('lihat-tabel-karyawan'), null],
                        ['Mutasi & Rangkap', 'bi-arrow-left-right', 'admin.penugasan.index', 'admin.penugasan.*', $u->can('kelola-penugasan'), null],
                    ]],
                    ['bagian' => 'Rekrutmen', 'item' => [
                        ['Pengajuan Formasi', 'bi-person-plus', 'admin.pengajuan-formasi.index', 'admin.pengajuan-formasi.*', true, $hitung['formasi'] ?? null],
                        ['Lamaran Masuk', 'bi-inbox', 'admin.lamaran.index', 'admin.lamaran.*', true, $hitung['lamaran'] ?? null],
                        ['Lowongan', 'bi-briefcase', 'admin.lowongan.index', 'admin.lowongan.*', true, null],
                    ]],
                    ['bagian' => 'Informasi', 'item' => [
                        ['Pengumuman', 'bi-megaphone', 'pengumuman.index', 'pengumuman.*', true, $hitung['pengumuman'] ?? null],
                        ['Notifikasi', 'bi-bell', 'notifikasi.index', 'notifikasi.index', true, $hitung['notifikasi'] ?? null],
                        ['Kirim Notifikasi', 'bi-send', 'notifikasi.kirim', 'notifikasi.kirim*', $u->can('kirim-notifikasi'), null],
                    ]],
                    ['bagian' => 'Sistem', 'item' => [
                        ['Kelola Akun', 'bi-person-lock', 'admin.users.index', 'admin.users.*', $u->can('kelola-sistem'), null],
                        ['Master Jabatan', 'bi-diagram-3', 'admin.jabatan.index', 'admin.jabatan.*', $u->can('kelola-sistem'), null],
                    ]],
                ];
            @endphp
            <aside class="admin-sidebar" id="adminSidebar" aria-label="Main navigation">
                <div class="sidebar-header">
                    <a class="brand-mark" href="{{ route('admin.dashboard') }}" aria-label="Dashboard">
                        <img src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="logo"
                            style="width: 32px; height: 32px; object-fit: contain;">
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
                            <a class="nav-link {{ $aktif ? 'active' : '' }}" href="{{ route($route) }}" @if ($aktif) aria-current="page" @endif
                                title="{{ $label }}">
                                <span class="nav-icon"><i class="bi {{ $ikon }}" aria-hidden="true"></i></span>
                                <span class="nav-text">{{ $label }}</span>
                                @if ($badge)
                                    <span class="nav-badge" aria-label="{{ $badge }} menunggu">{{ $badge > 99 ? '99+' : $badge }}</span>
                                @endif
                            </a>
                        @endforeach
                    @endforeach
                </nav>

                <div class="sidebar-footer">
                    <span class="sidebar-footer-text">{{ $u?->roleLabel() ?? 'User' }} login</span>
                </div>
            </aside>
