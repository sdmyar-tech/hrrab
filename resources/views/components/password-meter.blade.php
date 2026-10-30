{{--
    Indikator kekuatan kata sandi + daftar syarat yang tercentang saat diketik.
    <x-password-meter for="password" :rules="[['panjang', 'Minimal 8 karakter', 'len:8'], ['huruf', 'Mengandung huruf', 'letter'], ['angka', 'Mengandung angka', 'digit']]" />
    Jenis aturan: len:N · letter · digit · mix (huruf & angka) · same:idIsianLain (sama dengan isian lain, mis. ulangan)
    · differs:idIsianLain (berbeda dari isian lain, mis. kata sandi lama).
    Kekuatan (Lemah/Sedang/Kuat/Sangat kuat) dihitung dari panjang & ragam karakter — hanya panduan, server tetap memvalidasi.
--}}
@props([
    'for',
    'rules' => [],
])
<div {{ $attributes->merge(['class' => 'hr-sandi-meter']) }} data-sandi-meter="{{ $for }}" aria-live="polite">
    <div class="hr-sandi-batang" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    <p class="hr-sandi-label">Kekuatan: <strong data-sandi-label>belum diisi</strong></p>
    @if ($rules)
        <ul class="hr-sandi-syarat">
            @foreach ($rules as [$kunci, $teks, $aturan])
                <li data-syarat="{{ $aturan }}"><i class="bi bi-circle" aria-hidden="true"></i><span>{{ $teks }}</span><span class="visually-hidden" data-syarat-status>, belum terpenuhi</span></li>
            @endforeach
        </ul>
    @endif
</div>
@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-sandi-meter]').forEach(function (meter) {
                const isian = document.getElementById(meter.dataset.sandiMeter);
                if (!isian) return;
                const LABEL = ['belum diisi', 'Lemah', 'Sedang', 'Kuat', 'Sangat kuat'];
                const cek = function (aturan, v) {
                    const [jenis, arg] = aturan.split(':');
                    if (jenis === 'len') return v.length >= Number(arg);
                    if (jenis === 'letter') return /[a-z]/i.test(v);
                    if (jenis === 'digit') return /\d/.test(v);
                    if (jenis === 'mix') return /[a-z]/i.test(v) && /\d/.test(v);
                    if (jenis === 'same') { const lain = document.getElementById(arg); return v !== '' && !!lain && v === lain.value; }
                    if (jenis === 'differs') { const lain = document.getElementById(arg); return v !== '' && (!lain || v !== lain.value); }
                    return false;
                };
                const perbarui = function () {
                    const v = isian.value;
                    let skor = 0;
                    if (v) {
                        skor = 1;
                        if (v.length >= 8) skor++;
                        if (/[a-z]/i.test(v) && /\d/.test(v)) skor++;
                        if (v.length >= 12 && /[^a-z\d]/i.test(v) || (v.length >= 10 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /\d/.test(v))) skor++;
                    }
                    meter.dataset.skor = String(skor);
                    meter.querySelector('[data-sandi-label]').textContent = LABEL[skor];
                    meter.querySelectorAll('[data-syarat]').forEach(function (li) {
                        const ok = cek(li.dataset.syarat, v);
                        li.classList.toggle('ok', ok);
                        li.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
                        li.querySelector('[data-syarat-status]').textContent = ok ? ', terpenuhi' : ', belum terpenuhi';
                    });
                };
                isian.addEventListener('input', perbarui);
                meter.querySelectorAll('[data-syarat^="differs:"], [data-syarat^="same:"]').forEach(function (li) {
                    const lain = document.getElementById(li.dataset.syarat.split(':')[1]);
                    if (lain) lain.addEventListener('input', perbarui);
                });
                perbarui();
            });
        });
    </script>
@endonce
