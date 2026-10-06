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

        <section class="panel p-4" style="max-width:640px;">
            <form action="{{ route('admin.pengajuan-formasi.store') }}" method="POST">
                @csrf

                @if (count($unitOptions ?? []) > 1)
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <select name="unit_kerja" class="form-select @error('unit_kerja') is-invalid @enderror" required>
                            @foreach ($unitOptions as $u)
                                <option value="{{ $u }}" {{ old('unit_kerja') === $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                        @error('unit_kerja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Jabatan/Posisi yang Dibutuhkan</label>
                    <select name="jabatan" class="form-select @error('jabatan') is-invalid @enderror" required>
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach (config('kepegawaian.jabatan') as $j)
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
</main>
@endsection
