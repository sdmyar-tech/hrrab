{{--
    Aksi pada satu onboarding ($screening):
    - Batalkan: Kepala SDM/GM saat berjalan; Admin kapan saja.
    - Ubah (tanggal, durasi, pengawas, kepala, status, lokasi, arahan) & Hapus: khusus Admin.
--}}
<div class="d-flex flex-wrap gap-2 mt-2 justify-content-end">
    @if ($admin)
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#ubah-onboarding-{{ $screening->id }}">
            <i class="bi bi-pencil me-1"></i> Ubah Onboarding
        </button>
    @endif
    @if ($screening->status !== \App\Models\ScreeningOnboarding::STATUS_DIBATALKAN)
        <form action="{{ route('admin.lamaran.screening.cancel', [$lamaran, $screening]) }}" method="POST" class="form-batal-alasan" data-judul="Batalkan onboarding ini?">
            @csrf
            <input type="hidden" name="catatan" value="">
            <button type="button" class="btn btn-outline-danger btn-sm btn-batal-alasan"><i class="bi bi-x-lg me-1"></i> Batalkan</button>
        </form>
    @endif
    @if ($admin)
        <form action="{{ route('admin.lamaran.screening.destroy', [$lamaran, $screening]) }}" method="POST" data-confirm="Hapus permanen data onboarding ini?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-secondary btn-sm" title="Hapus (Admin)"><i class="bi bi-trash me-1"></i> Hapus</button>
        </form>
    @endif
</div>

@if ($admin)
    <div class="collapse mt-2" id="ubah-onboarding-{{ $screening->id }}">
        <form action="{{ route('admin.lamaran.screening.update', [$lamaran, $screening]) }}" method="POST" class="row g-2 bg-light rounded p-2"
            data-confirm="Simpan perubahan onboarding? (Mode Admin)">
            @csrf @method('PUT')
            <div class="col-md-6">
                <label class="form-label small mb-1">Pengawas</label>
                <select name="pengawas_id" class="form-select form-select-sm" required>
                    @foreach ($pengawasOptions as $opt)
                        <option value="{{ $opt->id }}" @selected($opt->id === $screening->pengawas_id)>{{ $opt->name }} ({{ $opt->roleLabel() }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Kepala Bagian (konfirmasi)</label>
                <select name="kepala_id" class="form-select form-select-sm">
                    <option value="">(tanpa — cukup Kepala SDM)</option>
                    @foreach ($pengawasOptions->whereIn('role', ['kepala_bagian', 'kepala_sdm', 'gm', 'admin']) as $opt)
                        <option value="{{ $opt->id }}" @selected($opt->id === $screening->kepala_id)>{{ $opt->name }} ({{ $opt->roleLabel() }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Hari pertama</label>
                <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $screening->tanggal_mulai->format('Y-m-d') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Durasi (hari kerja)</label>
                <input type="number" name="durasi_hari" class="form-control form-control-sm" value="{{ $screening->durasi_hari }}" min="1" max="365" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    @foreach (['berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $sv => $sl)
                        <option value="{{ $sv }}" @selected($screening->status === $sv)>{{ $sl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" name="lokasi" class="form-control form-control-sm" value="{{ $screening->lokasi }}" placeholder="Lokasi, contoh: Kantor SDM" aria-label="Lokasi" maxlength="255">
            </div>
            <div class="col-md-6">
                <input type="text" name="catatan" class="form-control form-control-sm" value="{{ $screening->catatan }}" placeholder="Arahan, contoh: hadir 07.30 berseragam" aria-label="Arahan" maxlength="1000">
            </div>
            <div class="col-12 d-flex justify-content-between align-items-center">
                <span class="text-muted" style="font-size:.75rem;">Hari terakhir dihitung ulang dari hari pertama + durasi. Hasil diubah lewat tombol konfirmasi.</span>
                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
            </div>
        </form>
    </div>
@endif

@once
    <script>
        // Batalkan dengan alasan wajib (dipakai tombol .btn-batal-alasan).
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.btn-batal-alasan').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const form = btn.closest('form');
                    Swal.fire({
                        icon: 'warning', title: form.dataset.judul || 'Batalkan?', input: 'textarea', inputLabel: 'Alasan',
                        inputPlaceholder: 'Tulis alasan pembatalan…', inputAttributes: { maxlength: 1000 },
                        inputValidator: (v) => (!v || !v.trim()) ? 'Alasan wajib diisi.' : undefined,
                        showCancelButton: true, confirmButtonText: 'Ya, batalkan', cancelButtonText: 'Tidak',
                        confirmButtonColor: '#880f12', reverseButtons: true,
                    }).then(function (r) {
                        if (!r.isConfirmed) return;
                        form.catatan.value = r.value.trim();
                        form.submit();
                    });
                });
            });
        });
    </script>
@endonce
