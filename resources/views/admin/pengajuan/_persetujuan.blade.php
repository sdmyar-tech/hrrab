{{--
    Dialog konfirmasi Setujui / Tolak pengajuan (cuti, lembur, izin keluar) — Batch 5. Dipasang SEKALI per halaman.
    Pemicu: elemen [data-persetujuan="setujui|tolak"] dengan
      data-url      : rute approve / reject
      data-judul    : jenis pengajuan, mis. "pengajuan cuti"
      data-konteks  : JSON [[label, nilai], …] — ditampilkan di dialog agar jelas apa yang disetujui/ditolak
      data-pengganti: (opsional) JSON daftar nama → wajib memilih pengganti tugas sebelum menyetujui (cuti tahap pertama)
      data-catatan-sumber: (opsional) selektor textarea berisi alasan yang sudah diketik (kartu tindakan halaman detail)
    Form dikirim biasa (POST + CSRF); toast sukses/galat datang dari sesi seperti aksi lain.
--}}
<x-modal id="modalSetujui" title="Setujui pengajuan?" icon="bi-check2-circle" tone="success">
    <form method="POST" id="formSetujui" novalidate>
        @csrf
        <p class="mb-3">Pengajuan berikut akan <strong>disetujui</strong> pada tahap Anda, lalu diteruskan ke tahap berikutnya bila masih ada.</p>
        <dl class="hr-konteks" data-konteks></dl>
        <div class="mt-3" data-blok-pengganti hidden>
            <label class="form-label required" for="setujui-pengganti">Pengganti tugas</label>
            {{-- Satu kolom per pengganti (bisa lebih dari satu); kolom tambahan diisi skrip di bawah. --}}
            <div class="d-grid gap-2" data-daftar-pengganti>
                <select name="pengganti_nama[]" id="setujui-pengganti" class="form-select" required disabled>
                    <option value="">Pilih pengganti tugas</option>
                </select>
            </div>
            <div class="invalid-feedback">Pilih pengganti tugas sebelum menyetujui.</div>
            <x-button variant="link" size="sm" type="button" icon="bi-plus-circle" class="px-0 mt-1" data-tambah-pengganti>Tambah pengganti</x-button>
            <div class="form-text">Boleh lebih dari satu pengganti. Pengganti yang dipilih langsung final, tidak perlu persetujuan lagi.</div>
        </div>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formSetujui" icon="bi-check-lg" data-kirim-persetujuan>Ya, Setujui</x-button>
    </x-slot:footer>
</x-modal>

<x-modal id="modalTolak" title="Tolak pengajuan?" icon="bi-x-octagon" tone="danger">
    <form method="POST" id="formTolak" novalidate>
        @csrf
        <p class="mb-3">Pengajuan berikut akan <strong>ditolak</strong> dan alurnya berhenti. Pegawai menerima alasan Anda lewat notifikasi.</p>
        <dl class="hr-konteks" data-konteks></dl>
        <div class="mt-3">
            <label class="form-label required" for="tolak-catatan">Alasan penolakan</label>
            <textarea name="catatan_admin" id="tolak-catatan" class="form-control" rows="3" maxlength="1000" required
                placeholder="Tulis alasan yang jelas, contoh: jadwal bentrok dengan ujian akhir; silakan ajukan ulang minggu depan."></textarea>
            <div class="invalid-feedback">Alasan penolakan wajib diisi.</div>
        </div>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" variant="danger" form="formTolak" icon="bi-x-lg" data-kirim-persetujuan>Ya, Tolak</x-button>
    </x-slot:footer>
</x-modal>

@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = { setujui: document.getElementById('modalSetujui'), tolak: document.getElementById('modalTolak') };
        const pengganti = document.getElementById('setujui-pengganti');
        const blokPengganti = modal.setujui.querySelector('[data-blok-pengganti]');
        const alasan = document.getElementById('tolak-catatan');
        const wadahPengganti = modal.setujui.querySelector('[data-daftar-pengganti]');
        const tombolTambah = modal.setujui.querySelector('[data-tambah-pengganti]');
        const MAKS_PENGGANTI = 5;
        let daftarPengganti = [];

        function isiPilihan(select) {
            select.replaceChildren(new Option('Pilih pengganti tugas', ''));
            daftarPengganti.forEach(function (nama) { select.append(new Option(nama, nama)); });
        }

        // Kolom pengganti tambahan (opsional, bisa dihapus).
        tombolTambah.addEventListener('click', function () {
            if (wadahPengganti.querySelectorAll('select').length >= MAKS_PENGGANTI) return;
            const baris = document.createElement('div');
            baris.className = 'input-group';
            baris.dataset.barisTambahan = '1';
            const select = document.createElement('select');
            select.name = 'pengganti_nama[]';
            select.className = 'form-select';
            select.required = true;
            select.setAttribute('aria-label', 'Pengganti tugas tambahan');
            isiPilihan(select);
            const hapus = document.createElement('button');
            hapus.type = 'button';
            hapus.className = 'btn btn-outline-secondary';
            hapus.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
            hapus.setAttribute('aria-label', 'Hapus kolom pengganti ini');
            hapus.addEventListener('click', function () { baris.remove(); tombolTambah.disabled = false; });
            baris.append(select, hapus);
            wadahPengganti.append(baris);
            select.focus();
            tombolTambah.disabled = wadahPengganti.querySelectorAll('select').length >= MAKS_PENGGANTI;
        });

        function isiKonteks(dl, data) {
            dl.replaceChildren();
            data.forEach(function ([label, nilai]) {
                if (nilai === null || nilai === undefined || String(nilai).trim() === '') return;
                const dt = document.createElement('dt');
                const dd = document.createElement('dd');
                dt.textContent = label;
                dd.textContent = nilai;
                dl.append(dt, dd);
            });
        }

        // Buka dialog dari tombol pemicu mana pun (ikon di daftar atau kartu tindakan di detail).
        document.addEventListener('click', function (e) {
            const tombol = e.target.closest('[data-persetujuan]');
            if (!tombol) return;
            const jenis = tombol.dataset.persetujuan === 'tolak' ? 'tolak' : 'setujui';
            const m = modal[jenis];
            const form = m.querySelector('form');
            form.action = tombol.dataset.url;
            form.classList.remove('was-validated');
            m.querySelector('.modal-title').textContent = (jenis === 'setujui' ? 'Setujui ' : 'Tolak ') + (tombol.dataset.judul || 'pengajuan') + '?';
            let konteks = [];
            try { konteks = JSON.parse(tombol.dataset.konteks || '[]'); } catch (err) { konteks = []; }
            isiKonteks(m.querySelector('[data-konteks]'), konteks);

            if (jenis === 'setujui') {
                let daftar = null;
                try { daftar = tombol.dataset.pengganti ? JSON.parse(tombol.dataset.pengganti) : null; } catch (err) { daftar = null; }
                blokPengganti.hidden = !daftar;
                daftarPengganti = daftar || [];
                // Kembalikan ke satu kolom (buang kolom tambahan dari pembukaan sebelumnya).
                wadahPengganti.querySelectorAll('[data-baris-tambahan]').forEach((b) => b.remove());
                pengganti.disabled = !daftar;
                isiPilihan(pengganti);
                tombolTambah.hidden = !daftar;
                tombolTambah.disabled = false;
            } else {
                const sumber = tombol.dataset.catatanSumber ? document.querySelector(tombol.dataset.catatanSumber) : null;
                alasan.value = sumber ? sumber.value.trim() : '';
            }
            bootstrap.Modal.getOrCreateInstance(m).show();
        });

        modal.setujui.addEventListener('shown.bs.modal', function () {
            (pengganti.disabled ? modal.setujui.querySelector('[data-kirim-persetujuan]') : pengganti).focus();
        });
        modal.tolak.addEventListener('shown.bs.modal', function () { alasan.focus(); });

        // Kirim: periksa isian wajib (pengganti / alasan), lalu kunci tombol agar tidak terkirim dua kali.
        Object.values(modal).forEach(function (m) {
            const form = m.querySelector('form');
            const kirim = m.querySelector('[data-kirim-persetujuan]');
            form.addEventListener('submit', function (e) {
                form.classList.add('was-validated');
                if (alasan.form === form) alasan.value = alasan.value.trim();
                if (!form.checkValidity()) {
                    e.preventDefault();
                    const salah = form.querySelector(':invalid');
                    if (salah) salah.focus();
                    return;
                }
                kirim.disabled = true;
                kirim.setAttribute('aria-busy', 'true');
                kirim.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>');
            });
        });

        // Kembali lewat tombol Back (halaman dari cache): lepas kunci tombol.
        window.addEventListener('pageshow', function (e) {
            if (!e.persisted) return;
            document.querySelectorAll('[data-kirim-persetujuan]').forEach(function (b) {
                b.disabled = false;
                b.removeAttribute('aria-busy');
                b.querySelector('.spinner-border')?.remove();
            });
        });
    });
</script>
@endonce
