{{--
    Sorot data yang baru ditambah/diubah (flash "sorot" dari App\Http\Middleware\KembaliKeHalamanAsal).
    Baris datanya dinaikkan ke paling atas tabel, disorot beberapa detik, dan digulir ke tengah layar.
    Baris dicari lewat tautan/aksi di dalamnya yang menunjuk ke /<halaman ini>/<id> (tanpa perubahan di tiap halaman daftar).
    Bila barisnya tidak ada di halaman ini (terhalang filter/cari atau ada di halaman lain) pengguna diberi tahu.
--}}
@if (session('sorot'))
    <style>
        @keyframes hr-sorot-baris { 0%, 55% { background-color: rgba(255, 193, 7, .38); } 100% { background-color: transparent; } }
        tr.baris-sorot > td, tr.baris-sorot > th { animation: hr-sorot-baris 5s ease-out 1; }
        tr.baris-sorot > td:first-child, tr.baris-sorot > th:first-child { box-shadow: inset 4px 0 0 #ffc107; }
        @media (prefers-reduced-motion: reduce) { tr.baris-sorot > td, tr.baris-sorot > th { animation: none; background-color: rgba(255, 193, 7, .2); } }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const kunci = @json((string) data_get(session('sorot'), 'kunci'));
            const dasar = location.pathname.replace(/\/+$/, '');
            const cocok = function (nilai) {
                if (!nilai || nilai.charAt(0) === '#') return false;
                let path;
                try { path = new URL(nilai, location.href).pathname.replace(/\/+$/, ''); } catch (e) { return false; }
                return path === dasar + '/' + kunci || path.indexOf(dasar + '/' + kunci + '/') === 0;
            };
            const ATRIBUT = ['href', 'action', 'data-url', 'data-href', 'data-action', 'formaction'];
            let baris = null;
            const kandidat = document.querySelectorAll('tbody [href], tbody [action], tbody [data-url], tbody [data-href], tbody [data-action], tbody [formaction]');
            for (const el of kandidat) {
                if (ATRIBUT.some((a) => cocok(el.getAttribute(a)))) { baris = el.closest('tr'); if (baris) break; }
            }

            if (baris) {
                const badan = baris.parentNode;
                if (badan.firstElementChild !== baris) badan.insertBefore(baris, badan.firstElementChild);
                baris.classList.add('baris-sorot');
                // Setelah skrip lain selesai menata halaman.
                setTimeout(function () { baris.scrollIntoView({ block: 'center', behavior: 'smooth' }); }, 150);
                return;
            }

            // Bukan halaman daftar → tidak perlu pemberitahuan.
            if (!document.querySelector('.hr-filter-bar, .pagination, form.panel-filter')) return;
            const info = 'Perubahan tersimpan. Datanya tidak tampil di halaman ini (terhalang filter/pencarian, atau ada di halaman lain).';
            if (typeof window.hrToast === 'function') window.hrToast('info', info);
            else document.addEventListener('hr:toast-siap', function () { window.hrToast('info', info); }, { once: true });
        });
    </script>
@endif
