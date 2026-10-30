{{-- Dijalankan di <head> sebelum halaman tampil: tema gelap/terang langsung benar (tanpa kedip putih). Kunci sama dengan main.js. --}}
<script>
    (function () {
        var tema = 'light';
        try {
            var simpan = localStorage.getItem('adminHMD.colorTheme');
            tema = simpan === 'dark' || simpan === 'light' ? simpan
                : (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        } catch (e) { /* penyimpanan diblokir: ikuti terang */ }
        document.documentElement.setAttribute('data-theme', tema);
        document.documentElement.setAttribute('data-bs-theme', tema);
    })();
</script>
