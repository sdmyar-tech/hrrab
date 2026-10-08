@extends('layouts.pegawai.app')

@section('content')
@php
    $pegawai = $user->dataPegawai;
    $kepegawaian = $pegawai?->kepegawaian;
    $kontakDarurat = $pegawai?->kontakDarurat;
    $bpjs = $pegawai?->bpjs;
    $dokumen = $pegawai?->dokumen;
    $dokumenList = [
        'pas_foto' => 'Pas Foto',
        'upload_ktp' => 'KTP',
        'upload_kk' => 'Kartu Keluarga',
        'upload_cv' => 'CV',
        'upload_ijazah_terakhir' => 'Ijazah Terakhir',
        'upload_buku_tabungan' => 'Buku Tabungan',
    ];
    $jumlahDokumen = collect(array_keys($dokumenList))->filter(fn ($f) => filled($dokumen?->{$f}))->count();
@endphp
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <p class="eyebrow mb-1">Data Saya</p>
                    <h1 class="h3 mb-1">Data Pegawai</h1>
                    <p class="text-muted mb-0">Data kepegawaian Anda yang tercatat di sistem.</p>
                </div>
            </div>
            <div class="heading-actions">
                <a href="{{ route('pegawai.pengkinian-data') }}" class="btn btn-primary"><i class="bi bi-pencil-square"></i>Perbarui Data</a>
            </div>
        </div>

        <section class="panel mb-3 profil-kepala">
            <img src="{{ $user->fotoUrl() }}" alt="Foto {{ $pegawai?->nama_lengkap }}" class="profil-foto">
            <div class="min-w-0">
                <h2 class="h5 fw-bold mb-1">{{ $pegawai?->nama_lengkap ?? $user->name }}</h2>
                <div class="text-muted">{{ $kepegawaian?->jabatan ?? '-' }} · {{ $kepegawaian?->unit_kerja ?? '-' }}</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge text-bg-light border"><i class="bi bi-person-badge me-1"></i>NIP {{ $user->nip ?? $pegawai?->nip ?? '-' }}</span>
                    @if ($kepegawaian?->status_kerja)<span class="badge text-bg-light border">{{ $kepegawaian->status_kerja }}</span>@endif
                    @if ($kepegawaian?->tanggal_masuk)<span class="badge text-bg-light border">Masuk {{ $kepegawaian->tanggal_masuk->translatedFormat('d M Y') }}</span>@endif
                </div>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-xl-6">
                <section class="panel h-100">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-lines-fill"></i>Identitas Pribadi</h2>
                    @include('admin.pegawai._info-grid', ['items' => [
                        'Nama Lengkap' => $pegawai?->nama_lengkap,
                        'NIK' => $pegawai?->nik,
                        'Email' => $user->email,
                        'No. HP' => $pegawai?->no_hp,
                        'Tempat, Tanggal Lahir' => trim(($pegawai?->tempat_lahir ?? '') . ($pegawai?->tanggal_lahir ? ', ' . $pegawai->tanggal_lahir->translatedFormat('d F Y') : ''), ', '),
                        'Jenis Kelamin' => $pegawai?->jenis_kelamin,
                        'Agama' => $pegawai?->agama,
                        'Golongan Darah' => $pegawai?->golongan_darah,
                        'Status Pernikahan' => $pegawai?->status_pernikahan,
                        'Jumlah Tanggungan' => $pegawai?->jumlah_tanggungan !== null ? (string) $pegawai->jumlah_tanggungan : null,
                    ]])
                </section>
            </div>
            <div class="col-xl-6 d-flex flex-column gap-3">
                <section class="panel">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-briefcase"></i>Kepegawaian</h2>
                    @include('admin.pegawai._info-grid', ['items' => [
                        'Jabatan' => $kepegawaian?->jabatan,
                        'Jabatan Tambahan' => $kepegawaian?->jabatan_tambahan,
                        'Unit Kerja' => $kepegawaian?->unit_kerja,
                        'Lokasi Kerja' => $kepegawaian?->lokasi_kerja,
                        'Tanggal Masuk' => $kepegawaian?->tanggal_masuk?->translatedFormat('d F Y'),
                        'Status Kerja' => $kepegawaian?->status_kerja,
                    ]])
                </section>
                <section class="panel flex-grow-1">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-telephone"></i>Kontak Darurat & BPJS</h2>
                    @include('admin.pegawai._info-grid', ['items' => [
                        'Nama Kerabat' => $kontakDarurat?->nama_kerabat,
                        'Hubungan' => $kontakDarurat?->hubungan_kerabat,
                        'No. HP Kerabat' => $kontakDarurat?->no_hp_kerabat,
                        'BPJS Kesehatan' => $bpjs?->no_bpjs_kesehatan,
                        'BPJS Ketenagakerjaan' => $bpjs?->no_bpjs_ketenagakerjaan,
                        'Faskes Tingkat 1' => $bpjs?->faskes_tingkat_1 && $bpjs->faskes_tingkat_1 !== '-' ? $bpjs->faskes_tingkat_1 : null,
                    ]])
                </section>
            </div>
        </div>

        <section class="panel mt-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h6 fw-bold mb-0"><i class="bi bi-folder2-open"></i>Dokumen</h2>
                <span class="badge {{ $jumlahDokumen === count($dokumenList) ? 'text-bg-success' : 'text-bg-light border' }}">{{ $jumlahDokumen }} / {{ count($dokumenList) }} terunggah</span>
            </div>
            <div class="row g-3">
                @foreach ($dokumenList as $field => $label)
                    <div class="col-md-6 col-xl-4">
                        @include('partials.preview-file', ['path' => $dokumen?->{$field}, 'label' => $label, 'tautanUnggah' => route('pegawai.pengkinian-data')])
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>

@include('partials.preview-file-modal')
@endsection
