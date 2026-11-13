{{--
    Dialog ganti kata sandi — hanya ada bila akun punya persetujuan Admin/SDM yang belum dipakai
    (PermintaanGantiPassword). Terbuka otomatis sekali per sesi tab; bisa ditutup dan dibuka lagi dari Profil
    (tombol [data-bs-target="#modalGantiPassword"]). Aturan server: min. 8, huruf + angka, beda dari yang lama.
--}}
@auth
    @php $izinGanti = \App\Models\PermintaanGantiPassword::siapDipakai(Auth::user()); @endphp
    @if ($izinGanti)
        <x-modal id="modalGantiPassword" title="Ganti kata sandi" icon="bi-key" subtitle="Permintaan Anda sudah disetujui SDM/Admin. Persetujuan ini berlaku untuk satu kali ganti.">
            <form method="POST" action="{{ route('akun.permintaan-password.ubah') }}" id="formUbahPassword" autocomplete="off">
                @csrf
                <x-input name="password_lama" id="gp-lama" type="password" label="Kata sandi saat ini" icon="bi-key"
                    placeholder="Kata sandi yang dipakai untuk masuk" autocomplete="current-password" required toggle />
                <x-input name="password" id="gp-baru" type="password" label="Kata sandi baru" icon="bi-lock"
                    placeholder="Minimal 8 karakter, huruf & angka" autocomplete="new-password" minlength="8" required toggle field-class="mb-2" />
                <x-password-meter for="gp-baru" class="mb-3" :rules="[
                    ['panjang', 'Minimal 8 karakter', 'len:8'],
                    ['huruf', 'Mengandung huruf', 'letter'],
                    ['angka', 'Mengandung angka', 'digit'],
                    ['beda', 'Berbeda dari kata sandi saat ini', 'differs:gp-lama'],
                ]" />
                <x-input name="password_confirmation" id="gp-ulang" type="password" label="Ulangi kata sandi baru" icon="bi-lock"
                    placeholder="Ketik ulang kata sandi baru" autocomplete="new-password" required toggle field-class="mb-0" />
            </form>
            <x-slot:footer>
                <x-button variant="secondary" data-bs-dismiss="modal">Nanti saja</x-button>
                <x-button type="submit" form="formUbahPassword" icon="bi-check-lg">Simpan Kata Sandi Baru</x-button>
            </x-slot:footer>
        </x-modal>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('modalGantiPassword');
                const lama = document.getElementById('gp-lama');
                const baru = document.getElementById('gp-baru');
                const ulang = document.getElementById('gp-ulang');
                function cek() {
                    const v = baru.value;
                    baru.setCustomValidity(!v ? '' : v.length < 8 ? 'Kata sandi baru minimal 8 karakter.'
                        : !/[a-z]/i.test(v) ? 'Kata sandi baru harus mengandung huruf.'
                        : !/\d/.test(v) ? 'Kata sandi baru harus mengandung angka.'
                        : v === lama.value ? 'Kata sandi baru harus berbeda dari kata sandi saat ini.' : '');
                    ulang.setCustomValidity(ulang.value && ulang.value !== v ? 'Ulangi kata sandi baru dengan benar.' : '');
                }
                [lama, baru, ulang].forEach((el) => el.addEventListener('input', cek));
                document.getElementById('formUbahPassword').addEventListener('submit', cek);

                // Buka otomatis sekali per tab; sesudahnya lewat tombol di Profil.
                let sudah = false;
                try { sudah = sessionStorage.getItem('gp-dibuka-{{ $izinGanti->id }}') === '1'; } catch (e) { /* abaikan */ }
                if (!sudah && window.bootstrap) {
                    try { sessionStorage.setItem('gp-dibuka-{{ $izinGanti->id }}', '1'); } catch (e) { /* abaikan */ }
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
                @if ($errors->has('password_lama') || $errors->has('password'))
                    if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).show();
                @endif
            });
        </script>
    @endif
@endauth
