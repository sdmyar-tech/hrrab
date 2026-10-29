@extends('layouts.guest.app')

@section('title', 'Daftar Akun Pelamar')

@section('styles')
    <style>
        body {
            background: linear-gradient(135deg, #fff7f7 0%, #f9e5e6 45%, #f3e4e4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--hr-font);
            padding: 2rem 1rem;
        }

        .login-box {
            width: min(100%, 480px);
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

        .form-control {
            border-radius: 10px;
            border: 1px solid #e4d3d3;
            padding: 0.8rem 0.9rem;
            background: #fff;
        }

        .form-control:focus {
            border-color: #880f12;
            box-shadow: 0 0 0 0.2rem rgba(136, 15, 18, 0.12);
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

        .footer-links {
            margin-top: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }
    </style>
@endsection

@section('content')
    <div class="login-box">
        <div class="brand">
            <img src="{{ asset('assets-admin/images/png/logo_abd.png') }}" alt="Logo Yayasan Abdurrab"
                style="width:56px; height:56px; object-fit:contain;">
            <div>
                <p class="brand-title">Daftar Akun Pelamar</p>
                <p class="brand-sub">Mulai lamar pekerjaan di Yayasan Abdurrab</p>
            </div>
        </div>

        @include('auth._google-button', ['label' => 'Daftar dengan Google'])

        <form action="{{ route('register.submit') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}" required autofocus>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    placeholder="Contoh: nama@gmail.com" value="{{ old('email') }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Kata Sandi</label>
                <input type="password" id="password" name="password"
                    class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 6 karakter" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Ulangi Kata Sandi</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-person-plus me-1"></i> Daftar
            </button>

            <div class="footer-links">
                <a href="{{ route('lowongan.index') }}" class="text-link">Lihat Lowongan</a>
                <a href="{{ route('pelamar.login') }}" class="text-link">Sudah punya akun? Masuk</a>
            </div>
        </form>
    </div>
@endsection
