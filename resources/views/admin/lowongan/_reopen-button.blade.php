@if ($lowongan->bisaDibukaKembali() || ($lowongan->status === \App\Models\Lowongan::STATUS_DITUTUP && auth()->user()->isAdmin()))
    <form action="{{ route('admin.lowongan.reopen', $lowongan) }}" method="POST" class="form-reopen-lowongan">
        @csrf
        <input type="hidden" name="tanggal_tutup" value="">
        <button type="button" class="btn btn-outline-success {{ ($small ?? false) ? 'btn-sm' : '' }} btn-reopen-lowongan"
            data-judul="{{ $lowongan->judul }}">
            <i class="bi bi-unlock{{ ($small ?? false) ? '' : ' me-1' }}"></i> Buka Kembali
        </button>
    </form>
@elseif ($lowongan->status === \App\Models\Lowongan::STATUS_DITUTUP && $lowongan->isKuotaPenuh())
    <span class="badge text-bg-light border align-self-center" title="Kuota sudah terpenuhi, tidak bisa dibuka kembali">
        <i class="bi bi-check2-all"></i> Kuota terpenuhi
    </span>
@endif

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.btn-reopen-lowongan').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const form = btn.closest('form');
                    const today = new Date();
                    const adminBebas = @json(auth()->user()->isAdmin()); // Admin boleh tanggal lampau
                    const toISO = function (d) {
                        return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
                    };
                    const defaultTutup = new Date(today);
                    defaultTutup.setDate(defaultTutup.getDate() + 14);

                    Swal.fire({
                        icon: 'question',
                        title: 'Buka Kembali Lowongan?',
                        // Judul lowongan diisi lewat textContent (bukan disambung ke HTML) agar tidak bisa menyisipkan skrip.
                        html: '<div class="small text-muted mb-2" data-judul></div>Pilih tanggal tutup baru (hari terakhir menerima lamaran):',
                        didOpen: function (el) { el.querySelector('[data-judul]').textContent = btn.dataset.judul; },
                        input: 'date',
                        inputValue: toISO(defaultTutup),
                        inputAttributes: adminBebas ? {} : { min: toISO(today) },
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Buka Kembali',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#6c757d',
                        reverseButtons: true,
                        preConfirm: function (value) {
                            if (!value) {
                                Swal.showValidationMessage('Tanggal tutup baru wajib diisi.');
                            } else if (!adminBebas && value < toISO(today)) {
                                Swal.showValidationMessage('Tanggal tutup baru minimal hari ini.');
                            }
                            return value;
                        },
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.querySelector('[name="tanggal_tutup"]').value = result.value;
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endonce
