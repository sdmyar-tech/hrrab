{{--
    Isian jam format Indonesia 24 jam (HH:MM), pengganti <input type="time"> yang ikut locale
    perangkat (sering tampil AM/PM). Pakai: <input type="text" data-jam24 ...>.
    - Hanya angka; titik dua disisipkan otomatis (1730 → 17:30).
    - Saat selesai diisi: dilengkapi (7 → 07:00, 730 → 07:30, 17.30 → 17:30) lalu divalidasi.
    - data-jam-min="HH:MM": tolak jam sebelum batas itu.
    Event delegation, jadi isian yang muncul belakangan ikut tertangani.
--}}
<script>
(function () {
    if (window.__jam24) return;
    window.__jam24 = true;

    const VALID = /^([01]\d|2[0-3]):[0-5]\d$/;
    const PESAN = 'Isi jam format 24 jam, contoh: 17:30.';
    const cocok = (el) => el instanceof HTMLInputElement && el.matches('[data-jam24]');

    // Lengkapi ketikan pendek menjadi HH:MM; kembalikan apa adanya bila tak bisa ditafsirkan.
    function rapikan(nilai) {
        // Ada pemisah (9:5, 17.30): hormati pembagian jam/menit yang diketik.
        const bagian = nilai.trim().match(/^(\d{1,2})\s*[:.]\s*(\d{1,2})$/);
        if (bagian) return bagian[1].padStart(2, '0') + ':' + bagian[2].padStart(2, '0');
        const angka = nilai.replace(/\D/g, '');
        if (!angka) return '';
        let jam, menit;
        if (angka.length <= 2) { jam = angka; menit = '00'; }
        else if (angka.length === 3) { jam = angka.slice(0, 1); menit = angka.slice(1); }
        else { jam = angka.slice(0, 2); menit = angka.slice(2, 4); }
        return jam.padStart(2, '0') + ':' + menit;
    }

    // data-jam-min="HH:MM" (opsional): batas jam paling awal; string HH:MM aman dibandingkan langsung.
    function periksa(el) {
        let pesan = '';
        if (el.value && !VALID.test(el.value)) pesan = PESAN;
        else if (el.value && el.dataset.jamMin && el.value < el.dataset.jamMin) pesan = 'Paling awal jam ' + el.dataset.jamMin + '.';
        el.setCustomValidity(pesan);
    }

    // Saat mengetik: hanya angka & satu pemisah; tanpa pemisah, titik dua disisipkan setelah 2 digit.
    document.addEventListener('input', function (e) {
        const el = e.target;
        if (!cocok(el)) return;
        const teks = el.value.replace(/\./g, ':').replace(/[^\d:]/g, '');
        const pisah = teks.indexOf(':');
        let baru;
        if (pisah >= 0) {
            baru = teks.slice(0, pisah).slice(0, 2) + ':' + teks.slice(pisah + 1).replace(/:/g, '').slice(0, 2);
        } else {
            // Digit pertama 3–9 pasti jam satu digit (730 → 7:30); selainnya jam dua digit.
            const digitJam = /^[3-9]/.test(teks) ? 1 : 2;
            const angka = teks.slice(0, digitJam + 2);
            baru = angka.length > digitJam ? angka.slice(0, digitJam) + ':' + angka.slice(digitJam) : angka;
        }
        if (baru !== el.value) el.value = baru;
        el.setCustomValidity('');
    }, true);

    // Fase capture: dirapikan sebelum listener 'change' milik halaman membaca nilainya.
    document.addEventListener('change', function (e) {
        const el = e.target;
        if (!cocok(el)) return;
        el.value = rapikan(el.value);
        periksa(el);
    }, true);

    // Tolak kirim formulir bila ada jam tak valid (mis. 25:00).
    document.addEventListener('submit', function (e) {
        const salah = Array.from(e.target.querySelectorAll('[data-jam24]')).filter(function (el) {
            el.value = rapikan(el.value);
            periksa(el);
            return !el.checkValidity();
        });
        if (salah.length) {
            e.preventDefault();
            e.stopImmediatePropagation();
            salah[0].reportValidity();
        }
    }, true);
})();
</script>
