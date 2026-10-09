{{--
    Modal koreksi absensi (satu untuk seluruh halaman). Dibuka oleh tombol ber-atribut data-koreksi='{...}'
    (lihat _tombol-koreksi). Alur: 1) pilih apa yang terjadi → 2) isi seperlunya → 3) alasan (klik cepat / ketik).
--}}
@php $ikAbs = \App\Support\Absensi\TampilanAbsensi::IKON; @endphp
<div class="modal fade" id="modalKoreksi" tabindex="-1" aria-labelledby="modalKoreksiJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" class="modal-content" id="formKoreksi">
            @csrf
            <input type="hidden" name="tanggal">
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="status_manual">

            <div class="modal-header">
                <div class="min-w-0">
                    <h2 class="modal-title h5 mb-0" id="modalKoreksiJudul" data-k="nama">Koreksi Absensi</h2>
                    <small class="text-muted" data-k="judul"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 align-items-center small bg-body-tertiary rounded-3 px-3 py-2 mb-3">
                    <span class="text-muted"><i class="bi {{ $ikAbs['mesin'] }} me-1"></i>Jam di mesin</span>
                    <strong class="abs-jam" data-k="scan"></strong>
                    <span class="ms-auto text-muted" data-k="surat"></span>
                </div>

                <p class="fw-semibold small mb-2">1. Apa yang perlu diperbaiki?</p>
                <div class="abs-pilihan mb-3" role="radiogroup">
                    <input type="radio" name="mode" value="jam" id="km-jam">
                    <label for="km-jam"><i class="bi bi-clock"></i><strong>Jam masuk/pulang</strong><span class="text-muted">Lupa finger atau jam keliru</span></label>
                    <input type="radio" name="mode" value="tidak_masuk" id="km-tidak">
                    <label for="km-tidak"><i class="bi bi-door-closed"></i><strong>Tidak masuk</strong><span class="text-muted">Izin, sakit, dinas luar</span></label>
                    <input type="radio" name="mode" value="lembur" id="km-lembur">
                    <label for="km-lembur"><i class="bi {{ $ikAbs['lembur'] }}"></i><strong>Lembur</strong><span class="text-muted">Lupa finger saat lembur</span></label>
                </div>

                <p class="fw-semibold small mb-2">2. Isi datanya</p>
                <div class="mb-3" data-mode="jam">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small mb-1" for="k-masuk">Jam masuk</label>
                            <input type="time" name="jam_masuk" id="k-masuk" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1" for="k-keluar">Jam pulang</label>
                            <input type="time" name="jam_keluar" id="k-keluar" class="form-control">
                        </div>
                    </div>
                    <div class="form-text">Isi sesuai jam sebenarnya. Status (On Time / Telat) dihitung otomatis.</div>
                </div>

                <div class="mb-3" data-mode="tidak_masuk">
                    <div class="d-flex flex-wrap gap-2" role="radiogroup" aria-label="Keterangan tidak masuk">
                        @foreach (\App\Models\Absensi::STATUS_MANUAL as $kunci => $label)
                            @continue($kunci === \App\Models\Absensi::MANUAL_HADIR)
                            <button type="button" class="abs-chip" data-status="{{ $kunci }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <div class="form-text">Cuti yang sudah disetujui otomatis tercatat — tidak perlu diisi di sini.</div>
                </div>

                <div class="mb-3" data-mode="lembur">
                    <label class="form-label small mb-1" for="k-lembur">Lama lembur (jam:menit)</label>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <input type="text" name="lembur_manual" id="k-lembur" class="form-control" style="max-width:120px;" placeholder="02:00" pattern="\d{1,2}:\d{2}" inputmode="numeric">
                        @foreach (['01:00', '02:00', '03:00'] as $j)
                            <button type="button" class="abs-chip" data-lembur="{{ $j }}">{{ (int) $j }} jam</button>
                        @endforeach
                    </div>
                    <div class="form-text" data-k="info-lembur">Hanya berlaku bila pegawai punya surat lembur yang disetujui.</div>
                </div>

                <p class="fw-semibold small mb-2">3. Alasan <span class="text-danger">*</span></p>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    @foreach (\App\Support\Absensi\TampilanAbsensi::ALASAN_CEPAT as $a)
                        <button type="button" class="abs-chip" data-alasan="{{ $a }}">{{ $a }}</button>
                    @endforeach
                </div>
                <textarea name="alasan" id="k-alasan" rows="2" class="form-control" maxlength="500" placeholder="Atau tulis alasan lain..." required></textarea>
            </div>

            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-link text-secondary px-0" data-k="reset">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Batalkan koreksi
                </button>
                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('modalKoreksi');
        const form = document.getElementById('formKoreksi');
        if (!form) return;
        const k = (n) => form.querySelector('[data-k="' + n + '"]');
        const alasan = form.alasan;

        function pilihMode(mode) {
            form.querySelectorAll('[name="mode"]').forEach((r) => { r.checked = r.value === mode; });
            form.querySelectorAll('[data-mode]').forEach((el) => { el.hidden = el.dataset.mode !== mode; });
        }
        function pilihStatus(status) {
            form.status_manual.value = status || '';
            form.querySelectorAll('[data-status]').forEach((b) => b.classList.toggle('aktif', b.dataset.status === status));
        }

        form.querySelectorAll('[name="mode"]').forEach((r) => r.addEventListener('change', () => pilihMode(r.value)));
        form.querySelectorAll('[data-status]').forEach((b) => b.addEventListener('click', () => pilihStatus(b.dataset.status)));
        form.querySelectorAll('[data-lembur]').forEach((b) => b.addEventListener('click', () => { form.lembur_manual.value = b.dataset.lembur; }));
        form.querySelectorAll('[data-alasan]').forEach((b) => b.addEventListener('click', function () {
            alasan.value = b.dataset.alasan;
            form.querySelectorAll('[data-alasan]').forEach((x) => x.classList.toggle('aktif', x === b));
        }));

        document.querySelectorAll('[data-koreksi]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const d = JSON.parse(btn.dataset.koreksi);
                form.action = d.url;
                form.tanggal.value = d.tanggal;
                form.aksi.value = 'simpan';
                k('nama').textContent = d.nama;
                k('judul').textContent = d.label;
                k('scan').innerHTML = (d.scan_masuk || '<span class="kosong">–</span>') + ' <span class="panah">→</span> ' + (d.scan_keluar || '<span class="kosong">–</span>');
                k('surat').textContent = d.surat || '';
                k('info-lembur').textContent = d.surat ? d.surat + '. Isi lama lembur sebenarnya.' : 'Pegawai ini tidak punya surat lembur disetujui pada tanggal ini — lembur manual tetap dicatat.';
                form.jam_masuk.value = d.masuk || '';
                form.jam_keluar.value = d.keluar || '';
                form.lembur_manual.value = d.lembur || '';
                alasan.value = d.alasan || '';
                form.querySelectorAll('[data-alasan]').forEach((x) => x.classList.remove('aktif'));
                pilihStatus(d.status && d.status !== 'hadir' ? d.status : '');
                // Mode awal menebak kebutuhan: status manual → "Tidak masuk"; lembur belum terverifikasi → "Lembur"; selain itu "Jam".
                pilihMode(d.status && d.status !== 'hadir' ? 'tidak_masuk' : (d.perlu_lembur ? 'lembur' : 'jam'));
                k('reset').hidden = !d.dikoreksi;
                bootstrap.Modal.getOrCreateInstance(modal).show();
            });
        });

        form.addEventListener('submit', function (e) {
            const mode = form.querySelector('[name="mode"]:checked')?.value;
            if (mode === 'jam') {
                pilihStatus('');
                if (!form.jam_masuk.value && !form.jam_keluar.value) {
                    e.preventDefault();
                    Swal.fire({ icon: 'info', title: 'Isi jam masuk atau pulang', confirmButtonColor: '#880f12' });
                }
            } else if (mode === 'tidak_masuk' && !form.status_manual.value) {
                e.preventDefault();
                Swal.fire({ icon: 'info', title: 'Pilih keterangan', text: 'Izin, Sakit, Dinas Luar, atau Alpa.', confirmButtonColor: '#880f12' });
            } else if (mode === 'lembur' && !/^\d{1,2}:\d{2}$/.test(form.lembur_manual.value)) {
                e.preventDefault();
                Swal.fire({ icon: 'info', title: 'Isi lama lembur', text: 'Format jam:menit, mis. 02:00.', confirmButtonColor: '#880f12' });
            }
        });

        // "Batalkan koreksi" = kembali ke data mesin (tanpa wajib alasan).
        k('reset').addEventListener('click', function () {
            Swal.fire({
                icon: 'question', title: 'Batalkan koreksi?', text: 'Absensi hari ini kembali memakai jam dari mesin.',
                showCancelButton: true, confirmButtonColor: '#880f12', confirmButtonText: 'Ya, batalkan koreksi', cancelButtonText: 'Tidak', reverseButtons: true,
            }).then(function (r) {
                if (!r.isConfirmed) return;
                form.aksi.value = 'reset';
                alasan.required = false;
                form.submit();
            });
        });
        modal.addEventListener('hidden.bs.modal', () => { alasan.required = true; });
    });
</script>
