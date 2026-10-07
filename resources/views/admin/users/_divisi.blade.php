<div class="mb-3">
    <label class="form-label">Divisi Dipegang <span class="text-muted">(khusus GM)</span></label>

    <select class="form-select form-select-sm mb-2" id="gmBidangPreset" style="max-width:320px;">
        <option value="">-- Pilih bidang GM (isi otomatis) --</option>
        @foreach ($gmBidang as $bidang => $units)
            <option value="{{ implode('|', $units) }}">{{ $bidang }}</option>
        @endforeach
    </select>

    <div class="row row-cols-2 row-cols-md-3 g-1">
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
    <div class="form-text">GM meng-approve pengajuan Kepala Bagian & Kepala SDM dari divisi yang dicentang. Centang unit tambahan untuk memegang unit lain sementara. Kosongkan = semua divisi.</div>
    @error('divisi')<div class="text-danger small">{{ $message }}</div>@enderror
    @error('divisi.*')<div class="text-danger small">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Kepala Unit <span class="text-muted">(bisa lebih dari satu — akun Staff yang dicentang otomatis jadi Kepala Bagian)</span></label>
    <div class="row row-cols-2 row-cols-md-3 g-1">
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
    <div class="form-text">
        Unit yang dipimpin akun ini: approve tahap Kepala Bagian & jadi interviewer 2. Kosongkan = Kepala Bagian/Kepala SDM memimpin unit utamanya sendiri.
        Unit lain yang dicentang dicatat sebagai rangkap kepala di riwayat penugasan pegawai. Akun harus tertaut ke Data Pegawai (isi NIP yang sama).
    </div>
    @error('kepala_unit')<div class="text-danger small">{{ $message }}</div>@enderror
    @error('kepala_unit.*')<div class="text-danger small">{{ $message }}</div>@enderror
</div>

<script>
    document.getElementById('gmBidangPreset').addEventListener('change', function () {
        if (!this.value) return;
        const units = this.value.split('|');
        document.querySelectorAll('.divisi-check').forEach(function (cb) {
            cb.checked = units.includes(cb.value);
        });
    });
</script>
