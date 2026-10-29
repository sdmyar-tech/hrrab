<script>
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

    document.querySelectorAll('.btn-confirm-delete').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.closest('form');
            const nama = form?.dataset.nama || 'data ini';

            Swal.fire({
                icon: 'warning',
                title: 'Hapus data ini?',
                html: `Data <strong>"${nama}"</strong> akan dihapus permanen.<br>Tindakan ini tidak bisa dibatalkan.`,
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash"></i> Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    document.querySelectorAll('.btn-confirm-logout').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const form = document.getElementById('logout-form');

            Swal.fire({
                icon: 'question',
                title: 'Keluar dari HRRAB?',
                text: 'Anda perlu masuk lagi untuk melanjutkan.',
                showCancelButton: true,
                confirmButtonColor: '#880f12',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed && form) {
                    form.submit();
                }
            });
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmed === '1') return;

            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                form.reportValidity();
                e.preventDefault();
                return;
            }

            e.preventDefault();
            const msg = form.dataset.confirm || 'Yakin ingin melanjutkan?';

            Swal.fire({
                icon: 'question',
                title: 'Konfirmasi',
                text: msg,
                showCancelButton: true,
                confirmButtonColor: '#880f12',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    form.dataset.confirmed = '1';
                    form.submit();
                }
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
    });

});
</script>
