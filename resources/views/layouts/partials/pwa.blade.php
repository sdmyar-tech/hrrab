{{-- Akses tanpa internet: manifest + service worker (public/sw.js) + pita "Anda sedang offline". --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<style>
    .pita-offline { position: fixed; left: 0; right: 0; bottom: 0; z-index: 3000; padding: .5rem 1rem; text-align: center; font-size: .875rem; font-weight: 600;
        background: #3d2c2e; color: #fff; transform: translateY(100%); transition: transform .25s; }
    .pita-offline.tampil { transform: none; }
</style>
<script>
(function () {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () { navigator.serviceWorker.register('{{ url('sw.js') }}', { scope: '/' }).catch(function () { /* tanpa dukungan: abaikan */ }); });
        // Keluar akun: hapus halaman yang tersimpan di perangkat.
        document.addEventListener('submit', function (e) {
            if (/\/logout\/?$/.test(e.target.action || '') && navigator.serviceWorker.controller) navigator.serviceWorker.controller.postMessage('bersihkan');
        }, true);
    }
    document.addEventListener('DOMContentLoaded', function () {
        var pita = document.createElement('div');
        pita.className = 'pita-offline';
        pita.setAttribute('role', 'status');
        pita.textContent = 'Anda sedang offline. Halaman yang pernah dibuka tetap bisa dibaca; menyimpan data menunggu koneksi.';
        document.body.appendChild(pita);
        var atur = function () { pita.classList.toggle('tampil', !navigator.onLine); };
        addEventListener('online', atur); addEventListener('offline', atur); atur();
    });
})();
</script>
