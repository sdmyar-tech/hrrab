@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-pilih-semua]').forEach(function (induk) {
            const anak = function () { return Array.from(document.querySelectorAll('input[name="' + induk.dataset.pilihSemua + '"]')); };
            const terlihat = function (c) { return ! c.closest('[hidden]'); };
            const info = induk.closest('.form-check')?.querySelector('[data-jumlah-terpilih]');

            function perbarui() {
                const semua = anak();
                const n = semua.filter(function (c) { return c.checked; }).length;
                induk.checked = n === semua.length && n > 0;
                induk.indeterminate = n > 0 && n < semua.length;
                if (info) info.textContent = n ? '(' + n + ' dari ' + semua.length + ' dipilih)' : '';
            }

            induk.addEventListener('change', function () {
                anak().filter(terlihat).forEach(function (c) { c.checked = induk.checked; });
                perbarui();
            });
            anak().forEach(function (c) { c.addEventListener('change', perbarui); });
            perbarui();
        });
    });
</script>
@endonce
