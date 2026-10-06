@extends(Auth::user()->panelLayout())

@section('content')
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-circle"></i></span>
                <div>
                    <div class="eyebrow">Akun</div>
                    <h1 class="mb-0">Profil Saya</h1>
                </div>
            </div>
        </div>

        @php
            $pegawai = $user->dataPegawai;
            $kepegawaian = $pegawai?->kepegawaian;
            $kontakDarurat = $pegawai?->kontakDarurat;
            $bpjs = $pegawai?->bpjs;
        @endphp

        <div class="row g-3">
            <div class="col-12">
                <div class="panel p-3 p-lg-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $user->fotoUrl() }}" alt="Foto {{ $user->name }}" class="rounded-circle border"
                                style="width:72px;height:72px;object-fit:cover;">
                            <div>
                                <h4 class="mb-1">Informasi Akun</h4>
                                <p class="text-muted small mb-0">Ringkasan data autentikasi dan identitas {{ $user->isPelamar() ? 'pelamar' : 'pegawai' }}.</p>
                                @if ($user->isPelamar())
                                    <a href="{{ route('pelamar.data-pelamar.edit') }}" class="small text-primary">Ubah data & pas foto di Data Pelamar</a>
                                @endif
                                @if (! $user->dataPegawai?->dokumen?->pas_foto && $user->isStaff())
                                    <a href="{{ route('pegawai.pengkinian-data') }}" class="small text-primary">Unggah pas foto lewat Pengkinian Data</a>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($user->isPelamar())
                        @php $pelamar = $user->dataPelamar; @endphp
                        <div class="row g-2">
                            @foreach (['Nama' => $user->name, 'Email' => $user->email, 'No. HP' => $pelamar?->no_hp, 'NIK' => $pelamar?->nik,
                                'Pendidikan Terakhir' => $pelamar?->pendidikan_terakhir, 'Alamat Domisili' => $pelamar?->alamat_domisili] as $label => $nilai)
                                <div class="col-md-6">
                                    <label class="form-label text-muted small">{{ $label }}</label>
                                    <input class="form-control form-control-sm" value="{{ $nilai ?: '-' }}" readonly>
                                </div>
                            @endforeach
                        </div>
                    @else
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nama</label>
                            <input class="form-control form-control-sm" value="{{ $user->name ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Email</label>
                            <input class="form-control form-control-sm" value="{{ $user->email ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">NIP</label>
                            <input class="form-control form-control-sm" value="{{ $user->nip ?? $pegawai?->nip ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">No. HP</label>
                            <input class="form-control form-control-sm" value="{{ $pegawai?->no_hp ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Nama Pegawai</label>
                            <input class="form-control form-control-sm" value="{{ $pegawai?->nama_lengkap ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">NIK</label>
                            <input class="form-control form-control-sm" value="{{ $pegawai?->nik ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Jabatan</label>
                            <input class="form-control form-control-sm" value="{{ $kepegawaian?->jabatan ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Unit Kerja</label>
                            <input class="form-control form-control-sm" value="{{ $kepegawaian?->unit_kerja ?? '-' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Kontak Darurat</label>
                            <input class="form-control form-control-sm" value="{{ $kontakDarurat?->nama_kerabat ?? '-' }} ({{ $kontakDarurat?->no_hp_kerabat ?? '-' }})" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">BPJS Kesehatan</label>
                            <input class="form-control form-control-sm" value="{{ $bpjs?->no_bpjs_kesehatan ?? '-' }}" readonly>
                        </div>
                    </div>                    @endif


                    <div class="mt-4 d-flex gap-2">
                        <a href="{{ route($user->dashboardRoute()) }}" class="btn btn-primary">{{ $user->isPelamar() ? 'Kembali ke Lamaran Saya' : 'Kembali ke Dashboard' }}</a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="btn btn-outline-danger js-confirm"
                                data-confirm="true"
                                data-title="Logout"
                                data-text="Apakah Anda yakin ingin keluar dari sesi login saat ini?"
                                data-confirm-text="Ya, logout"
                                data-cancel-text="Batal">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
