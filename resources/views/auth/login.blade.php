@extends('layouts.guest.app')

@section('title', 'Masuk')
@section('body-class', 'auth-body')
@section('pakai-banner', '1')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <x-auth-card eyebrow="Portal Staf" eyebrow-icon="bi-shield-lock" title="Masuk ke akun Anda"
        subtitle="Gunakan email & kata sandi akun pegawai.">
        <form action="{{ route('login.submit') }}" method="POST" id="login-form">
            @csrf

            <x-input name="email" type="email" label="Email" icon="bi-envelope" placeholder="Contoh: nama@gmail.com"
                autocomplete="username" required autofocus />

            <x-input name="password" type="password" label="Kata Sandi" icon="bi-lock" placeholder="Masukkan kata sandi"
                autocomplete="current-password" required toggle field-class="mb-4" />

            <x-button type="submit" size="lg" block icon="bi-box-arrow-in-right" id="btn-login">Masuk</x-button>
        </form>

        <x-slot:footer>
            <span class="text-muted">Lupa kata sandi? Hubungi SDM/Admin.</span>
            <a href="{{ route('lowongan.index') }}" class="auth-tautan">Ke Situs Karir <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </x-slot:footer>
    </x-auth-card>
@endsection
