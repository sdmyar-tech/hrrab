{{--
    Akun bertanda wajib ganti password. Middleware PaksaGantiPassword mengarahkan semua halaman ke sini,
    jadi menutup dialog / mengubah URL tidak membuka akses apa pun. Form di bawah dialog adalah cadangan bila JS mati.
--}}
@extends('layouts.guest.app')

@section('title', 'Ganti Kata Sandi')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('styles')
<style>
    body { min-height: 100vh; background: radial-gradient(circle at 20% 0%, rgba(139, 18, 21, .14), transparent 50%), var(--admin-bg, #f5f6f8); }
    .kunci-latar { min-height: 100vh; display: grid; place-items: center; padding: 1.5rem 1rem; }
    .kunci-kartu { width: min(100%, 440px); background: #fff; border: 1px solid #e6e8ec; border-radius: 16px; padding: 1.75rem; box-shadow: 0 16px 40px rgba(16, 24, 40, .1); }
    .kunci-ikon { width: 52px; height: 52px; border-radius: 14px; display: grid; place-items: center; background: #fbeeee; color: #8b1215; font-size: 1.4rem; }
    /* Isi SweetAlert: form rata kiri. */
    .swal-ganti { text-align: left; margin: .25rem .25rem 0; }
    .swal-ganti label { display: block; font-size: .8125rem; font-weight: 600; color: #1d2433; margin: .75rem 0 .3rem; }
    .swal-ganti .input-group .form-control { min-height: 42px; }
    .swal-ganti .syarat { list-style: none; padding: 0; margin: .6rem 0 0; font-size: .78rem; color: #687083; }
    .swal-ganti .syarat li { display: flex; align-items: center; gap: .35rem; }
    .swal-ganti .syarat li.ok { color: #146c2e; }
    .swal-ganti .syarat li i { font-size: .85rem; }
    .swal2-actions .swal2-deny { background: #fff !important; color: #687083 !important; border: 1px solid #dcdfe5 !important; }
</style>
@endsection

@section('content')
<div class="kunci-latar">
    <div class="kunci-kartu">
        <div class="d-flex align-items-center gap-3 mb-3">
            <span class="kunci-ikon"><i class="bi bi-shield-lock"></i></span>
            <div>
                <h1 class="h5 fw-bold mb-0">Ganti kata sandi dulu</h1>
                <p class="small text-muted mb-0">{{ $user->name }} · {{ $user->email }}</p>
            </div>
        </div>
        <p class="small text-muted">Demi keamanan, SDM/Admin mewajibkan akun Anda mengganti kata sandi sebelum memakai sistem.</p>

        {{-- Form cadangan (dipakai juga oleh dialog). --}}
        <form method="POST" action="{{ route('akun.ganti-password.simpan') }}" id="formGantiPassword" autocomplete="off">
            @csrf
            <div class="mb-2">
                <label class="form-label" for="f-lama">Kata sandi saat ini</label>
                <input type="password" name="password_lama" id="f-lama" class="form-control" required autocomplete="current-password">
            </div>
            <div class="mb-2">
                <label class="form-label" for="f-baru">Kata sandi baru</label>
                <input type="password" name="password" id="f-baru" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <div class="mb-3">
                <label class="form-label" for="f-ulang">Ulangi kata sandi baru</label>
                <input type="password" name="password_confirmation" id="f-ulang" class="form-control" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Simpan Kata Sandi Baru</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" id="formKeluar" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-link btn-sm w-100 text-muted">Keluar</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formGantiPassword');
        const galat = @json($errors->all());

        const kolom = (id, label, auto) => `
            <label for="${id}">${label}</label>
            <div class="input-group">
                <input type="password" id="${id}" class="form-control" autocomplete="${auto}">
                <button type="button" class="btn btn-light" data-lihat="${id}" tabindex="-1" aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button>
            </div>`;

        const syarat = [
            ['panjang', 'Minimal 8 karakter', (v) => v.length >= 8],
            ['huruf', 'Mengandung huruf', (v) => /[a-z]/i.test(v)],
            ['angka', 'Mengandung angka', (v) => /\d/.test(v)],
        ];

        function buka() {
            Swal.fire({
                title: 'Wajib Ganti Kata Sandi',
                html: `<div class="swal-ganti">
                        <p class="small text-muted mb-1">Akun Anda diwajibkan mengganti kata sandi sebelum bisa memakai sistem.</p>
                        ${kolom('s-lama', 'Kata sandi saat ini', 'current-password')}
                        ${kolom('s-baru', 'Kata sandi baru', 'new-password')}
                        ${kolom('s-ulang', 'Ulangi kata sandi baru', 'new-password')}
                        <ul class="syarat">${syarat.map(([k, t]) => `<li data-syarat="${k}"><i class="bi bi-circle"></i>${t}</li>`).join('')}</ul>
                    </div>`,
                icon: 'warning',
                // Tidak bisa ditutup: tanpa tombol batal, klik luar, atau Esc.
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: true,
                showCloseButton: false,
                showDenyButton: true,
                confirmButtonText: '<i class="bi bi-check2"></i> Simpan Password',
                denyButtonText: 'Keluar',
                confirmButtonColor: '#8b1215',
                reverseButtons: true,
                focusConfirm: false,
                didOpen: (el) => {
                    el.querySelectorAll('[data-lihat]').forEach((b) => b.addEventListener('click', () => {
                        const i = el.querySelector('#' + b.dataset.lihat);
                        i.type = i.type === 'password' ? 'text' : 'password';
                        b.querySelector('i').className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
                    }));
                    el.querySelector('#s-baru').addEventListener('input', (e) => {
                        syarat.forEach(([k, , cek]) => {
                            const li = el.querySelector(`[data-syarat="${k}"]`);
                            const ok = cek(e.target.value);
                            li.classList.toggle('ok', ok);
                            li.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
                        });
                    });
                    el.querySelector('#s-lama').focus();
                    if (galat.length) Swal.showValidationMessage(galat.join(' '));
                },
                preConfirm: () => {
                    const el = Swal.getPopup();
                    const lama = el.querySelector('#s-lama').value;
                    const baru = el.querySelector('#s-baru').value;
                    const ulang = el.querySelector('#s-ulang').value;
                    if (!lama) return Swal.showValidationMessage('Isi kata sandi saat ini.');
                    const gagal = syarat.find(([, , cek]) => !cek(baru));
                    if (gagal) return Swal.showValidationMessage('Password baru: ' + gagal[1].toLowerCase() + '.');
                    if (baru === lama) return Swal.showValidationMessage('Kata sandi baru harus berbeda dari kata sandi lama.');
                    if (baru !== ulang) return Swal.showValidationMessage('Ulangi kata sandi baru dengan benar.');
                    return { lama, baru, ulang };
                },
            }).then((r) => {
                if (r.isConfirmed) {
                    form.password_lama.value = r.value.lama;
                    form.password.value = r.value.baru;
                    form.password_confirmation.value = r.value.ulang;
                    Swal.fire({ title: 'Menyimpan…', allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading() });
                    form.submit();
                } else if (r.isDenied) {
                    document.getElementById('formKeluar').submit();
                } else {
                    buka(); // jaga-jaga: dialog tertutup dengan cara lain → buka lagi.
                }
            });
        }

        buka();
    });
</script>
@endsection
