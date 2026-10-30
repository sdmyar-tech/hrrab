{{--
    Kerangka panel TUNGGAL untuk semua peran yang login (Admin/SDM, Pegawai, Pelamar):
    sidebar kiri tetap (drawer di < 992px) · topbar (breadcrumb, cari menu, tema, lonceng, akun) · isi maks 1280px.

    Param (dikirim oleh pembungkus layouts.{admin,pegawai,pelamar}.app):
      $sidebar       = view menu sidebar sesuai peran (view composer di AppServiceProvider mengisi badge-nya)
      $judulCadangan = judul tab bila halaman tidak punya @section('title') / <h1>

    Isi halaman memakai @section('content'); halaman lama membungkusnya sendiri dengan <main class="dashboard-content">,
    jadi wadah di sini sengaja <div> (bukan <main>) agar tidak ada <main> bersarang.
--}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HRRAB - Sistem Informasi SDM Yayasan Abdurrab">
    @include('layouts.partials.pra-muat')
    @include('layouts.partials.favicon')
    @include('layouts.partials.judul-halaman', ['cadangan' => $judulCadangan ?? 'HRRAB'])
    @include('layouts.partials.css')
    @stack('head')
</head>

<body>
    <a href="#konten-utama" class="skip-link">Lewati ke konten utama</a>
    <script>
        // Mode ringkas sidebar dipasang sebelum tampil (tanpa kedip lebar → sempit saat halaman dimuat).
        try {
            if (localStorage.getItem('adminHMD.sidebarMini') === 'true' && matchMedia('(min-width: 992px)').matches) {
                document.body.classList.add('sidebar-mini');
            }
        } catch (e) { /* penyimpanan diblokir: sidebar lebar */ }
    </script>

    <div class="app-shell">
        <div class="app-backdrop" data-sidebar-close aria-hidden="true"></div>

        @include($sidebar)

        <div class="app-main">
            @include('layouts.partials.navbar')

            <div id="konten-utama" class="app-content" tabindex="-1">
                @include('layouts.partials.umpan-balik')
                @yield('content')
            </div>

            @include('layouts.partials.footer')
        </div>
    </div>

    @include('layouts.partials.cari-menu')
    @include('layouts.partials.js')
    @include('layouts.partials.sweetalert', ['pakaiBanner' => true])
    @include('layouts.partials.tanpa-spasi')
    @include('layouts.partials.geser-tab')
</body>

</html>
