    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets-admin/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets-admin/js/main.js') }}?v={{ filemtime(public_path('assets-admin/js/main.js')) }}"></script>
    @include('layouts.partials.placeholder-dropdown')
