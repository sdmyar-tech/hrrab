@extends(Auth::user()->panelLayout())

@section('content')
@php
    $pegawai = $user->dataPegawai;
    $kepegawaian = $pegawai?->kepegawaian;
    $kontakDarurat = $pegawai?->kontakDarurat;
    $bpjs = $pegawai?->bpjs;
    $pelamar = $user->dataPelamar;
    $tautanUbah = match (true) {
        $user->isPelamar() => [route('pelamar.data-pelamar.edit'), 'Ubah Data Pelamar'],
        $pegawai !== null && Route::has('pegawai.pengkinian-data') && $user->isStaff() => [route('pegawai.pengkinian-data'), 'Perbarui Data'],
        default => null,
    };
@endphp
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-circle"></i></span>
                <div>
                    <p class="eyebrow mb-1">Akun</p>
                    <h1 class="h3 mb-1">Profil Saya</h1>
                    <p class="text-muted mb-0">Informasi akun login dan identitas Anda.</p>
                </div>
            </div>
            <div class="heading-actions">
                <a href="{{ route($user->dashboardRoute()) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i>{{ $user->isPelamar() ? 'Lamaran Saya' : 'Dashboard' }}</a>
                @if ($user->isPelamar())
                    @if ($user->dataPelamar)
                        @include('partials.tombol-ekspor', ['eksporRoute' => 'pelamar.data-pelamar.export', 'eksporTeks' => 'Ekspor Data Saya'])
                    @endif
                @else
                    @include('partials.tombol-ekspor', ['eksporRoute' => 'profile.export', 'eksporTeks' => 'Ekspor Data Saya'])
                @endif
                @if ($tautanUbah)
                    <a href="{{ $tautanUbah[0] }}" class="btn btn-primary"><i class="bi bi-pencil-square"></i>{{ $tautanUbah[1] }}</a>
                @endif
            </div>
        </div>

        <section class="panel mb-3 profil-kepala">
            <img src="{{ $user->fotoUrl() }}" alt="Foto {{ $user->name }}" class="profil-foto">
            <div class="min-w-0 flex-grow-1">
                <h2 class="h5 fw-bold mb-1">{{ $user->name }}</h2>
                <div class="text-muted">{{ $user->email }}</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge text-bg-light border"><i class="bi bi-shield-check me-1"></i>{{ $user->roleLabel() }}</span>
                    @if ($kepegawaian?->unit_kerja)<span class="badge text-bg-light border">{{ $kepegawaian->unit_kerja }}</span>@endif
                    @if ($user->google_id)<span class="badge text-bg-light border"><i class="bi bi-google me-1"></i>Masuk dengan Google aktif</span>@endif
                </div>
                @if (! $user->isPelamar() && ! $pegawai?->dokumen?->pas_foto && $user->isStaff())
                    <a href="{{ route('pegawai.pengkinian-data') }}" class="small d-inline-block mt-2"><i class="bi bi-camera me-1"></i>Unggah pas foto lewat Pengkinian Data</a>
                @endif
            </div>
            <form action="{{ route('logout') }}" method="POST" class="ms-md-auto">
                @csrf
                <button type="submit" class="btn btn-outline-danger js-confirm" data-confirm="true" data-title="Logout"
                    data-text="Apakah Anda yakin ingin keluar dari sesi login saat ini?" data-confirm-text="Ya, logout" data-cancel-text="Batal">
                    <i class="bi bi-box-arrow-right"></i>Keluar
                </button>
            </form>
        </section>

        @if ($user->isPelamar())
            <section class="panel">
                <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-lines-fill"></i>Data Pelamar</h2>
                @include('admin.pegawai._info-grid', ['items' => [
                    'Nama' => $pelamar?->nama_lengkap ?? $user->name,
                    'Email' => $user->email,
                    'No. HP' => $pelamar?->no_hp,
                    'NIK' => $pelamar?->nik,
                    'Pendidikan Terakhir' => $pelamar?->pendidikan_terakhir,
                    'Alamat Domisili' => $pelamar?->alamat_domisili,
                ]])
            </section>
        @elseif ($user->isGm() && ! $pegawai)
            {{-- GM tidak wajib tertaut ke Data Pegawai: tampilkan info akun & bidang saja. --}}
            <section class="panel">
                <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-badge"></i>Akun General Manager</h2>
                @include('admin.pegawai._info-grid', ['items' => [
                    'Nama' => $user->name,
                    'Email' => $user->email,
                    'Role' => $user->roleLabel(),
                    'Divisi Dipegang' => $user->divisi ? implode(', ', $user->divisi) : 'Semua divisi',
                ]])
            </section>
        @else
            <div class="row g-3">
                <div class="col-lg-6">
                    <section class="panel h-100">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-badge"></i>Akun & Identitas</h2>
                        @include('admin.pegawai._info-grid', ['items' => [
                            'Nama Akun' => $user->name,
                            'Nama Pegawai' => $pegawai?->nama_lengkap,
                            'NIP' => $user->nip ?? $pegawai?->nip,
                            'NIK' => $pegawai?->nik,
                            'No. HP' => $pegawai?->no_hp,
                            'Email' => $user->email,
                        ]])
                    </section>
                </div>
                <div class="col-lg-6">
                    <section class="panel h-100">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-briefcase"></i>Kepegawaian & Kontak</h2>
                        @include('admin.pegawai._info-grid', ['items' => [
                            'Jabatan' => $kepegawaian?->jabatan,
                            'Unit Kerja' => $kepegawaian?->unit_kerja,
                            'Kontak Darurat' => $kontakDarurat ? trim("{$kontakDarurat->nama_kerabat} ({$kontakDarurat->no_hp_kerabat})") : null,
                            'BPJS Kesehatan' => $bpjs?->no_bpjs_kesehatan,
                        ]])
                    </section>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
