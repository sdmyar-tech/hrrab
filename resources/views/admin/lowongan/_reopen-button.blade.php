{{--
    Tombol "Buka Kembali" lowongan berstatus Ditutup: dialog memilih tanggal tutup baru → POST admin.lowongan.reopen.
    Param: $lowongan, opsional:
      $ikon (bool)    : tombol ikon untuk kolom aksi tabel (x-icon-button); bila tidak bisa dibuka → pengganti tak terlihat (ikon tetap sejajar).
      $form (string)  : id <form> reopen terpisah (mis. di luar form utama halaman Ubah) → hanya tombolnya yang dirender (atribut form).
    Kuota penuh & bukan Admin → tidak bisa dibuka kembali (label "Kuota terpenuhi").
--}}
@php
    $bisaBuka = $lowongan->bisaDibukaKembali() || ($lowongan->status === \App\Models\Lowongan::STATUS_DITUTUP && auth()->user()->can('sistem.override'));
    $kuotaPenuh = $lowongan->status === \App\Models\Lowongan::STATUS_DITUTUP && $lowongan->isKuotaPenuh();
    // Konteks di dialog: judul + bagian (dipasang lewat textContent, bukan disambung ke HTML).
    $judulDialog = $lowongan->judul . ' · ' . $lowongan->unit_kerja;
@endphp
@if ($bisaBuka && ($ikon ?? false))
    <form action="{{ route('admin.lowongan.reopen', $lowongan) }}" method="POST" class="form-reopen-lowongan">
        @csrf
        <input type="hidden" name="tanggal_tutup" value="">
        <x-icon-button icon="bi-unlock" tone="success" label="Buka kembali" class="btn-reopen-lowongan" :data-judul="$judulDialog" />
    </form>
@elseif ($bisaBuka && ! empty($form))
    <x-button variant="success" size="sm" icon="bi-unlock" class="btn-reopen-lowongan" :form="$form" :data-judul="$judulDialog">Buka Kembali</x-button>
@elseif ($bisaBuka)
    <form action="{{ route('admin.lowongan.reopen', $lowongan) }}" method="POST" class="form-reopen-lowongan">
        @csrf
        <input type="hidden" name="tanggal_tutup" value="">
        <x-button variant="success" size="sm" icon="bi-unlock" class="btn-reopen-lowongan" :data-judul="$judulDialog">Buka Kembali</x-button>
    </form>
@elseif ($ikon ?? false)
    <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
@elseif ($kuotaPenuh)
    <x-badge tone="neutral" icon="bi-check2-all" title="Kuota sudah terpenuhi, tidak bisa dibuka kembali">Kuota terpenuhi</x-badge>
@endif

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Warna tombol dialog dari token ui.css (tanpa hex di sini).
            const token = (nama) => getComputedStyle(document.documentElement).getPropertyValue(nama).trim() || undefined;
            document.querySelectorAll('.btn-reopen-lowongan').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    // Tombol di dalam form-nya sendiri, atau terhubung lewat atribut form="…" (halaman Ubah).
                    const form = btn.form || btn.closest('form');
                    const today = new Date();
                    const adminBebas = @json(auth()->user()->can('sistem.override')); // Admin boleh tanggal lampau
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
                        inputAttributes: adminBebas ? { 'aria-label': 'Tanggal tutup baru' } : { min: toISO(today), 'aria-label': 'Tanggal tutup baru' },
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Buka Kembali',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: token('--hr-success'),
                        cancelButtonColor: token('--hr-gray-500'),
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
