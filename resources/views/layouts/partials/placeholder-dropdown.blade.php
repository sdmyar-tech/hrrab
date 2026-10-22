{{--
    Placeholder dropdown (opsi bernilai kosong berteks "-- Pilih … --" / "Isi hasil…", atau bertanda data-placeholder)
    tidak bisa dipilih lagi setelah ada pilihan lain. Opsi kosong yang bermakna ("Semua …", "Tidak ada", "Tidak ditautkan", …)
    tetap bisa dipilih. Berlaku untuk semua <select>, termasuk yang opsinya disusun ulang lewat skrip.
--}}
<script>
    (function () {
        const POLA_PLACEHOLDER = /^\s*[-—–]*\s*(pilih|isi hasil)\b/i;
        const cariPlaceholder = (s) => Array.from(s.options)
            .find((o) => o.value === '' && (o.hasAttribute('data-placeholder') || POLA_PLACEHOLDER.test(o.text)));

        function kunci(s) {
            if (!(s instanceof HTMLSelectElement) || s.multiple) return;
            const placeholder = cariPlaceholder(s);
            if (placeholder) placeholder.disabled = s.selectedOptions[0] !== placeholder;
        }

        // Dicek saat dropdown disentuh/berubah (sebelum daftar terbuka), dan sebelum kirim agar placeholder terpilih tetap terkirim "".
        ['focusin', 'mousedown', 'keydown', 'change'].forEach((jenis) => document.addEventListener(jenis, (e) => kunci(e.target), true));
        document.addEventListener('submit', (e) => e.target.querySelectorAll('select').forEach(kunci), true);
        document.addEventListener('reset', () => setTimeout(() => document.querySelectorAll('select').forEach(kunci)), true);
        document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('select').forEach(kunci));
    })();
</script>
