{{--
    Pilihan jabatan mengikuti bagian (sumber: Master Jabatan → bagian). Pasang pada <select> jabatan atau <input list="..."> jabatan:
        data-jabatan-unit="[name=unit_kerja]"   (selector dropdown bagian di form yang sama), atau
        data-jabatan-unit-tetap="NAMA BAGIAN"   (bagian tetap, mis. form ubah pegawai yang bagiannya terkunci)
        data-jabatan-bebas                      (Mode Admin: jabatan lain tetap tersedia di grup "Jabatan lainnya")
    Select: hanya jabatan bagian terpilih (bagian dengan satu jabatan → langsung terisi); bagian yang belum punya jabatan → semua.
    Jabatan tersimpan di luar bagian tetap tampil sebagai "(nilai sekarang)" saat form dibuka. Server juga memvalidasi (Jabatan::jabatanSah).
--}}
@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const perUnit = @json(\App\Models\Jabatan::perUnit());

        document.querySelectorAll('[data-jabatan-unit], [data-jabatan-unit-tetap]').forEach(function (el) {
            const wadah = el.form || el.closest('form') || document;
            const unit = el.dataset.jabatanUnit ? wadah.querySelector(el.dataset.jabatanUnit) : null;
            const namaUnit = () => (unit ? unit.value : (el.dataset.jabatanUnitTetap || ''));
            if (!unit && !el.dataset.jabatanUnitTetap) return;

            if (el.tagName === 'SELECT') {
                const bebas = el.hasAttribute('data-jabatan-bebas');
                const kosong = Array.from(el.options).filter((o) => o.value === '');
                const semua = Array.from(el.options).filter((o) => o.value !== '').map((o) => [o.value, o.text]);
                const susun = function (otomatis) {
                    const nilai = el.value;
                    const diUnit = perUnit[namaUnit()] || [];
                    el.innerHTML = '';
                    kosong.forEach((o) => el.add(o.cloneNode(true)));
                    const tambah = (wadahOpsi, daftar) => daftar.forEach(([v, t]) => wadahOpsi.append(new Option(t, v, false, v === nilai)));
                    if (!namaUnit() || !diUnit.length) {
                        tambah(el, semua);
                    } else if (bebas) {
                        [['Jabatan di bagian ' + namaUnit(), semua.filter(([v]) => diUnit.includes(v))],
                         ['Jabatan lainnya', semua.filter(([v]) => !diUnit.includes(v))]].forEach(([label, daftar]) => {
                            if (!daftar.length) return;
                            const g = document.createElement('optgroup');
                            g.label = label;
                            tambah(g, daftar);
                            el.append(g);
                        });
                    } else {
                        tambah(el, semua.filter(([v]) => diUnit.includes(v)));
                        // Saat form dibuka: jabatan tersimpan di luar bagian tetap tampil (tidak hilang diam-diam).
                        if (!otomatis && nilai && !diUnit.includes(nilai)) el.append(new Option(nilai + ' (nilai sekarang)', nilai, false, true));
                    }
                    const ada = Array.from(el.options).some((o) => o.value === nilai && nilai !== '');
                    // Bagian dengan satu jabatan → langsung terisi (hanya isian wajib, bukan mis. jabatan tambahan);
                    // jabatan lama yang tidak ada di bagian baru dikosongkan.
                    if (diUnit.length === 1 && !bebas && el.required && (!ada || otomatis)) {
                        el.value = diUnit[0];
                    } else {
                        el.value = ada ? nilai : '';
                    }
                    if (otomatis && el.value !== nilai) el.dispatchEvent(new Event('change', { bubbles: true }));
                };
                if (unit) unit.addEventListener('change', () => susun(true));
                susun(false);
            } else if (el.list) {
                // <input list>: datalist tidak mendukung grup, jadi cukup diurutkan (jabatan bagian terpilih lebih dulu).
                const dl = el.list;
                const semua = Array.from(dl.options).map((o) => o.value);
                const susun = function () {
                    const diUnit = perUnit[namaUnit()] || [];
                    dl.innerHTML = '';
                    [...semua.filter((j) => diUnit.includes(j)), ...semua.filter((j) => !diUnit.includes(j))]
                        .forEach((j) => dl.append(new Option(j)));
                };
                if (unit) unit.addEventListener('change', susun);
                susun();
            }
        });
    });
</script>
@endonce
