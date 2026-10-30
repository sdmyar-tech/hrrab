@extends(Auth::user()->panelLayout())

@section('title', 'Profil Saya')

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
    $adaKepegawaian = ! $user->isPelamar() && ! ($user->isGm() && ! $pegawai);
    $belumDibaca = $user->unreadNotifications()->count();

    $tab = array_values(array_filter([
        ['key' => 'profil', 'label' => 'Profil', 'icon' => 'bi-person'],
        $adaKepegawaian ? ['key' => 'kepegawaian', 'label' => 'Kepegawaian', 'icon' => 'bi-briefcase'] : null,
        ['key' => 'keamanan', 'label' => 'Keamanan', 'icon' => 'bi-shield-lock'],
        ['key' => 'notifikasi', 'label' => 'Notifikasi', 'icon' => 'bi-bell', 'badge' => $belumDibaca ?: null],
    ]));
@endphp
<main class="dashboard-content">
    <div class="container-fluid">

        <x-page-header title="Profil Saya" subtitle="Informasi akun login dan identitas Anda.">
            <x-slot:actions>
                @if ($user->isPelamar())
                    @if ($user->dataPelamar)
                        @include('partials.tombol-ekspor', ['eksporRoute' => 'pelamar.data-pelamar.export', 'eksporTeks' => 'Ekspor Data Saya'])
                    @endif
                @else
                    @include('partials.tombol-ekspor', ['eksporRoute' => 'profile.export', 'eksporTeks' => 'Ekspor Data Saya'])
                @endif
                @if ($tautanUbah)
                    <x-button :href="$tautanUbah[0]" icon="bi-pencil">{{ $tautanUbah[1] }}</x-button>
                @endif
            </x-slot:actions>
        </x-page-header>

        {{-- Kartu identitas --}}
        <x-card class="mb-4 profil-identitas">
            <div class="profil-identitas-isi">
                <x-avatar :src="$user->fotoUrl()" :name="$user->name" size="xl" />
                <div class="min-w-0 flex-grow-1">
                    <h2 class="profil-nama">{{ $user->name }}</h2>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <x-badge tone="primary" icon="bi-shield-check">{{ $user->roleLabel() }}</x-badge>
                        @if ($kepegawaian?->unit_kerja)<x-badge tone="neutral" icon="bi-building">{{ $kepegawaian->unit_kerja }}</x-badge>@endif
                        @if ($kepegawaian?->jabatan)<x-badge tone="neutral" icon="bi-person-badge">{{ $kepegawaian->jabatan }}</x-badge>@endif
                        @if ($user->google_id)<x-badge tone="info" icon="bi-google">Masuk dengan Google aktif</x-badge>@endif
                    </div>
                    @if (! $user->isPelamar() && ! $pegawai?->dokumen?->pas_foto && $user->isStaff())
                        <a href="{{ route('pegawai.pengkinian-data') }}" class="small fw-semibold d-inline-flex align-items-center gap-1 mt-3">
                            <i class="bi bi-camera" aria-hidden="true"></i>Unggah pas foto lewat Pengkinian Data
                        </a>
                    @endif
                </div>
            </div>
        </x-card>

        <div class="row g-4">
            {{-- Kiri: tab vertikal --}}
            <div class="col-lg-3">
                <x-tabs id="profil" :items="$tab" active="profil" vertical label="Bagian profil" class="profil-tab" />
            </div>

            {{-- Kanan: isi tab --}}
            <div class="col-lg-9">
                <div class="tab-content">
                    <x-tabs.pane tabs="profil" key="profil" active>
                        @if ($user->isPelamar())
                            <x-card title="Data Pelamar" subtitle="Dipakai untuk semua lamaran Anda." icon="bi-person-lines-fill">
                                @include('admin.pegawai._info-grid', ['items' => [
                                    'Nama' => $pelamar?->nama_lengkap ?? $user->name,
                                    'Email' => $user->email,
                                    'No. HP' => $pelamar?->no_hp,
                                    'NIK' => $pelamar?->nik,
                                    'Pendidikan Terakhir' => $pelamar?->pendidikan_terakhir,
                                    'Alamat Domisili' => $pelamar?->alamat_domisili,
                                ]])
                            </x-card>
                        @elseif ($user->isGm() && ! $pegawai)
                            {{-- GM tidak wajib tertaut ke Data Pegawai: tampilkan info akun & bidang saja. --}}
                            <x-card title="Akun General Manager" icon="bi-person-badge">
                                @include('admin.pegawai._info-grid', ['items' => [
                                    'Nama' => $user->name,
                                    'Email' => $user->email,
                                    'Role' => $user->roleLabel(),
                                    'Divisi Dipegang' => $user->divisi ? implode(', ', $user->divisi) : 'Semua divisi',
                                ]])
                            </x-card>
                        @else
                            <x-card title="Akun & Identitas" subtitle="Data akun login dan identitas pegawai yang tertaut." icon="bi-person-badge">
                                @include('admin.pegawai._info-grid', ['items' => [
                                    'Nama Akun' => $user->name,
                                    'Nama Pegawai' => $pegawai?->nama_lengkap,
                                    'NIP' => $user->nip ?? $pegawai?->nip,
                                    'NIK' => $pegawai?->nik,
                                    'No. HP' => $pegawai?->no_hp,
                                    'Email' => $user->email,
                                ]])
                            </x-card>
                        @endif
                    </x-tabs.pane>

                    @if ($adaKepegawaian)
                        <x-tabs.pane tabs="profil" key="kepegawaian">
                            <x-card title="Kepegawaian & Kontak" subtitle="Penempatan kerja, kontak darurat, dan BPJS." icon="bi-briefcase">
                                @include('admin.pegawai._info-grid', ['items' => [
                                    'Jabatan' => $kepegawaian?->jabatan,
                                    'Bagian' => $kepegawaian?->unit_kerja,
                                    'Kontak Darurat' => $kontakDarurat ? trim("{$kontakDarurat->nama_kerabat} ({$kontakDarurat->no_hp_kerabat})") : null,
                                    'BPJS Kesehatan' => $bpjs?->no_bpjs_kesehatan,
                                ]])
                                @if ($tautanUbah)
                                    <x-slot:footer>
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <span class="small text-muted">Ada data yang salah atau berubah?</span>
                                            <x-button size="sm" variant="secondary" :href="$tautanUbah[0]" icon="bi-pencil">{{ $tautanUbah[1] }}</x-button>
                                        </div>
                                    </x-slot:footer>
                                @endif
                            </x-card>
                        </x-tabs.pane>
                    @endif

                    <x-tabs.pane tabs="profil" key="keamanan">
                        <x-card title="Keamanan Akun" subtitle="Cara Anda masuk ke HRRAB." icon="bi-shield-lock">
                            <div class="profil-baris">
                                <div>
                                    <p class="profil-baris-judul">Email masuk</p>
                                    <p class="profil-baris-teks">{{ $user->email }}</p>
                                </div>
                            </div>
                            <div class="profil-baris">
                                <div>
                                    <p class="profil-baris-judul">Kata sandi</p>
                                    <p class="profil-baris-teks">
                                        {{ $user->password_diganti_pada ? 'Terakhir diganti ' . $user->password_diganti_pada->translatedFormat('d F Y') . '.' : 'Belum pernah diganti lewat sistem.' }}
                                        Lupa atau ingin mengganti kata sandi? Hubungi SDM/Admin.
                                    </p>
                                </div>
                                <x-badge tone="success" icon="bi-check2">Aktif</x-badge>
                            </div>
                            <div class="profil-baris">
                                <div>
                                    <p class="profil-baris-judul">Masuk dengan Google</p>
                                    <p class="profil-baris-teks">{{ $user->google_id ? 'Akun Google sudah tertaut ke email ini.' : 'Belum tertaut.' }}</p>
                                </div>
                                <x-badge :tone="$user->google_id ? 'success' : 'neutral'">{{ $user->google_id ? 'Tertaut' : 'Tidak aktif' }}</x-badge>
                            </div>
                            <div class="profil-baris">
                                <div>
                                    <p class="profil-baris-judul">Peran & hak akses</p>
                                    <p class="profil-baris-teks">{{ $user->roleLabel() }} — menu yang tampil di sidebar menyesuaikan peran ini.</p>
                                </div>
                            </div>
                            <x-slot:footer>
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <span class="small text-muted">Selesai memakai perangkat bersama? Keluar agar akun tetap aman.</span>
                                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                                        @csrf
                                        <x-button type="submit" size="sm" variant="danger-outline" icon="bi-box-arrow-right" data-confirm="true" data-title="Keluar dari akun?"
                                            data-text="Anda perlu masuk lagi untuk melanjutkan." data-confirm-text="Ya, Keluar" data-cancel-text="Batal">Keluar</x-button>
                                    </form>
                                </div>
                            </x-slot:footer>
                        </x-card>
                    </x-tabs.pane>

                    <x-tabs.pane tabs="profil" key="notifikasi">
                        <x-card title="Notifikasi" subtitle="Pemberitahuan pengajuan, persetujuan, jadwal, dan pengumuman." icon="bi-bell">
                            <div class="profil-baris">
                                <div>
                                    <p class="profil-baris-judul">Belum dibaca</p>
                                    <p class="profil-baris-teks">Notifikasi baru juga ditandai di ikon lonceng pada bilah atas.</p>
                                </div>
                                <x-badge :tone="$belumDibaca ? 'danger' : 'neutral'">{{ $belumDibaca }} notifikasi</x-badge>
                            </div>
                            <div class="profil-baris">
                                <div>
                                    <p class="profil-baris-judul">Saluran</p>
                                    <p class="profil-baris-teks">Notifikasi dikirim di dalam aplikasi. Tidak ada pengaturan tambahan yang perlu diubah.</p>
                                </div>
                                <x-badge tone="success" icon="bi-check2">Aktif</x-badge>
                            </div>
                            <x-slot:footer>
                                <div class="d-flex justify-content-end">
                                    <x-button size="sm" variant="secondary" :href="route('notifikasi.index')" icon="bi-bell">Buka Semua Notifikasi</x-button>
                                </div>
                            </x-slot:footer>
                        </x-card>
                    </x-tabs.pane>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
