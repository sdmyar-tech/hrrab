@extends('layouts.guest.app')

@section('title', 'Login')

@section('styles')
    <style>
        body {
            background: linear-gradient(135deg, #fff7f7 0%, #f9e5e6 45%, #f3e4e4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--hr-font);
            padding: 16px; /* HP: kartu tidak menempel ke tepi layar */
        }

        .login-box {
            width: min(100%, 460px);
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #e7d5d5;
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(95, 7, 8, 0.12);
            padding: 2rem;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: linear-gradient(135deg, #880f12, #5f0708);
            color: white;
            display: grid;
            place-items: center;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .brand-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1f2937;
        }

        .brand-sub {
            margin: 0;
            color: #6b7280;
            font-size: 0.8rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid #e4d3d3;
            padding: 0.8rem 0.9rem;
            background: #fff;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #880f12;
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
            color: #880f12;
        }

        .btn-login {
            width: 100%;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #880f12, #5f0708);
            color: white;
            padding: 0.85rem 1rem;
            font-weight: 700;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #5f0708, #880f12);
        }

        .text-link {
            color: #880f12;
            text-decoration: none;
            font-weight: 600;
        }

        .text-link:hover {
            text-decoration: underline;
        }

        .brand-logo { width: 64px; height: 64px; object-fit: contain; flex: none; }
        .brand { padding-bottom: 1.25rem; border-bottom: 1px solid #f0e4e4; }
        .login-judul { font-size: 1.25rem; font-weight: 700; margin: 0 0 .25rem; color: #1d2433; letter-spacing: -.01em; }
        .login-sub { font-size: .875rem; color: #687083; margin-bottom: 1.25rem; }
        .login-box .form-control { min-height: 46px; font-size: .9375rem; }

        .footer-links {
            margin-top: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }
    </style>
@endsection

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="login-box">
        <div class="brand">
            <img class="brand-logo" src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="Logo Yayasan Abdurrab">
            <div>
                <p class="brand-title">HRRAB</p>
                <p class="brand-sub">Sistem Informasi SDM · Yayasan Abdurrab</p>
            </div>
        </div>
        <h1 class="login-judul">Masuk ke akun Anda</h1>
        <p class="login-sub">Gunakan email & password akun pegawai.</p>

        <form action="{{ route('login.submit') }}" method="POST" id="login-form">
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
                    <button type="button" class="btn btn-toggle-password" id="toggle-password" aria-label="Tampilkan password" aria-pressed="false">
                        <i class="bi bi-eye" id="toggle-password-icon" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="btn-login">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
            </button>

            <div class="footer-links">
                <a href="{{ route('lowongan.index') }}" class="text-link">Ke Situs Karir</a>
                <a href="{{ route('pelamar.login') }}" class="text-link">Login Pelamar / Guest</a>
            </div>
        </form>
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
                    toggleBtn.setAttribute('aria-pressed', String(isHidden));
                    toggleBtn.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
                });
            }
        });
    </script>
@endsection
