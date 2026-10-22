<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HRRAB - Sistem Informasi SDM Yayasan Abdurrab">
    @include('layouts.partials.favicon')
    <title>Dashboard | HRRAB</title>

    @include('layouts.partials.css')
</head>

<body>
    <script>
        window.adminHMDUser = {
            name: "{{ Auth::user()->name ?? 'User' }}",
            workspace: "{{ Auth::user()->roleLabel() ?? 'User' }}",
            avatar: @json(Auth::user()?->fotoUrl() ?? asset('assets-admin/images/avatar/avatar-fallback.jpg'))
        };
    </script>

    <div class="admin-shell">
        <div class="sidebar-backdrop" data-sidebar-close></div>

        @include('layouts.admin.sidebar')

        <div class="admin-main">

            @include('layouts.partials.navbar')

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
