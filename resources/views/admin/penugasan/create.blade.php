@extends('layouts.admin.app')

@section('content')
@php
    $mutasi = $jenis === 'mutasi';
    $hariIni = now()->format('Y-m-d');
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi {{ $mutasi ? 'bi-arrow-left-right' : 'bi-diagram-3' }}"></i></span>
                <div>
                    <p class="eyebrow mb-1">Penugasan</p>
                    <h1 class="h3 mb-1">{{ $mutasi ? 'Mutasi Pegawai' : 'Tambah Rangkap Tugas' }}</h1>
                    <p class="text-muted mb-0">{{ $pegawai->nama_lengkap }} &middot; NIP {{ $pegawai->nip ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <section class="panel p-4">
                    <ul class="nav nav-pills mb-3">
                        <li class="nav-item"><a class="nav-link {{ $mutasi ? 'active' : '' }}" href="{{ route('admin.penugasan.create', ['pegawai' => $pegawai, 'jenis' => 'mutasi']) }}">Mutasi (pindah unit utama)</a></li>
                        <li class="nav-item"><a class="nav-link {{ $mutasi ? '' : 'active' }}" href="{{ route('admin.penugasan.create', ['pegawai' => $pegawai, 'jenis' => 'rangkap']) }}">Rangkap Tugas</a></li>
                    </ul>

                    <form action="{{ route('admin.penugasan.store', $pegawai) }}" method="POST"
                        data-confirm="{{ $mutasi ? 'Simpan mutasi pegawai ini?' : 'Tambahkan rangkap tugas ini?' }}">
                        @csrf
                        <input type="hidden" name="jenis" value="{{ $jenis }}">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ $mutasi ? 'Unit Tujuan' : 'Unit Rangkap' }}</label>
                                <select name="unit_kerja" class="form-select @error('unit_kerja') is-invalid @enderror" required>
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach (config('kepegawaian.unit_kerja') as $unit)
                                        <option value="{{ $unit }}" @selected(old('unit_kerja') === $unit)>{{ $unit }}</option>
                                    @endforeach
                                </select>
                                @error('unit_kerja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Peran di Unit</label>
                                <select name="peran" class="form-select @error('peran') is-invalid @enderror" required>
                                    <option value="anggota" @selected(old('peran', 'anggota') === 'anggota')>Anggota / Staff</option>
                                    <option value="kepala" @selected(old('peran') === 'kepala')>Kepala Unit</option>
                                </select>
                                <div class="form-text">Kepala unit meng-approve cuti/lembur unit itu & jadi interviewer tahap 2.</div>
                                @error('peran')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Jabatan {{ $mutasi ? 'Baru' : 'di Unit Rangkap' }} @unless ($mutasi)<span class="text-muted">(opsional)</span>@endunless</label>
                                <input type="text" name="jabatan" list="daftarJabatan" class="form-control @error('jabatan') is-invalid @enderror"
                                    value="{{ old('jabatan', $mutasi ? $utama?->jabatan : '') }}" {{ $mutasi ? 'required' : '' }} autocomplete="off">
                                <datalist id="daftarJabatan">
                                    @foreach (config('kepegawaian.jabatan') as $j)
                                        <option value="{{ $j }}">
                                    @endforeach
                                </datalist>
                                @error('jabatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if ($mutasi)
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Efektif</label>
                                    <input type="date" name="tanggal_efektif" class="form-control @error('tanggal_efektif') is-invalid @enderror"
                                        value="{{ old('tanggal_efektif', $hariIni) }}" required>
                                    <div class="form-text">Tanggal ke depan = mutasi terjadwal, berlaku otomatis pada tanggal itu.</div>
                                    @error('tanggal_efektif')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @else
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                        value="{{ old('tanggal_mulai', $hariIni) }}" required>
                                    @error('tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Selesai <span class="text-muted">(opsional)</span></label>
                                    <input type="date" name="tanggal_selesai" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                                        value="{{ old('tanggal_selesai') }}">
                                    <div class="form-text">Kosongkan bila belum ditentukan; bisa diakhiri kapan saja.</div>
                                    @error('tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif

                            <div class="col-md-6">
                                <label class="form-label">No. SK <span class="text-muted">(opsional)</span></label>
                                <input type="text" name="no_sk" class="form-control @error('no_sk') is-invalid @enderror" value="{{ old('no_sk') }}" maxlength="100">
                                @error('no_sk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Keterangan <span class="text-muted">(opsional)</span></label>
                                <textarea name="keterangan" rows="2" class="form-control @error('keterangan') is-invalid @enderror">{{ old('keterangan') }}</textarea>
                                @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if ($mutasi)
                                <div class="col-12">
                                    <label class="form-label">Pengajuan cuti/lembur yang masih menunggu
                                        <span class="badge text-bg-{{ $jumlahPending ? 'warning' : 'secondary' }}">{{ $jumlahPending }}</span></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pengajuan_pending" id="pendingLanjut" value="lanjut" @checked(old('pengajuan_pending', 'lanjut') === 'lanjut')>
                                        <label class="form-check-label" for="pendingLanjut">Tetap diproses kepala unit lama <span class="text-muted">(disarankan — terjadi saat masih di unit lama)</span></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pengajuan_pending" id="pendingTolak" value="tolak" @checked(old('pengajuan_pending') === 'tolak')>
                                        <label class="form-check-label" for="pendingTolak">Tolak otomatis, pegawai mengajukan ulang di unit baru</label>
                                    </div>
                                    @error('pengajuan_pending')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> {{ $mutasi ? 'Simpan Mutasi' : 'Simpan Rangkap' }}
                            </button>
                            <a href="{{ route('admin.pegawai.show', ['pegawai' => $pegawai, 'tab' => 'penugasan']) }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </section>
            </div>

            <div class="col-12 col-lg-5">
                <section class="panel p-4">
                    <h2 class="h6 fw-bold mb-3">Penugasan Saat Ini</h2>
                    @forelse ($pegawai->penugasanAktif as $p)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">{{ $p->unit_kerja }}</span>
                                <span>
                                    <span class="badge {{ $p->is_utama ? 'text-bg-primary' : 'text-bg-info' }}">{{ $p->is_utama ? 'Utama' : 'Rangkap' }}</span>
                                    @if ($p->isKepala())<span class="badge text-bg-dark">Kepala</span>@endif
                                </span>
                            </div>
                            <small class="text-muted">{{ $p->jabatan ?? '-' }} &middot; sejak {{ $p->tanggal_mulai->format('d-m-Y') }}</small>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Belum ada penugasan tercatat.</p>
                    @endforelse

                    <hr>
                    <ul class="small text-muted mb-0 ps-3">
                        @if ($mutasi)
                            <li>Penugasan utama lama ditutup sehari sebelum tanggal efektif; rangkap tugas tetap berjalan.</li>
                            <li>Pengajuan yang sudah dibuat tetap tercatat di unit lama (laporan tidak berubah).</li>
                            <li>Bila pegawai ini kepala di unit lama, sistem mengingatkan unit yang jadi tanpa kepala.</li>
                        @else
                            <li>Cuti tetap di-approve kepala unit utama; kepala unit rangkap ikut melihat.</li>
                            <li>Lembur: pegawai memilih unit tempat lembur, di-approve kepala unit itu.</li>
                            <li>Peran Kepala: akun Staff otomatis dinaikkan menjadi Kepala Bagian.</li>
                        @endif
                    </ul>
                </section>
            </div>
        </div>
    </div>
</main>
@endsection
