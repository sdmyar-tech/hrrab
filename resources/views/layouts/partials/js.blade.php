    <script src="{{ asset('assets-admin/vendors/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets-admin/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets-admin/js/main.js') }}?v={{ filemtime(public_path('assets-admin/js/main.js')) }}"></script>
    @include('layouts.partials.placeholder-dropdown')
