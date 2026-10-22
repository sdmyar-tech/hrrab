{{--
    Tata letak halaman error. Sengaja mandiri (CSS di dalam file) agar tetap tampil
    walau error terjadi sebelum layout/aset aplikasi sempat dimuat.
--}}
@php
    $kode = trim($__env->yieldContent('kode'));
    $pesanKhusus = isset($exception) ? trim((string) $exception->getMessage()) : '';
    // Pesan bawaan Symfony/Laravel (bahasa Inggris) tidak ditampilkan; pesan buatan aplikasi (bahasa Indonesia) ditampilkan.
    $pesanBawaan = ['', 'Forbidden', 'Not Found', 'Unauthorized', 'Page Expired', 'Too Many Requests', 'Server Error', 'Service Unavailable', 'This action is unauthorized.'];
    $tampilkanPesan = ! in_array($pesanKhusus, $pesanBawaan, true) && ! str_starts_with($pesanKhusus, 'The route ') && ! str_starts_with($pesanKhusus, 'No query results');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('judul') · HRRAB</title>
    <script>
        // Ikuti tema yang dipilih pengguna di aplikasi (tombol bulan/matahari), bila ada.
        try { var tema = localStorage.getItem('adminHMD.colorTheme'); if (tema === 'dark') document.documentElement.classList.add('gelap'); if (tema === 'light') document.documentElement.classList.add('terang'); } catch (e) {}
    </script>
    <link rel="icon" href="{{ asset('assets-admin/images/png/logo_abd.png') }}">
    <link rel="stylesheet" href="{{ asset('assets-admin/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap">
    <style>
        :root {
            --merah: #880f12; --merah-tua: #5f0708; --merah-muda: #f9e5e6; --aksen: #880f12;
            --latar: #f5f7fb; --kartu: #ffffff; --teks: #1f2937; --redup: #6b7280; --garis: #dbe4ef;
        }
        @media (prefers-color-scheme: dark) {
            :root:not(.terang) { --aksen: #f08a8c; --merah-muda: #3b2020; --latar: #0f172a; --kartu: #182235; --teks: #e5edf7; --redup: #9aa8bd; --garis: #2f3b52; }
        }
        :root.gelap { --aksen: #f08a8c; --merah-muda: #3b2020; --latar: #0f172a; --kartu: #182235; --teks: #e5edf7; --redup: #9aa8bd; --garis: #2f3b52; }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            display: grid; place-items: center; padding: 24px 16px;
            font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; -webkit-font-smoothing: antialiased;
            background: radial-gradient(circle at 20% 0%, rgba(136, 15, 18, .08), transparent 45%), var(--latar);
            color: var(--teks);
        }
        .kartu {
            width: 100%; max-width: 520px; padding: 40px 32px 32px; text-align: center;
            background: var(--kartu); border: 1px solid var(--garis); border-radius: 20px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .08);
        }
        .merek { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 28px; color: var(--redup); font-size: 13px; font-weight: 600; text-decoration: none; }
        .merek img { width: 26px; height: 26px; object-fit: contain; }
        .ikon {
            width: 76px; height: 76px; margin: 0 auto 18px; display: grid; place-items: center;
            border-radius: 22px; background: var(--merah-muda); color: var(--aksen); font-size: 34px;
        }
        .kode { margin: 0; font-size: 13px; font-weight: 800; letter-spacing: .12em; color: var(--aksen); }
        h1 { margin: 6px 0 10px; font-size: clamp(22px, 5vw, 28px); line-height: 1.25; }
        .pesan { margin: 0 auto; max-width: 400px; color: var(--redup); font-size: 15px; line-height: 1.6; }
        .pesan-khusus {
            margin: 16px auto 0; max-width: 420px; padding: 10px 14px; border-radius: 12px;
            background: var(--merah-muda); color: var(--teks); font-size: 14px; line-height: 1.5;
        }
        .aksi { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 28px; }
        .tombol {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 42px; padding: 0 18px;
            border-radius: 12px; border: 1px solid var(--garis); background: var(--kartu); color: var(--teks);
            font-family: inherit; font-size: 14px; font-weight: 600; line-height: 1; text-decoration: none; cursor: pointer; transition: background .15s, border-color .15s, color .15s;
        }
        .tombol:hover { border-color: #e4b0b1; background: var(--merah-muda); color: var(--merah-tua); }
        .tombol.utama { background: var(--merah); border-color: var(--merah); color: #fff; }
        .tombol.utama:hover { background: var(--merah-tua); border-color: var(--merah-tua); color: #fff; }
        .tombol:focus-visible { outline: 3px solid rgba(136, 15, 18, .35); outline-offset: 2px; }
        .catatan { margin-top: 24px; font-size: 12px; color: var(--redup); }
        @media (max-width: 420px) {
            .kartu { padding: 32px 20px 24px; }
            .aksi .tombol { flex: 1 1 100%; }
        }
    </style>
</head>
<body>
    <main class="kartu" role="main">
        <a href="{{ url('/beranda') }}" class="merek"><img src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="">HRRAB · Yayasan Abdurrab</a>
        <div class="ikon"><i class="bi @yield('ikon', 'bi-exclamation-circle')" aria-hidden="true"></i></div>
        <p class="kode">ERROR {{ $kode }}</p>
        <h1>@yield('judul')</h1>
        <p class="pesan">@yield('pesan')</p>
        @if ($tampilkanPesan)
            <p class="pesan-khusus">{{ $pesanKhusus }}</p>
        @endif

        <div class="aksi">
            @hasSection('aksi')
                @yield('aksi')
            @else
                <a href="{{ url('/beranda') }}" class="tombol utama"><i class="bi bi-house-door"></i>Ke Beranda</a>
                <button type="button" class="tombol" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/beranda') }}')"><i class="bi bi-arrow-left"></i>Kembali</button>
            @endif
        </div>

        <p class="catatan">@yield('catatan', 'Jika masalah berlanjut, hubungi bagian SDM atau admin sistem.')</p>
    </main>
</body>
</html>
