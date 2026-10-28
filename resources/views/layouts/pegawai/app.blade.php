<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HRRAB pegawai self-service workspace">
    @include('layouts.partials.favicon')
    @include('layouts.partials.judul-halaman', ['cadangan' => 'Pegawai'])

    @include('layouts.partials.css')
</head>

<body>
    <a href="#konten-utama" class="skip-link">Lewati ke konten utama</a>
    <script>
        window.adminHMDUser = {
            name: "{{ Auth::user()->name ?? 'User' }}",
            workspace: "{{ Auth::user()->roleLabel() ?? 'User' }}",
            avatar: @json(Auth::user()?->fotoUrl() ?? asset('assets-admin/images/avatar/avatar-fallback.jpg'))
        };
    </script>

    <div class="admin-shell">
        <div class="sidebar-backdrop" data-sidebar-close></div>

        @include('layouts.pegawai.sidebar')

        <div class="admin-main">

            @include('layouts.partials.navbar')

            <div id="konten-utama" tabindex="-1"></div>
            @yield('content')

            @include('layouts.partials.footer')
        </div>
    </div>

    @include('layouts.partials.js')

    @include('layouts.partials.sweetalert')
@include('layouts.partials.tanpa-spasi')
@include('layouts.partials.geser-tab')
</body>

</html>
