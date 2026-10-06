@extends('layouts.pegawai.app')

@section('content')
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <div class="eyebrow">Pegawai</div>
                    <h1 class="mb-0">Data Pegawai</h1>
                </div>
            </div>
        </div>

        @php
            $pegawai = $user->dataPegawai;
            $kepegawaian = $pegawai?->kepegawaian;
            $kontakDarurat = $pegawai?->kontakDarurat;
            $bpjs = $pegawai?->bpjs;
            $dokumen = $pegawai?->dokumen;
        @endphp

        <div class="panel p-4">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label text-muted small">Nama Lengkap</label><input class="form-control form-control-sm" value="{{ $pegawai?->nama_lengkap ?? '-' }}" readonly></div>
                <div class="col-md-6"><label class="form-label text-muted small">NIK</label><input class="form-control form-control-sm" value="{{ $pegawai?->nik ?? '-' }}" readonly></div>

                <div class="col-md-6"><label class="form-label text-muted small">NIP</label><input class="form-control form-control-sm" value="{{ $user->nip ?? $pegawai?->nip ?? '-' }}" readonly></div>
                <div class="col-md-6"><label class="form-label text-muted small">Email</label><input class="form-control form-control-sm" value="{{ $user->email ?? '-' }}" readonly></div>

                <div class="col-md-6"><label class="form-label text-muted small">No. HP</label><input class="form-control form-control-sm" value="{{ $pegawai?->no_hp ?? '-' }}" readonly></div>
                <div class="col-md-6"><label class="form-label text-muted small">Jenis Kelamin</label><input class="form-control form-control-sm" value="{{ $pegawai?->jenis_kelamin ?? '-' }}" readonly></div>

                <div class="col-md-6"><label class="form-label text-muted small">Tempat Lahir</label><input class="form-control form-control-sm" value="{{ $pegawai?->tempat_lahir ?? '-' }}" readonly></div>
                <div class="col-md-6"><label class="form-label text-muted small">Tanggal Lahir</label><input class="form-control form-control-sm" value="{{ optional($pegawai?->tanggal_lahir)->format('d-m-Y') ?? '-' }}" readonly></div>

                <div class="col-md-6"><label class="form-label text-muted small">Agama</label><input class="form-control form-control-sm" value="{{ $pegawai?->agama ?? '-' }}" readonly></div>
                <div class="col-md-6"><label class="form-label text-muted small">Golongan Darah</label><input class="form-control form-control-sm" value="{{ $pegawai?->golongan_darah ?? '-' }}" readonly></div>

                <div class="col-md-6"><label class="form-label text-muted small">Status Pernikahan</label><input class="form-control form-control-sm" value="{{ $pegawai?->status_pernikahan ?? '-' }}" readonly></div>
                <div class="col-md-6"><label class="form-label text-muted small">Jumlah Tanggungan</label><input class="form-control form-control-sm" value="{{ $pegawai?->jumlah_tanggungan ?? '-' }}" readonly></div>

                <div class="col-md-6"><label class="form-label text-muted small">Jabatan</label><input class="form-control form-control-sm" value="{{ $kepegawaian?->jabatan ?? '-' }}" readonly></div>
                <div class="col-md-6"><label class="form-label text-muted small">Jabatan Tambahan</label><input class="form-control form-control-sm" value="{{ $kepegawaian?->jabatan_tambahan ?? '-' }}" readonly></div>

                <div class="col-md-6"><label class="form-label text-muted small">Unit Kerja</label><input class="form-control form-control-sm" value="{{ $kepegawaian?->unit_kerja ?? '-' }}" readonly></div>
                <div class="col-md-6"><label class="form-label text-muted small">Lokasi Kerja</label><input class="form-control form-control-sm" value="{{ $kepegawaian?->lokasi_kerja ?? '-' }}" readonly></div>

                <div class="col-md-6"><label class="form-label text-muted small">Tanggal Masuk</label><input class="form-control form-control-sm" value="{{ optional($kepegawaian?->tanggal_masuk)->format('d-m-Y') ?? '-' }}" readonly></div>

                <div class="col-md-6"><label class="form-label text-muted small">Nama Kerabat</label><input class="form-control form-control-sm" value="{{ $kontakDarurat?->nama_kerabat ?? '-' }}" readonly></div>
                <div class="col-md-6"><label class="form-label text-muted small">Hubungan Kerabat</label><input class="form-control form-control-sm" value="{{ $kontakDarurat?->hubungan_kerabat ?? '-' }}" readonly></div>

                <div class="col-md-6"><label class="form-label text-muted small">No. HP Kerabat</label><input class="form-control form-control-sm" value="{{ $kontakDarurat?->no_hp_kerabat ?? '-' }}" readonly></div>

                <div class="col-md-6"><label class="form-label text-muted small">No. BPJS Kesehatan</label><input class="form-control form-control-sm" value="{{ $bpjs?->no_bpjs_kesehatan ?? '-' }}" readonly></div>
                <div class="col-md-6"><label class="form-label text-muted small">No. BPJS Ketenagakerjaan</label><input class="form-control form-control-sm" value="{{ $bpjs?->no_bpjs_ketenagakerjaan ?? '-' }}" readonly></div>
                <div class="col-md-6"><label class="form-label text-muted small">Faskes Tingkat 1</label><input class="form-control form-control-sm" value="{{ $bpjs?->faskes_tingkat_1 ?? '-' }}" readonly></div>
            </div>
        </div>

        <div class="panel p-4 mt-3">
            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-folder2-open me-2"></i>Dokumen</h6>
            @php
                $dokumenList = [
                    'pas_foto' => 'Pas Foto',
                    'upload_ktp' => 'KTP',
                    'upload_kk' => 'Kartu Keluarga',
                    'upload_cv' => 'CV',
                    'upload_ijazah_terakhir' => 'Ijazah Terakhir',
                    'upload_buku_tabungan' => 'Buku Tabungan',
                ];
            @endphp
            <div class="row g-3">
                @foreach ($dokumenList as $field => $label)
                    <div class="col-md-6">
                        @include('partials.preview-file', [
                            'path' => $dokumen?->{$field},
                            'label' => $label,
                        ])
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@include('partials.preview-file-modal')
@endsection
