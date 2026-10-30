<div class="panel p-4 mt-3 border-warning-subtle">
    <h2 class="h6 fw-bold mb-1"><i class="bi bi-shield-lock me-1"></i>Ubah Langsung (Admin)</h2>
    <p class="small text-muted mb-3">Mengubah data & status tanpa mengikuti tahapan persetujuan. Status Disetujui langsung menerbitkan tiket.</p>

    <form action="{{ route('admin.izin-keluar.ubah-admin', $izinKeluar) }}" method="POST" data-confirm="Simpan perubahan langsung pada izin keluar ini?">
        @csrf
        @method('PUT')
        <div class="row g-2">
            @include('admin.partials.status-admin', ['pengajuan' => $izinKeluar])
            <div class="col-md-4">
                <label class="form-label small mb-1">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ old('tanggal', $izinKeluar->tanggal->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Jam Keluar</label>
                <input type="time" name="jam_keluar" class="form-control form-control-sm" value="{{ old('jam_keluar', substr((string) $izinKeluar->jam_keluar, 0, 5)) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Jam Kembali</label>
                <input type="time" name="jam_kembali" class="form-control form-control-sm" value="{{ old('jam_kembali', substr((string) $izinKeluar->jam_kembali, 0, 5)) }}">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="tidak_kembali" value="1" id="admin-tidak-kembali" @checked(old('tidak_kembali', $izinKeluar->tidakKembali()))>
                    <label class="form-check-label small" for="admin-tidak-kembali">Tidak kembali lagi hari itu</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Keperluan</label>
                <select name="keperluan" class="form-select form-select-sm" required>
                    @foreach (\App\Models\PengajuanIzinKeluar::KEPERLUAN as $kode => $label)
                        <option value="{{ $kode }}" @selected(old('keperluan', $izinKeluar->keperluan) === $kode)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label small mb-1">Alasan</label>
                <textarea name="alasan" rows="2" class="form-control form-control-sm" maxlength="500" required>{{ old('alasan', $izinKeluar->alasan) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label small mb-1">Catatan / Alasan Penolakan</label>
                <input type="text" name="catatan_admin" class="form-control form-control-sm" maxlength="1000" value="{{ old('catatan_admin', $izinKeluar->catatan_admin) }}">
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

    <form action="{{ route('admin.izin-keluar.destroy', $izinKeluar) }}" method="POST" class="mt-2"
        data-confirm="Hapus permanen izin keluar {{ $izinKeluar->dataPegawai?->nama_lengkap }}?">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Hapus Pengajuan</button>
    </form>
</div>
