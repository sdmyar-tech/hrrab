{{--
    Sidebar bersama untuk semua panel. Variabel:
    $menu    = [['bagian' => ?string, 'ikon' => ?string, 'item' => [[label, ikon, route, polaAktif, tampil, badge, (opsional) badgePeringatan, (opsional) eksternal]]]]
    $beranda = nama route tujuan logo
    Bagian = menu induk yang bisa dibuka-tutup (dropdown) berisi submenu. Setiap halaman dibuka semua menu tertutup,
    kecuali menu berisi halaman aktif; membuka satu menu menutup menu lain (akordeon). Saat tertutup, jumlah tugas
    (badge) submenu tampil di menu induknya.
    Posisi menu aktif dititipkan ke atribut request ('navAktif', 'navBeranda') untuk breadcrumb topbar —
    sidebar selalu dirender sebelum topbar. (Bukan View::share: nilai bersama bisa basi di proses yang melayani banyak request.)
--}}
@php
    $pengguna = Auth::user();
    $ikonBagian = [
        'Tugas Saya' => 'bi-check2-square', 'Data Pengajuan' => 'bi-inbox', 'Pengajuan Saya' => 'bi-send', 'Data Saya' => 'bi-person-vcard',
        'Kepegawaian' => 'bi-people', 'Absensi' => 'bi-fingerprint', 'Rekrutmen' => 'bi-person-plus', 'Informasi' => 'bi-megaphone',
        'Sistem' => 'bi-gear', 'Lowongan' => 'bi-briefcase',
    ];
    $aktifDi = fn (array $item) => ! ($item[7] ?? false) && request()->routeIs($item[3]);

    $navAktif = null;
    foreach ($menu as $grup) {
        foreach ($grup['item'] as $item) {
            if ($item[4] && $aktifDi($item)) {
                $navAktif = ['bagian' => $grup['bagian'], 'label' => $item[0], 'url' => route($item[2]), 'route' => $item[2]];
                break 2;
            }
        }
    }
    request()->attributes->set('navAktif', $navAktif);
    request()->attributes->set('navBeranda', $beranda);
@endphp
<aside class="app-sidebar" id="adminSidebar" aria-label="Navigasi utama">
    <div class="app-sidebar-head">
        <a class="app-brand" href="{{ route($beranda) }}" aria-label="Beranda HRRAB">
            <img src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="" class="app-brand-logo">
            <span class="app-brand-teks">
                <span class="app-brand-nama">HRRAB</span>
                <span class="app-brand-sub">Yayasan Abdurrab</span>
            </span>
        </a>
        <button type="button" class="app-icon-btn app-tutup-drawer" data-sidebar-close aria-label="Tutup menu">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="app-nav" aria-label="Menu">
        @foreach ($menu as $grup)
            @php $tampil = array_values(array_filter($grup['item'], fn ($i) => $i[4])); @endphp
            @continue(! $tampil)

            @if ($grup['bagian'])
                @php
                    $idGrup = 'nav-grup-' . \Illuminate\Support\Str::slug($grup['bagian']);
                    $adaAktif = collect($tampil)->contains($aktifDi);
                    $berbadge = collect($tampil)->filter(fn ($i) => $i[5]);
                    $totalBadge = $berbadge->sum(fn ($i) => (int) $i[5]);
                @endphp
                <div class="app-nav-grup {{ $adaAktif ? 'terbuka punya-aktif' : '' }}" data-nav-grup="{{ $idGrup }}">
                    <button type="button" class="app-nav-item app-nav-induk" aria-expanded="{{ $adaAktif ? 'true' : 'false' }}"
                        aria-controls="{{ $idGrup }}" title="{{ $grup['bagian'] }}">
                        <i class="bi {{ $grup['ikon'] ?? $ikonBagian[$grup['bagian']] ?? 'bi-folder2' }} app-nav-ikon" aria-hidden="true"></i>
                        <span class="app-nav-teks">{{ $grup['bagian'] }}</span>
                        @if ($totalBadge)
                            <span class="app-nav-badge {{ $berbadge->every(fn ($i) => $i[6] ?? false) ? 'is-warning' : '' }}"
                                aria-label="{{ $totalBadge }} perlu perhatian">{{ $totalBadge > 99 ? '99+' : $totalBadge }}</span>
                        @endif
                        <i class="bi bi-chevron-down app-nav-panah" aria-hidden="true"></i>
                    </button>
                    <div class="app-nav-sub" id="{{ $idGrup }}" role="group" aria-label="{{ $grup['bagian'] }}">
                        <div class="app-nav-sub-isi">
                            @foreach ($tampil as $item)
                                @include('layouts.partials.sidebar-link', ['item' => $item, 'bagian' => $grup['bagian']])
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                @foreach ($tampil as $item)
                    @include('layouts.partials.sidebar-link', ['item' => $item, 'bagian' => null])
                @endforeach
            @endif
        @endforeach
    </nav>

    <div class="app-sidebar-foot">
        <a href="{{ route('profile') }}" class="app-sidebar-akun" title="Profil {{ $pengguna?->name }}">
            <x-avatar :src="$pengguna?->fotoUrl()" :name="$pengguna?->name" size="sm" />
            <span class="app-sidebar-akun-teks">
                <span class="app-sidebar-akun-nama">{{ $pengguna?->name }}</span>
                <span class="app-sidebar-akun-peran">{{ $pengguna?->roleLabel() }}</span>
            </span>
        </a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="app-icon-btn" title="Keluar" aria-label="Keluar"
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
        const semua = document.querySelectorAll('#adminSidebar [data-nav-grup]');
        const atur = function (grup, buka) {
            grup.classList.toggle('terbuka', buka);
            grup.querySelector('.app-nav-induk').setAttribute('aria-expanded', String(buka));
        };

        semua.forEach(function (grup) {
            grup.querySelector('.app-nav-induk').addEventListener('click', function () {
                const buka = !grup.classList.contains('terbuka');
                if (buka) {
                    semua.forEach(function (lain) { if (lain !== grup) atur(lain, false); });
                }
                atur(grup, buka);
            });
        });
    })();
</script>
