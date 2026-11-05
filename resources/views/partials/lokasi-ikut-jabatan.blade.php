{{--
    Pilihan lokasi kerja mengikuti bagian + jabatan (sumber: Master Jabatan → Atur lokasi). Pasang pada <select> lokasi:
        data-lokasi-jabatan="[name=jabatan]"          (selector dropdown jabatan di form yang sama)
        data-lokasi-unit="[name=unit_kerja]"          (selector dropdown bagian), atau
        data-lokasi-unit-tetap="SDM"                  (bagian tetap, mis. form ubah pegawai yang bagiannya terkunci)
        data-lokasi-bebas                             (Mode Admin: lokasi lain tetap tersedia di grup "Lokasi lainnya")
    Jabatan bertaut satu lokasi → terisi otomatis; beberapa → hanya lokasi itu yang bisa dipilih; belum diatur → semua lokasi.
--}}
@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const peta = @json(\App\Models\Jabatan::lokasiPerUnit());

        document.querySelectorAll('select[data-lokasi-jabatan]').forEach(function (el) {
            const wadah = el.form || el.closest('form') || document;
            const jabatan = wadah.querySelector(el.dataset.lokasiJabatan);
            const unit = el.dataset.lokasiUnit ? wadah.querySelector(el.dataset.lokasiUnit) : null;
            if (!jabatan) return;
            const bebas = el.hasAttribute('data-lokasi-bebas');
            const kosong = Array.from(el.options).filter((o) => o.value === '');
            const semua = Array.from(el.querySelectorAll('option')).filter((o) => o.value !== '').map((o) => o.value);
            // Keterangan di bawah dropdown (dibuat sekali).
            const info = document.createElement('div');
            info.className = 'form-text';
            info.setAttribute('aria-live', 'polite');
            (el.closest('.hr-field') || el.parentElement).append(info);

            const susun = function (otomatis) {
                const namaUnit = unit ? unit.value : (el.dataset.lokasiUnitTetap || '');
                const tertaut = ((peta[namaUnit] || {})[(jabatan.value || '').toUpperCase()] || []).filter((l) => semua.includes(l));
                const nilai = el.value;
                el.innerHTML = '';
                kosong.forEach((o) => el.add(o.cloneNode(true)));
                const tambah = (tujuan, daftar) => daftar.forEach((l) => tujuan.append(new Option(l, l, false, l === nilai)));
                if (!tertaut.length) {
                    tambah(el, semua);
                    info.textContent = '';
                } else if (bebas) {
                    [['Lokasi jabatan ini', tertaut], ['Lokasi lainnya', semua.filter((l) => !tertaut.includes(l))]].forEach(([label, daftar]) => {
                        if (!daftar.length) return;
                        const g = document.createElement('optgroup');
                        g.label = label;
                        tambah(g, daftar);
                        el.append(g);
                    });
                } else {
                    tambah(el, tertaut);
                    // Saat form dibuka: lokasi tersimpan yang di luar tautan tetap tampil (tidak hilang diam-diam).
                    if (!otomatis && nilai && !tertaut.includes(nilai)) el.append(new Option(nilai + ' (nilai sekarang)', nilai, false, true));
                }
                // Satu lokasi → terisi otomatis; lokasi lama yang tidak lagi tersedia dikosongkan agar dipilih ulang.
                if (tertaut.length === 1 && (otomatis || !nilai)) {
                    el.value = tertaut[0];
                } else {
                    el.value = Array.from(el.options).some((o) => o.value === nilai) ? nilai : '';
                }
                if (tertaut.length) {
                    info.textContent = tertaut.length === 1
                        ? 'Lokasi ' + jabatan.value + ' di bagian ' + namaUnit + ' terisi otomatis.'
                        : 'Lokasi untuk ' + jabatan.value + ' di bagian ' + namaUnit + ': ' + tertaut.join(', ') + '.';
                }
                el.dispatchEvent(new Event('change', { bubbles: true }));
            };
            jabatan.addEventListener('change', () => susun(true));
            if (unit) unit.addEventListener('change', () => susun(true));
            susun(false);
        });
    });
</script>
@endonce
