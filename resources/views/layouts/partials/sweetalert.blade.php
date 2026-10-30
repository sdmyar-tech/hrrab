<script>
// Dialog SweetAlert di atas modal Bootstrap: focus trap modal menarik fokus kembali ke tombol × modal,
// sehingga Enter menutup modal alih-alih mengonfirmasi dialog. Fokus di dalam dialog tidak diteruskan ke Bootstrap.
window.addEventListener('focusin', function (e) {
    if (e.target instanceof Element && e.target.closest('.swal2-container')) {
        e.stopImmediatePropagation();
    }
}, true);

document.addEventListener('DOMContentLoaded', function () {
    if (typeof Swal === 'undefined') {
        return;
    }

    // Sukses & info: toast di pojok kanan bawah (tidak menghalangi kerja), berhenti saat disorot.
    const toast = Swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        showCloseButton: true,
        timer: 4500,
        timerProgressBar: true,
        didOpen: function (el) {
            el.addEventListener('mouseenter', Swal.stopTimer);
            el.addEventListener('mouseleave', Swal.resumeTimer);
        },
    });

    @if (session('success'))
        toast.fire({ icon: 'success', titleText: @json(session('success')) });
    @endif

    @if (session('info'))
        toast.fire({ icon: 'info', titleText: @json(session('info')) });
    @endif

    {{-- Layout panel menampilkan galat & peringatan sebagai banner di area konten (layouts.partials.umpan-balik).
         Halaman tamu/publik belum punya banner → tetap memakai dialog. --}}
    @unless ($pakaiBanner ?? false)
        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: @json(session('error')),
                confirmButtonColor: '#880f12',
                confirmButtonText: 'Tutup',
            });
        @endif

        @if (session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: @json(session('warning')),
                confirmButtonColor: '#880f12',
                confirmButtonText: 'Mengerti',
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Periksa kembali isian Anda',
                html: `<ul style="text-align:left; padding-left:20px; margin:0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>`,
                confirmButtonColor: '#880f12',
                confirmButtonText: 'Perbaiki',
            });
        @endif
    @endunless

    /*
     * Satu sistem konfirmasi untuk dua cara pemasangan:
     *  - <form data-confirm="Pesan">                         → dialog saat form dikirim;
     *  - <button data-confirm="true" data-title data-text …> → dialog saat tombol diklik
     *    (opsional data-icon, data-confirm-text, data-cancel-text, data-confirm-color).
     * Aksi destruktif (hapus, tolak, batalkan, …) otomatis: ikon peringatan + tombol merah berlabel kata kerjanya.
     * Setelah disetujui form dikirim ulang lewat requestSubmit → validasi, spinner, dan name/value tombol tetap berlaku.
     */
    const KATA_BAHAYA = /\b(hapus|tolak|batalkan|nonaktifkan|kosongkan|keluarkan|cabut)\b/i;

    function konfirmasi(o) {
        const cocok = (o.cariBahaya || '').match(KATA_BAHAYA);
        const bahaya = Boolean(cocok) || o.icon === 'warning';
        const kata = cocok ? cocok[1].charAt(0).toUpperCase() + cocok[1].slice(1).toLowerCase() : null;

        return Swal.fire({
            icon: o.icon || (bahaya ? 'warning' : 'question'),
            title: o.judul || 'Konfirmasi',
            text: o.teks,
            showCancelButton: true,
            confirmButtonColor: o.warna || (bahaya ? '#dc3545' : '#880f12'),
            cancelButtonColor: '#6c757d',
            confirmButtonText: o.ya || (kata ? 'Ya, ' + kata : 'Ya, Lanjutkan'),
            // "Batal" di samping "Ya, Batalkan" membingungkan → "Kembali".
            cancelButtonText: o.batal || (kata === 'Batalkan' ? 'Kembali' : 'Batal'),
            reverseButtons: true,
        }).then((r) => r.isConfirmed);
    }

    function kirimTerkonfirmasi(form, tombol) {
        form.dataset.confirmed = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(tombol && tombol.form === form && tombol.type === 'submit' ? tombol : undefined);
        } else {
            form.submit();
        }
    }

    document.querySelectorAll('form[data-confirm]:not([data-confirm="true"])').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            // Kiriman ulang setelah disetujui: lolos sekali saja.
            if (form.dataset.confirmed === '1') {
                delete form.dataset.confirmed;
                return;
            }
            e.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            const pesan = form.dataset.confirm || 'Yakin ingin melanjutkan?';
            konfirmasi({ teks: pesan, cariBahaya: pesan }).then((ya) => ya && kirimTerkonfirmasi(form, e.submitter));
        });
    });

    document.querySelectorAll('button[data-confirm="true"], input[type="submit"][data-confirm="true"]').forEach(function (tombol) {
        tombol.addEventListener('click', function (e) {
            const form = tombol.form;
            if (!form) return;
            e.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            const d = tombol.dataset;
            konfirmasi({
                judul: d.title,
                teks: d.text || 'Apakah Anda yakin ingin melanjutkan?',
                icon: d.icon,
                ya: d.confirmText,
                batal: d.cancelText,
                warna: d.confirmColor,
                cariBahaya: [d.title, d.confirmText].join(' '),
            }).then(function (ya) {
                if (!ya) return;
                // Form tanpa data-confirm tidak punya penjaga "confirmed" → hapus tanda setelah dikirim.
                kirimTerkonfirmasi(form, tombol);
                delete form.dataset.confirmed;
            });
        });
    });

    function pulihkanTombol(btn) {
        if (btn && btn.dataset.originalText !== undefined) {
            btn.innerHTML = btn.dataset.originalText;
            btn.disabled = false;
            delete btn.dataset.originalText;
        }
    }

    // Spinner hanya untuk form yang benar-benar terkirim. Dicek setelah semua handler submit lain
    // berjalan, supaya batal di dialog konfirmasi / validasi gagal tidak membuat tombol berputar terus.
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            setTimeout(function () {
                if (e.defaultPrevented) return;
                const btn = e.submitter && e.submitter.form === form ? e.submitter : form.querySelector('button[type="submit"]');
                if (btn && !btn.disabled) {
                    btn.dataset.originalText = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
                }
            }, 0);
        });
    });

    // Kembali lewat tombol Back browser (halaman dari cache): kembalikan tombol ke keadaan semula.
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('[data-original-text]').forEach(pulihkanTombol);
        document.querySelectorAll('form[data-confirmed]').forEach((f) => delete f.dataset.confirmed);
    });

});
</script>
