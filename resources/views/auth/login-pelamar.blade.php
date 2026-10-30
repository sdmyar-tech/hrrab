@extends('layouts.guest.app')

@section('title', 'Masuk Pelamar')
@section('body-class', 'auth-body')
@section('pakai-banner', '1')

@section('content')
    <x-auth-card eyebrow="Portal Pelamar" eyebrow-icon="bi-briefcase" title="Masuk ke akun pelamar"
        subtitle="Pantau status lamaran dan kelola profil pelamar Anda.">
        @include('auth._google-button', ['label' => 'Masuk dengan Google'])

        <form action="{{ route('pelamar.login.submit') }}" method="POST">
            @csrf

            <x-input name="email" type="email" label="Email" icon="bi-envelope" placeholder="Contoh: nama@gmail.com"
                autocomplete="username" required autofocus />

            <x-input name="password" type="password" label="Kata Sandi" icon="bi-lock" placeholder="Masukkan kata sandi"
                autocomplete="current-password" required toggle field-class="mb-4" />

            <x-button type="submit" size="lg" block icon="bi-box-arrow-in-right">Masuk</x-button>
        </form>

        <x-slot:footer>
            <span>Belum punya akun? <a href="{{ route('register') }}" class="auth-tautan">Daftar sekarang</a></span>
            <a href="{{ route('lowongan.index') }}" class="auth-tautan">Lihat Lowongan <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </x-slot:footer>
    </x-auth-card>
@endsection
