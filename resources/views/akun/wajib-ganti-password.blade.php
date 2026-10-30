{{--
    Akun bertanda wajib ganti kata sandi. Middleware PaksaGantiPassword mengarahkan semua halaman ke sini,
    jadi mengubah URL tidak membuka akses apa pun. Form langsung di kartu (tanpa dialog), dengan indikator
    kekuatan & syarat yang tercentang saat diketik. Aturan server: min. 8, huruf + angka, beda dari yang lama.
--}}
@extends('layouts.guest.app')

@section('title', 'Ganti Kata Sandi')
@section('body-class', 'auth-body')
@section('pakai-banner', '1')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <x-auth-card eyebrow="Keamanan Akun" eyebrow-icon="bi-shield-lock" title="Ganti kata sandi dulu"
        subtitle="Demi keamanan, SDM/Admin mewajibkan akun Anda mengganti kata sandi sebelum memakai sistem.">

        <div class="auth-akun">
            <x-avatar :src="$user->fotoUrl()" :name="$user->name" size="md" />
            <div class="min-w-0">
                <div class="fw-semibold text-truncate">{{ $user->name }}</div>
                <div class="small text-muted text-truncate">{{ $user->email }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('akun.ganti-password.simpan') }}" id="formGantiPassword" autocomplete="off">
            @csrf

            <x-input name="password_lama" id="f-lama" type="password" label="Kata sandi saat ini" icon="bi-key"
                placeholder="Kata sandi yang dipakai untuk masuk tadi" autocomplete="current-password" required toggle autofocus />

            <x-input name="password" id="f-baru" type="password" label="Kata sandi baru" icon="bi-lock"
                placeholder="Minimal 8 karakter, huruf & angka" autocomplete="new-password" minlength="8" required toggle field-class="mb-2" />

            <x-password-meter for="f-baru" class="mb-3" :rules="[
                ['panjang', 'Minimal 8 karakter', 'len:8'],
                ['huruf', 'Mengandung huruf', 'letter'],
                ['angka', 'Mengandung angka', 'digit'],
                ['beda', 'Berbeda dari kata sandi saat ini', 'differs:f-lama'],
            ]" />

            <x-input name="password_confirmation" id="f-ulang" type="password" label="Ulangi kata sandi baru" icon="bi-lock"
                placeholder="Ketik ulang kata sandi baru" autocomplete="new-password" required toggle field-class="mb-1" />
            <p class="auth-cocok mb-4" data-cocok hidden aria-live="polite"></p>

            <x-button type="submit" size="lg" block icon="bi-check-lg">Simpan Kata Sandi Baru</x-button>
        </form>

        <x-slot:footer>
            <span class="text-muted">Bukan akun Anda?</span>
            <form method="POST" action="{{ route('logout') }}" id="formKeluar" class="m-0">
                @csrf
                <button type="submit" class="btn btn-link auth-tautan p-0 min-h-0"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Keluar</button>
            </form>
        </x-slot:footer>
    </x-auth-card>
@endsection

@section('scripts')
<script>
    // Tanda cocok/tidak untuk ulangan kata sandi + cegah kirim bila jelas belum memenuhi syarat (server tetap memvalidasi).
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formGantiPassword');
        const lama = document.getElementById('f-lama');
        const baru = document.getElementById('f-baru');
        const ulang = document.getElementById('f-ulang');
        const tanda = document.querySelector('[data-cocok]');

        function cekCocok() {
            if (!ulang.value) { tanda.hidden = true; ulang.setCustomValidity(''); return; }
            const cocok = ulang.value === baru.value;
            tanda.hidden = false;
            tanda.className = 'auth-cocok mb-4 ' + (cocok ? 'is-ok' : 'is-galat');
            tanda.innerHTML = cocok
                ? '<i class="bi bi-check-circle-fill" aria-hidden="true"></i> Kata sandi cocok'
                : '<i class="bi bi-x-circle-fill" aria-hidden="true"></i> Belum sama dengan kata sandi baru';
            ulang.setCustomValidity(cocok ? '' : 'Ulangi kata sandi baru dengan benar.');
        }
        function cekBaru() {
            const v = baru.value;
            const pesan = !v ? '' : v.length < 8 ? 'Kata sandi baru minimal 8 karakter.'
                : !/[a-z]/i.test(v) ? 'Kata sandi baru harus mengandung huruf.'
                : !/\d/.test(v) ? 'Kata sandi baru harus mengandung angka.'
                : v === lama.value ? 'Kata sandi baru harus berbeda dari kata sandi saat ini.' : '';
            baru.setCustomValidity(pesan);
        }

        [lama, baru].forEach((el) => el.addEventListener('input', () => { cekBaru(); cekCocok(); }));
        ulang.addEventListener('input', cekCocok);
        form.addEventListener('submit', () => { cekBaru(); cekCocok(); });
    });
</script>
@endsection
