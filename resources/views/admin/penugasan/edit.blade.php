@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <a href="{{ route('admin.pegawai.show', ['pegawai' => $penugasan->data_pegawai_id, 'tab' => 'penugasan']) }}" class="text-decoration-none text-muted small d-inline-block mb-2">
            <i class="bi bi-arrow-left"></i> Kembali ke detail pegawai
        </a>

        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-shield-lock"></i></span>
                <div>
                    <p class="eyebrow mb-1">Mode Admin</p>
                    <h1 class="h3 mb-1">Ubah {{ $penugasan->jenisLabel() }} Langsung</h1>
                    <p class="text-muted mb-0">{{ $penugasan->dataPegawai?->nama_lengkap }} &middot; koreksi data tanpa aturan mutasi/rangkap.</p>
                </div>
            </div>
        </div>

        <section class="panel p-4" style="max-width: 720px;">
            <form action="{{ route('admin.penugasan.update', $penugasan) }}" method="POST" data-confirm="Simpan perubahan penugasan ini?">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="f-unit-kerja" class="form-label">Unit Kerja</label>
                        <select id="f-unit-kerja" name="unit_kerja" class="form-select @error('unit_kerja') is-invalid @enderror">
                            @foreach (config('kepegawaian.unit_kerja') as $unit)
                                <option value="{{ $unit }}" @selected(old('unit_kerja', $penugasan->unit_kerja) === $unit)>{{ $unit }}</option>
                            @endforeach
                        </select>
                        @error('unit_kerja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="f-jabatan" class="form-label">Jabatan</label>
                        <select id="f-jabatan" name="jabatan" class="form-select" data-jabatan-unit="[name=unit_kerja]">
                            <option value="">-</option>
                            @foreach (\App\Models\Jabatan::daftar() as $j)
                                <option value="{{ $j }}" @selected(old('jabatan', $penugasan->jabatan) === $j)>{{ $j }}</option>
                            @endforeach
                            @if ($penugasan->jabatan && ! in_array($penugasan->jabatan, \App\Models\Jabatan::daftar(), true))
                                <option value="{{ $penugasan->jabatan }}" selected>{{ $penugasan->jabatan }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="f-peran" class="form-label">Peran</label>
                        <select id="f-peran" name="peran" class="form-select">
                            <option value="anggota" @selected(old('peran', $penugasan->peran) === 'anggota')>Anggota</option>
                            <option value="kepala" @selected(old('peran', $penugasan->peran) === 'kepala')>Kepala</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="f-tanggal-mulai" class="form-label">Tanggal Mulai</label>
                        <input id="f-tanggal-mulai" type="date" name="tanggal_mulai" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                            value="{{ old('tanggal_mulai', $penugasan->tanggal_mulai->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="f-tanggal-selesai" class="form-label">Tanggal Selesai</label>
                        <input id="f-tanggal-selesai" type="date" name="tanggal_selesai" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                            value="{{ old('tanggal_selesai', $penugasan->tanggal_selesai?->format('Y-m-d')) }}">
                        @error('tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Kosongkan = masih berlaku.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="f-no-sk" class="form-label">No. SK</label>
                        <input id="f-no-sk" type="text" name="no_sk" class="form-control" maxlength="100" value="{{ old('no_sk', $penugasan->no_sk) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="f-keterangan" class="form-label">Keterangan</label>
                        <input id="f-keterangan" type="text" name="keterangan" class="form-control" maxlength="1000" value="{{ old('keterangan', $penugasan->keterangan) }}">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i>Simpan Langsung</button>
                    <a href="{{ route('admin.pegawai.show', ['pegawai' => $penugasan->data_pegawai_id, 'tab' => 'penugasan']) }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </section>
    </div>
</main>
@include('partials.jabatan-ikut-unit')
@endsection
