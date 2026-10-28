{{--
    Sidebar bersama untuk semua panel. Variabel:
    $menu    = [['bagian' => ?string, 'ikon' => ?string, 'item' => [[label, ikon, route, polaAktif, tampil, badge, (opsional) badgePeringatan, (opsional) eksternal]]]]
    $beranda = nama route tujuan logo
    Bagian = menu induk yang bisa dibuka-tutup (dropdown) berisi submenu. Bagian yang memuat halaman aktif selalu terbuka;
    pilihan buka/tutup lainnya diingat browser. Saat tertutup, jumlah tugas (badge) submenu tampil di menu induknya.
--}}
@php
    $pengguna = Auth::user();
    $ikonBagian = [
        'Tugas Saya' => 'bi-check2-square', 'Data Pengajuan' => 'bi-inbox', 'Pengajuan Saya' => 'bi-send', 'Data Saya' => 'bi-person-vcard',
        'Kepegawaian' => 'bi-people', 'Absensi' => 'bi-fingerprint', 'Rekrutmen' => 'bi-person-plus', 'Informasi' => 'bi-megaphone',
        'Sistem' => 'bi-gear', 'Lowongan' => 'bi-briefcase',
    ];
@endphp
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
                @php
                    $idGrup = 'nav-grup-' . \Illuminate\Support\Str::slug($grup['bagian']);
                    $adaAktif = collect($tampil)->contains(fn ($i) => ! ($i[7] ?? false) && request()->routeIs($i[3]));
                    $berbadge = collect($tampil)->filter(fn ($i) => $i[5]);
                    $totalBadge = $berbadge->sum(fn ($i) => (int) $i[5]);
                @endphp
                <div class="nav-grup {{ $adaAktif ? 'terbuka' : '' }}" data-nav-grup="{{ $idGrup }}" data-aktif="{{ $adaAktif ? 1 : 0 }}">
                    <button type="button" class="nav-link nav-grup-tombol" aria-expanded="{{ $adaAktif ? 'true' : 'false' }}" aria-controls="{{ $idGrup }}"
                        title="{{ $grup['bagian'] }}">
                        <span class="nav-icon"><i class="bi {{ $grup['ikon'] ?? $ikonBagian[$grup['bagian']] ?? 'bi-folder2' }}" aria-hidden="true"></i></span>
                        <span class="nav-text">{{ $grup['bagian'] }}</span>
                        @if ($totalBadge)
                            <span class="nav-badge nav-badge-grup {{ $berbadge->every(fn ($i) => $i[6] ?? false) ? 'is-warning' : '' }}"
                                aria-label="{{ $totalBadge }} perlu perhatian">{{ $totalBadge > 99 ? '99+' : $totalBadge }}</span>
                        @endif
                        <i class="bi bi-chevron-down nav-panah" aria-hidden="true"></i>
                    </button>
                    <div class="nav-sub" id="{{ $idGrup }}" role="group" aria-label="{{ $grup['bagian'] }}">
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
<script>
    // Dropdown menu sidebar: dijalankan langsung (bukan DOMContentLoaded) agar status tersimpan terpasang sebelum tampil.
    (function () {
        const KUNCI = 'hrrab.sidebar.grup';
        let simpan = {};
        try { simpan = JSON.parse(localStorage.getItem(KUNCI) || '{}') || {}; } catch (e) { simpan = {}; }

        const atur = function (grup, buka) {
            grup.classList.toggle('terbuka', buka);
            grup.querySelector('.nav-grup-tombol').setAttribute('aria-expanded', String(buka));
        };

        document.querySelectorAll('#adminSidebar [data-nav-grup]').forEach(function (grup) {
            const id = grup.dataset.navGrup;
            // Grup berisi halaman aktif selalu terbuka; lainnya mengikuti pilihan terakhir pengguna.
            if (grup.dataset.aktif !== '1' && typeof simpan[id] === 'boolean') {
                atur(grup, simpan[id]);
            }
            grup.querySelector('.nav-grup-tombol').addEventListener('click', function () {
                const buka = !grup.classList.contains('terbuka');
                atur(grup, buka);
                simpan[id] = buka;
                try { localStorage.setItem(KUNCI, JSON.stringify(simpan)); } catch (e) { /* mode privat: tidak diingat */ }
            });
        });
    })();
</script>
