{{--
    Layout TAMU: halaman tanpa sidebar (masuk staf/pelamar, daftar, wajib ganti kata sandi, layar absen acara).
    Section:
      title            → judul tab
      body-class       → kelas <body> (halaman auth: "auth-body" = latar pola titik + cahaya merek)
      styles / scripts → CSS / JS khusus halaman
      pakai-banner     → isi "1" bila halaman menampilkan pesan galat/peringatan sendiri (x-alert di kartu),
                         agar dialog SweetAlert bawaan tidak muncul ganda.
    Tema selalu terang (halaman tamu lama memakai warna tetap).
--}}
<!DOCTYPE html>
<html lang="id" data-theme="light" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.partials.favicon')
    @stack('head')
    <title>@yield('title', 'Masuk') | HRRAB</title>

    <link rel="stylesheet" href="{{ asset('assets-admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets-admin/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    @include('layouts.partials.ui-css')

    @yield('styles')
</head>

<body class="@yield('body-class')">
    @yield('content')

    <script src="{{ asset('assets-admin/js/bootstrap.bundle.min.js') }}"></script>
    @include('layouts.partials.placeholder-dropdown')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('layouts.partials.sweetalert', ['pakaiBanner' => trim($__env->yieldContent('pakai-banner')) === '1'])
    @include('layouts.partials.tanpa-spasi')

    @yield('scripts')
</body>

</html>
