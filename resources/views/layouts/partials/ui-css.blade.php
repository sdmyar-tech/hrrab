{{-- Lapisan desain global: font + ui.css. Dimuat paling akhir di setiap layout. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ asset('assets-admin/css/ui.css') }}?v={{ filemtime(public_path('assets-admin/css/ui.css')) }}">
