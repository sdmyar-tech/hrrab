{{--
    Galat dari form di dalam modal: tanpa ini halaman dimuat ulang, modal tertutup, dan pesan galat tidak terlihat.
    Cara kerja (berlaku untuk SEMUA modal, tanpa kode per halaman):
      1. Saat form di dalam modal dikirim, simpan modal mana + tombol pembukanya + isian yang diketik (sessionStorage).
      2. Setelah halaman dimuat ulang dengan galat (validasi / pesan 'error'), buka lagi modal yang sama,
         kembalikan isiannya, dan tampilkan daftar galat di bagian atas modal.
    Halaman yang sudah membuka ulang modalnya sendiri (mis. Bagian, Unit, Lokasi) dilewati agar tidak dobel.
--}}
<script>
(function () {
    const KUNCI = 'hrrab.modalGalat';
    const BATAS = 2 * 60 * 1000; // data simpanan hanya berlaku 2 menit
    const simpan = (v) => { try { v === null ? sessionStorage.removeItem(KUNCI) : sessionStorage.setItem(KUNCI, JSON.stringify(v)); } catch (e) {} };
    const baca = () => { try { return JSON.parse(sessionStorage.getItem(KUNCI) || 'null'); } catch (e) { return null; } };

    // Modal terakhir yang dibuka + tombol pembukanya (indeks di antara tombol yang menargetkan modal itu).
    const pembuka = {};
    document.addEventListener('show.bs.modal', function (e) {
        const modal = e.target;
        if (!modal.id) return;
        const t = e.relatedTarget;
        const semua = Array.prototype.slice.call(document.querySelectorAll('[data-bs-target="#' + modal.id + '"], a[href="#' + modal.id + '"]'));
        pembuka[modal.id] = t ? semua.indexOf(t) : -1;
    });

    // 1) Form dalam modal terkirim → ingat keadaannya.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || e.defaultPrevented) return;
        if ((form.method || 'get').toLowerCase() === 'get') return;
        const modal = form.closest('.modal');
        if (!modal || !modal.id) return;
        const nilai = [];
        Array.prototype.forEach.call(form.elements, function (el, i) {
            if (!el.name || el.type === 'password' || el.type === 'file' || el.type === 'submit' || el.type === 'button') return;
            if (el.name === '_token' || el.name === '_method') return;
            nilai.push({ i: i, n: el.name, v: el.value, c: el.checked });
        });
        simpan({ modal: modal.id, tombol: pembuka[modal.id] ?? -1, form: form.id || null, nilai: nilai, waktu: Date.now() });
    });

    // 2) Halaman dimuat ulang.
    const galat = @json(array_values(array_unique(array_merge(
        isset($errors) ? $errors->all() : [],
        session('error') ? [session('error')] : []
    ))));
    const tersimpan = baca();
    simpan(null); // sekali pakai
    if (!galat.length || !tersimpan || Date.now() - tersimpan.waktu > BATAS) return;

    document.addEventListener('DOMContentLoaded', function () {
        // Tunggu skrip halaman lain (yang mungkin membuka modalnya sendiri) selesai dulu.
        setTimeout(function () {
            const el = document.getElementById(tersimpan.modal);
            if (!el || typeof bootstrap === 'undefined') return;
            if (bootstrap.Modal.getInstance(el)) return; // sudah dibuka skrip halaman

            const semua = Array.prototype.slice.call(document.querySelectorAll('[data-bs-target="#' + el.id + '"], a[href="#' + el.id + '"]'));
            const tombol = semua[tersimpan.tombol] || null;
            const form = (tersimpan.form && document.getElementById(tersimpan.form)) || el.querySelector('form');

            function pulihkan() {
                if (!form) return;
                tersimpan.nilai.forEach(function (d) {
                    const ada = form.elements[d.i];
                    if (!ada || ada.name !== d.n) return;
                    if (ada.type === 'checkbox' || ada.type === 'radio') ada.checked = d.c;
                    else ada.value = d.v;
                    ada.dispatchEvent(new Event('input', { bubbles: true }));
                    ada.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }
            function tampilkanGalat() {
                const badan = el.querySelector('.modal-body');
                if (!badan) return;
                badan.querySelectorAll('[data-galat-modal]').forEach((x) => x.remove());
                const kotak = document.createElement('div');
                kotak.className = 'alert alert-danger d-flex gap-2 align-items-start mb-3';
                kotak.setAttribute('role', 'alert');
                kotak.setAttribute('data-galat-modal', '');
                const ikon = document.createElement('i');
                ikon.className = 'bi bi-exclamation-octagon-fill mt-1';
                ikon.setAttribute('aria-hidden', 'true');
                const isi = document.createElement('div');
                isi.className = 'small';
                const judul = document.createElement('strong');
                judul.textContent = 'Belum berhasil disimpan.';
                isi.append(judul, ' Periksa hal berikut, lalu coba lagi:');
                const daftar = document.createElement('ul');
                daftar.className = 'mb-0 ps-3';
                galat.forEach(function (g) { const li = document.createElement('li'); li.textContent = g; daftar.append(li); });
                isi.append(daftar);
                kotak.append(ikon, isi);
                badan.prepend(kotak);
                badan.scrollTop = 0;
            }

            // Pendengar ini dipasang setelah milik halaman → berjalan sesudah isian modal diisi ulang oleh halaman.
            el.addEventListener('show.bs.modal', function () { pulihkan(); tampilkanGalat(); }, { once: true });
            el.addEventListener('shown.bs.modal', function () {
                pulihkan();
                const salah = el.querySelector('.is-invalid');
                if (salah) salah.focus();
            }, { once: true });
            // Galat lama dibuang begitu modal ditutup / dibuka lagi.
            el.addEventListener('hidden.bs.modal', function () { el.querySelectorAll('[data-galat-modal]').forEach((x) => x.remove()); }, { once: true });

            bootstrap.Modal.getOrCreateInstance(el).show(tombol || undefined);
        }, 0);
    });
})();
</script>
