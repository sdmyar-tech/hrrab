@extends('layouts.pegawai.app')

@section('title', 'Data Pegawai')

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

    $biodata = [
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
    ];
    $dataKepegawaian = [
        'Jabatan' => $kepegawaian?->jabatan,
        'Jabatan Tambahan' => $kepegawaian?->jabatan_tambahan,
        'Bagian' => $kepegawaian?->unit_kerja,
        'Lokasi Kerja' => $kepegawaian?->lokasi_kerja,
        'Tanggal Masuk' => $kepegawaian?->tanggal_masuk?->translatedFormat('d F Y'),
        'Status Kerja' => $kepegawaian?->status_kerja,
    ];
    $dataKontak = [
        'Nama Kerabat' => $kontakDarurat?->nama_kerabat,
        'Hubungan' => $kontakDarurat?->hubungan_kerabat,
        'No. HP Kerabat' => $kontakDarurat?->no_hp_kerabat,
    ];
    $dataBpjs = [
        'BPJS Kesehatan' => $bpjs?->no_bpjs_kesehatan,
        'BPJS Ketenagakerjaan' => $bpjs?->no_bpjs_ketenagakerjaan,
        'Faskes Tingkat 1' => $bpjs?->faskes_tingkat_1 && $bpjs->faskes_tingkat_1 !== '-' ? $bpjs->faskes_tingkat_1 : null,
    ];
    $lengkap = fn (array $d) => collect($d)->except(['Jabatan Tambahan', 'Golongan Darah'])->every(fn ($v) => filled($v));
    $nav = [
        ['id' => 'bagian-biodata', 'label' => 'Biodata', 'icon' => 'bi-person', 'status' => $lengkap($biodata)],
        ['id' => 'bagian-kepegawaian', 'label' => 'Kepegawaian', 'icon' => 'bi-briefcase', 'status' => $lengkap($dataKepegawaian)],
        ['id' => 'bagian-kontak', 'label' => 'Kontak Darurat', 'icon' => 'bi-telephone', 'status' => $lengkap($dataKontak)],
        ['id' => 'bagian-bpjs', 'label' => 'BPJS', 'icon' => 'bi-heart-pulse', 'status' => null],
        ['id' => 'bagian-dokumen', 'label' => 'Dokumen', 'icon' => 'bi-folder2-open', 'status' => $jumlahDokumen === count($dokumenList)],
    ];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Data Pegawai" eyebrow="Data Saya" subtitle="Data kepegawaian Anda yang tercatat di sistem.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'profile.export', 'eksporTeks' => 'Ekspor Data Saya'])
                <x-button :href="route('pegawai.pengkinian-data')" icon="bi-pencil">Perbarui Data</x-button>
            </x-slot:actions>
        </x-page-header>

        <div class="row g-4 align-items-start">
            {{-- ============ Kiri: identitas + navigasi bagian ============ --}}
            <aside class="col-lg-3 hr-samping">
                <x-card class="mb-3 text-center">
                    <x-avatar :src="$user->fotoUrl()" :name="$pegawai?->nama_lengkap ?? $user->name" size="xl" class="mb-3" />
                    <p class="profil-nama mb-1">{{ $pegawai?->nama_lengkap ?? $user->name }}</p>
                    <p class="small text-muted mb-3">{{ $kepegawaian?->jabatan ?? '-' }} · {{ $kepegawaian?->unit_kerja ?? '-' }}</p>
                    <div class="d-flex flex-wrap justify-content-center gap-1">
                        <x-badge tone="neutral" icon="bi-person-badge">NIP {{ $user->nip ?? $pegawai?->nip ?? '-' }}</x-badge>
                        @if ($kepegawaian?->status_kerja)<x-badge tone="primary">{{ $kepegawaian->status_kerja }}</x-badge>@endif
                    </div>
                </x-card>
                <x-section-nav label="Bagian data pegawai" :items="$nav" />
            </aside>

            {{-- ============ Kanan: kartu per bagian ============ --}}
            <div class="col-lg-9 d-grid gap-3">
                <x-card id="bagian-biodata" title="Biodata" subtitle="Identitas pribadi sesuai KTP." icon="bi-person" class="hr-bagian">
                    @include('admin.pegawai._info-grid', ['items' => $biodata])
                </x-card>
                <x-card id="bagian-kepegawaian" title="Kepegawaian" subtitle="Penempatan dan status kerja." icon="bi-briefcase" class="hr-bagian">
                    @include('admin.pegawai._info-grid', ['items' => $dataKepegawaian])
                </x-card>
                <x-card id="bagian-kontak" title="Kontak Darurat" subtitle="Dihubungi bila terjadi keadaan darurat." icon="bi-telephone" class="hr-bagian">
                    @include('admin.pegawai._info-grid', ['items' => $dataKontak])
                </x-card>
                <x-card id="bagian-bpjs" title="BPJS" icon="bi-heart-pulse" class="hr-bagian">
                    @include('admin.pegawai._info-grid', ['items' => $dataBpjs])
                </x-card>
                <x-card id="bagian-dokumen" title="Dokumen" subtitle="Klik untuk melihat atau perbesar." icon="bi-folder2-open" class="hr-bagian">
                    <x-slot:actions>
                        <x-badge :tone="$jumlahDokumen === count($dokumenList) ? 'success' : 'warning'">{{ $jumlahDokumen }} / {{ count($dokumenList) }} terunggah</x-badge>
                    </x-slot:actions>
                    <div class="row g-3">
                        @foreach ($dokumenList as $field => $label)
                            <div class="col-md-6 col-xl-4">
                                @include('partials.preview-file', ['path' => $dokumen?->{$field}, 'label' => $label, 'tautanUnggah' => route('pegawai.pengkinian-data')])
                            </div>
                        @endforeach
                    </div>
                </x-card>
                <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Ada data yang salah? Data yang sudah terisi diubah lewat Admin SDM; data yang masih kosong bisa diisi di <a href="{{ route('pegawai.pengkinian-data') }}">Pengkinian Data</a>.</p>
            </div>
        </div>
    </div>
</main>

@include('partials.preview-file-modal')
@endsection
