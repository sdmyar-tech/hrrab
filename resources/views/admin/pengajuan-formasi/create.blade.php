@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-plus"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">Ajukan Formasi</h1>
                    <p class="text-muted mb-0">Permintaan kekurangan pegawai untuk divisi Anda.</p>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start">
        <div class="col-lg-7 col-xl-8">
        <section class="panel p-4">
            <form action="{{ route('admin.pengajuan-formasi.store') }}" method="POST">
                @csrf

                @if (count($unitOptions ?? []) > 1)
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <select name="unit_kerja" class="form-select @error('unit_kerja') is-invalid @enderror" required>
                            @foreach ($unitOptions as $u)
                                <option value="{{ $u }}" {{ old('unit_kerja', auth()->user()->unitDipimpin()) === $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                        @error('unit_kerja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Jabatan/Posisi yang Dibutuhkan</label>
                    <select name="jabatan" class="form-select @error('jabatan') is-invalid @enderror" required>
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach (\App\Models\Jabatan::daftar() as $j)
                            <option value="{{ $j }}" {{ old('jabatan') == $j ? 'selected' : '' }}>{{ $j }}</option>
                        @endforeach
                    </select>
                    @error('jabatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah Dibutuhkan</label>
                    <input type="number" name="jumlah_dibutuhkan" class="form-control @error('jumlah_dibutuhkan') is-invalid @enderror"
                        value="{{ old('jumlah_dibutuhkan', 1) }}" min="1" max="50" required>
                    @error('jumlah_dibutuhkan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Kualifikasi</label>
                    <textarea name="kualifikasi" rows="3" class="form-control @error('kualifikasi') is-invalid @enderror" required>{{ old('kualifikasi') }}</textarea>
                    @error('kualifikasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Target Waktu Kebutuhan <span class="text-muted">(opsional)</span></label>
                    <input type="date" name="target_waktu" class="form-control @error('target_waktu') is-invalid @enderror"
                        value="{{ old('target_waktu', now()->format('Y-m-d')) }}">
                    @error('target_waktu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Alasan/Justifikasi Kebutuhan</label>
                    <textarea name="alasan" rows="4" class="form-control @error('alasan') is-invalid @enderror" required>{{ old('alasan') }}</textarea>
                    @error('alasan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Kirim Pengajuan
                    </button>
                    <a href="{{ route('admin.pengajuan-formasi.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </section>
        </div>

        <div class="col-lg-5 col-xl-4">
            <section class="panel">
                <h2 class="h6 fw-bold mb-3"><i class="bi bi-signpost-split"></i>Alur Pengajuan</h2>
                <ol class="alur-langkah">
                    <li><strong>Diajukan</strong><span>Anda mengisi kebutuhan pegawai beserta alasannya.</span></li>
                    <li><strong>Direview</strong><span>{{ auth()->user()->isKepalaSdm() ? 'GM' : 'Kepala SDM atau GM' }} memeriksa pengajuan. Unit SMK direview Pembina.</span></li>
                    <li><strong>Lowongan terbit</strong><span>Bila disetujui, lowongan otomatis tampil di halaman publik dan pelamar bisa mulai melamar.</span></li>
                </ol>
                <div class="alert alert-light border small mb-0">
                    <i class="bi bi-lightbulb me-1"></i>Tulis kualifikasi sejelas mungkin (pendidikan, pengalaman, keahlian) agar kandidat yang melamar lebih sesuai.
                </div>
            </section>
        </div>
        </div>
    </div>
</main>
@endsection
