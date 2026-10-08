{{--
    Modal koreksi absensi (satu untuk seluruh halaman). Tombol pemicu:
    <button data-koreksi='{"url":..., "nama":..., "tanggal":..., "label":..., "masuk":..., "keluar":..., "scan_masuk":..., "scan_keluar":..., "status":..., "lembur":..., "alasan":..., "dikoreksi":bool, "surat":...}'>
--}}
<div class="modal fade" id="modalKoreksi" tabindex="-1" aria-labelledby="modalKoreksiJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="formKoreksi">
            @csrf
            <input type="hidden" name="tanggal">
            <input type="hidden" name="aksi" value="simpan">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 mb-0" id="modalKoreksiJudul">Koreksi Absensi</h2>
                    <small class="text-muted" data-k="judul"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small py-2 mb-3">
                    <i class="bi bi-fingerprint me-1"></i>Data mesin: <strong data-k="scan"></strong>
                    <span class="d-block text-muted" data-k="surat"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label small" for="k-status">Status</label>
                    <select name="status_manual" id="k-status" class="form-select">
                        <option value="">Otomatis dari jam (On Time / Telat / ...)</option>
                        @foreach (\App\Models\Absensi::STATUS_MANUAL as $kunci => $label)
                            <option value="{{ $kunci }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Pilih Izin / Sakit / Dinas Luar / Alpa bila tidak berdasarkan jam finger.</div>
                </div>

                <div class="row g-2 mb-3" data-k="blok-jam">
                    <div class="col-6">
                        <label class="form-label small" for="k-masuk">Jam masuk</label>
                        <input type="time" name="jam_masuk" id="k-masuk" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="k-keluar">Jam keluar</label>
                        <input type="time" name="jam_keluar" id="k-keluar" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small" for="k-lembur">Lembur manual <span class="text-muted">(opsional, JJ:MM)</span></label>
                    <input type="text" name="lembur_manual" id="k-lembur" class="form-control" placeholder="mis. 02:00" pattern="\d{1,2}:\d{2}" inputmode="numeric">
                    <div class="form-text">Isi bila pegawai punya surat lembur tetapi lupa finger / ada kendala. Kosongkan untuk dihitung otomatis.</div>
                </div>

                <div>
                    <label class="form-label small" for="k-alasan">Alasan koreksi <span class="text-danger">*</span></label>
                    <textarea name="alasan" id="k-alasan" rows="2" class="form-control" maxlength="500" placeholder="mis. Lupa finger pulang, dikonfirmasi kepala unit"></textarea>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-k="reset">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Kembalikan ke data mesin
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
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
        const k = (n) => form.querySelector('[data-k="' + n + '"]');
        const statusEl = form.querySelector('[name="status_manual"]');
        const alasan = form.querySelector('[name="alasan"]');

        // Jam hanya relevan untuk status otomatis / Hadir.
        function sinkronStatus() {
            const pakaiJam = statusEl.value === '' || statusEl.value === 'hadir';
            k('blok-jam').hidden = !pakaiJam;
        }
        statusEl.addEventListener('change', sinkronStatus);

        document.querySelectorAll('[data-koreksi]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const d = JSON.parse(btn.dataset.koreksi);
                form.action = d.url;
                form.tanggal.value = d.tanggal;
                form.aksi.value = 'simpan';
                k('judul').textContent = d.nama + ' · ' + d.label;
                k('scan').textContent = (d.scan_masuk || '–') + ' s/d ' + (d.scan_keluar || '–');
                k('surat').textContent = d.surat || '';
                form.jam_masuk.value = d.masuk || '';
                form.jam_keluar.value = d.keluar || '';
                statusEl.value = d.status || '';
                form.lembur_manual.value = d.lembur || '';
                alasan.value = d.alasan || '';
                alasan.required = true;
                k('reset').hidden = !d.dikoreksi;
                sinkronStatus();
                bootstrap.Modal.getOrCreateInstance(modal).show();
            });
        });
        // "Kembalikan ke data mesin": konfirmasi dulu, tanpa wajib alasan.
        k('reset').addEventListener('click', function () {
            Swal.fire({
                icon: 'question', title: 'Kembalikan ke data mesin?', text: 'Koreksi pada hari ini akan dihapus.',
                showCancelButton: true, confirmButtonColor: '#880f12', confirmButtonText: 'Ya, kembalikan', cancelButtonText: 'Batal', reverseButtons: true,
            }).then(function (r) {
                if (!r.isConfirmed) return;
                form.aksi.value = 'reset';
                alasan.required = false;
                form.submit();
            });
        });
    });
</script>
