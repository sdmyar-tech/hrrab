{{-- Pilihan status + tahap untuk form "Ubah Langsung (Admin)" pengajuan cuti/lembur. --}}
<div class="col-md-6">
    <label class="form-label small mb-1">Status</label>
    <select name="status" class="form-select form-select-sm" data-status-admin>
        @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui (final)', 'rejected' => 'Ditolak'] as $nilai => $label)
            <option value="{{ $nilai }}" @selected(old('status', $pengajuan->status) === $nilai)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6" data-tahap-admin>
    <label class="form-label small mb-1">Tahap (bila Menunggu)</label>
    <select name="current_level" class="form-select form-select-sm">
        {{-- Tangga alur: pilihan = tahap tangga pengajuan ini ("tangga:indeks"); selain itu level alur bawaan. --}}
        @foreach (\App\Support\PengajuanWorkflow::pilihanTahapAdmin($pengajuan) as $nilai => $label)
            <option value="{{ $nilai }}" @selected(old('current_level', \App\Support\PengajuanWorkflow::nilaiTahapAdmin($pengajuan)) === $nilai)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-status-admin]').forEach(function (sel) {
            const tahap = sel.closest('form').querySelector('[data-tahap-admin]');
            const atur = function () { tahap.hidden = sel.value !== 'pending'; };
            sel.addEventListener('change', atur);
            atur();
        });
    });
</script>
