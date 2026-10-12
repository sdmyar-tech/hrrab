<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.partials.favicon')
    <title>@yield('title', 'Karir') | HRRAB</title>

    <link rel="stylesheet" href="{{ asset('assets-admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets-admin/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        :root {
            --brand: #880f12;
            --brand-dark: #5f0708;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .public-navbar {
            background: #ffffff;
            border-bottom: 1px solid #e7d5d5;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .public-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: #1f2937;
            text-decoration: none;
        }

        .public-brand-logo {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .public-navbar .nav-link {
            color: #4b5563;
        }

        .public-navbar .nav-link.active,
        .public-navbar .nav-link:hover {
            color: var(--brand);
        }

        .btn-brand {
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            border: 0;
            color: #fff;
            font-weight: 600;
        }

        .btn-brand:hover {
            color: #fff;
            background: linear-gradient(135deg, var(--brand-dark), var(--brand));
        }

        .btn-outline-brand {
            border: 1px solid var(--brand);
            color: var(--brand);
            font-weight: 600;
            background: #fff;
        }

        .btn-outline-brand:hover {
            background: #fdf1f1;
            color: var(--brand-dark);
        }

        .public-footer {
            background: #1f2937;
            color: #cbd5e1;
            padding: 2.5rem 0 1.5rem;
            margin-top: 3rem;
            font-size: 0.9rem;
        }

        .public-footer a {
            color: #cbd5e1;
            text-decoration: none;
        }

        .public-footer a:hover {
            color: #f9e5e6;
        }

        .public-footer .footer-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-weight: 700;
            margin-bottom: .75rem;
        }

        .public-footer .footer-logo {
            width: 46px;
            height: 46px;
            object-fit: contain;
            background: #fff;
            border-radius: 12px;
            padding: 4px;
        }

        .public-footer h6 {
            color: #fff;
            font-weight: 700;
            margin-bottom: .9rem;
        }

        .public-footer li {
            margin-bottom: .45rem;
        }

        .public-footer hr {
            border-color: rgba(255,255,255,.1);
            margin: 1.5rem 0 1rem;
        }

        .public-hero {
            background: radial-gradient(circle at top right, #a3161a 0%, var(--brand) 45%, var(--brand-dark) 100%);
            color: #fff;
            padding: 3.5rem 0 5.5rem;
            position: relative;
            overflow: hidden;
        }

        .public-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,.12) 1px, transparent 1px);
            background-size: 22px 22px;
            opacity: .5;
        }

        .public-hero > .container {
            position: relative;
        }

        .public-hero h1 {
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .search-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 45px rgba(95, 7, 8, .18);
            padding: 1.1rem;
            margin-top: -3.25rem;
            position: relative;
            z-index: 2;
        }

        .search-card .form-control,
        .search-card .form-select {
            border-radius: 10px;
            border-color: #e4d3d3;
            min-height: 46px;
        }

        .search-card .form-control:focus,
        .search-card .form-select:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 .2rem rgba(136,15,18,.12);
        }

        .job-card {
            border-radius: 14px;
            border: 1px solid #ece2e2;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(31, 41, 55, .1);
            border-color: #e4b0b1;
        }

        .job-card .job-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #f9e5e6;
            color: var(--brand-dark);
            display: grid;
            place-items: center;
            font-size: 1.1rem;
        }

        .job-card .badge-kategori {
            background: #f9e5e6;
            color: var(--brand-dark);
            font-weight: 600;
        }

        .stat-pill {
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 999px;
            padding: .4rem 1rem;
            font-size: .85rem;
            font-weight: 600;
            backdrop-filter: blur(2px);
        }

        .hero-logo {
            width: 92px;
            height: 92px;
            object-fit: contain;
            background: #fff;
            border-radius: 24px;
            padding: 10px;
            box-shadow: 0 12px 30px rgba(0,0,0,.18);
        }

        .hero-eyebrow {
            text-transform: uppercase;
            letter-spacing: .14em;
            font-size: .75rem;
            font-weight: 700;
            opacity: .85;
        }

        .job-card .job-meta li {
            display: flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: .3rem;
        }

        .job-card .job-meta i {
            color: var(--brand);
        }

        .job-card .job-footer {
            border-top: 1px dashed #ece2e2;
            padding-top: .85rem;
            margin-top: auto;
        }

        .badge-baru {
            background: #e7f6ec;
            color: #157347;
            font-weight: 600;
        }

        .badge-segera {
            background: #fff4e5;
            color: #b45309;
            font-weight: 600;
        }

        .empty-state {
            background: #fff;
            border: 1px dashed #e4d3d3;
            border-radius: 16px;
            padding: 3rem 1.5rem;
        }

        .empty-state img {
            width: 72px;
            opacity: .35;
            filter: grayscale(1);
        }

        .detail-hero {
            padding: 2.5rem 0 4.5rem;
        }

        .detail-hero a {
            color: rgba(255,255,255,.8);
            text-decoration: none;
        }

        .detail-hero a:hover {
            color: #fff;
        }

        .detail-card {
            border-radius: 16px;
            border: 1px solid #ece2e2;
            margin-top: -3rem;
            position: relative;
            z-index: 2;
        }

        .detail-card .section-title {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: .75rem;
        }

        .detail-card .section-title i {
            color: var(--brand);
        }

        .summary-card {
            border-radius: 16px;
            border: 1px solid #ece2e2;
            position: sticky;
            top: 96px;
        }

        .summary-card dt {
            font-weight: 500;
            color: #6b7280;
            font-size: .82rem;
        }

        .summary-card dd {
            font-weight: 600;
            margin-bottom: .8rem;
        }

        @media (min-width: 992px) {
            .summary-card {
                margin-top: -3rem;
                z-index: 2;
            }
        }
    </style>
    @include('layouts.partials.ui-css')
    <style>
        /* Halaman publik: latar & kartu mengikuti token ui.css. */
        body { background: var(--admin-bg); }
        .public-navbar { background: rgba(255, 255, 255, .92); backdrop-filter: saturate(160%) blur(12px); border-bottom-color: var(--admin-border); }
        .job-card, .detail-card, .summary-card { border-color: var(--admin-border); border-radius: 14px; box-shadow: var(--admin-shadow); }
        .job-card:hover { transform: translateY(-2px); box-shadow: var(--admin-shadow-lg); border-color: #e3c3c4; }
        .search-card { border-radius: 14px; box-shadow: 0 16px 40px rgba(16, 24, 40, .12); }
        .search-card .form-control, .search-card .form-select { border-color: #dcdfe5; }
        .public-footer { background: #17181d; }
    </style>

    @yield('styles')
</head>

<body>
    <nav class="navbar navbar-expand-md public-navbar py-3">
        <div class="container">
            <a class="public-brand" href="{{ route('lowongan.index') }}">
                <img class="public-brand-logo" src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="Logo Yayasan Abdurrab">
                <span>
                    Karir HRRAB
                    <small class="d-block fw-normal text-muted" style="font-size:.72rem;">Yayasan Abdurrab</small>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="publicNav">
                <ul class="navbar-nav align-items-md-center gap-2">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold {{ request()->routeIs('lowongan.*') ? 'active' : '' }}" href="{{ route('lowongan.index') }}">Lowongan</a>
                    </li>
                    @if (Auth::user()?->isPelamar())
                        <li class="nav-item">
                            <a class="nav-link fw-semibold" href="{{ route('pelamar.lamaran.index') }}">Lamaran Saya</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold" href="{{ route('pelamar.data-pelamar.edit') }}">Profil Saya</a>
                        </li>
                        <li class="nav-item">
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-brand btn-sm">Logout</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="btn btn-outline-brand btn-sm" href="{{ route('pelamar.login') }}">Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-brand btn-sm" href="{{ route('register') }}">Daftar</a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="public-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="footer-brand">
                        <img class="footer-logo" src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="Logo Yayasan Abdurrab">
                        <span>
                            Karir HRRAB
                            <small class="d-block fw-normal" style="color:#94a3b8;">Yayasan Abdurrab</small>
                        </span>
                    </div>
                    <p class="mb-0" style="max-width:380px;">
                        Portal rekrutmen resmi Yayasan Abdurrab. Temukan posisi yang sesuai dan tumbuh bersama kami.
                    </p>
                </div>
                <div class="col-md-3 ms-md-auto">
                    <h6>Pelamar</h6>
                    <ul class="list-unstyled mb-0">
                        <li><a href="{{ route('lowongan.index') }}">Lowongan Terbuka</a></li>
                        <li><a href="{{ route('register') }}">Daftar Akun</a></li>
                        <li><a href="{{ route('pelamar.login') }}">Masuk Pelamar</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center small">
                &copy; {{ date('Y') }} Yayasan Abdurrab &mdash; Human Resource Management System
            </div>
        </div>
    </footer>

    <script src="{{ asset('assets-admin/js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('layouts.partials.sweetalert')

    @yield('scripts')
</body>

</html>
