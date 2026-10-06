@extends('layouts.admin.app')

@section('content')
@php
    $jenis = old('jenis', 'luar');
    $posisi = old('posisi', 'lowongan');
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <a href="{{ route('admin.lamaran.index') }}" class="text-decoration-none text-muted small d-inline-block mb-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Lamaran Masuk
        </a>

        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-plus"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">Tambah Kandidat Langsung</h1>
                    <p class="text-muted mb-0">Daftarkan kandidat tanpa melalui lamaran online, misalnya pegawai yang akan dipindah atau naik jabatan, atau calon yang diusulkan pimpinan. Kandidat langsung masuk tahap interview.</p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.lamaran.internal.store') }}" method="POST" data-confirm="Tambahkan kandidat ini dan lanjut ke tahap interview?">
            @csrf

            <div class="row g-3">
                <div class="col-lg-7">
                    <section class="panel p-4 mb-3">
                        <h2 class="h6 fw-bold mb-3">1. Kandidat</h2>
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jenis" id="jenis-luar" value="luar" {{ $jenis === 'luar' ? 'checked' : '' }}>
                                <label class="form-check-label" for="jenis-luar">Calon pegawai baru</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jenis" id="jenis-pegawai" value="pegawai" {{ $jenis === 'pegawai' ? 'checked' : '' }}>
                                <label class="form-check-label" for="jenis-pegawai">Pegawai yayasan (mutasi/promosi)</label>
                            </div>
                        </div>

                        <div data-jenis="pegawai">
                            <label class="form-label">Pilih Pegawai</label>
                            <select name="data_pegawai_id" class="form-select @error('data_pegawai_id') is-invalid @enderror">
                                <option value="">-- Pilih Pegawai --</option>
                                @foreach ($pegawais as $pg)
                                    <option value="{{ $pg->id }}" {{ old('data_pegawai_id') == $pg->id ? 'selected' : '' }}>
                                        {{ $pg->nama_lengkap }} — {{ $pg->kepegawaian?->jabatan ?? '-' }} ({{ $pg->kepegawaian?->unit_kerja ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('data_pegawai_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Data pegawai sudah ada di sistem, tidak perlu diisi ulang.</div>
                        </div>

                        <div data-jenis="luar">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" class="form-control @error('nama_lengkap') is-invalid @enderror" value="{{ old('nama_lengkap') }}">
                                    @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">NIK</label>
                                    <input type="text" name="nik" class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik') }}" maxlength="20">
                                    @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">No. HP / WhatsApp</label>
                                    <input type="text" name="no_hp" class="form-control @error('no_hp') is-invalid @enderror" value="{{ old('no_hp') }}">
                                    @error('no_hp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tempat Lahir</label>
                                    <input type="text" name="tempat_lahir" class="form-control @error('tempat_lahir') is-invalid @enderror" value="{{ old('tempat_lahir') }}">
                                    @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" class="form-control @error('tanggal_lahir') is-invalid @enderror" value="{{ old('tanggal_lahir') }}">
                                    @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-select @error('jenis_kelamin') is-invalid @enderror">
                                        <option value="">-- Pilih --</option>
                                        @foreach (['Laki-laki', 'Perempuan'] as $jk)
                                            <option value="{{ $jk }}" {{ old('jenis_kelamin') === $jk ? 'selected' : '' }}>{{ $jk }}</option>
                                        @endforeach
                                    </select>
                                    @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Agama</label>
                                    <select name="agama" class="form-select @error('agama') is-invalid @enderror">
                                        <option value="">-- Pilih --</option>
                                        @foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $ag)
                                            <option value="{{ $ag }}" {{ old('agama') === $ag ? 'selected' : '' }}>{{ $ag }}</option>
                                        @endforeach
                                    </select>
                                    @error('agama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status Pernikahan</label>
                                    <select name="status_pernikahan" class="form-select @error('status_pernikahan') is-invalid @enderror">
                                        @foreach (['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'] as $sp)
                                            <option value="{{ $sp }}" {{ old('status_pernikahan', 'Belum Menikah') === $sp ? 'selected' : '' }}>{{ $sp }}</option>
                                        @endforeach
                                    </select>
                                    @error('status_pernikahan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Ibu Kandung</label>
                                    <input type="text" name="nama_ibu_kandung" class="form-control @error('nama_ibu_kandung') is-invalid @enderror" value="{{ old('nama_ibu_kandung') }}">
                                    @error('nama_ibu_kandung')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pendidikan Terakhir</label>
                                    <input type="text" name="pendidikan_terakhir" class="form-control @error('pendidikan_terakhir') is-invalid @enderror" value="{{ old('pendidikan_terakhir') }}" placeholder="mis. S1 Akuntansi">
                                    @error('pendidikan_terakhir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">No. KK <span class="text-muted">(opsional)</span></label>
                                    <input type="text" name="no_kk" class="form-control" value="{{ old('no_kk') }}" maxlength="20">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Alamat KTP</label>
                                    <textarea name="alamat_ktp" rows="2" class="form-control @error('alamat_ktp') is-invalid @enderror">{{ old('alamat_ktp') }}</textarea>
                                    @error('alamat_ktp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="form-text mt-2">Biodata dipakai langsung saat kandidat direkrut menjadi pegawai. Bila email sudah pernah terdaftar sebagai pelamar, datanya dipakai ulang.</div>

                            @include('partials.pelamar-riwayat', ['pelamar' => null])
                        </div>
                    </section>
                </div>

                <div class="col-lg-5">
                    <section class="panel p-4 mb-3">
                        <h2 class="h6 fw-bold mb-3">2. Posisi Tujuan</h2>
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="posisi" id="posisi-lowongan" value="lowongan" {{ $posisi === 'lowongan' ? 'checked' : '' }}>
                                <label class="form-check-label" for="posisi-lowongan">Dari lowongan</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="posisi" id="posisi-manual" value="manual" {{ $posisi === 'manual' ? 'checked' : '' }}>
                                <label class="form-check-label" for="posisi-manual">Tanpa lowongan</label>
                            </div>
                        </div>

                        <div data-posisi="lowongan">
                            <label class="form-label">Lowongan</label>
                            <select name="lowongan_id" class="form-select @error('lowongan_id') is-invalid @enderror">
                                <option value="">-- Pilih Lowongan --</option>
                                @foreach ($lowongans as $lw)
                                    <option value="{{ $lw->id }}" {{ old('lowongan_id') == $lw->id ? 'selected' : '' }}>
                                        {{ $lw->judul }} — {{ $lw->unit_kerja }} ({{ $lw->statusLabel() }}, {{ $lw->jumlah_terisi }}/{{ $lw->jumlah_dibutuhkan }})
                                    </option>
                                @endforeach
                            </select>
                            @error('lowongan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Termasuk lowongan yang sudah ditutup, selama kuotanya belum terpenuhi.</div>
                        </div>

                        <div data-posisi="manual">
                            <div class="mb-3">
                                <label class="form-label">Jabatan Tujuan</label>
                                <select name="jabatan_tujuan" class="form-select @error('jabatan_tujuan') is-invalid @enderror">
                                    <option value="">-- Pilih Jabatan --</option>
                                    @foreach (config('kepegawaian.jabatan') as $j)
                                        <option value="{{ $j }}" {{ old('jabatan_tujuan') === $j ? 'selected' : '' }}>{{ $j }}</option>
                                    @endforeach
                                </select>
                                @error('jabatan_tujuan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Unit Kerja Tujuan</label>
                                <select name="unit_kerja_tujuan" class="form-select @error('unit_kerja_tujuan') is-invalid @enderror">
                                    <option value="">-- Pilih Unit Kerja --</option>
                                    @foreach (config('kepegawaian.unit_kerja') as $u)
                                        <option value="{{ $u }}" {{ old('unit_kerja_tujuan') === $u ? 'selected' : '' }}>{{ $u }}</option>
                                    @endforeach
                                </select>
                                @error('unit_kerja_tujuan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Interview 2 otomatis dengan Kepala Bagian unit ini.</div>
                            </div>
                            <div>
                                <label class="form-label">Lokasi Kerja <span class="text-muted">(opsional)</span></label>
                                <select name="lokasi_kerja_tujuan" class="form-select">
                                    <option value="">-- Pilih Lokasi --</option>
                                    @foreach (config('kepegawaian.lokasi_kerja') as $lk)
                                        <option value="{{ $lk }}" {{ old('lokasi_kerja_tujuan') === $lk ? 'selected' : '' }}>{{ $lk }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="panel p-4 mb-3">
                        <h2 class="h6 fw-bold mb-3">3. Pengusul <span class="text-muted fw-normal small">(opsional)</span></h2>
                        <label class="form-label">Diusulkan oleh</label>
                        <input type="text" name="direkomendasikan_oleh" class="form-control" value="{{ old('direkomendasikan_oleh') }}"
                            placeholder="mis. Kepala Bagian Keuangan">
                    </section>

                    <div class="alert alert-secondary small">
                        <i class="bi bi-info-circle me-1"></i>
                        Kandidat langsung masuk tahap <strong>Interview 1 (Kepala SDM)</strong>. Setelah disimpan, Anda mendapat
                        <strong>link jadwal</strong> untuk dibagikan ke kandidat (bisa dibuka tanpa login).
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Simpan & Lanjut ke Interview
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function sinkron(nama, atribut) {
            const nilai = document.querySelector('input[name="' + nama + '"]:checked').value;
            document.querySelectorAll('[' + atribut + ']').forEach(function (blok) {
                const aktif = blok.getAttribute(atribut) === nilai;
                blok.hidden = ! aktif;
                blok.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = ! aktif; });
            });
        }

        [['jenis', 'data-jenis'], ['posisi', 'data-posisi']].forEach(function (pasangan) {
            document.querySelectorAll('input[name="' + pasangan[0] + '"]').forEach(function (radio) {
                radio.addEventListener('change', function () { sinkron(pasangan[0], pasangan[1]); });
            });
            sinkron(pasangan[0], pasangan[1]);
        });
    });
</script>
@endsection
