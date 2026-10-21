{{--
    Pilihan jabatan mengikuti unit (sumber: Master Jabatan → unit). Pasang pada <select> jabatan atau <input list="..."> jabatan:
        data-jabatan-unit="[name=unit_kerja]"   (selector dropdown unit di form yang sama)
    Jabatan unit terpilih tampil paling atas ("Jabatan di unit X"), sisanya di "Jabatan lainnya"; nilai terpilih tetap dipertahankan.
--}}
@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const perUnit = @json(\App\Models\Jabatan::perUnit());

        document.querySelectorAll('[data-jabatan-unit]').forEach(function (el) {
            const wadah = el.form || el.closest('form') || document;
            const unit = wadah.querySelector(el.dataset.jabatanUnit);
            if (!unit) return;

            if (el.tagName === 'SELECT') {
                const kosong = Array.from(el.options).filter((o) => o.value === '');
                const semua = Array.from(el.options).filter((o) => o.value !== '').map((o) => [o.value, o.text]);
                const susun = function () {
                    const nilai = el.value;
                    const diUnit = perUnit[unit.value] || [];
                    el.innerHTML = '';
                    kosong.forEach((o) => el.add(o.cloneNode(true)));
                    const tambah = (wadahOpsi, daftar) => daftar.forEach(([v, t]) => wadahOpsi.append(new Option(t, v, false, v === nilai)));
                    if (!unit.value || !diUnit.length) {
                        tambah(el, semua);
                    } else {
                        [['Jabatan di unit ' + unit.value, semua.filter(([v]) => diUnit.includes(v))],
                         ['Jabatan lainnya', semua.filter(([v]) => !diUnit.includes(v))]].forEach(([label, daftar]) => {
                            if (!daftar.length) return;
                            const g = document.createElement('optgroup');
                            g.label = label;
                            tambah(g, daftar);
                            el.append(g);
                        });
                    }
                    el.value = nilai;
                };
                unit.addEventListener('change', susun);
                susun();
            } else if (el.list) {
                // <input list>: datalist tidak mendukung grup, jadi cukup diurutkan (jabatan unit terpilih lebih dulu).
                const dl = el.list;
                const semua = Array.from(dl.options).map((o) => o.value);
                const susun = function () {
                    const diUnit = perUnit[unit.value] || [];
                    dl.innerHTML = '';
                    [...semua.filter((j) => diUnit.includes(j)), ...semua.filter((j) => !diUnit.includes(j))]
                        .forEach((j) => dl.append(new Option(j)));
                };
                unit.addEventListener('change', susun);
                susun();
            }
        });
    });
</script>
@endonce
