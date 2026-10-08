{{-- Kartu pengaturan unit. Tampil sesuai role yang dipilih (lihat skrip di bawah). --}}
<section class="panel mb-3" data-untuk-role="gm">
    <h2 class="h6 fw-bold mb-1"><i class="bi bi-diagram-3"></i>Divisi Dipegang</h2>
    <p class="small text-muted mb-3">GM meng-approve pengajuan Kepala Bagian & Kepala SDM dari divisi yang dicentang. Kosongkan = semua divisi.</p>

    <select class="form-select form-select-sm mb-3" id="gmBidangPreset">
        <option value="">-- Isi otomatis dari bidang GM --</option>
        @foreach ($gmBidang as $bidang => $units)
            <option value="{{ implode('|', $units) }}">{{ $bidang }}</option>
        @endforeach
    </select>

    <div class="row row-cols-2 row-cols-md-3 g-1 kotak-centang">
        @foreach ($divisiOptions as $divisi)
            <div class="col">
                <div class="form-check">
                    <input class="form-check-input divisi-check" type="checkbox" name="divisi[]" value="{{ $divisi }}" id="divisi-{{ $loop->index }}"
                        {{ in_array($divisi, $selectedDivisi, true) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="divisi-{{ $loop->index }}">{{ $divisi }}</label>
                </div>
            </div>
        @endforeach
    </div>
    @error('divisi')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
    @error('divisi.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
</section>

<section class="panel" data-untuk-role="gm kepala_bagian kepala_sdm staff">
    <h2 class="h6 fw-bold mb-1"><i class="bi bi-person-badge"></i>Kepala Unit</h2>
    <p class="small text-muted mb-3">
        Unit yang dipimpin akun ini (approve tahap Kepala Bagian & interviewer tahap 2). Bisa lebih dari satu.
        Akun <strong>Staff</strong> yang dicentang otomatis jadi Kepala Bagian. Akun harus tertaut ke Data Pegawai (NIP sama).
    </p>
    <div class="row row-cols-2 row-cols-md-3 g-1 kotak-centang">
        @foreach ($divisiOptions as $unit)
            <div class="col">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="kepala_unit[]" value="{{ $unit }}" id="kepala-unit-{{ $loop->index }}"
                        {{ in_array($unit, (array) ($selectedKepalaUnit ?? []), true) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="kepala-unit-{{ $loop->index }}">{{ $unit }}</label>
                </div>
            </div>
        @endforeach
    </div>
    @error('kepala_unit')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
    @error('kepala_unit.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
</section>

<div class="panel kosong-role text-center text-muted small" hidden>
    <i class="bi bi-info-circle d-block fs-4 mb-2 opacity-50"></i>
    <span data-teks-kosong>Role ini tidak memerlukan pengaturan divisi atau unit.</span>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pilihRole = document.querySelector('select[name=role]');
        const kartu = document.querySelectorAll('[data-untuk-role]');
        const kosong = document.querySelector('.kosong-role');

        // Tampilkan kartu sesuai role; input di kartu tersembunyi dinonaktifkan agar tidak ikut terkirim.
        function aturKartu() {
            let tampil = 0;
            kartu.forEach(function (k) {
                const cocok = k.dataset.untukRole.split(' ').includes(pilihRole.value);
                k.hidden = ! cocok;
                k.querySelectorAll('input, select').forEach(function (el) { el.disabled = ! cocok; });
                tampil += cocok ? 1 : 0;
            });
            kosong.hidden = tampil > 0;
            kosong.querySelector('[data-teks-kosong]').textContent = pilihRole.value
                ? 'Role ini tidak memerlukan pengaturan divisi atau unit.'
                : 'Pilih role terlebih dahulu. Pengaturan divisi/kepala unit muncul untuk GM, Kepala SDM, Kepala Bagian, dan Staff.';
        }

        pilihRole.addEventListener('change', aturKartu);
        aturKartu();

        document.getElementById('gmBidangPreset').addEventListener('change', function () {
            if (! this.value) return;
            const units = this.value.split('|');
            document.querySelectorAll('.divisi-check').forEach(function (cb) { cb.checked = units.includes(cb.value); });
        });
    });
</script>
