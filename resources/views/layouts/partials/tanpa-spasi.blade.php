{{--
    Isian yang tidak butuh spasi (login, email, password, NIK/NIP, No. HP, dsb.):
    - spasi dari keyboard ditolak (tidak masuk);
    - spasi dari tempelan/autofill/seret otomatis dihapus.
    Memakai event delegation, jadi isian yang muncul belakangan (mis. dialog SweetAlert) ikut terlindungi.
    Tambah data-tanpa-spasi pada input lain untuk ikut aturan ini, atau data-boleh-spasi untuk mengecualikan.
--}}
<script>
(function () {
    if (window.__tanpaSpasi) return;
    window.__tanpaSpasi = true;

    const SELEKTOR = [
        'input[type=email]', 'input[type=password]', 'input[name=email]', 'input[name^=password]',
        'input[name=nik]', 'input[name=nip]', 'input[name^=no_hp]', 'input[name^=no_bpjs]', 'input[name=no_kk]',
        'input[name=npwp]', 'input[name=token]', 'input[data-tanpa-spasi]',
    ].join(',');
    const SPASI = /\s/;
    const cocok = (el) => el instanceof HTMLInputElement && el.matches(SELEKTOR) && !el.matches('[data-boleh-spasi]');

    // Keyboard (termasuk keyboard HP yang tidak mengirim keydown yang jelas): tolak sebelum masuk.
    document.addEventListener('beforeinput', function (e) {
        if (cocok(e.target) && e.data && SPASI.test(e.data) && e.inputType !== 'insertFromPaste') {
            e.preventDefault();
        }
    }, true);
    document.addEventListener('keydown', function (e) {
        if (cocok(e.target) && e.key === ' ') e.preventDefault();
    }, true);

    // Tempel: sisipkan versi tanpa spasi di posisi kursor.
    document.addEventListener('paste', function (e) {
        if (!cocok(e.target)) return;
        const teks = (e.clipboardData || window.clipboardData)?.getData('text') ?? '';
        if (!SPASI.test(teks)) return;
        e.preventDefault();
        const bersih = teks.replace(/\s+/g, '');
        // execCommand menjaga undo & bekerja juga di type=email (setRangeText tidak didukung di sana).
        if (!document.execCommand('insertText', false, bersih)) {
            e.target.value = (e.target.value + bersih).replace(/\s+/g, '');
            e.target.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }, true);

    // Jaring pengaman: autofill, seret-lepas, IME, dsb.
    document.addEventListener('input', function (e) {
        const el = e.target;
        if (!cocok(el) || !SPASI.test(el.value)) return;
        let posisi = null;
        try { posisi = el.selectionStart; } catch (_) { /* type=email tidak punya selectionStart */ }
        const sebelum = posisi === null ? 0 : (el.value.slice(0, posisi).match(/\s/g) || []).length;
        el.value = el.value.replace(/\s+/g, '');
        if (posisi !== null) {
            try { el.setSelectionRange(posisi - sebelum, posisi - sebelum); } catch (_) { /* abaikan */ }
        }
    }, true);
})();
</script>
