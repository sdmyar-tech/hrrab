{{-- Lapisan desain global: font + ui.css. Dimuat paling akhir di setiap layout. --}}
<link rel="stylesheet" href="{{ asset('assets-admin/css/inter.css') }}">
<link rel="stylesheet" href="{{ asset('assets-admin/css/ui.css') }}?v={{ filemtime(public_path('assets-admin/css/ui.css')) }}">
