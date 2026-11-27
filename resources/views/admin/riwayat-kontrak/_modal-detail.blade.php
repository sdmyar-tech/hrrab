{{--
    Jendela "Detail kontrak" untuk kolom Aksi tabel. Pasang tombol: <x-icon-button icon="bi-card-list" label="Detail kontrak"
    data-detail-kontrak="{{ url isi }}" data-nama="Nama pegawai" />. Isi dimuat lewat fetch; gagal → pesan + "Coba lagi".
    Pasang sekali per halaman: @include('admin.riwayat-kontrak._modal-detail').
--}}
<x-modal id="modalDetailKontrak" title="Detail Kontrak" icon="bi-journal-text" size="lg" scrollable>
    <div id="isiDetailKontrak" aria-live="polite"></div>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Tutup</x-button>
    </x-slot:footer>
</x-modal>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('modalDetailKontrak');
        const isi = document.getElementById('isiDetailKontrak');
        const judul = document.getElementById('modalDetailKontrak-judul');
        let urlAktif = null;

        function muat() {
            isi.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Memuat detail…</div>';
            fetch(urlAktif, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw new Error(r.status === 403 ? 'Anda tidak berwenang melihat detail ini.' : 'Gagal memuat detail (kode ' + r.status + ').'); return r.text(); })
                .then(function (html) { isi.innerHTML = html; })
                .catch(function (e) {
                    isi.innerHTML = '<div class="alert alert-danger d-flex flex-wrap align-items-center gap-2 mb-0" role="alert"><i class="bi bi-x-octagon-fill"></i><span class="flex-grow-1"></span>'
                        + '<button type="button" class="btn btn-sm btn-outline-danger" data-coba-lagi><i class="bi bi-arrow-clockwise me-1"></i>Coba lagi</button></div>';
                    isi.querySelector('span.flex-grow-1').textContent = e.message || 'Gagal memuat detail. Periksa koneksi lalu coba lagi.';
                });
        }

        document.addEventListener('click', function (e) {
            const tombol = e.target.closest('[data-detail-kontrak]');
            if (tombol) {
                e.preventDefault();
                urlAktif = tombol.dataset.detailKontrak;
                judul.textContent = 'Detail Kontrak' + (tombol.dataset.nama ? ' — ' + tombol.dataset.nama : '');
                bootstrap.Modal.getOrCreateInstance(modal).show(tombol);
                muat();
                return;
            }
            if (e.target.closest('[data-coba-lagi]') && urlAktif) muat();
        });
    });
</script>
