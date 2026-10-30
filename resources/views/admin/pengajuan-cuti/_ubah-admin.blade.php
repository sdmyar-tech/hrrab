<div class="panel p-4 mt-3 border-warning-subtle">
    <h2 class="h6 fw-bold mb-1"><i class="bi bi-shield-lock me-1"></i>Ubah Langsung (Admin)</h2>
    <p class="small text-muted mb-3">Mengubah data & status tanpa mengikuti tahapan persetujuan. Gunakan untuk koreksi.</p>

    <form action="{{ route('admin.pengajuan-cuti.ubah-admin', $pengajuanCuti) }}" method="POST"
        data-confirm="Simpan perubahan langsung pada pengajuan cuti ini?">
        @csrf
        @method('PUT')
        <div class="row g-2">
            @include('admin.partials.status-admin', ['pengajuan' => $pengajuanCuti])
            <div class="col-md-6">
                <label class="form-label small mb-1">Jenis Cuti</label>
                <select name="jenis_cuti" class="form-select form-select-sm">
                    <option value="tahunan" @selected(old('jenis_cuti', $pengajuanCuti->jenis_cuti) === 'tahunan')>Cuti Tahunan</option>
                    <option value="besar" @selected(old('jenis_cuti', $pengajuanCuti->jenis_cuti) === 'besar')>Cuti Besar</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Kategori Cuti Besar</label>
                <select name="kategori_cuti_besar" class="form-select form-select-sm">
                    <option value="">-</option>
                    @foreach (\App\Models\PengajuanCuti::KATEGORI_BESAR_OPTIONS as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('kategori_cuti_besar', $pengajuanCuti->kategori_cuti_besar) === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ old('tanggal_mulai', $pengajuanCuti->tanggal_mulai->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" class="form-control form-control-sm" value="{{ old('tanggal_selesai', $pengajuanCuti->tanggal_selesai->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Lama (hari)</label>
                <input type="number" name="lama_cuti" min="1" max="365" class="form-control form-control-sm" value="{{ old('lama_cuti', $pengajuanCuti->lama_cuti) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1" for="ubah-pengganti">Pengganti Tugas</label>
                @include('partials.pilih-pegawai', [
                    'name' => 'pengganti_nama', 'id' => 'ubah-pengganti', 'nilai' => 'nama', 'kelas' => 'form-select-sm',
                    'terpilih' => old('pengganti_nama', $pengajuanCuti->pengganti_nama), 'kosong' => 'Belum ditentukan',
                    'utama' => $pengajuanCuti->substituteOptions()->all(), 'labelUtama' => 'Satu bagian', 'kecuali' => [$pengajuanCuti->data_pegawai_id],
                ])
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Catatan / Alasan</label>
                <input type="text" name="catatan_admin" class="form-control form-control-sm" maxlength="1000" value="{{ old('catatan_admin', $pengajuanCuti->catatan_admin) }}">
            </div>
            <div class="col-12">
                <label class="form-label small mb-1">Keterangan Pemohon</label>
                <textarea name="keterangan" rows="2" class="form-control form-control-sm" maxlength="1000">{{ old('keterangan', $pengajuanCuti->keterangan) }}</textarea>
            </div>
        </div>
        {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
        @section('galat-di-halaman', '1')
        @if ($errors->any())
            <div class="alert alert-danger small mt-2 mb-0">{{ $errors->first() }}</div>
        @endif
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-check-lg me-1"></i>Simpan Langsung</button>
        </div>
    </form>

    <form action="{{ route('admin.pengajuan-cuti.destroy', $pengajuanCuti) }}" method="POST" class="mt-2"
        data-confirm="Hapus permanen pengajuan cuti {{ $pengajuanCuti->dataPegawai?->nama_lengkap }}?">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Hapus Pengajuan</button>
    </form>
</div>
