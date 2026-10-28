@extends('layouts.pelamar.app')

@section('content')
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <div class="eyebrow">Pelamar</div>
                    <h1 class="mb-0">Data Pelamar</h1>
                </div>
            </div>
            @if ($pelamar)
                <div class="heading-actions">
                    @include('partials.tombol-ekspor', ['eksporRoute' => 'pelamar.data-pelamar.export', 'eksporTeks' => 'Ekspor Data Saya'])
                </div>
            @endif
        </div>

        <div class="panel p-4">
            <p class="text-muted small mb-4">
                Lengkapi data diri Anda dengan benar. Data ini akan digunakan untuk verifikasi lamaran dan,
                jika Anda diterima bekerja, akan menjadi dasar Data Pegawai Anda.
            </p>

            <form action="{{ route('pelamar.data-pelamar.update') }}" method="POST" enctype="multipart/form-data" data-confirm="Simpan data pelamar ini?">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control @error('nama_lengkap') is-invalid @enderror"
                            value="{{ old('nama_lengkap', $pelamar?->nama_lengkap) }}" required>
                        @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">NIK (No. KTP)</label>
                        <input type="text" name="nik" class="form-control @error('nik') is-invalid @enderror"
                            value="{{ old('nik', $pelamar?->nik) }}" maxlength="20" required>
                        @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control @error('no_hp') is-invalid @enderror"
                            value="{{ old('no_hp', $pelamar?->no_hp) }}" required>
                        @error('no_hp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">NPWP <span class="text-muted">(opsional)</span></label>
                        <input type="text" name="npwp" class="form-control" value="{{ old('npwp', $pelamar?->npwp) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">No. KK</label>
                        <input type="text" name="no_kk" class="form-control @error('no_kk') is-invalid @enderror"
                            value="{{ old('no_kk', $pelamar?->no_kk) }}" required>
                        @error('no_kk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control @error('tempat_lahir') is-invalid @enderror"
                            value="{{ old('tempat_lahir', $pelamar?->tempat_lahir) }}" required>
                        @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control @error('tanggal_lahir') is-invalid @enderror"
                            value="{{ old('tanggal_lahir', optional($pelamar?->tanggal_lahir)->format('Y-m-d')) }}" required>
                        @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="form-select @error('jenis_kelamin') is-invalid @enderror" required>
                            <option value="">-- Pilih --</option>
                            @foreach (['Laki-laki', 'Perempuan'] as $opt)
                                <option value="{{ $opt }}" {{ old('jenis_kelamin', $pelamar?->jenis_kelamin) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Agama</label>
                        <input type="text" name="agama" class="form-control @error('agama') is-invalid @enderror"
                            value="{{ old('agama', $pelamar?->agama) }}" required>
                        @error('agama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Golongan Darah <span class="text-muted">(opsional)</span></label>
                        <select name="golongan_darah" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach (['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-', 'Rh-Null', 'Belum Diketahui'] as $opt)
                                <option value="{{ $opt }}" {{ old('golongan_darah', $pelamar?->golongan_darah) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status Pernikahan</label>
                        <select name="status_pernikahan" class="form-select @error('status_pernikahan') is-invalid @enderror" required>
                            @foreach (['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'] as $opt)
                                <option value="{{ $opt }}" {{ old('status_pernikahan', $pelamar?->status_pernikahan ?? 'Belum Menikah') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('status_pernikahan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Jumlah Tanggungan</label>
                        <input type="number" name="jumlah_tanggungan" class="form-control" min="0" max="20"
                            value="{{ old('jumlah_tanggungan', $pelamar?->jumlah_tanggungan ?? 0) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nama Ibu Kandung</label>
                        <input type="text" name="nama_ibu_kandung" class="form-control @error('nama_ibu_kandung') is-invalid @enderror"
                            value="{{ old('nama_ibu_kandung', $pelamar?->nama_ibu_kandung) }}" required>
                        @error('nama_ibu_kandung')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pendidikan Terakhir</label>
                        <input type="text" name="pendidikan_terakhir" class="form-control @error('pendidikan_terakhir') is-invalid @enderror"
                            value="{{ old('pendidikan_terakhir', $pelamar?->pendidikan_terakhir) }}" placeholder="Contoh: S1 Manajemen" required>
                        @error('pendidikan_terakhir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Alamat KTP</label>
                        <textarea name="alamat_ktp" rows="2" class="form-control @error('alamat_ktp') is-invalid @enderror" required>{{ old('alamat_ktp', $pelamar?->alamat_ktp) }}</textarea>
                        @error('alamat_ktp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Alamat Domisili <span class="text-muted">(opsional, isi jika berbeda dari KTP)</span></label>
                        <textarea name="alamat_domisili" rows="2" class="form-control">{{ old('alamat_domisili', $pelamar?->alamat_domisili) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pas Foto <span class="text-muted">(opsional)</span></label>
                        <input type="file" name="foto" class="form-control @error('foto') is-invalid @enderror" accept="image/*">
                        @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($pelamar?->foto_path)
                            <small class="text-muted d-block mt-1"><i class="bi bi-check-circle text-success"></i> Foto sudah diunggah. Unggah ulang jika ingin mengganti.</small>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">CV / Resume <span class="text-muted">(PDF/DOC, opsional)</span></label>
                        <input type="file" name="cv" class="form-control @error('cv') is-invalid @enderror" accept=".pdf,.doc,.docx">
                        @error('cv')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($pelamar?->cv_path)
                            <small class="text-muted d-block mt-1"><i class="bi bi-check-circle text-success"></i> CV sudah diunggah.</small>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Ijazah <span class="text-muted">(opsional)</span></label>
                        <input type="file" name="ijazah" class="form-control @error('ijazah') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                        @error('ijazah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($pelamar?->ijazah_path)
                            <small class="text-muted d-block mt-1"><i class="bi bi-check-circle text-success"></i> Ijazah sudah diunggah.</small>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Sertifikat <span class="text-muted">(opsional)</span></label>
                        <input type="file" name="sertifikat" class="form-control @error('sertifikat') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                        @error('sertifikat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($pelamar?->sertifikat_path)
                            <small class="text-muted d-block mt-1"><i class="bi bi-check-circle text-success"></i> Sertifikat sudah diunggah.</small>
                        @endif
                    </div>
                </div>

                <hr class="my-4">

                @include('partials.pelamar-riwayat', ['pelamar' => $pelamar])

                <button type="submit" class="btn btn-primary mt-4">
                    <i class="bi bi-save me-1"></i> Simpan Data Pelamar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
