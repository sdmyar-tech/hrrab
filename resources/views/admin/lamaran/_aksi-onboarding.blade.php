{{--
    Aksi pada satu onboarding ($screening):
    - Batalkan: Kepala SDM/GM saat berjalan; Admin kapan saja. Alasan wajib lewat dialog alasan (#modalAlasan di admin.lamaran.show).
    - Ubah (tanggal, durasi, pengawas, kepala, status, lokasi, arahan) & Hapus: khusus Admin.
--}}
<div class="d-flex flex-wrap gap-2 justify-content-end align-items-center mt-3">
    @if ($admin)
        <x-button size="sm" variant="secondary" icon="bi-pencil" data-bs-toggle="collapse" :data-bs-target="'#ubah-onboarding-' . $screening->id"
            aria-expanded="false" :aria-controls="'ubah-onboarding-' . $screening->id">Ubah Onboarding</x-button>
    @endif
    @if ($screening->status !== \App\Models\ScreeningOnboarding::STATUS_DIBATALKAN)
        <form action="{{ route('admin.lamaran.screening.cancel', [$lamaran, $screening]) }}" method="POST" class="form-batal-alasan" data-judul="Batalkan onboarding ini?">
            @csrf
            <input type="hidden" name="catatan" value="">
            <x-button size="sm" variant="danger-outline" icon="bi-x-lg" class="btn-batal-alasan" data-alasan="batal-onboarding"
                :data-konteks="json_encode([
                    ['Kandidat', $lamaran->namaKandidat()],
                    ['Onboarding', $screening->tanggal_mulai->translatedFormat('d M') . ' – ' . $screening->tanggal_selesai->translatedFormat('d M Y') . ' (' . $screening->durasi_hari . ' hari)'],
                    ['Pengawas', $screening->pengawas?->name],
                ], JSON_UNESCAPED_UNICODE)">Batalkan</x-button>
        </form>
    @endif
    @if ($admin)
        <form action="{{ route('admin.lamaran.screening.destroy', [$lamaran, $screening]) }}" method="POST"
            data-confirm="Hapus permanen data onboarding {{ $lamaran->namaKandidat() }} ({{ $screening->tanggal_mulai->translatedFormat('d M') }} – {{ $screening->tanggal_selesai->translatedFormat('d M Y') }})?">
            @csrf @method('DELETE')
            <x-icon-button type="submit" icon="bi-trash" label="Hapus permanen (Admin)" tone="danger" />
        </form>
    @endif
</div>

@if ($admin)
    <div class="collapse" id="ubah-onboarding-{{ $screening->id }}">
        <form action="{{ route('admin.lamaran.screening.update', [$lamaran, $screening]) }}" method="POST" class="row g-3 mt-3 pt-3 border-top"
            data-confirm="Simpan perubahan onboarding {{ $lamaran->namaKandidat() }}? (Mode Admin)">
            @csrf @method('PUT')
            <div class="col-md-6">
                <label class="form-label" for="uo-pengawas-{{ $screening->id }}">Pengawas</label>
                <select name="pengawas_id" id="uo-pengawas-{{ $screening->id }}" class="form-select form-select-sm" required>
                    @foreach ($pengawasOptions as $opt)
                        <option value="{{ $opt->id }}" @selected($opt->id === $screening->pengawas_id)>{{ $opt->name }} ({{ $opt->roleLabel() }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="uo-kepala-{{ $screening->id }}">Kepala Bagian (konfirmasi)</label>
                <select name="kepala_id" id="uo-kepala-{{ $screening->id }}" class="form-select form-select-sm">
                    <option value="">(tanpa — cukup Kepala SDM)</option>
                    @foreach ($pengawasOptions->whereIn('role', ['kepala_bagian', 'kepala_sdm', 'gm', 'admin']) as $opt)
                        <option value="{{ $opt->id }}" @selected($opt->id === $screening->kepala_id)>{{ $opt->name }} ({{ $opt->roleLabel() }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="uo-mulai-{{ $screening->id }}">Hari pertama</label>
                <input type="date" name="tanggal_mulai" id="uo-mulai-{{ $screening->id }}" class="form-control form-control-sm" value="{{ $screening->tanggal_mulai->format('Y-m-d') }}" required>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label" for="uo-durasi-{{ $screening->id }}">Durasi (hari kerja)</label>
                <input type="number" name="durasi_hari" id="uo-durasi-{{ $screening->id }}" class="form-control form-control-sm" value="{{ $screening->durasi_hari }}" min="1" max="365" required>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label" for="uo-status-{{ $screening->id }}">Status</label>
                <select name="status" id="uo-status-{{ $screening->id }}" class="form-select form-select-sm">
                    @foreach (['berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $sv => $sl)
                        <option value="{{ $sv }}" @selected($screening->status === $sv)>{{ $sl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="uo-lokasi-{{ $screening->id }}">Lokasi <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" name="lokasi" id="uo-lokasi-{{ $screening->id }}" class="form-control form-control-sm" value="{{ $screening->lokasi }}" placeholder="Tempat onboarding, contoh: Kantor SDM" maxlength="255">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="uo-arahan-{{ $screening->id }}">Arahan <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" name="catatan" id="uo-arahan-{{ $screening->id }}" class="form-control form-control-sm" value="{{ $screening->catatan }}" placeholder="Arahan untuk kandidat, contoh: hadir 07.30 berseragam" maxlength="1000">
            </div>
            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="form-text m-0">Hari terakhir dihitung ulang dari hari pertama + durasi. Hasil diubah lewat tombol konfirmasi.</span>
                <x-button type="submit" size="sm" icon="bi-check-lg">Simpan</x-button>
            </div>
        </form>
    </div>
@endif
