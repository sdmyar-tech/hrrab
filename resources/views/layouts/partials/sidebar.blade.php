{{--
    Sidebar bersama untuk semua panel. Variabel:
    $menu    = [['bagian' => ?string, 'ikon' => ?string, 'item' => [[label, ikon, route, polaAktif, tampil, badge, (opsional) badgePeringatan, (opsional) eksternal]]]]
    $beranda = nama route tujuan logo
    Bagian = menu induk yang bisa dibuka-tutup (dropdown) berisi submenu. Setiap halaman dibuka semua menu tertutup,
    kecuali menu berisi halaman aktif; membuka satu menu menutup menu lain (akordeon). Saat tertutup, jumlah tugas
    (badge) submenu tampil di menu induknya.
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
            <button type="submit" class="btn-akun-keluar" title="Keluar" aria-label="Keluar"
                data-confirm="true" data-title="Keluar dari akun?" data-text="Anda perlu masuk lagi untuk melanjutkan."
                data-confirm-text="Ya, Keluar" data-cancel-text="Batal">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            </button>
        </form>
    </div>
</aside>
<script>
    // Dropdown menu sidebar (akordeon): satu menu terbuka pada satu waktu; status tidak diingat antarhalaman.
    (function () {
        try { localStorage.removeItem('hrrab.sidebar.grup'); } catch (e) { /* sisa versi lama yang mengingat status */ }

        const semua = document.querySelectorAll('#adminSidebar [data-nav-grup]');
        const atur = function (grup, buka) {
            grup.classList.toggle('terbuka', buka);
            grup.querySelector('.nav-grup-tombol').setAttribute('aria-expanded', String(buka));
        };

        semua.forEach(function (grup) {
            grup.querySelector('.nav-grup-tombol').addEventListener('click', function () {
                const buka = !grup.classList.contains('terbuka');
                if (buka) {
                    semua.forEach(function (lain) { if (lain !== grup) atur(lain, false); });
                }
                atur(grup, buka);
            });
        });
    })();
</script>
