@extends(Auth::user()->panelLayout())

@section('content')
    @php
        $kepeg = $pegawai->kepegawaian;
        $foto = optional($pegawai->dokumen)->pas_foto;
        $status = $kepeg->status_rekrutmen ?? 'Non-Aktif';
        $aktif = strtolower($status) === 'aktif';
        // Keluar lewat "Proses Keluar" (merah); tidak aktif tanpa proses keluar tetap abu.
        $keluar = ! $aktif && filled($kepeg?->jenis_keluar);
        $utama = $pegawai->penugasanAktif->firstWhere('is_utama', true);
        $rangkapAktif = $pegawai->penugasanAktif->where('is_utama', false);

        // Masa kerja dihitung dari tanggal masuk sampai hari ini (aktif) atau tanggal keluar (tidak aktif).
        $masuk = $kepeg?->tanggal_masuk;
        $sampai = $aktif ? now() : $kepeg?->tanggal_keluar;
        $masaKerja = match (true) {
            ! $masuk || ! $sampai => null,
            $masuk->isFuture() => 'Belum mulai bekerja',
            default => (fn ($s) => ($s->y ? "{$s->y} tahun " : '') . "{$s->m} bulan")($masuk->diff($sampai)),
        };
        $habisKontrak = $aktif ? $kepeg?->tanggal_habis_kontrak : null;
        $sisaKontrak = $habisKontrak ? (int) now()->startOfDay()->diffInDays($habisKontrak, false) : null;

        // Kelengkapan data: daftar isian wajib sama dengan dashboard pegawai (Pegawai\DashboardController).
        $wajib = [
            'Nama lengkap' => $pegawai->nama_lengkap, 'NIK' => $pegawai->nik, 'No. KK' => $pegawai->no_kk, 'No. HP' => $pegawai->no_hp,
            'Tempat lahir' => $pegawai->tempat_lahir, 'Tanggal lahir' => $pegawai->tanggal_lahir, 'Jenis kelamin' => $pegawai->jenis_kelamin,
            'Agama' => $pegawai->agama, 'Alamat KTP' => $pegawai->alamat_ktp, 'Status pernikahan' => $pegawai->status_pernikahan,
            'Nama ibu kandung' => $pegawai->nama_ibu_kandung, 'Pendidikan terakhir' => $pegawai->pendidikan_terakhir,
            'Jabatan' => $kepeg?->jabatan, 'Bagian' => $kepeg?->unit_kerja, 'Lokasi kerja' => $kepeg?->lokasi_kerja, 'Tanggal masuk' => $masuk,
            'No. HP kerabat' => $pegawai->kontakDarurat?->no_hp_kerabat, 'Nama kerabat' => $pegawai->kontakDarurat?->nama_kerabat,
            'Hubungan kerabat' => $pegawai->kontakDarurat?->hubungan_kerabat,
            'File KTP' => $pegawai->dokumen?->upload_ktp, 'File KK' => $pegawai->dokumen?->upload_kk, 'Pas foto' => $foto,
        ];
        $kurang = array_keys(array_filter($wajib, fn ($v) => blank($v)));
        $kelengkapan = (int) round((count($wajib) - count($kurang)) / count($wajib) * 100);

        $dokumen = [
            'pas_foto' => 'Pas Foto',
            'upload_ktp' => 'KTP',
            'upload_kk' => 'Kartu Keluarga',
            'upload_cv' => 'CV',
            'upload_ijazah_terakhir' => 'Ijazah Terakhir',
            'upload_buku_tabungan' => 'Buku Tabungan',
        ];

        // Nomor WhatsApp dari No. HP (08… → 628…).
        $angkaHp = preg_replace('/\D+/', '', (string) $pegawai->no_hp);
        $nomorWa = $angkaHp ? (str_starts_with($angkaHp, '0') ? '62' . substr($angkaHp, 1) : $angkaHp) : null;

        $tabs = [
            ['key' => 'pribadi', 'label' => 'Data Pribadi', 'icon' => 'bi-person'],
            ['key' => 'kepegawaian', 'label' => 'Kepegawaian', 'icon' => 'bi-briefcase'],
            ['key' => 'penugasan', 'label' => 'Penugasan & Alur', 'icon' => 'bi-diagram-3'],
            ['key' => 'kontak', 'label' => 'Kontak & BPJS', 'icon' => 'bi-telephone'],
            ['key' => 'dokumen', 'label' => 'Dokumen', 'icon' => 'bi-folder2-open'],
        ];
        // ?tab=penugasan dkk. (tautan dari Mutasi & kembali dari simpan alur/akun) membuka tab itu sejak awal;
        // galat isian tampil di tab Penugasan. #hash tab tetap diutamakan oleh x-tabs.
        $tabDiminta = is_string(request('tab')) ? request('tab') : null;
        $tabAwal = match (true) {
            in_array($tabDiminta, array_column($tabs, 'key'), true) => $tabDiminta,
            $errors->any() => 'penugasan',
            default => 'pribadi',
        };

        // Riwayat singkat di kolom samping: keluar → penugasan terbaru → mulai bekerja.
        $riwayat = collect();
        if ($keluar) {
            $riwayat->push(['state' => 'stopped', 'icon' => 'bi-box-arrow-right', 'title' => 'Keluar · ' . $kepeg->jenisKeluarLabel(),
                'meta' => $kepeg->tanggal_keluar?->translatedFormat('d M Y'), 'teks' => $kepeg->alasan_keluar]);
        }
        foreach ($pegawai->penugasans->take(5) as $p) {
            $riwayat->push([
                'state' => $p->isAkanDatang() ? 'pending' : ($p->isAktif() ? 'active' : 'done'),
                'icon' => null,
                'title' => $p->jenisLabel() . ' · ' . $p->unit_kerja,
                'meta' => $p->tanggal_mulai->translatedFormat('d M Y'),
                'teks' => ($p->jabatan ?? 'Jabatan belum diisi') . ($p->isKepala() ? ' · Kepala' : '') . ' · ' . match (true) {
                    $p->isAkanDatang() => 'terjadwal',
                    (bool) $p->tanggal_selesai => 'sampai ' . $p->tanggal_selesai->translatedFormat('d M Y'),
                    default => 'masih berlaku',
                },
            ]);
        }
        if ($masuk && ! $pegawai->penugasans->contains('jenis', \App\Models\Penugasan::JENIS_AWAL)) {
            $riwayat->push(['state' => $masuk->isFuture() ? 'pending' : 'done', 'icon' => $masuk->isFuture() ? null : 'bi-door-open',
                'title' => 'Mulai bekerja', 'meta' => $masuk->translatedFormat('d M Y'), 'teks' => $kepeg?->unit_kerja]);
        }
        $riwayatLain = max(0, $pegawai->penugasans->count() - 5);
    @endphp

    <main class="dashboard-content">
        <div class="container-fluid">

            <x-page-header :title="$pegawai->nama_lengkap" eyebrow="Kepegawaian" :back="route('admin.pegawai.index')" back-label="Data Pegawai"
                :subtitle="collect([$kepeg?->jabatan ?? 'Jabatan belum diisi', $kepeg?->unit_kerja, $pegawai->nip ? 'NIP ' . $pegawai->nip : 'NIP belum diisi'])->filter()->implode(' · ')">
                <x-slot:media><x-avatar :src="$foto ? \App\Support\Berkas::url($foto) : null" :name="$pegawai->nama_lengkap" size="xl" /></x-slot:media>

                <x-badge size="lg" :tone="$aktif ? 'success' : ($keluar ? 'danger' : 'neutral')" dot
                    :title="$keluar ? $status . ' · ' . $kepeg->jenisKeluarLabel() : null">{{ $keluar ? 'Keluar' : $status }}</x-badge>
                @if ($kepeg?->status_kerja)
                    <x-badge tone="neutral" icon="bi-briefcase">{{ $kepeg->status_kerja }}</x-badge>
                @endif
                @if ($utama?->isKepala())
                    <x-badge tone="primary" icon="bi-star-fill" title="Kepala bagian utama">Kepala {{ $utama->unit_kerja }}</x-badge>
                @endif
                @foreach ($rangkapAktif as $rangkap)
                    <x-badge tone="info" icon="bi-diagram-3" title="Rangkap tugas sejak {{ $rangkap->tanggal_mulai->translatedFormat('d M Y') }}">
                        Rangkap {{ $rangkap->unit_kerja }}{{ $rangkap->isKepala() ? ' · Kepala' : '' }}
                    </x-badge>
                @endforeach

                <x-slot:actions>
                    @if (auth()->user()->can('sistem.editor_data'))
                        {{-- Admin: buka data mentah pegawai ini di Editor Data (semua kolom, tanpa aturan). --}}
                        <x-dropdown label="Data mentah (Admin)" icon="bi-shield-lock" icon-only>
                            <a class="dropdown-item" href="{{ route('admin.data.edit', ['pegawai', $pegawai->id, 'kembali' => request()->fullUrl()]) }}"><i class="bi bi-person me-2" aria-hidden="true"></i>Data pribadi</a>
                            @foreach (['kepegawaian' => ['Kepegawaian', 'bi-person-badge'], 'penugasan' => ['Penugasan / mutasi', 'bi-arrow-left-right'], 'bpjs' => ['BPJS', 'bi-shield-plus'],
                                'kontak_darurat' => ['Kontak darurat', 'bi-telephone'], 'dokumen' => ['Dokumen', 'bi-folder2'], 'absensi' => ['Absensi', 'bi-fingerprint'],
                                'jadwal_kerja' => ['Jam kerja', 'bi-alarm'], 'cuti' => ['Cuti', 'bi-calendar2-check'], 'lembur' => ['Lembur', 'bi-clock-history']] as $ent => [$lbl, $ik])
                                <a class="dropdown-item" href="{{ route('admin.data.daftar', [$ent, 'q' => $pegawai->nama_lengkap]) }}"><i class="bi {{ $ik }} me-2" aria-hidden="true"></i>{{ $lbl }}</a>
                            @endforeach
                        </x-dropdown>
                    @endif
                    @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pegawai.export-detail', 'eksporParam' => ['pegawai' => $pegawai->id]])
                    @if ($dampakKeluar)
                        <x-button variant="danger-outline" icon="bi-box-arrow-right" data-bs-toggle="modal" data-bs-target="#modalKeluar">Resign / Keluar</x-button>
                    @endif
                    @can('pegawai.delete')
                        <form action="{{ route('admin.pegawai.destroy', $pegawai->id) }}" method="POST" class="d-inline-flex">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="danger-outline" icon="bi-trash"
                                data-confirm="true" data-icon="warning" data-title="Hapus Data Pegawai?"
                                data-text="Data {{ $pegawai->nama_lengkap }} akan dihapus permanen beserta dokumen terkait."
                                data-confirm-text="Ya, Hapus" data-cancel-text="Batal">Hapus</x-button>
                        </form>
                    @endcan
                    @can('pegawai.update')
                        <x-button :href="route('admin.pegawai.edit', $pegawai)" icon="bi-pencil">Ubah Data</x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            @if (session('peringatan_keluar'))
                <x-alert tone="info" class="mb-4">
                    <ul class="mb-0 ps-3">@foreach (session('peringatan_keluar') as $p)<li>{{ $p }}</li>@endforeach</ul>
                </x-alert>
            @endif
            @if ($keluar)
                {{-- Pegawai sudah keluar lewat "Proses Keluar": ringkasan + pembatalan. --}}
                <x-alert tone="info" icon="bi-box-arrow-right" class="mb-4"
                    :title="'Keluar per ' . ($kepeg->tanggal_keluar?->translatedFormat('d F Y') ?? '-') . ' · ' . $kepeg->jenisKeluarLabel()">
                    @if ($kepeg->alasan_keluar)<span class="d-block">{{ $kepeg->alasan_keluar }}</span>@endif
                    <span class="d-block text-muted small">Diproses oleh {{ $kepeg->pemrosesKeluar?->name ?? '-' }}. Akunnya kini akun pelamar; untuk kembali bekerja harus melamar lagi.</span>
                    @can('pegawai.update')
                        @if ($kepeg->keluar_snapshot)
                            <x-slot:action>
                                <form action="{{ route('admin.pegawai.keluar.batal', $pegawai) }}" method="POST"
                                    data-confirm="Batalkan proses keluar {{ $pegawai->nama_lengkap }}? Status, akun, penugasan, dan pengajuan dikembalikan seperti sebelum keluar.">
                                    @csrf @method('DELETE')
                                    <x-button type="submit" size="sm" variant="secondary" icon="bi-arrow-counterclockwise">Batalkan Keluar</x-button>
                                </form>
                            </x-slot:action>
                        @endif
                    @endcan
                </x-alert>
            @endif

            <div class="row g-4">
                {{-- ============ Kiri: data lengkap per tab ============ --}}
                <div class="col-xl-8">
                    {{-- Kartu tanpa padding: garis tab selebar kartu; isi tab memakai padding kartu biasa (.hr-card-body). --}}
                    <x-card id="detail-pegawai" class="hr-bagian" :padding="false" aria-label="Data pegawai">
                        <x-tabs id="pegawai" :items="$tabs" :active="$tabAwal" label="Bagian data pegawai" class="px-2" />

                        <div class="tab-content hr-card-body pt-4">
                            {{-- ---------- Data Pribadi ---------- --}}
                            <x-tabs.pane tabs="pegawai" key="pribadi" :active="$tabAwal === 'pribadi'">
                                <section class="hr-detail-bagian" aria-labelledby="judul-identitas">
                                    <h2 class="hr-detail-judul" id="judul-identitas"><i class="bi bi-person-lines-fill" aria-hidden="true"></i> Identitas</h2>
                                    <x-info-list :items="[
                                        'Nama Lengkap' => $pegawai->nama_lengkap,
                                        'Email' => $pegawai->email,
                                        'No. HP' => $pegawai->no_hp,
                                        'NIK (No. KTP)' => $pegawai->nik,
                                        'No. KK' => $pegawai->no_kk,
                                        'No. NPWP' => $pegawai->npwp,
                                        'Tempat, Tanggal Lahir' => collect([$pegawai->tempat_lahir, optional($pegawai->tanggal_lahir)->translatedFormat('d F Y')])->filter()->implode(', '),
                                        'Jenis Kelamin' => $pegawai->jenis_kelamin,
                                        'Agama' => $pegawai->agama,
                                        'Golongan Darah' => $pegawai->golongan_darah,
                                        'Status Pernikahan' => $pegawai->status_pernikahan,
                                        'Jumlah Tanggungan' => $pegawai->jumlah_tanggungan,
                                        'Nama Ibu Kandung' => $pegawai->nama_ibu_kandung,
                                        'Pendidikan Terakhir' => $pegawai->pendidikan_terakhir,
                                    ]" />
                                </section>
                                <section class="hr-detail-bagian" aria-labelledby="judul-alamat">
                                    <h2 class="hr-detail-judul" id="judul-alamat"><i class="bi bi-geo-alt" aria-hidden="true"></i> Alamat</h2>
                                    <x-info-list :items="[
                                        'Alamat KTP' => $pegawai->alamat_ktp,
                                        'Alamat Domisili' => $pegawai->alamat_domisili ?: 'Sama dengan KTP',
                                    ]" />
                                </section>
                            </x-tabs.pane>

                            {{-- ---------- Kepegawaian ---------- --}}
                            <x-tabs.pane tabs="pegawai" key="kepegawaian" :active="$tabAwal === 'kepegawaian'">
                                <section class="hr-detail-bagian" aria-labelledby="judul-jabatan">
                                    <h2 class="hr-detail-judul" id="judul-jabatan"><i class="bi bi-briefcase" aria-hidden="true"></i> Jabatan & Penempatan</h2>
                                    <x-info-list :items="[
                                        'NIP' => $pegawai->nip,
                                        'Jabatan Utama' => $kepeg?->jabatan,
                                        'Jabatan Tambahan' => $kepeg?->jabatan_tambahan,
                                        'Bagian Utama' => $kepeg?->unit_kerja ? $kepeg->unit_kerja . ($utama?->isKepala() ? ' (Kepala)' : '') : null,
                                        'Unit' => $kepeg?->unit_organisasi,
                                        'Lokasi Kerja' => $kepeg?->lokasi_kerja,
                                        ...($rangkapAktif->isNotEmpty() ? [
                                            'Rangkap Tugas' => $rangkapAktif->map(fn ($r) => $r->unit_kerja . ($r->isKepala() ? ' (Kepala)' : ''))->implode(', '),
                                        ] : []),
                                    ]" />
                                </section>
                                <section class="hr-detail-bagian" aria-labelledby="judul-status-kerja">
                                    <h2 class="hr-detail-judul" id="judul-status-kerja"><i class="bi bi-calendar2-check" aria-hidden="true"></i> Status & Masa Kerja</h2>
                                    <x-info-list :items="[
                                        'Tanggal Masuk' => optional($masuk)->translatedFormat('d F Y'),
                                        'Masa Kerja' => $masaKerja,
                                        'Status Pegawai' => $kepeg?->status_rekrutmen,
                                        'Status Kerja' => $kepeg?->status_kerja,
                                        ...($kepeg?->tanggal_habis_kontrak || strtolower((string) $kepeg?->status_kerja) === 'kontrak' ? [
                                            'Tanggal Habis Kontrak' => optional($kepeg?->tanggal_habis_kontrak)->translatedFormat('d F Y'),
                                        ] : []),
                                    ]" />
                                </section>
                                @if ($kepeg?->status_rekrutmen === 'Tidak Aktif')
                                    <section class="hr-detail-bagian" aria-labelledby="judul-data-keluar">
                                        <h2 class="hr-detail-judul" id="judul-data-keluar"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Data Keluar</h2>
                                        <x-info-list :items="[
                                            'Tanggal Keluar' => optional($kepeg->tanggal_keluar)->translatedFormat('d F Y') ?? 'Tidak diketahui',
                                            'Jenis Keluar' => $kepeg->jenisKeluarLabel(),
                                            'Alasan Keluar' => $kepeg->alasan_keluar,
                                            ...($kepeg->jenis_keluar ? ['Diproses Oleh' => $kepeg->pemrosesKeluar?->name] : []),
                                        ]" />
                                    </section>
                                @endif
                            </x-tabs.pane>

                            {{-- ---------- Penugasan & Persetujuan ---------- --}}
                            <x-tabs.pane tabs="pegawai" key="penugasan" :active="$tabAwal === 'penugasan'">
                                @if (session('peringatan_penugasan'))
                                    <x-alert tone="warning" class="mb-4">
                                        <ul class="mb-0 ps-3">
                                            @foreach (session('peringatan_penugasan') as $p)
                                                <li>{{ $p }}</li>
                                            @endforeach
                                        </ul>
                                    </x-alert>
                                @endif
                                {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
                                @section('galat-di-halaman', '1')
                                @if ($errors->any())
                                    <x-alert tone="danger" class="mb-4">{{ $errors->first() }}</x-alert>
                                @endif

                                {{-- Riwayat penugasan & mutasi --}}
                                <section class="hr-detail-bagian hr-bagian" id="riwayat-penugasan" aria-labelledby="judul-riwayat-penugasan">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                        <h2 class="hr-detail-judul mb-0" id="judul-riwayat-penugasan"><i class="bi bi-clock-history" aria-hidden="true"></i> Riwayat Penugasan & Mutasi</h2>
                                        @can('penugasan.manage')
                                            <div class="d-flex flex-wrap gap-2">
                                                <x-button size="sm" variant="secondary" icon="bi-plus-lg" :href="route('admin.penugasan.create', ['pegawai' => $pegawai, 'jenis' => 'rangkap'])">Tambah Rangkap</x-button>
                                                <x-button size="sm" icon="bi-arrow-left-right" :href="route('admin.penugasan.create', ['pegawai' => $pegawai, 'jenis' => 'mutasi'])">Mutasi</x-button>
                                            </div>
                                        @endcan
                                    </div>

                                    @if ($pegawai->penugasans->isEmpty())
                                        <x-empty-state icon="bi-inbox" title="Belum ada riwayat penugasan" compact>
                                            Penempatan awal, mutasi, dan rangkap tugas pegawai ini akan tercatat di sini.
                                        </x-empty-state>
                                    @else
                                        <x-table caption="Riwayat penugasan dan mutasi" class="border rounded-3" compact>
                                            <x-slot:head>
                                                <tr>
                                                    <th>Bagian</th>
                                                    <th>Jabatan / Peran</th>
                                                    <th>Periode &amp; Status</th>
                                                    <th>SK / Keterangan</th>
                                                    @can('penugasan.manage')<th class="hr-kolom-aksi">Aksi</th>@endcan
                                                </tr>
                                            </x-slot:head>
                                            @foreach ($pegawai->penugasans as $p)
                                                <tr>
                                                    <td>
                                                        <span class="hr-sel-judul">{{ $p->unit_kerja }}</span>
                                                        <span class="hr-sel-badge">
                                                            @if ($p->is_utama)
                                                                <x-badge tone="primary">Utama</x-badge>
                                                                <x-badge tone="neutral">{{ $p->jenisLabel() }}</x-badge>
                                                            @else
                                                                <x-badge tone="info">Rangkap</x-badge>
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="d-block">{{ $p->jabatan ?? '-' }}</span>
                                                        @if ($p->isKepala())
                                                            <span class="hr-sel-badge"><x-badge tone="neutral" icon="bi-star-fill">Kepala</x-badge></span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="d-block"><span class="text-nowrap">{{ $p->tanggal_mulai->translatedFormat('d M Y') }} &ndash;</span> <span class="text-nowrap">{{ $p->tanggal_selesai?->translatedFormat('d M Y') ?? 'sekarang' }}</span></span>
                                                        <span class="hr-sel-badge">
                                                            <x-badge :tone="$p->isAkanDatang() ? 'warning' : ($p->isAktif() ? 'success' : 'neutral')" dot>{{ $p->statusLabel() }}</x-badge>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="d-block">{{ $p->no_sk ?? '-' }}</span>
                                                        @if ($p->keterangan)<span class="hr-sel-sub">{{ $p->keterangan }}</span>@endif
                                                        <span class="hr-sel-sub">Dicatat: {{ $p->dibuatOleh?->name ?? 'sistem' }}</span>
                                                    </td>
                                                    @can('penugasan.manage')
                                                        <td class="text-end hr-kolom-aksi">
                                                            <div class="hr-aksi-baris">
                                                                @if (auth()->user()->can('sistem.override'))
                                                                    <x-icon-button icon="bi-pencil" label="Ubah langsung (Admin)" :href="route('admin.penugasan.edit', $p)" />
                                                                @endif
                                                                @if ($p->isAkanDatang())
                                                                    <form action="{{ route('admin.penugasan.destroy', $p) }}" method="POST"
                                                                        data-confirm="Batalkan {{ strtolower($p->jenisLabel()) }} terjadwal ke {{ $p->unit_kerja }}?">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <x-icon-button type="submit" icon="bi-x-circle" :label="'Batalkan ' . strtolower($p->jenisLabel()) . ' terjadwal'" tone="danger" />
                                                                    </form>
                                                                @elseif (! $p->is_utama && $p->isAktif())
                                                                    {{-- Akhiri rangkap: isian tanggal di menu kecil agar kolom aksi tetap ramping. --}}
                                                                    <x-dropdown label="Akhiri rangkap tugas">
                                                                        <x-slot:trigger>
                                                                            <x-icon-button icon="bi-box-arrow-right" label="Akhiri rangkap tugas" tone="warning"
                                                                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" />
                                                                        </x-slot:trigger>
                                                                        <form action="{{ route('admin.penugasan.akhiri', $p) }}" method="POST" class="d-grid gap-2 px-3 py-2 text-start"
                                                                            data-confirm="Akhiri rangkap tugas di {{ $p->unit_kerja }}?">
                                                                            @csrf
                                                                            <label class="form-label small mb-0" for="akhiri-{{ $p->id }}">Hari terakhir bertugas di {{ $p->unit_kerja }}</label>
                                                                            <input type="date" name="tanggal_selesai" id="akhiri-{{ $p->id }}" class="form-control form-control-sm"
                                                                                value="{{ now()->format('Y-m-d') }}" min="{{ $p->tanggal_mulai->format('Y-m-d') }}" required>
                                                                            <x-button type="submit" size="sm" variant="danger-outline" icon="bi-box-arrow-right">Akhiri Rangkap</x-button>
                                                                        </form>
                                                                    </x-dropdown>
                                                                @else
                                                                    {{-- Pengganti selebar tombol agar ikon antarbaris tetap sejajar. --}}
                                                                    <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                                                @endif
                                                                @if (! $p->isAkanDatang() && ($p->jenis !== \App\Models\Penugasan::JENIS_AWAL || auth()->user()->can('sistem.override')))
                                                                    <form action="{{ route('admin.penugasan.destroy', $p) }}" method="POST"
                                                                        data-confirm="Hapus {{ strtolower($p->jenisLabel()) }} ke {{ $p->unit_kerja }} (koreksi salah input)?{{ $p->is_utama ? ' Pegawai dikembalikan ke bagian sebelumnya.' : '' }}">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <x-icon-button type="submit" icon="bi-trash" label="Hapus (koreksi salah input)" tone="danger" />
                                                                    </form>
                                                                @elseif ($p->jenis === \App\Models\Penugasan::JENIS_AWAL && ! auth()->user()->can('sistem.override'))
                                                                    <span class="text-muted" aria-label="Tidak ada aksi">&mdash;</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    @endcan
                                                </tr>
                                            @endforeach
                                        </x-table>
                                    @endif
                                </section>

                                {{-- Alur persetujuan cuti & lembur --}}
                                @if ($bolehAturAkun && $akun)
                                    @push('modal-pegawai')
                                        @include('admin.pegawai._modal-alur')
                                    @endpush
                                @endif
                                <section class="hr-detail-bagian hr-bagian" id="alur-approval" aria-labelledby="judul-alur">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                        <h2 class="hr-detail-judul mb-0" id="judul-alur">
                                            <i class="bi bi-signpost-split" aria-hidden="true"></i> Alur Persetujuan
                                            @if ($alurKhusus)<x-badge tone="primary" icon="bi-sliders">Diatur khusus</x-badge>@endif
                                        </h2>
                                        @if ($bolehAturAkun && $akun)
                                            <div class="d-flex flex-wrap gap-2">
                                                @if ($alurKhusus)
                                                    <form action="{{ route('admin.pegawai.alur.destroy', $pegawai) }}" method="POST"
                                                        data-confirm="Kembalikan alur persetujuan {{ $pegawai->nama_lengkap }} ke otomatis (mengikuti bagian & jabatan)?">
                                                        @csrf @method('DELETE')
                                                        <x-button type="submit" size="sm" variant="ghost" icon="bi-arrow-counterclockwise">Kembali Otomatis</x-button>
                                                    </form>
                                                @endif
                                                <x-button size="sm" variant="outline" icon="bi-diagram-3" data-bs-toggle="modal" data-bs-target="#modalAlurPegawai">Ubah Alur & Atasan</x-button>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($alurKhusus)
                                        <x-alert tone="info" class="mb-3">
                                            Alur pegawai ini diatur khusus oleh {{ $alurKhusus->pengubah?->name ?? 'SDM' }}
                                            ({{ $alurKhusus->updated_at?->translatedFormat('d M Y H:i') }}) dan berlaku untuk cuti & lembur.
                                            @if ($alurKhusus->catatan)<span class="d-block text-muted">Catatan: {{ $alurKhusus->catatan }}</span>@endif
                                        </x-alert>
                                    @endif
                                    @if ($unitsDisetujui)
                                        <div class="d-flex flex-wrap align-items-center gap-2 small text-muted mb-3">
                                            <span>Menyetujui pengajuan staf di:</span>
                                            @foreach ($unitsDisetujui as $unit)
                                                <x-badge tone="neutral" icon="bi-shield-check">{{ $unit }}</x-badge>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if (! $alurKhusus && $alurApproval)
                                        <p class="text-muted small mb-3">
                                            Cuti mengikuti bagian utama. Lembur dipilih per bagian dan disetujui kepala bagian tersebut;
                                            bila pegawai ini kepala di bagian itu, atau bagiannya tidak punya kepala, persetujuan naik ke GM.
                                        </p>
                                    @endif

                                    @if (! $akun)
                                        <x-empty-state icon="bi-signpost-split" title="Alur belum berlaku" compact>
                                            Pegawai ini belum punya akun, jadi belum bisa mengajukan cuti/lembur.
                                        </x-empty-state>
                                    @elseif (! $alurApproval)
                                        <x-empty-state icon="bi-slash-circle" title="Tidak mengajukan lewat sistem" compact>
                                            Akun pegawai ini tidak mengajukan cuti/lembur lewat sistem (bagian/jabatan dikecualikan).
                                        </x-empty-state>
                                    @else
                                        <div class="row g-3">
                                            @foreach ($alurApproval as $alur)
                                                @php
                                                    $adaKosong = collect($alur['tahap'])->contains(fn ($t) => $t['approvers']->isEmpty());
                                                    // Data tahap → x-tangga-alur; nama tahap Kepala SDM & atasan langsung (alur khusus) disesuaikan.
                                                    $tahapTampil = collect(\App\Support\PengajuanWorkflow::tahapTampil($alur['tahap']))
                                                        ->map(function ($t, $i) use ($alur, $alurKhusus) {
                                                            $asli = $alur['tahap'][$i];
                                                            $t['label'] = match (true) {
                                                                $asli['level'] === \App\Support\PengajuanWorkflow::LEVEL_KEPALA_SDM => 'Kepala SDM',
                                                                $asli['level'] === \App\Support\PengajuanWorkflow::LEVEL_KEPALA_DIVISI && ($asli['khusus'] ?? false) && $alurKhusus?->atasan_user_id => 'Atasan Langsung',
                                                                default => $t['label'],
                                                            };
                                                            $t['catatan'] = match (true) {
                                                                $t['kosong'] => 'Pengajuan akan ditolak sistem.',
                                                                $asli['level'] === \App\Support\PengajuanWorkflow::LEVEL_GM && $asli['approvers']->count() > 1 => 'GM lain bisa menyetujui sebagai pengganti.',
                                                                default => null,
                                                            };

                                                            return $t;
                                                        })->values()->all();
                                                @endphp
                                                <div class="col-md-6">
                                                    <x-card as="article" title-tag="h3" class="h-100"
                                                        :title="$alur['jenis'] . ' · ' . ($alur['unit'] ?? 'Bagian belum diisi')"
                                                        :icon="$alur['jenis'] === 'Cuti' ? 'bi-calendar2-week' : 'bi-clock-history'">
                                                        @if ($alur['tahap'][0]['tangga'] ?? false)
                                                            <x-slot:actions>
                                                                <a href="{{ route('admin.tangga-alur.index') }}" title="Diatur di Tangga Alur Pengajuan">Tangga alur</a>
                                                            </x-slot:actions>
                                                        @endif
                                                        @if ($adaKosong)
                                                            <x-badge tone="warning" icon="bi-exclamation-triangle" class="mb-3">Ada tahap tanpa penyetuju</x-badge>
                                                        @endif
                                                        <x-tangga-alur :tahap="$tahapTampil" arah="vertikal" :label="'Alur persetujuan ' . strtolower($alur['jenis']) . ' ' . ($alur['unit'] ?? '')" />
                                                    </x-card>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </section>

                                {{-- Akun login pegawai --}}
                                @include('admin.pegawai._akun-login')
                            </x-tabs.pane>

                            {{-- ---------- Kontak & BPJS ---------- --}}
                            <x-tabs.pane tabs="pegawai" key="kontak" :active="$tabAwal === 'kontak'">
                                <section class="hr-detail-bagian" aria-labelledby="judul-kontak-darurat">
                                    <h2 class="hr-detail-judul" id="judul-kontak-darurat"><i class="bi bi-telephone" aria-hidden="true"></i> Kontak Darurat</h2>
                                    <x-info-list :items="[
                                        'Nama Kerabat' => optional($pegawai->kontakDarurat)->nama_kerabat,
                                        'No. HP Kerabat' => optional($pegawai->kontakDarurat)->no_hp_kerabat,
                                        'Hubungan' => optional($pegawai->kontakDarurat)->hubungan_kerabat,
                                    ]" />
                                </section>
                                <section class="hr-detail-bagian" aria-labelledby="judul-bpjs">
                                    <h2 class="hr-detail-judul" id="judul-bpjs"><i class="bi bi-card-checklist" aria-hidden="true"></i> Data BPJS</h2>
                                    <x-info-list :items="[
                                        'No. BPJS Kesehatan' => optional($pegawai->bpjs)->no_bpjs_kesehatan,
                                        'No. BPJS Ketenagakerjaan' => optional($pegawai->bpjs)->no_bpjs_ketenagakerjaan,
                                        'Faskes Tingkat 1' => optional($pegawai->bpjs)->faskes_tingkat_1,
                                    ]" />
                                </section>
                            </x-tabs.pane>

                            {{-- ---------- Dokumen ---------- --}}
                            <x-tabs.pane tabs="pegawai" key="dokumen" :active="$tabAwal === 'dokumen'">
                                <section class="hr-detail-bagian" aria-labelledby="judul-dokumen">
                                    <h2 class="hr-detail-judul" id="judul-dokumen"><i class="bi bi-folder2-open" aria-hidden="true"></i> Dokumen Pegawai</h2>
                                    <div class="row g-3">
                                        @foreach ($dokumen as $field => $label)
                                            <div class="col-md-6">
                                                @include('partials.preview-file', [
                                                    'path' => optional($pegawai->dokumen)->{$field},
                                                    'label' => $label,
                                                ])
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            </x-tabs.pane>
                        </div>
                    </x-card>
                </div>

                {{-- ============ Kanan: ringkasan + riwayat (lengket di desktop) ============ --}}
                <div class="col-xl-4">
                    <div class="hr-detail-samping d-grid gap-4">
                        <x-card title="Ringkasan" icon="bi-person-vcard">
                            <section class="hr-detail-bagian" aria-label="Ringkasan kepegawaian">
                                <dl class="dash-dl">
                                    <div><dt>Masa kerja</dt><dd>{{ $masaKerja ?? '-' }}</dd></div>
                                    <div><dt>Tanggal masuk</dt><dd>{{ $masuk?->translatedFormat('d M Y') ?? '-' }}</dd></div>
                                    @if ($keluar)
                                        <div><dt>Tanggal keluar</dt><dd>{{ $kepeg->tanggal_keluar?->translatedFormat('d M Y') ?? '-' }}</dd></div>
                                    @endif
                                    <div><dt>Status kerja</dt><dd>{{ $kepeg?->status_kerja ?? '-' }}</dd></div>
                                    <div><dt>Lokasi kerja</dt><dd>{{ $kepeg?->lokasi_kerja ?? '-' }}</dd></div>
                                    <div><dt>Akun login</dt><dd>{{ $akun ? $akun->roleLabel() : 'Belum ada' }}</dd></div>
                                </dl>
                                @if ($habisKontrak)
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small mt-3">
                                        <span>Kontrak s/d <strong>{{ $habisKontrak->translatedFormat('d M Y') }}</strong></span>
                                        @if ($sisaKontrak < 0)
                                            <x-badge tone="danger">Berakhir</x-badge>
                                        @else
                                            <x-badge :tone="$sisaKontrak <= 60 ? 'warning' : 'neutral'">{{ $sisaKontrak }} hari lagi</x-badge>
                                        @endif
                                    </div>
                                @endif
                            </section>

                            <section class="hr-detail-bagian" aria-labelledby="judul-kelengkapan">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                    <h3 class="hr-detail-judul mb-0" id="judul-kelengkapan"><i class="bi bi-ui-checks" aria-hidden="true"></i> Kelengkapan Data</h3>
                                    <x-badge :tone="$kelengkapan >= 100 ? 'success' : 'warning'">{{ $kelengkapan }}%</x-badge>
                                </div>
                                <div class="progress" role="progressbar" aria-label="Kelengkapan data" aria-valuenow="{{ $kelengkapan }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar {{ $kelengkapan >= 100 ? 'bg-success' : 'bg-warning' }}" style="width: {{ $kelengkapan }}%"></div>
                                </div>
                                <p class="small text-muted mt-2 mb-0">
                                    @if ($kurang)
                                        Belum diisi: {{ implode(', ', array_slice($kurang, 0, 4)) }}{{ count($kurang) > 4 ? ', +' . (count($kurang) - 4) . ' lainnya' : '' }}.
                                    @else
                                        Semua data wajib sudah terisi.
                                    @endif
                                </p>
                                @if ($kurang)
                                    @can('pegawai.update')
                                        <a href="{{ route('admin.pegawai.edit', $pegawai) }}" class="small fw-semibold d-inline-flex align-items-center gap-1 mt-2">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>Lengkapi data
                                        </a>
                                    @endcan
                                @endif
                            </section>

                            <section class="hr-detail-bagian" aria-labelledby="judul-kontak-cepat">
                                <h3 class="hr-detail-judul" id="judul-kontak-cepat"><i class="bi bi-chat-dots" aria-hidden="true"></i> Kontak Cepat</h3>
                                @if ($pegawai->no_hp || $pegawai->email || $pegawai->kontakDarurat?->no_hp_kerabat)
                                    <ul class="hr-list">
                                        @if ($pegawai->no_hp)
                                            <li class="hr-list-item">
                                                <div class="hr-list-utama">
                                                    <span class="hr-list-judul angka-utuh">{{ $pegawai->no_hp }}</span>
                                                    <span class="hr-list-sub">No. HP</span>
                                                </div>
                                                <div class="hr-aksi-baris">
                                                    <x-icon-button icon="bi-telephone" label="Telepon" :href="'tel:' . $angkaHp" />
                                                    <x-icon-button icon="bi-whatsapp" label="Chat WhatsApp" :href="'https://wa.me/' . $nomorWa" target="_blank" rel="noopener" />
                                                </div>
                                            </li>
                                        @endif
                                        @if ($pegawai->email)
                                            <li class="hr-list-item">
                                                <div class="hr-list-utama">
                                                    <span class="hr-list-judul">{{ $pegawai->email }}</span>
                                                    <span class="hr-list-sub">Email</span>
                                                </div>
                                                <div class="hr-aksi-baris">
                                                    <x-icon-button icon="bi-envelope" label="Kirim email" :href="'mailto:' . $pegawai->email" />
                                                </div>
                                            </li>
                                        @endif
                                        @if ($pegawai->kontakDarurat?->no_hp_kerabat)
                                            <li class="hr-list-item">
                                                <div class="hr-list-utama">
                                                    <span class="hr-list-judul">{{ $pegawai->kontakDarurat->nama_kerabat ?? 'Kerabat' }}</span>
                                                    <span class="hr-list-sub">Kontak darurat{{ $pegawai->kontakDarurat->hubungan_kerabat ? ' · ' . $pegawai->kontakDarurat->hubungan_kerabat : '' }} · {{ $pegawai->kontakDarurat->no_hp_kerabat }}</span>
                                                </div>
                                                <div class="hr-aksi-baris">
                                                    <x-icon-button icon="bi-telephone" label="Telepon kontak darurat" :href="'tel:' . preg_replace('/[^\d+]+/', '', $pegawai->kontakDarurat->no_hp_kerabat)" />
                                                </div>
                                            </li>
                                        @endif
                                    </ul>
                                @else
                                    <p class="small text-muted mb-0">Belum ada nomor HP atau email.</p>
                                @endif
                            </section>
                        </x-card>

                        <x-card title="Riwayat Kepegawaian" icon="bi-clock-history">
                            @if ($pegawai->penugasans->isNotEmpty())
                                <x-slot:actions>
                                    <a href="{{ route('admin.pegawai.show', [$pegawai, 'tab' => 'penugasan']) }}#riwayat-penugasan" data-buka-tab="penugasan">Lihat semua</a>
                                </x-slot:actions>
                            @endif
                            @if ($riwayat->isEmpty())
                                <x-empty-state icon="bi-clock-history" compact>Belum ada riwayat penugasan atau tanggal masuk.</x-empty-state>
                            @else
                                <x-timeline label="Riwayat kepegawaian">
                                    @foreach ($riwayat as $r)
                                        <x-timeline.item :state="$r['state']" :icon="$r['icon']" :title="$r['title']" :meta="$r['meta']">{{ $r['teks'] }}</x-timeline.item>
                                    @endforeach
                                </x-timeline>
                                @if ($riwayatLain)
                                    <p class="small text-muted mt-3 mb-0">+{{ $riwayatLain }} riwayat penugasan lain di tab Penugasan.</p>
                                @endif
                            @endif
                        </x-card>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('partials.preview-file-modal')
    @stack('modal-pegawai')
    @if ($dampakKeluar)
        @include('admin.pegawai._modal-keluar')
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // HP/tablet: bilah tab bergulir mendatar → geser agar tab yang terbuka (mis. dari ?tab=penugasan) terlihat.
            const navTab = document.querySelector('[data-hr-tabs="pegawai"]');
            function tampakkanTabAktif() {
                const aktif = navTab && navTab.querySelector('.hr-tab.active');
                if (aktif) navTab.scrollLeft += aktif.getBoundingClientRect().left - navTab.getBoundingClientRect().left - 8;
            }
            if (navTab) {
                tampakkanTabAktif();
                navTab.addEventListener('shown.bs.tab', tampakkanTabAktif);
            }

            // "Lihat semua" di kolom samping: buka tab Penugasan tanpa memuat ulang, lalu gulir ke tabelnya.
            document.querySelectorAll('[data-buka-tab]').forEach(function (tautan) {
                tautan.addEventListener('click', function (e) {
                    const tombol = document.getElementById('pegawai-tab-' + tautan.dataset.bukaTab);
                    if (!tombol || !window.bootstrap) return;
                    e.preventDefault();
                    bootstrap.Tab.getOrCreateInstance(tombol).show();
                    const tujuan = document.getElementById('riwayat-penugasan') || document.getElementById('detail-pegawai');
                    tujuan.scrollIntoView({ block: 'start' });
                    tombol.focus({ preventScroll: true });
                });
            });
        });
    </script>
@endsection
