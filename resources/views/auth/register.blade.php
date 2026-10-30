@extends('layouts.guest.app')

@section('title', 'Daftar Akun Pelamar')
@section('body-class', 'auth-body')
@section('pakai-banner', '1')

@section('content')
    <x-auth-card eyebrow="Portal Pelamar" eyebrow-icon="bi-person-plus" title="Buat akun pelamar"
        subtitle="Satu akun untuk melamar semua lowongan di Yayasan Abdurrab." wide>

        {{-- Penunjuk langkah: posisi pendaftaran dalam alur pelamar (langkah 2–3 dilakukan setelah masuk). --}}
        <ol class="auth-langkah" aria-label="Langkah menjadi pelamar">
            <li class="is-aktif" aria-current="step"><span class="auth-langkah-no">1</span><span>Buat akun</span></li>
            <li><span class="auth-langkah-no">2</span><span>Lengkapi data pelamar</span></li>
            <li><span class="auth-langkah-no">3</span><span>Lamar lowongan</span></li>
        </ol>

        @include('auth._google-button', ['label' => 'Daftar dengan Google'])

        <form action="{{ route('register.submit') }}" method="POST">
            @csrf

            <div class="row g-0 gx-md-3">
                <div class="col-md-6">
                    <x-input name="name" label="Nama Lengkap" icon="bi-person" placeholder="Contoh: Siti Aminah"
                        autocomplete="name" maxlength="150" required autofocus />
                </div>
                <div class="col-md-6">
                    <x-input name="email" type="email" label="Email" icon="bi-envelope" placeholder="Contoh: nama@gmail.com"
                        autocomplete="email" maxlength="150" required />
                </div>
                <div class="col-md-6">
                    <x-input name="password" type="password" label="Kata Sandi" icon="bi-lock" placeholder="Minimal 6 karakter"
                        autocomplete="new-password" minlength="6" required toggle field-class="mb-2" />
                </div>
                <div class="col-md-6">
                    <x-input name="password_confirmation" type="password" label="Ulangi Kata Sandi" icon="bi-lock"
                        placeholder="Ketik ulang kata sandi" autocomplete="new-password" required toggle field-class="mb-2" />
                </div>
            </div>

            <x-password-meter for="password" class="mb-4" :rules="[
                ['panjang', 'Minimal 6 karakter', 'len:6'],
                ['campur', 'Campuran huruf & angka (disarankan)', 'mix'],
            ]" />

            <x-button type="submit" size="lg" block icon="bi-person-plus">Daftar</x-button>
        </form>

        <x-slot:footer>
            <span>Sudah punya akun? <a href="{{ route('pelamar.login') }}" class="auth-tautan">Masuk</a></span>
            <a href="{{ route('lowongan.index') }}" class="auth-tautan">Lihat Lowongan <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </x-slot:footer>
    </x-auth-card>
@endsection
