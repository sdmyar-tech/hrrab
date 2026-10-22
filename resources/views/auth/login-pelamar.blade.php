@extends('layouts.guest.app')

@section('title', 'Login Pelamar')

@section('styles')
    <style>
        :root {
            --brand: #880f12;
            --brand-dark: #5f0708;
        }

        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            font-family: var(--hr-font);
            background: #fff;
        }

        .pelamar-auth {
            min-height: 100vh;
            display: flex;
            flex-wrap: wrap;
        }

        .pelamar-auth-brand {
            flex: 1 1 46%;
            min-width: 320px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(160deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: #fff;
            padding: 3rem 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .pelamar-auth-brand::before,
        .pelamar-auth-brand::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }

        .pelamar-auth-brand::before {
            width: 320px;
            height: 320px;
            top: -120px;
            right: -100px;
        }

        .pelamar-auth-brand::after {
            width: 220px;
            height: 220px;
            bottom: -90px;
            left: -60px;
        }

        .pelamar-auth-brand > * {
            position: relative;
            z-index: 1;
        }

        .pelamar-auth-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.25);
            display: grid;
            place-items: center;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .pelamar-auth-brand h1 {
            font-weight: 800;
            font-size: 2rem;
            letter-spacing: -0.02em;
            margin-bottom: 0.75rem;
        }

        .pelamar-auth-brand p.lead {
            color: rgba(255, 255, 255, 0.85);
            font-size: 1rem;
            max-width: 380px;
        }

        .pelamar-auth-features {
            list-style: none;
            padding: 0;
            margin: 2rem 0 0;
            display: grid;
            gap: 0.9rem;
        }

        .pelamar-auth-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.92rem;
            color: rgba(255, 255, 255, 0.92);
        }

        .pelamar-auth-features i {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.14);
            display: grid;
            place-items: center;
            flex: 0 0 auto;
        }

        .pelamar-auth-form {
            flex: 1 1 54%;
            min-width: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            background: #fff;
        }

        .pelamar-auth-form-inner {
            width: 100%;
            max-width: 380px;
        }

        .pelamar-auth-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #f9e5e6;
            color: var(--brand-dark);
            font-weight: 700;
            font-size: 0.72rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            margin-bottom: 1rem;
        }

        .pelamar-auth-form h2 {
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.35rem;
        }

        .pelamar-auth-form .subtitle {
            color: #6b7280;
            margin-bottom: 1.75rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #e4d3d3;
            padding: 0.8rem 0.9rem;
            background: #fff;
        }

        .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 0.2rem rgba(136, 15, 18, 0.12);
        }

        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group .btn-toggle-password {
            border: 1px solid #e4d3d3;
            border-left: 0;
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            background: #fff;
            color: #6b7280;
        }

        .input-group .btn-toggle-password:hover {
            color: var(--brand);
        }

        .btn-login {
            width: 100%;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            color: white;
            padding: 0.85rem 1rem;
            font-weight: 700;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, var(--brand-dark), var(--brand));
            color: #fff;
        }

        .text-link {
            color: var(--brand);
            text-decoration: none;
            font-weight: 600;
        }

        .text-link:hover {
            text-decoration: underline;
        }

        .pelamar-auth-divider {
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f0e4e4;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.88rem;
        }

        @media (max-width: 767.98px) {
            .pelamar-auth-brand {
                padding: 2.25rem 1.75rem;
                text-align: center;
            }

            .pelamar-auth-icon {
                margin-inline: auto;
            }

            .pelamar-auth-brand h1 {
                font-size: 1.5rem;
            }

            .pelamar-auth-brand p.lead {
                max-width: none;
                margin-inline: auto;
            }

            .pelamar-auth-features {
                display: none;
            }
        }
    </style>
@endsection

@section('content')
    <div class="pelamar-auth">
        <div class="pelamar-auth-brand">
            <div class="pelamar-auth-icon"><i class="bi bi-briefcase-fill"></i></div>
            <h1>Wujudkan Karirmu Bersama Kami</h1>
            <p class="lead">Masuk untuk melanjutkan lamaran, pantau status seleksi, dan kelola profil pelamar Anda di Karir Yayasan Abdurrab.</p>

            <ul class="pelamar-auth-features">
                <li><i class="bi bi-search"></i> Jelajahi lowongan terbaru setiap saat</li>
                <li><i class="bi bi-clock-history"></i> Pantau status lamaran secara transparan</li>
                <li><i class="bi bi-person-check"></i> Satu profil untuk semua lowongan</li>
            </ul>
        </div>

        <div class="pelamar-auth-form">
            <div class="pelamar-auth-form-inner">
                <span class="pelamar-auth-eyebrow"><i class="bi bi-person-badge"></i> Portal Pelamar</span>
                <h2>Masuk ke Akun Anda</h2>
                <p class="subtitle">Belum jadi bagian dari tim kami? Daftar dan mulai melamar.</p>

                @include('auth._google-button', ['label' => 'Masuk dengan Google'])

                <form action="{{ route('pelamar.login.submit') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="nama@gmail.com"
                            value="{{ old('email') }}" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="Masukkan password" required>
                            <button type="button" class="btn btn-toggle-password" id="toggle-password" tabindex="-1">
                                <i class="bi bi-eye" id="toggle-password-icon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                    </button>

                    <div class="pelamar-auth-divider">
                        <a href="{{ route('lowongan.index') }}" class="text-link">Lihat Lowongan</a>
                        <a href="{{ route('register') }}" class="text-link">Belum punya akun? Daftar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggle-password-icon');

            if (toggleBtn && passwordInput && toggleIcon) {
                toggleBtn.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    toggleIcon.classList.toggle('bi-eye', !isHidden);
                    toggleIcon.classList.toggle('bi-eye-slash', isHidden);
                });
            }
        });
    </script>
@endsection
