{{--
    <title> tiap halaman = @section('title') bila ada, selain itu judul <h1> konten (bukan "Dashboard" untuk semua halaman),
    agar tab/riwayat browser bisa dibedakan & dibacakan pembaca layar. Param: $cadangan (judul bila tidak ada h1).
--}}
@php
    if (! ($judulHalaman = trim($__env->yieldContent('title')))) {
        preg_match('/<h1\b[^>]*>(.*?)<\/h1>/s', $__env->yieldContent('content'), $h1);
        $judulHalaman = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($h1[1] ?? ''), ENT_QUOTES)));
    }
@endphp
<title>{{ $judulHalaman ?: $cadangan }} | HRRAB</title>
