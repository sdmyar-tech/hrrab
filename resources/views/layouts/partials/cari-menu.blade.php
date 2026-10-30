{{--
    Palet "Cari menu" (Ctrl+K atau "/"): lompat ke halaman mana pun di sidebar tanpa membuka menu induknya.
    Daftar diambil dari tautan sidebar yang tampil untuk pengguna ini (hak akses sudah tersaring di server).
--}}
<div class="modal fade app-palet" id="paletMenu" tabindex="-1" aria-labelledby="paletMenuJudul" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <h2 class="visually-hidden" id="paletMenuJudul">Cari menu</h2>
            <div class="app-palet-cari">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="paletMenuIsian" autocomplete="off" spellcheck="false"
                    placeholder="Cari menu, contoh: cuti, absensi, pegawai" aria-label="Cari menu"
                    role="combobox" aria-expanded="true" aria-controls="paletMenuDaftar" aria-autocomplete="list">
            </div>
            <ul class="app-palet-daftar" id="paletMenuDaftar" role="listbox" aria-label="Hasil"></ul>
            <div class="px-4 pb-3" data-palet-kosong hidden>
                <x-empty-state icon="bi-search" title="Menu tidak ditemukan" compact>Coba kata lain, mis. "lembur" atau "rekap".</x-empty-state>
            </div>
            <div class="app-palet-kaki" aria-hidden="true">
                <span><kbd>↑</kbd> <kbd>↓</kbd> pilih</span>
                <span><kbd>Enter</kbd> buka</span>
                <span><kbd>Esc</kbd> tutup</span>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('paletMenu');
        if (!modalEl || typeof bootstrap === 'undefined') return;
        const isian = document.getElementById('paletMenuIsian');
        const daftar = document.getElementById('paletMenuDaftar');
        const kosong = modalEl.querySelector('[data-palet-kosong]');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Sumber: semua tautan sidebar (termasuk submenu yang tertutup) + Profil.
        const sumber = Array.from(document.querySelectorAll('#adminSidebar a.app-nav-item[href]')).map((a) => ({
            label: a.querySelector('.app-nav-teks')?.textContent.trim() || a.title,
            bagian: a.dataset.bagian || '',
            ikon: (a.querySelector('.app-nav-ikon')?.className.match(/bi-[\w-]+/) || ['bi-dot'])[0],
            url: a.href,
        }));
        sumber.push({ label: 'Profil Saya', bagian: 'Akun', ikon: 'bi-person', url: @json(route('profile')) });

        let hasil = [];
        let pilih = 0;
        const normal = (s) => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

        function gambar() {
            const kata = normal(isian.value.trim());
            hasil = sumber.filter((m) => !kata || normal(m.label + ' ' + m.bagian).includes(kata));
            pilih = Math.min(pilih, Math.max(hasil.length - 1, 0));
            daftar.innerHTML = '';
            hasil.forEach((m, i) => {
                const li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.id = 'palet-opsi-' + i;
                li.setAttribute('aria-selected', String(i === pilih));
                const a = document.createElement('a');
                a.className = 'app-palet-item' + (i === pilih ? ' is-pilih' : '');
                a.href = m.url;
                a.tabIndex = -1;
                a.innerHTML = '<i class="bi ' + m.ikon + '" aria-hidden="true"></i><span></span><small></small>';
                a.querySelector('span').textContent = m.label;
                a.querySelector('small').textContent = m.bagian;
                a.addEventListener('mousemove', () => { if (pilih !== i) { pilih = i; tandai(); } });
                li.appendChild(a);
                daftar.appendChild(li);
            });
            kosong.hidden = hasil.length > 0;
            isian.setAttribute('aria-activedescendant', hasil.length ? 'palet-opsi-' + pilih : '');
        }

        function tandai() {
            daftar.querySelectorAll('li').forEach((li, i) => {
                li.setAttribute('aria-selected', String(i === pilih));
                li.firstChild.classList.toggle('is-pilih', i === pilih);
            });
            isian.setAttribute('aria-activedescendant', 'palet-opsi-' + pilih);
            daftar.children[pilih]?.scrollIntoView({ block: 'nearest' });
        }

        isian.addEventListener('input', () => { pilih = 0; gambar(); });
        isian.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!hasil.length) return;
                pilih = (pilih + (e.key === 'ArrowDown' ? 1 : -1) + hasil.length) % hasil.length;
                tandai();
            } else if (e.key === 'Enter' && hasil[pilih]) {
                e.preventDefault();
                window.location.href = hasil[pilih].url;
            }
        });
        modalEl.addEventListener('show.bs.modal', () => { isian.value = ''; pilih = 0; gambar(); });
        modalEl.addEventListener('shown.bs.modal', () => isian.focus());

        // Pintasan: Ctrl/⌘+K di mana saja, atau "/" saat tidak sedang mengetik.
        if (/Mac|iPhone|iPad/.test(navigator.platform)) {
            document.querySelectorAll('.app-cari kbd').forEach((k) => { k.textContent = '⌘ K'; });
        }
        document.addEventListener('keydown', (e) => {
            const mengetik = e.target instanceof Element && e.target.closest('input, textarea, select, [contenteditable="true"]');
            if (((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') || (e.key === '/' && !mengetik && !e.ctrlKey && !e.metaKey)) {
                if (document.querySelector('.modal.show:not(#paletMenu), .swal2-container')) return;
                e.preventDefault();
                modal.show();
            }
        });
    });
</script>
