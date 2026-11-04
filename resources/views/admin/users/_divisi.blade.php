{{-- Pengaturan bidang GM & bagian yang dipimpin (di dalam bagian "Role & Akses"). Tampil sesuai role yang dipilih (skrip di bawah). --}}
<div class="hr-subbagian" data-untuk-role="gm">
    <h3 class="hr-subbagian-judul"><i class="bi bi-diagram-3" aria-hidden="true"></i>Divisi Dipegang</h3>
    <p class="hr-subbagian-sub">GM menyetujui pengajuan Kepala Bagian & Kepala SDM dari divisi yang dicentang. Kosongkan = semua divisi.</p>

    <label class="visually-hidden" for="gmBidangPreset">Isi otomatis dari bidang GM</label>
    <select class="form-select form-select-sm mb-3 hr-subbagian-preset" id="gmBidangPreset">
        <option value="">Isi otomatis dari bidang GM</option>
        @foreach ($gmBidang as $bidang => $units)
            <option value="{{ implode('|', $units) }}">{{ $bidang }}</option>
        @endforeach
    </select>

    <div class="hr-pilih-grid">
        @foreach ($divisiOptions as $divisi)
            <div class="form-check">
                <input class="form-check-input divisi-check" type="checkbox" name="divisi[]" value="{{ $divisi }}" id="divisi-{{ $loop->index }}"
                    @checked(in_array($divisi, $selectedDivisi, true))>
                <label class="form-check-label" for="divisi-{{ $loop->index }}">{{ $divisi }}</label>
            </div>
        @endforeach
    </div>
    @error('divisi')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
    @error('divisi.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
</div>

<div class="hr-subbagian" data-untuk-role="gm kepala_bagian kepala_sdm staff">
    <h3 class="hr-subbagian-judul"><i class="bi bi-person-badge" aria-hidden="true"></i>Kepala Bagian</h3>
    <p class="hr-subbagian-sub">
        Bagian yang dipimpin akun ini (menyetujui tahap Kepala Bagian & pewawancara tahap 2). Bisa lebih dari satu.
        Akun <strong>Staff</strong> yang dicentang otomatis menjadi Kepala Bagian.
        <span data-untuk-role="gm">Akun <strong>GM</strong> boleh memimpin bagian <strong>tanpa NIP / Data Pegawai</strong>.</span>
        <span data-untuk-role="kepala_bagian kepala_sdm staff">Untuk role ini akun harus tertaut ke Data Pegawai (pilih pegawai atau isi NIP).</span>
    </p>
    <div class="hr-pilih-grid">
        @foreach ($divisiOptions as $unit)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="kepala_unit[]" value="{{ $unit }}" id="kepala-unit-{{ $loop->index }}"
                    @checked(in_array($unit, (array) ($selectedKepalaUnit ?? []), true))>
                <label class="form-check-label" for="kepala-unit-{{ $loop->index }}">{{ $unit }}</label>
            </div>
        @endforeach
    </div>
    @error('kepala_unit')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
    @error('kepala_unit.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
</div>

<p class="form-text mb-0 kosong-role" hidden><i class="bi bi-info-circle me-1" aria-hidden="true"></i><span data-teks-kosong>Role ini tidak memerlukan pengaturan divisi atau bagian.</span></p>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pilihRole = document.querySelector('select[name=role]');
        const kartu = document.querySelectorAll('[data-untuk-role]');
        const kosong = document.querySelector('.kosong-role');

        // Tampilkan blok sesuai role; input di blok tersembunyi dinonaktifkan agar tidak ikut terkirim.
        function aturKartu() {
            let tampil = 0;
            // Role kustom memakai pengaturan jenis dasarnya.
            const dasar = pilihRole.selectedOptions[0]?.dataset.dasar || pilihRole.value;
            kartu.forEach(function (k) {
                const cocok = k.dataset.untukRole.split(' ').includes(dasar);
                k.hidden = ! cocok;
                k.querySelectorAll('input, select').forEach(function (el) { el.disabled = ! cocok; });
                tampil += cocok ? 1 : 0;
            });
            kosong.hidden = tampil > 0;
            kosong.querySelector('[data-teks-kosong]').textContent = pilihRole.value
                ? 'Role ini tidak memerlukan pengaturan divisi atau bagian.'
                : 'Pilih role terlebih dahulu. Pengaturan divisi/kepala bagian muncul untuk GM, Kepala SDM, Kepala Bagian, dan Staff.';
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
