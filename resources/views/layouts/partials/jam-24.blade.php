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

    // ---- Pemilih jam (tombol ikon jam di kanan isian): kolom jam 00–23 & menit 00–59 yang bisa digulir/diputar. ----
    const gaya = document.createElement('style');
    gaya.textContent = `
        .jam24-tombol { position: absolute; z-index: 5; display: inline-flex; align-items: center; justify-content: center; width: 2.5rem; padding: 0;
            border: 0; background: transparent; color: var(--admin-muted, #6c757d); cursor: pointer; border-radius: 0 .375rem .375rem 0; }
        .jam24-tombol:hover:not(:disabled), .jam24-tombol:focus-visible { color: var(--bs-primary, #880f12); }
        .jam24-tombol:disabled { opacity: .45; cursor: not-allowed; }
        .jam24-pop { position: absolute; z-index: 2000; display: flex; gap: .25rem; padding: .5rem; background: var(--bs-body-bg, #fff); color: var(--bs-body-color, #212529);
            border: 1px solid var(--bs-border-color, #dee2e6); border-radius: .5rem; box-shadow: 0 .5rem 1.25rem rgba(0, 0, 0, .18); }
        .jam24-kolom { width: 3.5rem; max-height: 12.5rem; overflow-y: auto; scroll-snap-type: y proximity; overscroll-behavior: contain; }
        .jam24-kolom-judul { position: sticky; top: 0; background: inherit; font-size: .7rem; text-align: center; color: var(--admin-muted, #6c757d); padding-bottom: .15rem; }
        .jam24-opsi { display: block; width: 100%; border: 0; background: transparent; color: inherit; padding: .3rem 0; text-align: center; border-radius: .35rem; scroll-snap-align: center; font-variant-numeric: tabular-nums; }
        .jam24-opsi:hover { background: rgba(136, 15, 18, .08); }
        .jam24-opsi.aktif { background: var(--bs-primary, #880f12); color: #fff; }
        .jam24-pisah { align-self: center; font-weight: 700; }
    `;
    document.head.appendChild(gaya);

    let pop = null;
    const tutup = () => { if (pop) { pop.remove(); pop = null; } };

    function pasangTombol(el) {
        if (el.dataset.jam24Siap) return;
        el.dataset.jam24Siap = '1';
        const induk = el.parentElement;
        if (getComputedStyle(induk).position === 'static') induk.style.position = 'relative';
        const tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.className = 'jam24-tombol';
        tombol.setAttribute('aria-label', 'Pilih jam');
        tombol.tabIndex = -1;
        tombol.innerHTML = '<i class="bi bi-clock" aria-hidden="true"></i>';
        el.after(tombol);
        el.style.paddingRight = '2.5rem';

        const letak = () => {
            tombol.hidden = el.offsetWidth === 0;
            tombol.style.top = el.offsetTop + 'px';
            tombol.style.height = el.offsetHeight + 'px';
            tombol.style.left = (el.offsetLeft + el.offsetWidth - tombol.offsetWidth) + 'px';
            tombol.disabled = el.disabled || el.readOnly;
        };
        letak();
        if ('ResizeObserver' in window) new ResizeObserver(letak).observe(el);
        window.addEventListener('resize', letak);
        new MutationObserver(letak).observe(el, { attributes: true, attributeFilter: ['disabled', 'readonly', 'hidden', 'class'] });

        tombol.addEventListener('click', function (e) {
            e.stopPropagation();
            if (pop && pop.dataset.untuk === el.id) { tutup(); return; }
            tutup();
            buka(el, tombol);
        });
    }

    function buka(el, tombol) {
        const [jamNow, menitNow] = VALID.test(el.value) ? el.value.split(':') : [null, null];
        pop = document.createElement('div');
        pop.className = 'jam24-pop';
        pop.dataset.untuk = el.id;
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-label', 'Pilih jam');

        const kolom = (judul, jumlah, terpilih, pilih) => {
            const k = document.createElement('div');
            k.className = 'jam24-kolom';
            k.innerHTML = '<div class="jam24-kolom-judul">' + judul + '</div>';
            for (let i = 0; i < jumlah; i++) {
                const t = String(i).padStart(2, '0');
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'jam24-opsi' + (t === terpilih ? ' aktif' : '');
                b.textContent = t;
                b.addEventListener('click', function () {
                    k.querySelectorAll('.aktif').forEach((a) => a.classList.remove('aktif'));
                    b.classList.add('aktif');
                    pilih(t);
                });
                k.appendChild(b);
            }
            return k;
        };

        let jam = jamNow, menit = menitNow;
        const terapkan = () => {
            if (jam === null || menit === null) return;
            el.value = jam + ':' + menit;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        };
        const kJam = kolom('Jam', 24, jamNow, (t) => { jam = t; if (menit === null) menit = '00'; terapkan(); });
        const kMenit = kolom('Menit', 60, menitNow, (t) => { menit = t; if (jam === null) jam = '00'; terapkan(); tutup(); });
        const pisah = document.createElement('span');
        pisah.className = 'jam24-pisah';
        pisah.textContent = ':';
        pop.append(kJam, pisah, kMenit);
        document.body.appendChild(pop);

        // Letakkan di bawah isian (di atasnya bila tak muat), lalu gulir ke nilai terpilih.
        const r = el.getBoundingClientRect();
        const tinggi = pop.offsetHeight;
        const bawah = r.bottom + tinggi + 8 < window.innerHeight;
        pop.style.top = (window.scrollY + (bawah ? r.bottom + 4 : Math.max(8, r.top - tinggi - 4))) + 'px';
        pop.style.left = Math.max(8, Math.min(window.scrollX + r.right - pop.offsetWidth, window.scrollX + document.documentElement.clientWidth - pop.offsetWidth - 8)) + 'px';
        // Gulir kolom saja (bukan scrollIntoView, agar halaman tidak ikut bergeser).
        [kJam, kMenit].forEach((k) => { const a = k.querySelector('.aktif'); if (a) k.scrollTop = a.offsetTop - k.clientHeight / 2 + a.offsetHeight / 2; });
        (kJam.querySelector('.aktif') || kJam.querySelector('.jam24-opsi')).focus({ preventScroll: true });
    }

    document.addEventListener('click', (e) => { if (pop && !pop.contains(e.target)) tutup(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') tutup(); });

    function pasangSemua() { document.querySelectorAll('input[data-jam24]').forEach(pasangTombol); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', pasangSemua); else pasangSemua();
    // Isian yang muncul belakangan (dialog, bagian yang dibuka) ikut dipasangi.
    new MutationObserver(pasangSemua).observe(document.documentElement, { childList: true, subtree: true });

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
