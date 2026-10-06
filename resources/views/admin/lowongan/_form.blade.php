<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Judul Lowongan</label>
        <input type="text" name="judul" class="form-control @error('judul') is-invalid @enderror"
            value="{{ old('judul', $lowongan->judul ?? '') }}" required>
        @error('judul')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Jumlah Dibutuhkan</label>
        <input type="number" name="jumlah_dibutuhkan" class="form-control @error('jumlah_dibutuhkan') is-invalid @enderror"
            value="{{ old('jumlah_dibutuhkan', $lowongan->jumlah_dibutuhkan ?? 1) }}" min="1" max="50" required>
        @error('jumlah_dibutuhkan')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Unit Kerja</label>
        <select name="unit_kerja" class="form-select @error('unit_kerja') is-invalid @enderror" required>
            <option value="">-- Pilih Unit Kerja --</option>
            @foreach (config('kepegawaian.unit_kerja') as $unit)
                <option value="{{ $unit }}" {{ old('unit_kerja', $lowongan->unit_kerja ?? '') == $unit ? 'selected' : '' }}>{{ $unit }}</option>
            @endforeach
        </select>
        @error('unit_kerja')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Jabatan</label>
        <select name="jabatan" class="form-select @error('jabatan') is-invalid @enderror" required>
            <option value="">-- Pilih Jabatan --</option>
            @foreach (\App\Models\Jabatan::daftar() as $j)
                <option value="{{ $j }}" {{ old('jabatan', $lowongan->jabatan ?? '') == $j ? 'selected' : '' }}>{{ $j }}</option>
            @endforeach
        </select>
        @error('jabatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Lokasi Kerja <span class="text-muted">(opsional)</span></label>
        <select name="lokasi_kerja" class="form-select">
            <option value="">-- Pilih Lokasi Kerja --</option>
            @foreach (config('kepegawaian.lokasi_kerja') as $lokasi)
                <option value="{{ $lokasi }}" {{ old('lokasi_kerja', $lowongan->lokasi_kerja ?? '') == $lokasi ? 'selected' : '' }}>{{ $lokasi }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Tipe Kerja <span class="text-muted">(opsional)</span></label>
        <select name="tipe_kerja" class="form-select">
            <option value="">-- Pilih --</option>
            @foreach (['Full Time', 'Part Time', 'Kontrak', 'Magang'] as $opt)
                <option value="{{ $opt }}" {{ old('tipe_kerja', $lowongan->tipe_kerja ?? '') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Tanggal Tutup <span class="text-muted">(opsional)</span></label>
        <input type="date" name="tanggal_tutup" class="form-control @error('tanggal_tutup') is-invalid @enderror"
            value="{{ old('tanggal_tutup', isset($lowongan) ? optional($lowongan->tanggal_tutup)->format('Y-m-d') : now()->format('Y-m-d')) }}">
        @error('tanggal_tutup')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Deskripsi Pekerjaan</label>
        <textarea name="deskripsi" rows="4" class="form-control @error('deskripsi') is-invalid @enderror">{{ old('deskripsi', $lowongan->deskripsi ?? '') }}</textarea>
        @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Kualifikasi</label>
        <textarea name="kualifikasi" rows="4" class="form-control @error('kualifikasi') is-invalid @enderror">{{ old('kualifikasi', $lowongan->kualifikasi ?? '') }}</textarea>
        @error('kualifikasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Deskripsi & kualifikasi wajib diisi sebelum lowongan bisa dipublikasikan.</small>
    </div>
</div>
