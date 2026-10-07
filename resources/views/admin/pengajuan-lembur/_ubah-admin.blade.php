<div class="panel p-4 mt-3 border-warning-subtle">
    <h2 class="h6 fw-bold mb-1"><i class="bi bi-shield-lock me-1"></i>Ubah Langsung (Admin)</h2>
    <p class="small text-muted mb-3">Mengubah data & status tanpa mengikuti tahapan persetujuan. Gunakan untuk koreksi.</p>

    <form action="{{ route('admin.pengajuan-lembur.ubah-admin', $pengajuanLembur) }}" method="POST"
        data-confirm="Simpan perubahan langsung pada pengajuan lembur ini?">
        @csrf
        @method('PUT')
        <div class="row g-2">
            @include('admin.partials.status-admin', ['pengajuan' => $pengajuanLembur])
            <div class="col-md-4">
                <label class="form-label small mb-1">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ old('tanggal', $pengajuanLembur->tanggal->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Jam Mulai</label>
                <input type="time" name="jam_mulai" class="form-control form-control-sm" value="{{ old('jam_mulai', substr((string) $pengajuanLembur->jam_mulai, 0, 5)) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control form-control-sm" value="{{ old('jam_selesai', substr((string) $pengajuanLembur->jam_selesai, 0, 5)) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Jam</label>
                <input type="number" name="jumlah_jam" step="0.25" min="0" max="24" class="form-control form-control-sm" value="{{ old('jumlah_jam', $pengajuanLembur->jumlah_jam) }}" required>
            </div>
            <div class="col-12">
                <label class="form-label small mb-1">Uraian Tugas</label>
                <textarea name="uraian_tugas" rows="2" class="form-control form-control-sm" maxlength="2000" required>{{ old('uraian_tugas', $pengajuanLembur->uraian_tugas) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label small mb-1">Catatan / Alasan</label>
                <input type="text" name="catatan_admin" class="form-control form-control-sm" maxlength="1000" value="{{ old('catatan_admin', $pengajuanLembur->catatan_admin) }}">
            </div>
        </div>
        @if ($errors->any())
            <div class="alert alert-danger small mt-2 mb-0">{{ $errors->first() }}</div>
        @endif
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-save me-1"></i>Simpan Langsung</button>
        </div>
    </form>

    <form action="{{ route('admin.pengajuan-lembur.destroy', $pengajuanLembur) }}" method="POST" class="mt-2"
        data-confirm="Hapus permanen pengajuan lembur {{ $pengajuanLembur->dataPegawai?->nama_lengkap }}?">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Hapus Pengajuan</button>
    </form>
</div>
