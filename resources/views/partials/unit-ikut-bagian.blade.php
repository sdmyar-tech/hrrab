{{--
    Unit (Master Unit) mengikuti bagian: unit yang membawahi bagian terpilih langsung terisi & terkunci. Pasang pada <select> unit:
        data-unit-bagian="[name=unit_kerja]"      (selector dropdown bagian di form yang sama), atau
        data-unit-bagian-tetap="NAMA BAGIAN"       (bagian tetap, mis. form ubah pegawai yang bagiannya terkunci)
    Bagian yang belum dimasukkan ke unit mana pun → unit bebas dipilih. Server juga memaksa nilai ini (Unit::unitOrganisasiDari).
--}}
@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const peta = @json(\App\Models\Unit::unitOrganisasiPerBagian());

        document.querySelectorAll('select[data-unit-bagian], select[data-unit-bagian-tetap]').forEach(function (el) {
            const wadah = el.form || el.closest('form') || document;
            const bagian = el.dataset.unitBagian ? wadah.querySelector(el.dataset.unitBagian) : null;
            const info = document.createElement('div');
            info.className = 'form-text';
            info.setAttribute('aria-live', 'polite');
            (el.closest('.hr-field') || el.parentElement).append(info);

            const susun = function () {
                const namaBagian = bagian ? bagian.value : (el.dataset.unitBagianTetap || '');
                const unit = peta[namaBagian] || '';
                // Unit dari bagian: pilihan lain dinonaktifkan (nilai tetap terkirim karena select tidak disabled).
                Array.from(el.options).forEach((o) => { o.disabled = !!unit && o.value !== unit; });
                if (unit) {
                    el.value = unit;
                    el.dataset.otomatis = '1';
                } else if (el.dataset.otomatis) {
                    // Sebelumnya terisi otomatis dari bagian lain → kosongkan agar tidak salah unit.
                    el.value = '';
                    delete el.dataset.otomatis;
                }
                info.textContent = unit ? 'Mengikuti bagian ' + namaBagian + ' (Master Unit).' : '';
            };
            if (bagian) bagian.addEventListener('change', susun);
            susun();
        });
    });
</script>
@endonce
