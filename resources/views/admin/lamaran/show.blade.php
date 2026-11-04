@extends('layouts.admin.app')

@section('content')
@php
    /*
     * Detail Lamaran (Batch 6): kepala halaman (avatar, status besar, aksi) → kiri: kartu bertab (Profil · Dokumen · Interview ·
     * Onboarding & Kontrak · Keputusan) · kanan lengket: kartu Tindakan sesuai tahap, Timeline Lamaran, Info Lowongan.
     * Semua form, rute, dan nama isian sama dengan versi sebelumnya; tombol di kartu Tindakan / bilah cepat hanya menunjuk form itu.
     */
    $L = \App\Models\Lamaran::class;
    $IJ = \App\Models\InterviewJadwal::class;
    $SO = \App\Models\ScreeningOnboarding::class;
    $KJ = \App\Models\KontrakJadwal::class;
    $saya = auth()->user();

    // Kandidat pegawai (mutasi/promosi) menampilkan data pegawai; selain itu data pelamar.
    $pg = $lamaran->isKandidatPegawai() ? $lamaran->dataPegawai : null;
    $p = $lamaran->isKandidatPegawai() ? null : $lamaran->dataPelamar;
    $nama = $lamaran->namaKandidat() ?? 'Kandidat';
    $email = $lamaran->emailKandidat();
    $noHp = $p?->no_hp ?? $pg?->no_hp;
    $angkaHp = preg_replace('/\D+/', '', (string) $noHp);
    $nomorWa = $angkaHp ? (str_starts_with($angkaHp, '0') ? '62' . substr($angkaHp, 1) : $angkaHp) : null;
    $fotoPath = $p?->foto_path ?? $pg?->dokumen?->pas_foto;
    $fotoUrl = $fotoPath ? \App\Support\Berkas::url($fotoPath) : null;
    $cvUrl = $p?->cv_path ? \App\Support\Berkas::url($p->cv_path) : null;
    $posisi = $lamaran->judulPosisi() ?? '-';
    $unit = $lamaran->unitKerjaTujuan();
    $posisiLengkap = $posisi . ($unit ? ' · ' . $unit : '');
    $berhenti = in_array($lamaran->status, [$L::STATUS_DITOLAK, $L::STATUS_DIBATALKAN], true);
    // Warna status: hijau diterima · kuning menunggu/proses · merah ditolak · abu dibatalkan.
    $toneStatus = match ($lamaran->status) {
        $L::STATUS_DITERIMA => 'success',
        $L::STATUS_DITOLAK => 'danger',
        $L::STATUS_DIBATALKAN => 'neutral',
        default => 'warning',
    };
    $konteksTolak = [['Kandidat', $nama], ['Posisi', $posisiLengkap], ['Status sekarang', $lamaran->statusLabel()], ['Status tujuan', 'Ditolak — proses rekrutmen berhenti']];
    // Status jadwal: terjadwal/berjalan = kuning; selesai = abu (hasilnya punya badge sendiri), kecuali kontrak selesai = hijau.
    $toneJadwal = fn (?string $status, bool $selesaiHijau = false) => match (true) {
        in_array($status, ['terjadwal', 'berjalan'], true) => 'warning',
        $status === 'selesai' && $selesaiHijau => 'success',
        default => 'neutral',
    };
    $namaBerkasCv = $p?->cv_path ? 'CV ' . $nama . '.' . pathinfo($p->cv_path, PATHINFO_EXTENSION) : null;

    $terjadwal = $lamaran->interviewJadwals->first(fn ($j) => $j->isTerjadwal() && ! $j->hasil_status);
    $onboardingJalan = $lamaran->screeningOnboardings->firstWhere('status', $SO::STATUS_BERJALAN);
    $bisaMulaiOnboarding = $canReview && ($admin || ($lamaran->tahap === $L::TAHAP_SCREENING && $lamaran->screeningOnboardings->where('status', $SO::STATUS_BERJALAN)->isEmpty()));
    $kontrakTerjadwalKosong = $lamaran->kontrakJadwals->where('status', $KJ::STATUS_TERJADWAL)->isEmpty();
    $bisaJadwalKontrak = $admin || ($canReview && $onboardingLulus && $lamaran->tahap === $L::TAHAP_KONTRAK && $kontrakTerjadwalKosong);
    $labelInterviewBerikutnya = $tahapBerikutnya === $IJ::TAHAP_KEPALA_BAGIAN ? 'Interview 2' : 'Interview 1';

    // Bagian yang ditampilkan (kondisi sama dengan versi sebelumnya).
    $adaInterview = $lamaran->isProses() || $lamaran->interviewJadwals->isNotEmpty() || $admin;
    $adaOnboarding = $lamaran->isProses() || $admin;
    $adaKeputusan = $adaOnboarding && $canReview;

    // Langkah berikutnya: [teks, tab, id tujuan].
    [$langkah, $tabLangkah, $keLangkah] = match (true) {
        $berhenti => ['Lamaran ' . mb_strtolower($lamaran->statusLabel()) . '. Tidak ada langkah lanjutan.', null, null],
        $lamaran->status === $L::STATUS_DITERIMA => ['Kandidat sudah direkrut. Proses selesai.', null, null],
        ! $canReview => ['Tahap ini diproses Kepala SDM/GM.' . ($terjadwal && $terjadwal->interviewer_id === $saya->id ? ' Isi hasil interview yang ditugaskan kepada Anda.' : ''),
            $terjadwal ? 'interview' : null, $terjadwal ? 'jadwal-' . $terjadwal->id : null],
        $lamaran->tahap === $L::TAHAP_VERIFIKASI => ['Periksa data & berkas pelamar, lalu Terima atau Tolak verifikasi.', 'profil', 'detail-lamaran'],
        $lamaran->tahap === $L::TAHAP_INTERVIEW && $terjadwal => ['Isi hasil ' . $terjadwal->tahapLabel() . ' (' . $terjadwal->tanggal->translatedFormat('d M') . ').', 'interview', 'jadwal-' . $terjadwal->id],
        $lamaran->tahap === $L::TAHAP_INTERVIEW && $bisaKonfirmasiInterview => ["{$jumlahInterviewLulus} interview lulus — konfirmasi interview selesai untuk lanjut ke onboarding.", 'interview', 'bagian-interview'],
        $lamaran->tahap === $L::TAHAP_INTERVIEW && $tahapBerikutnya => ['Jadwalkan interview berikutnya.', 'interview', 'bagian-interview'],
        $lamaran->tahap === $L::TAHAP_INTERVIEW => ['Tunggu hasil interview.', 'interview', 'bagian-interview'],
        $lamaran->tahap === $L::TAHAP_SCREENING && ! $onboardingJalan => ['Mulai masa onboarding.', 'onboarding', 'bagian-onboarding'],
        $lamaran->tahap === $L::TAHAP_SCREENING => ['Konfirmasi hasil onboarding (Lulus bisa mulai ' . $onboardingJalan->tanggal_selesai->translatedFormat('d M') . ').', 'onboarding', 'onboarding-' . $onboardingJalan->id],
        $lamaran->tahap === $L::TAHAP_KONTRAK => ['Tanda tangan kontrak, lalu Rekrut di tab Keputusan.', 'keputusan', 'bagian-keputusan'],
        default => ['Periksa status lamaran.', null, null],
    };

    // Tombol tindakan tahap ini (kartu Tindakan + bilah cepat HP). Hanya menunjuk form/dialog/tab yang sudah ada di halaman.
    $aksiTahap = [];
    if ($canReview && $lamaran->isMenungguVerifikasi()) {
        $aksiTahap[] = ['label' => 'Terima Verifikasi', 'icon' => 'bi-check-lg', 'variant' => 'success', 'type' => 'submit', 'form' => 'form-verifikasi-terima'];
        $aksiTahap[] = ['label' => 'Tolak', 'icon' => 'bi-x-lg', 'variant' => 'danger-outline', 'form' => 'form-verifikasi-tolak', 'alasan' => 'tolak', 'konteks' => $konteksTolak];
    }
    if ($lamaran->tahap === $L::TAHAP_INTERVIEW && $lamaran->isProses()) {
        if ($terjadwal && \App\Support\InterviewFlow::bisaIsiHasil($saya, $terjadwal)) {
            $aksiTahap[] = ['label' => 'Isi Hasil ' . \Illuminate\Support\Str::before($terjadwal->tahapLabel(), ' ('), 'icon' => 'bi-clipboard-check', 'tab' => 'interview', 'ke' => 'jadwal-' . $terjadwal->id];
        }
        if ($canReview && $tahapBerikutnya && $interviewerBerikutnya) {
            $aksiTahap[] = ['label' => 'Jadwalkan ' . $labelInterviewBerikutnya, 'icon' => 'bi-calendar-plus', 'tab' => 'interview', 'ke' => 'form-jadwal-interview'];
        }
        if ($canReview && $bisaKonfirmasiInterview) {
            $aksiTahap[] = ['label' => 'Konfirmasi Interview Selesai', 'icon' => 'bi-check2-all', 'variant' => 'success', 'type' => 'submit', 'form' => 'form-interview-selesai'];
        }
        if ($canReview && $bisaTambahLanjutan) {
            $aksiTahap[] = ['label' => 'Tambah Interview Lanjutan', 'icon' => 'bi-plus-lg', 'variant' => 'secondary', 'modal' => '#modalInterviewLanjutan'];
        }
    }
    if ($lamaran->tahap === $L::TAHAP_SCREENING && $lamaran->isProses()) {
        if ($bisaMulaiOnboarding && ! $onboardingJalan) {
            $aksiTahap[] = ['label' => 'Mulai Onboarding', 'icon' => 'bi-play-fill', 'modal' => '#modalMulaiOnboarding'];
        }
        if ($onboardingJalan) {
            $onboardingJalan->setRelation('lamaran', $lamaran);
            foreach ([\App\Support\OnboardingFlow::SISI_SDM => 'Kepala SDM', \App\Support\OnboardingFlow::SISI_KEPALA => 'Kepala Bagian'] as $sisiAksi => $labelSisi) {
                if (($sisiAksi === \App\Support\OnboardingFlow::SISI_SDM || $onboardingJalan->kepala_id) && \App\Support\OnboardingFlow::bisaKonfirmasi($saya, $onboardingJalan, $sisiAksi)) {
                    $aksiTahap[] = ['label' => 'Konfirmasi ' . $labelSisi, 'icon' => 'bi-clipboard-check', 'variant' => 'success', 'modal' => '#modalKonfirmasi-' . $onboardingJalan->id . '-' . $sisiAksi];
                }
            }
        }
    }
    if ($lamaran->tahap === $L::TAHAP_KONTRAK && $lamaran->isProses() && $canReview) {
        if ($bisaJadwalKontrak && $kontrakTerjadwalKosong) {
            $aksiTahap[] = ['label' => 'Jadwalkan Kontrak', 'icon' => 'bi-calendar-plus', 'variant' => 'secondary', 'tab' => 'onboarding', 'ke' => 'form-jadwal-kontrak'];
        }
        $aksiTahap[] = ['label' => $lamaran->isKandidatPegawai() ? 'Tetapkan ke Posisi Baru' : 'Rekrut Jadi Pegawai', 'icon' => 'bi-check-circle', 'variant' => 'success', 'tab' => 'keputusan', 'ke' => 'form-rekrut'];
    }
    if ($canReview && $lamaran->isProses()) {
        $aksiTahap[] = ['label' => 'Tolak Pelamar', 'icon' => 'bi-x-lg', 'variant' => 'danger-outline', 'form' => 'form-tolak-lamaran', 'alasan' => 'tolak', 'konteks' => $konteksTolak];
    }
    if ($admin && $lamaran->isSelesai()) {
        $aksiTahap[] = ['label' => 'Ubah Keputusan (Admin)', 'icon' => 'bi-shield-lock', 'variant' => 'secondary', 'tab' => 'keputusan', 'ke' => 'bagian-keputusan'];
    }

    // Tab kiri sesuai data yang ada.
    $jumlahDokumen = $p ? collect([$p->cv_path, $p->ijazah_path, $p->sertifikat_path, $p->foto_path])->filter()->count() : 0;
    $tabs = [['key' => 'profil', 'label' => 'Profil', 'icon' => 'bi-person']];
    if ($p) {
        $tabs[] = ['key' => 'dokumen', 'label' => 'Dokumen', 'icon' => 'bi-folder2-open', 'badge' => $jumlahDokumen ?: null];
    }
    if ($adaInterview) {
        $tabs[] = ['key' => 'interview', 'label' => 'Interview', 'icon' => 'bi-chat-square-text', 'badge' => $lamaran->interviewJadwals->count() ?: null];
    }
    if ($adaOnboarding) {
        $tabs[] = ['key' => 'onboarding', 'label' => 'Onboarding & Kontrak', 'icon' => 'bi-clipboard-check'];
    }
    if ($adaKeputusan) {
        $tabs[] = ['key' => 'keputusan', 'label' => 'Keputusan', 'icon' => 'bi-flag'];
    }
    $kunciTab = array_column($tabs, 'key');
    // Tab awal mengikuti tahap (bisa ditimpa #hash URL oleh x-tabs).
    $tabAwal = match (true) {
        $lamaran->isSelesai() || $lamaran->isMenungguVerifikasi() => 'profil',
        $lamaran->tahap === $L::TAHAP_INTERVIEW && in_array('interview', $kunciTab, true) => 'interview',
        $lamaran->tahap === $L::TAHAP_SCREENING && in_array('onboarding', $kunciTab, true) => 'onboarding',
        $lamaran->tahap === $L::TAHAP_KONTRAK && in_array('keputusan', $kunciTab, true) => 'keputusan',
        $lamaran->tahap === $L::TAHAP_KONTRAK && in_array('onboarding', $kunciTab, true) => 'onboarding',
        default => 'profil',
    };
    $labelTab = array_column($tabs, 'label', 'key');
@endphp

<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="$nama" eyebrow="Rekrutmen" :back="route('admin.lamaran.index')" back-label="Lamaran Masuk"
            :subtitle="($lamaran->isJalurInternal() ? 'Posisi tujuan' : 'Melamar') . ': ' . $posisi . ($unit ? ' (' . $unit . ')' : '')">
            <x-slot:media><x-avatar :src="$fotoUrl" :name="$nama" size="xl" /></x-slot:media>

            <x-badge size="lg" :tone="$toneStatus" dot>{{ $lamaran->statusLabel() }}</x-badge>
            @if ($lamaran->isJalurInternal())
                <x-badge tone="neutral" icon="bi-person-check">Jalur Langsung</x-badge>
            @endif
            @if ($lamaran->isKandidatPegawai())
                <x-badge tone="info" icon="bi-person-badge">Pegawai yayasan</x-badge>
            @endif
            <x-badge tone="neutral" icon="bi-calendar3">Masuk {{ optional($lamaran->created_at)->translatedFormat('d M Y') }}</x-badge>

            <x-slot:actions>
                @if ($email)
                    <x-button variant="secondary" icon="bi-envelope" :href="'mailto:' . $email" :title="'Kirim email ke ' . $email">Kirim Email</x-button>
                @endif
                @if ($cvUrl)
                    <x-button variant="secondary" icon="bi-download" :href="$cvUrl" :download="$namaBerkasCv">Unduh CV</x-button>
                @endif
                @if (auth()->user()->can('sistem.override'))
                    <x-button variant="secondary" icon="bi-shield-lock" data-bs-toggle="modal" data-bs-target="#modalUbahAdmin">Ubah Status (Admin)</x-button>
                @endif
                @if ($admin)
                    <form action="{{ route('admin.data.destroy', ['lamaran', $lamaran->id]) }}" method="POST" class="d-inline-flex"
                        data-confirm="Hapus permanen lamaran {{ $nama }} beserta semua interview, onboarding, dan kontraknya?">
                        @csrf @method('DELETE')
                        <input type="hidden" name="kembali" value="{{ route('admin.lamaran.index') }}">
                        <x-button type="submit" variant="danger-outline" icon="bi-trash">Hapus Lamaran</x-button>
                    </form>
                @endif
            </x-slot:actions>
        </x-page-header>

        <div class="row g-4">
            {{-- ============ Kiri: data kandidat & proses seleksi per tab ============ --}}
            <div class="col-xl-8 d-grid gap-4 align-content-start">
                @if ($aksiTahap)
                    {{-- Layar < 1200px: kartu Tindakan baru tampil di bawah, jadi tindakan tahap ini ditaruh di atas. --}}
                    <div class="hr-aksi-cepat d-xl-none" role="region" aria-label="Tindakan cepat">
                        <div class="min-w-0">
                            <strong class="d-block">{{ $canReview ? 'Perlu tindakan Anda' : 'Tugas Anda' }}</strong>
                            <span class="hr-sel-sub">{{ $langkah }}</span>
                        </div>
                        <div class="hr-aksi-cepat-tombol">
                            @include('admin.lamaran._aksi-tahap', ['ukuran' => 'sm'])
                        </div>
                    </div>
                @endif

                <x-card id="detail-lamaran" class="hr-bagian" :padding="false" aria-label="Data kandidat dan proses seleksi">
                    <x-tabs id="lamaran" :items="$tabs" :active="$tabAwal" label="Bagian detail lamaran" class="px-2" />

                    <div class="tab-content hr-card-body pt-4">
                        {{-- ---------- Profil ---------- --}}
                        <x-tabs.pane tabs="lamaran" key="profil" :active="$tabAwal === 'profil'">
                            @if ($lamaran->isJalurInternal())
                                <section class="hr-detail-bagian" aria-labelledby="judul-jalur">
                                    <h2 class="hr-detail-judul" id="judul-jalur"><i class="bi bi-person-check" aria-hidden="true"></i> Jalur Langsung</h2>
                                    <x-info-list :items="[
                                        'Kandidat' => $lamaran->isKandidatPegawai() ? 'Pegawai yayasan (mutasi/promosi)' : 'Calon pegawai baru',
                                        'Diusulkan Oleh' => $lamaran->direkomendasikan_oleh,
                                        'Posisi Tujuan' => collect([$lamaran->jabatanTujuan(), $lamaran->unitKerjaTujuan()])->filter()->implode(' · '),
                                        'Lowongan' => $lamaran->lowongan ? $lamaran->lowongan->judul : 'Tanpa lowongan',
                                        'Ditambahkan' => ($lamaran->diverifikasiOleh?->name ?? '-') . ', ' . optional($lamaran->created_at)->translatedFormat('d M Y'),
                                    ]" />
                                </section>
                            @endif

                            @if ($lamaran->isKandidatPegawai())
                                <section class="hr-detail-bagian" aria-labelledby="judul-data-pegawai">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                        <h2 class="hr-detail-judul mb-0" id="judul-data-pegawai"><i class="bi bi-person-badge" aria-hidden="true"></i> Data Pegawai</h2>
                                        @if ($pg)
                                            <x-button size="sm" variant="secondary" icon="bi-person-vcard" :href="route('admin.pegawai.show', $pg->id)">Lihat Data Pegawai</x-button>
                                        @endif
                                    </div>
                                    <x-info-list :items="[
                                        'Nama Lengkap' => $pg?->nama_lengkap,
                                        'NIP' => $pg?->nip,
                                        'Email' => $pg?->email,
                                        'No. HP' => $pg?->no_hp,
                                        'Jabatan Sekarang' => $pg?->kepegawaian?->jabatan,
                                        'Bagian Sekarang' => $pg?->kepegawaian?->unit_kerja,
                                        'Status Kerja' => $pg?->kepegawaian?->status_kerja,
                                    ]" />
                                </section>
                            @else
                                <section class="hr-detail-bagian" aria-labelledby="judul-identitas">
                                    <h2 class="hr-detail-judul" id="judul-identitas"><i class="bi bi-person-lines-fill" aria-hidden="true"></i> Identitas</h2>
                                    <x-info-list :items="[
                                        'Nama Lengkap' => $p?->nama_lengkap,
                                        'NIK' => $p?->nik,
                                        'Tempat, Tanggal Lahir' => collect([$p?->tempat_lahir, optional($p?->tanggal_lahir)->translatedFormat('d F Y')])->filter()->implode(', '),
                                        'Jenis Kelamin' => $p?->jenis_kelamin,
                                        'Agama' => $p?->agama,
                                        'Status Pernikahan' => $p?->status_pernikahan,
                                        'Pendidikan Terakhir' => $p?->pendidikan_terakhir,
                                    ]" />
                                </section>

                                <section class="hr-detail-bagian" aria-labelledby="judul-kontak">
                                    <h2 class="hr-detail-judul" id="judul-kontak"><i class="bi bi-chat-dots" aria-hidden="true"></i> Kontak &amp; Tautan</h2>
                                    @if ($noHp || $email || $p?->sosial_media)
                                        <ul class="hr-list">
                                            @if ($noHp)
                                                <li class="hr-list-item">
                                                    <div class="hr-list-utama">
                                                        <span class="hr-list-judul angka-utuh">{{ $noHp }}</span>
                                                        <span class="hr-list-sub">No. HP</span>
                                                    </div>
                                                    <div class="hr-aksi-baris">
                                                        <x-icon-button icon="bi-telephone" label="Telepon" :href="'tel:' . $angkaHp" />
                                                        <x-icon-button icon="bi-whatsapp" label="Chat WhatsApp" :href="'https://wa.me/' . $nomorWa" target="_blank" rel="noopener" />
                                                    </div>
                                                </li>
                                            @endif
                                            @if ($email)
                                                <li class="hr-list-item">
                                                    <div class="hr-list-utama">
                                                        <span class="hr-list-judul">{{ $email }}</span>
                                                        <span class="hr-list-sub">Email</span>
                                                    </div>
                                                    <div class="hr-aksi-baris">
                                                        <x-icon-button icon="bi-envelope" label="Kirim email" :href="'mailto:' . $email" />
                                                    </div>
                                                </li>
                                            @endif
                                            @foreach ($p?->sosial_media ?? [] as $sm)
                                                @php
                                                    $ikonSosmed = [
                                                        'linkedin' => 'bi-linkedin', 'instagram' => 'bi-instagram', 'facebook' => 'bi-facebook', 'tiktok' => 'bi-tiktok',
                                                        'x (twitter)' => 'bi-twitter-x', 'youtube' => 'bi-youtube', 'github' => 'bi-github', 'behance' => 'bi-behance',
                                                    ][mb_strtolower($sm['platform'] ?? '')] ?? 'bi-link-45deg';
                                                @endphp
                                                <li class="hr-list-item">
                                                    <div class="hr-list-utama">
                                                        <span class="hr-list-judul"><i class="bi {{ $ikonSosmed }} me-1" aria-hidden="true"></i>{{ $sm['platform'] ?? 'Tautan' }}</span>
                                                        <span class="hr-list-sub">{{ $sm['url'] ?? '' }}</span>
                                                    </div>
                                                    <div class="hr-aksi-baris">
                                                        <x-icon-button icon="bi-box-arrow-up-right" :label="'Buka ' . ($sm['platform'] ?? 'tautan')" :href="$sm['url'] ?? '#'" target="_blank" rel="noopener noreferrer" />
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="small text-muted mb-0">Belum ada nomor HP, email, atau tautan.</p>
                                    @endif
                                </section>

                                <section class="hr-detail-bagian" aria-labelledby="judul-alamat">
                                    <h2 class="hr-detail-judul" id="judul-alamat"><i class="bi bi-geo-alt" aria-hidden="true"></i> Alamat</h2>
                                    <x-info-list :items="[
                                        'Alamat KTP' => $p?->alamat_ktp,
                                        'Alamat Domisili' => $p?->alamat_domisili,
                                    ]" />
                                </section>

                                <section class="hr-detail-bagian" aria-labelledby="judul-pendidikan">
                                    <h2 class="hr-detail-judul" id="judul-pendidikan"><i class="bi bi-mortarboard" aria-hidden="true"></i> Riwayat Pendidikan</h2>
                                    @if (($p?->riwayatPendidikan ?? collect())->isEmpty())
                                        <x-empty-state icon="bi-mortarboard" compact>Belum ada riwayat pendidikan.</x-empty-state>
                                    @else
                                        <x-timeline label="Riwayat pendidikan">
                                            @foreach ($p->riwayatPendidikan as $pend)
                                                <x-timeline.item :state="$pend->tahun_lulus ? 'done' : 'active'" icon="bi-mortarboard"
                                                    :title="$pend->jenjang . ' · ' . $pend->nama_institusi" :meta="$pend->periodeLabel()">
                                                    @if ($pend->jurusan)<span class="d-block">{{ $pend->jurusan }}</span>@endif
                                                    @if ($pend->nilai)<span class="d-block">Nilai/IPK {{ $pend->nilai }}</span>@endif
                                                </x-timeline.item>
                                            @endforeach
                                        </x-timeline>
                                    @endif
                                </section>

                                <section class="hr-detail-bagian" aria-labelledby="judul-pekerjaan">
                                    <h2 class="hr-detail-judul" id="judul-pekerjaan"><i class="bi bi-briefcase" aria-hidden="true"></i> Riwayat Pekerjaan</h2>
                                    @if (($p?->riwayatPekerjaan ?? collect())->isEmpty())
                                        <x-empty-state icon="bi-briefcase" compact>Belum ada riwayat pekerjaan.</x-empty-state>
                                    @else
                                        <x-timeline label="Riwayat pekerjaan">
                                            @foreach ($p->riwayatPekerjaan as $riwayat)
                                                <x-timeline.item :state="$riwayat->tanggal_selesai ? 'done' : 'active'" icon="bi-briefcase"
                                                    :title="$riwayat->posisi . ' · ' . $riwayat->nama_perusahaan"
                                                    :meta="optional($riwayat->tanggal_mulai)->translatedFormat('M Y') . ' – ' . ($riwayat->tanggal_selesai ? $riwayat->tanggal_selesai->translatedFormat('M Y') : 'Sekarang')">
                                                    @if ($riwayat->deskripsi)<span class="d-block">{{ $riwayat->deskripsi }}</span>@endif
                                                </x-timeline.item>
                                            @endforeach
                                        </x-timeline>
                                    @endif
                                </section>
                            @endif
                        </x-tabs.pane>

                        {{-- ---------- Dokumen ---------- --}}
                        @if ($p)
                            <x-tabs.pane tabs="lamaran" key="dokumen" :active="$tabAwal === 'dokumen'">
                                <section class="hr-detail-bagian" aria-labelledby="judul-cv">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                        <h2 class="hr-detail-judul mb-0" id="judul-cv"><i class="bi bi-file-earmark-person" aria-hidden="true"></i> CV (Daftar Riwayat Hidup)</h2>
                                        @if ($cvUrl)
                                            <x-button size="sm" variant="secondary" icon="bi-download" :href="$cvUrl" :download="$namaBerkasCv">Unduh CV</x-button>
                                        @endif
                                    </div>
                                    @include('partials.preview-file', ['path' => $p->cv_path, 'label' => 'CV'])
                                </section>
                                <section class="hr-detail-bagian" aria-labelledby="judul-dok-pendukung">
                                    <h2 class="hr-detail-judul" id="judul-dok-pendukung"><i class="bi bi-folder2-open" aria-hidden="true"></i> Dokumen Pendukung</h2>
                                    <div class="row g-3">
                                        @foreach (['ijazah_path' => 'Ijazah', 'sertifikat_path' => 'Sertifikat', 'foto_path' => 'Pas Foto'] as $kolomDok => $labelDok)
                                            <div class="col-md-6">
                                                @include('partials.preview-file', ['path' => $p->{$kolomDok}, 'label' => $labelDok])
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            </x-tabs.pane>
                        @endif

                        {{-- ---------- Interview ---------- --}}
                        @if ($adaInterview)
                            <x-tabs.pane tabs="lamaran" key="interview" :active="$tabAwal === 'interview'">
                                <section class="hr-detail-bagian" id="bagian-interview" aria-labelledby="judul-interview">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <h2 class="hr-detail-judul mb-0" id="judul-interview"><i class="bi bi-chat-square-text" aria-hidden="true"></i> Jadwal &amp; Hasil Interview</h2>
                                        <x-badge :tone="$jumlahInterviewLulus >= \App\Support\InterviewFlow::MIN_LULUS ? 'success' : 'neutral'" icon="bi-check2-circle">
                                            {{ $jumlahInterviewLulus }} lulus &middot; min. {{ \App\Support\InterviewFlow::MIN_LULUS }}
                                        </x-badge>
                                    </div>
                                    <p class="small text-muted mb-4">
                                        Interview 1 oleh Kepala SDM &rarr; Interview 2 oleh Kepala Bagian {{ $lamaran->unitKerjaTujuan() }} (otomatis setelah Interview 1 lulus)
                                        &rarr; interview lanjutan bila perlu (bebas) &rarr; <strong>Konfirmasi Interview Selesai</strong> &rarr; onboarding.
                                    </p>

                                    @forelse ($lamaran->interviewJadwals as $jadwal)
                                        @php
                                            $aksiJadwal = ($jadwal->isTerjadwal() && $lamaran->isProses()) || $admin;
                                            $bisaIsi = $aksiJadwal && \App\Support\InterviewFlow::bisaIsiHasil($saya, $jadwal);
                                            $bisaUbah = $aksiJadwal && \App\Support\InterviewFlow::bisaUbahJadwal($saya, $jadwal);
                                            $waktuJadwal = $jadwal->tanggal->translatedFormat('l, d M Y') . ', ' . substr($jadwal->waktu_mulai, 0, 5) . ($jadwal->waktu_selesai ? '–' . substr($jadwal->waktu_selesai, 0, 5) : '');
                                            $hasilTerkunci = $jadwal->hasil_status && ! $admin;
                                        @endphp
                                        <article class="lm-item hr-bagian {{ $jadwal->isTerjadwal() ? 'is-aktif' : '' }}" id="jadwal-{{ $jadwal->id }}" aria-labelledby="judul-jadwal-{{ $jadwal->id }}">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                                <div class="min-w-0">
                                                    <h3 class="hr-subbagian-judul" id="judul-jadwal-{{ $jadwal->id }}">{{ $jadwal->tahapLabel() }}</h3>
                                                    <p class="hr-subbagian-sub mb-0">
                                                        {{ $waktuJadwal }}
                                                        @if ($jadwal->lokasi) &middot; @include('partials.lokasi', ['lokasi' => $jadwal->lokasi, 'tanpaIkon' => true]) @endif
                                                    </p>
                                                </div>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <x-badge :tone="$toneJadwal($jadwal->status)">{{ $jadwal->statusLabel() }}</x-badge>
                                                    <x-status-badge :badge="$jadwal->hasilBadgeClass()" :icon="$hasilTerkunci ? 'bi-lock-fill' : null"
                                                        :title="$hasilTerkunci ? 'Hasil sudah final, tidak bisa diubah' : null">{{ $jadwal->hasilLabel() }}</x-status-badge>
                                                </div>
                                            </div>
                                            <dl class="hr-konteks hr-konteks-ringkas mt-3">
                                                <dt>Interviewer</dt>
                                                <dd>{{ $jadwal->interviewer?->name ?? '-' }}@if ($jadwal->interviewer) ({{ $jadwal->interviewer->roleLabel() }})@endif</dd>
                                                @if ($jadwal->catatan)
                                                    <dt>Catatan jadwal</dt>
                                                    <dd>{{ $jadwal->catatan }}</dd>
                                                @endif
                                            </dl>
                                            @if ($jadwal->catatan_hasil)
                                                <p class="lamaran-catatan"><i class="bi bi-chat-left-quote" aria-hidden="true"></i><span><strong>Catatan penilaian:</strong> {{ $jadwal->catatan_hasil }}</span></p>
                                            @endif

                                            @if ($aksiJadwal)
                                                {{-- Baris 1: isi hasil (aksi utama). Baris 2: aksi pendukung, rata kanan. --}}
                                                @if ($bisaIsi)
                                                    <form action="{{ route('admin.lamaran.interview.hasil', [$lamaran, $jadwal]) }}" method="POST" class="row g-2 align-items-end mt-2"
                                                        data-confirm="{{ $admin ? 'Simpan hasil ' . $jadwal->tahapLabel() . ' untuk ' . $nama . '? (Mode Admin: hasil bisa diubah lagi kapan saja. Tidak Lulus menolak lamaran; mengubah Tidak Lulus membuka lamaran kembali.)' : 'Simpan hasil ' . $jadwal->tahapLabel() . ' untuk ' . $nama . '? Hasil final tidak bisa diubah, dan Tidak Lulus langsung menolak lamaran.' }}">
                                                        @csrf
                                                        <div class="col-sm-4">
                                                            <label class="form-label" for="hasil-iv-{{ $jadwal->id }}">Hasil interview</label>
                                                            <select name="hasil_status" id="hasil-iv-{{ $jadwal->id }}" class="form-select form-select-sm" required>
                                                                <option value="">Pilih hasil</option>
                                                                <option value="lulus" @selected($jadwal->hasil_status === 'lulus')>Lulus</option>
                                                                <option value="dipertimbangkan" @selected($jadwal->hasil_status === 'dipertimbangkan')>Dipertimbangkan</option>
                                                                <option value="tidak_lulus" @selected($jadwal->hasil_status === 'tidak_lulus')>Tidak Lulus</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-sm-5">
                                                            <label class="form-label" for="catatan-iv-{{ $jadwal->id }}">Catatan penilaian <span class="hr-field-opsional">(opsional)</span></label>
                                                            <input type="text" name="catatan_hasil" id="catatan-iv-{{ $jadwal->id }}" class="form-control form-control-sm" maxlength="1000"
                                                                placeholder="Penilaian singkat, contoh: komunikasi baik, menguasai materi" value="{{ $jadwal->catatan_hasil }}">
                                                        </div>
                                                        <div class="col-sm-3 d-grid">
                                                            <x-button type="submit" size="sm" variant="success" icon="bi-check-lg">{{ $jadwal->hasil_status ? 'Ubah Hasil' : 'Simpan Hasil' }}</x-button>
                                                        </div>
                                                    </form>
                                                @endif

                                                <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center mt-3">
                                                    @if ($bisaUbah)
                                                        <x-button size="sm" variant="secondary" icon="bi-pencil" data-bs-toggle="collapse" :data-bs-target="'#ubah-jadwal-' . $jadwal->id"
                                                            aria-expanded="false" :aria-controls="'ubah-jadwal-' . $jadwal->id">Ubah Jadwal</x-button>
                                                    @endif
                                                    @if ($canReview && $jadwal->status !== \App\Models\InterviewJadwal::STATUS_DIBATALKAN)
                                                        <form action="{{ route('admin.lamaran.interview.cancel', [$lamaran, $jadwal]) }}" method="POST" class="form-cancel-interview">
                                                            @csrf
                                                            <input type="hidden" name="catatan" value="">
                                                            <x-button size="sm" variant="danger-outline" icon="bi-x-lg" class="btn-cancel-interview" data-alasan="batal-interview"
                                                                :data-konteks="json_encode([['Kandidat', $nama], ['Jadwal', $jadwal->tahapLabel() . ' · ' . $waktuJadwal], ['Interviewer', $jadwal->interviewer?->name]], JSON_UNESCAPED_UNICODE)">Batalkan</x-button>
                                                        </form>
                                                    @endif
                                                    @if ($admin)
                                                        <form action="{{ route('admin.lamaran.interview.destroy', [$lamaran, $jadwal]) }}" method="POST"
                                                            data-confirm="Hapus permanen {{ $jadwal->tahapLabel() }} {{ $nama }} ({{ $jadwal->tanggal->translatedFormat('d M Y') }})?">
                                                            @csrf @method('DELETE')
                                                            <x-icon-button type="submit" icon="bi-trash" label="Hapus permanen (Admin)" tone="danger" />
                                                        </form>
                                                    @endif
                                                </div>

                                                @if ($bisaUbah)
                                                    <div class="collapse" id="ubah-jadwal-{{ $jadwal->id }}">
                                                        <form action="{{ route('admin.lamaran.interview.update', [$lamaran, $jadwal]) }}" method="POST"
                                                            class="row g-3 mt-3 pt-3 border-top" data-confirm="Simpan perubahan jadwal {{ $jadwal->tahapLabel() }} untuk {{ $nama }}?">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="col-md-4">
                                                                <label class="form-label" for="uj-tanggal-{{ $jadwal->id }}">Tanggal</label>
                                                                <input type="date" name="tanggal" id="uj-tanggal-{{ $jadwal->id }}" class="form-control form-control-sm" value="{{ $jadwal->tanggal->format('Y-m-d') }}" required>
                                                            </div>
                                                            <div class="col-6 col-md-4">
                                                                <label class="form-label" for="uj-mulai-{{ $jadwal->id }}">Jam Mulai</label>
                                                                <input type="time" name="waktu_mulai" id="uj-mulai-{{ $jadwal->id }}" class="form-control form-control-sm" value="{{ substr($jadwal->waktu_mulai, 0, 5) }}" required>
                                                            </div>
                                                            <div class="col-6 col-md-4">
                                                                <label class="form-label" for="uj-selesai-{{ $jadwal->id }}">Jam Selesai</label>
                                                                <input type="time" name="waktu_selesai" id="uj-selesai-{{ $jadwal->id }}" class="form-control form-control-sm" value="{{ $jadwal->waktu_selesai ? substr($jadwal->waktu_selesai, 0, 5) : '' }}">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label" for="uj-lokasi-{{ $jadwal->id }}">Tempat / Link</label>
                                                                <input type="text" name="lokasi" id="uj-lokasi-{{ $jadwal->id }}" class="form-control form-control-sm" placeholder="Tempat atau link, contoh: Ruang Rapat Lt. 2" value="{{ $jadwal->lokasi }}">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label" for="uj-catatan-{{ $jadwal->id }}">Catatan <span class="hr-field-opsional">(opsional)</span></label>
                                                                <input type="text" name="catatan" id="uj-catatan-{{ $jadwal->id }}" class="form-control form-control-sm" placeholder="Pesan untuk kandidat, contoh: bawa berkas asli" value="{{ $jadwal->catatan }}">
                                                            </div>
                                                            @if ($admin)
                                                                <div class="col-md-5">
                                                                    <label class="form-label" for="uj-interviewer-{{ $jadwal->id }}">Interviewer</label>
                                                                    <select name="interviewer_id" id="uj-interviewer-{{ $jadwal->id }}" class="form-select form-select-sm">
                                                                        @foreach ($opsiInterviewerAdmin as $opt)
                                                                            <option value="{{ $opt->id }}" @selected($opt->id === $jadwal->interviewer_id)>{{ $opt->name }} ({{ $opt->roleLabel() }})</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-6 col-md-4">
                                                                    <label class="form-label" for="uj-tahap-{{ $jadwal->id }}">Tahap</label>
                                                                    <select name="tahap_interview" id="uj-tahap-{{ $jadwal->id }}" class="form-select form-select-sm">
                                                                        @foreach ([1 => 'Interview 1', 2 => 'Interview 2', 3 => 'Lanjutan'] as $tv => $tl)
                                                                            <option value="{{ $tv }}" @selected($jadwal->tahap_interview === $tv)>{{ $tl }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-6 col-md-3">
                                                                    <label class="form-label" for="uj-status-{{ $jadwal->id }}">Status</label>
                                                                    <select name="status" id="uj-status-{{ $jadwal->id }}" class="form-select form-select-sm">
                                                                        @foreach (['terjadwal' => 'Terjadwal', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $sv => $sl)
                                                                            <option value="{{ $sv }}" @selected($jadwal->status === $sv)>{{ $sl }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            @endif
                                                            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                                <span class="form-text m-0">{{ $admin ? 'Mode Admin: status Terjadwal mengosongkan hasil (interview diulang).' : 'Interviewer tidak bisa diganti setelah dijadwalkan.' }}</span>
                                                                <x-button type="submit" size="sm" icon="bi-check-lg">Simpan Jadwal</x-button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                @endif
                                                <p class="form-text mb-0 mt-2">
                                                    @if ($jadwal->tahap_interview === \App\Models\InterviewJadwal::TAHAP_KEPALA_SDM)
                                                        Lulus: Interview 2 dengan Kepala Bagian otomatis dibuat.
                                                    @else
                                                        Lulus: bisa tambah interview lanjutan atau konfirmasi interview selesai.
                                                    @endif
                                                    <span class="text-danger">Tidak Lulus: lamaran langsung ditolak.</span>
                                                </p>
                                            @endif
                                        </article>
                                    @empty
                                        <x-empty-state icon="bi-calendar-x" compact>Belum ada jadwal interview.</x-empty-state>
                                    @endforelse

                                    @if ($canReview && $tahapBerikutnya && $interviewerBerikutnya)
                                        <div class="hr-subbagian mt-4">
                                            <h3 class="hr-subbagian-judul"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Jadwalkan {{ $labelInterviewBerikutnya }}</h3>
                                            <p class="hr-subbagian-sub">Interviewer: <strong>{{ $interviewerBerikutnya->name }}</strong> ({{ $labelInterviewBerikutnya }}).</p>
                                            <form action="{{ route('admin.lamaran.interview.store', $lamaran) }}" method="POST" class="row g-3 hr-bagian" id="form-jadwal-interview"
                                                data-confirm="Jadwalkan {{ $labelInterviewBerikutnya }} untuk {{ $nama }}?">
                                                @csrf
                                                <div class="col-md-4">
                                                    <label class="form-label" for="ji-tanggal">Tanggal</label>
                                                    <input type="date" name="tanggal" id="ji-tanggal" class="form-control form-control-sm" value="{{ \App\Support\Kalender::hariKerjaTerdekat(now())->format('Y-m-d') }}" required>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <label class="form-label" for="ji-mulai">Jam Mulai</label>
                                                    <input type="time" name="waktu_mulai" id="ji-mulai" class="form-control form-control-sm" required>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <label class="form-label" for="ji-selesai">Jam Selesai <span class="hr-field-opsional">(opsional)</span></label>
                                                    <input type="time" name="waktu_selesai" id="ji-selesai" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label" for="ji-lokasi">Tempat / Link <span class="hr-field-opsional">(opsional)</span></label>
                                                    <input type="text" name="lokasi" id="ji-lokasi" class="form-control form-control-sm" placeholder="Tempat atau link, contoh: Ruang Rapat Lt. 2">
                                                </div>
                                                <div class="col-md-4 d-flex align-items-end">
                                                    <x-button type="submit" size="sm" icon="bi-calendar-check" block>Jadwalkan {{ $labelInterviewBerikutnya }}</x-button>
                                                </div>
                                            </form>
                                        </div>
                                    @elseif ($canReview && $tahapBerikutnya && ! $interviewerBerikutnya)
                                        <x-alert tone="warning" class="mt-3 mb-0">
                                            Belum ada akun <strong>Kepala SDM</strong> untuk menjadi interviewer Interview 1. Tambahkan lewat Kelola Akun.
                                        </x-alert>
                                    @endif

                                    @if ($canReview && ($bisaTambahLanjutan || $bisaKonfirmasiInterview || $admin))
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-4 pt-3 border-top">
                                            @if ($bisaTambahLanjutan)
                                                <x-button size="sm" variant="outline" icon="bi-plus-lg" data-bs-toggle="modal" data-bs-target="#modalInterviewLanjutan">Tambah Interview Lanjutan</x-button>
                                            @endif
                                            @if ($admin)
                                                <x-button size="sm" variant="secondary" icon="bi-shield-lock" data-bs-toggle="modal" data-bs-target="#modalInterviewAdmin">Tambah Interview (Admin)</x-button>
                                            @endif
                                            @if ($bisaKonfirmasiInterview || ($admin && $lamaran->tahap !== \App\Models\Lamaran::TAHAP_SCREENING))
                                                <form action="{{ route('admin.lamaran.interview.selesai', $lamaran) }}" method="POST" class="ms-auto" id="form-interview-selesai"
                                                    data-confirm="Konfirmasi interview {{ $nama }} selesai ({{ $jumlahInterviewLulus }} interview lulus) dan lanjut ke onboarding? Interview lanjutan tidak bisa ditambah lagi.">
                                                    @csrf
                                                    <x-button type="submit" size="sm" variant="success" icon="bi-check2-all">Konfirmasi Interview Selesai</x-button>
                                                </form>
                                            @elseif ($lamaran->tahap === \App\Models\Lamaran::TAHAP_INTERVIEW)
                                                <span class="text-muted small ms-auto">Konfirmasi selesai tersedia setelah minimal {{ \App\Support\InterviewFlow::MIN_LULUS }} interview lulus.</span>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($admin)
                                        @push('modal-lamaran')
                                            @include('admin.lamaran._modal-interview-admin')
                                        @endpush
                                    @endif

                                    @if ($canReview && $bisaTambahLanjutan)
                                        @push('modal-lamaran')
                                            <x-modal id="modalInterviewLanjutan" title="Interview Lanjutan" icon="bi-calendar-plus" :subtitle="$nama . ' · ' . $posisi">
                                                <form action="{{ route('admin.lamaran.interview.store', $lamaran) }}" method="POST" id="form-interview-lanjutan"
                                                    data-confirm="Jadwalkan interview lanjutan untuk {{ $nama }}?">
                                                    @csrf
                                                    <div class="row g-3">
                                                        <div class="col-12">
                                                            <label class="form-label" for="il-interviewer">Interviewer</label>
                                                            <select name="interviewer_id" id="il-interviewer" class="form-select" required>
                                                                <option value="">Pilih interviewer</option>
                                                                @foreach ($opsiInterviewerLanjutan as $opt)
                                                                    <option value="{{ $opt->id }}">{{ $opt->name }} ({{ $opt->roleLabel() }})</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label" for="il-tanggal">Tanggal</label>
                                                            <input type="date" name="tanggal" id="il-tanggal" class="form-control" value="{{ \App\Support\Kalender::hariKerjaTerdekat(now())->format('Y-m-d') }}" @unless (auth()->user()->can('sistem.override')) min="{{ now()->format('Y-m-d') }}" @endunless required>
                                                        </div>
                                                        <div class="col-6 col-md-3">
                                                            <label class="form-label" for="il-mulai">Jam Mulai</label>
                                                            <input type="time" name="waktu_mulai" id="il-mulai" class="form-control" required>
                                                        </div>
                                                        <div class="col-6 col-md-3">
                                                            <label class="form-label" for="il-selesai">Jam Selesai</label>
                                                            <input type="time" name="waktu_selesai" id="il-selesai" class="form-control">
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="il-lokasi">Tempat / Link <span class="hr-field-opsional">(opsional)</span></label>
                                                            <input type="text" name="lokasi" id="il-lokasi" class="form-control" placeholder="Tempat atau link, contoh: Ruang Rapat Lt. 2">
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="il-catatan">Tujuan <span class="hr-field-opsional">(opsional)</span></label>
                                                            <input type="text" name="catatan" id="il-catatan" class="form-control" placeholder="Tujuan interview, contoh: tes praktik mengajar">
                                                        </div>
                                                    </div>
                                                </form>
                                                <x-slot:footer>
                                                    <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
                                                    <x-button type="submit" form="form-interview-lanjutan" icon="bi-calendar-check">Jadwalkan</x-button>
                                                </x-slot:footer>
                                            </x-modal>
                                        @endpush
                                    @endif
                                </section>
                            </x-tabs.pane>
                        @endif

                        {{-- ---------- Onboarding & Kontrak ---------- --}}
                        @if ($adaOnboarding)
                            <x-tabs.pane tabs="lamaran" key="onboarding" :active="$tabAwal === 'onboarding'">
                                <section class="hr-detail-bagian" id="bagian-onboarding" aria-labelledby="judul-onboarding">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <h2 class="hr-detail-judul mb-0" id="judul-onboarding"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Screening / Onboarding</h2>
                                        @if ($bisaMulaiOnboarding)
                                            <x-button size="sm" icon="bi-play-fill" data-bs-toggle="modal" data-bs-target="#modalMulaiOnboarding">Mulai Onboarding</x-button>
                                        @endif
                                    </div>
                                    <p class="small text-muted mb-4">
                                        Hasil dikonfirmasi <strong>Kepala SDM</strong>@if ($kepalaTerkait) &amp; <strong>{{ $kepalaTerkait->name }}</strong> (Kepala Bagian {{ $lamaran->unitKerjaTujuan() }})@endif.
                                        Setelah lulus, jadwal kontrak otomatis dibuat sehari setelah hari terakhir onboarding; tanggal masuk = hari pertama onboarding.
                                    </p>

                                    @if ($lamaran->tahap === \App\Models\Lamaran::TAHAP_INTERVIEW && ! $admin)
                                        <x-alert tone="info" icon="bi-hourglass-split" class="mb-3">Tersedia setelah interview dikonfirmasi selesai.</x-alert>
                                    @endif

                                    @foreach ($lamaran->screeningOnboardings as $screening)
                                        @php
                                            $screening->setRelation('lamaran', $lamaran);
                                            $sisiKonfirmasi = [
                                                \App\Support\OnboardingFlow::SISI_SDM => ['label' => 'Kepala SDM', 'hasil' => $screening->hasil_sdm, 'oleh' => $screening->konfirmasiSdmOleh, 'pada' => $screening->konfirmasi_sdm_pada, 'catatan' => $screening->catatan_sdm],
                                            ];
                                            if ($screening->kepala_id) {
                                                $sisiKonfirmasi[\App\Support\OnboardingFlow::SISI_KEPALA] = ['label' => 'Kepala Bagian (' . ($screening->kepala?->name ?? '-') . ')', 'hasil' => $screening->hasil_kepala, 'oleh' => $screening->konfirmasiKepalaOleh, 'pada' => $screening->konfirmasi_kepala_pada, 'catatan' => $screening->catatan_kepala];
                                            }
                                            $rentangOnboarding = $screening->tanggal_mulai->translatedFormat('d M Y') . ' – ' . $screening->tanggal_selesai->translatedFormat('d M Y');
                                        @endphp
                                        <article class="lm-item hr-bagian {{ $screening->status === \App\Models\ScreeningOnboarding::STATUS_BERJALAN ? 'is-aktif' : '' }}" id="onboarding-{{ $screening->id }}"
                                            aria-labelledby="judul-onboarding-{{ $screening->id }}">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                                <div class="min-w-0">
                                                    <h3 class="hr-subbagian-judul" id="judul-onboarding-{{ $screening->id }}">{{ $rentangOnboarding }}</h3>
                                                    <p class="hr-subbagian-sub mb-0">{{ $screening->durasi_hari }} hari kerja</p>
                                                </div>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <x-badge :tone="$toneJadwal($screening->status)">{{ $screening->statusLabel() }}</x-badge>
                                                    <x-status-badge :badge="$screening->hasilBadgeClass()">{{ $screening->hasilLabel() }}</x-status-badge>
                                                </div>
                                            </div>
                                            <dl class="hr-konteks hr-konteks-ringkas mt-3">
                                                <dt>Pengawas</dt>
                                                <dd>{{ $screening->pengawas?->name ?? '-' }}</dd>
                                                @if ($screening->lokasi)
                                                    <dt>Lokasi</dt>
                                                    <dd class="text-wrap">@include('partials.lokasi', ['lokasi' => $screening->lokasi, 'tanpaIkon' => true])</dd>
                                                @endif
                                                @if ($screening->catatan)
                                                    <dt>Arahan</dt>
                                                    <dd>{{ $screening->catatan }}</dd>
                                                @endif
                                            </dl>
                                            @if ($admin || ($canReview && $screening->status === \App\Models\ScreeningOnboarding::STATUS_BERJALAN))
                                                @include('admin.lamaran._aksi-onboarding')
                                            @endif

                                            <div class="row g-2 mt-2">
                                                @foreach ($sisiKonfirmasi as $sisi => $k)
                                                    @php $bisaKonfirmasiSisi = \App\Support\OnboardingFlow::bisaKonfirmasi(auth()->user(), $screening, $sisi); @endphp
                                                    <div class="col-md-6">
                                                        <div class="hr-subbagian h-100">
                                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                                <h4 class="hr-subbagian-judul">{{ $k['label'] }}</h4>
                                                                <x-badge :tone="$k['hasil'] === 'lulus' ? 'success' : ($k['hasil'] === 'tidak_lulus' ? 'danger' : 'warning')">
                                                                    {{ \App\Models\ScreeningOnboarding::labelHasil($k['hasil']) }}
                                                                </x-badge>
                                                            </div>
                                                            @if ($k['oleh'])
                                                                <span class="small text-muted d-block mt-1">{{ $k['oleh']->name }}, {{ $k['pada']?->translatedFormat('d M Y H:i') }}</span>
                                                            @endif
                                                            @if ($k['catatan'])
                                                                <span class="small d-block mt-1">{{ $k['catatan'] }}</span>
                                                            @endif
                                                            @if ($bisaKonfirmasiSisi)
                                                                <x-button size="sm" :variant="$k['hasil'] ? 'secondary' : 'success'" icon="bi-clipboard-check" class="mt-2"
                                                                    data-bs-toggle="modal" :data-bs-target="'#modalKonfirmasi-' . $screening->id . '-' . $sisi">{{ $k['hasil'] ? 'Ubah Konfirmasi' : 'Proses Konfirmasi' }}</x-button>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    @if ($bisaKonfirmasiSisi)
                                                        @push('modal-lamaran')
                                                            @php $lulusTerkunci = ! \App\Support\OnboardingFlow::bolehKonfirmasiLulus($screening) && ! auth()->user()->can('sistem.override'); @endphp
                                                            <x-modal :id="'modalKonfirmasi-' . $screening->id . '-' . $sisi" :title="'Konfirmasi Onboarding — ' . $k['label']" icon="bi-clipboard-check" tone="success"
                                                                :subtitle="$nama . ' · ' . $screening->tanggal_mulai->translatedFormat('d M') . ' – ' . $screening->tanggal_selesai->translatedFormat('d M Y')">
                                                                <form action="{{ route('admin.lamaran.screening.hasil', [$lamaran, $screening]) }}" method="POST" id="form-konfirmasi-{{ $screening->id }}-{{ $sisi }}"
                                                                    data-confirm="{{ $admin ? 'Simpan konfirmasi onboarding ' . $nama . '? (Mode Admin: bisa diubah lagi kapan saja.)' : 'Simpan konfirmasi onboarding ' . $nama . '? Hasil tidak bisa diubah.' }}">
                                                                    @csrf
                                                                    <input type="hidden" name="sisi" value="{{ $sisi }}">
                                                                    @if ($lulusTerkunci)
                                                                        <x-alert tone="warning" icon="bi-hourglass-split" class="mb-3">
                                                                            Onboarding masih berjalan. <strong>Lulus</strong> bisa dikonfirmasi mulai
                                                                            {{ $screening->tanggal_selesai->translatedFormat('l, d M Y') }}. <strong>Tidak Lulus</strong> bisa kapan saja.
                                                                        </x-alert>
                                                                    @endif
                                                                    <fieldset class="mb-3">
                                                                        <legend class="form-label">Hasil onboarding</legend>
                                                                        <div class="d-flex flex-wrap gap-4">
                                                                            <div class="form-check">
                                                                                <input class="form-check-input" type="radio" name="hasil_status" value="lulus" id="hasil-{{ $screening->id }}-{{ $sisi }}-l" required @disabled($lulusTerkunci) @checked($k['hasil'] === 'lulus')>
                                                                                <label class="form-check-label" for="hasil-{{ $screening->id }}-{{ $sisi }}-l">Lulus</label>
                                                                            </div>
                                                                            <div class="form-check">
                                                                                <input class="form-check-input" type="radio" name="hasil_status" value="tidak_lulus" id="hasil-{{ $screening->id }}-{{ $sisi }}-t" @checked($k['hasil'] === 'tidak_lulus')>
                                                                                <label class="form-check-label" for="hasil-{{ $screening->id }}-{{ $sisi }}-t">Tidak Lulus</label>
                                                                            </div>
                                                                        </div>
                                                                    </fieldset>
                                                                    <label class="form-label" for="catatan-konfirmasi-{{ $screening->id }}-{{ $sisi }}">Catatan penilaian <span class="hr-field-opsional">(opsional)</span></label>
                                                                    <textarea name="catatan_hasil" id="catatan-konfirmasi-{{ $screening->id }}-{{ $sisi }}" rows="3" class="form-control" maxlength="1000"
                                                                        placeholder="Penilaian singkat, contoh: disiplin dan cepat beradaptasi dengan tim">{{ $k['catatan'] }}</textarea>
                                                                </form>
                                                                <x-slot:footer>
                                                                    <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
                                                                    <x-button type="submit" variant="success" :form="'form-konfirmasi-' . $screening->id . '-' . $sisi" icon="bi-check-lg">Simpan Konfirmasi</x-button>
                                                                </x-slot:footer>
                                                            </x-modal>
                                                        @endpush
                                                    @endif
                                                @endforeach
                                            </div>
                                        </article>
                                    @endforeach

                                    @if ($bisaMulaiOnboarding)
                                        @push('modal-lamaran')
                                            <x-modal id="modalMulaiOnboarding" title="Mulai Screening / Onboarding" icon="bi-play-fill" :subtitle="$nama . ' · ' . $posisi">
                                                <form action="{{ route('admin.lamaran.screening.store', $lamaran) }}" method="POST" id="form-mulai-onboarding"
                                                    data-confirm="Mulai masa onboarding untuk {{ $nama }}?">
                                                    @csrf
                                                    <div class="row g-3">
                                                        <div class="col-12">
                                                            <label for="f-pengawas-id" class="form-label">Pengawas Harian</label>
                                                            <select id="f-pengawas-id" name="pengawas_id" class="form-select" required>
                                                                <option value="">Pilih pengawas harian</option>
                                                                @foreach ($pengawasOptions as $opt)
                                                                    <option value="{{ $opt->id }}">{{ $opt->name }} ({{ $opt->roleLabel() }})</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-7">
                                                            <label for="onboarding-mulai" class="form-label">Hari Pertama</label>
                                                            <input type="date" name="tanggal_mulai" id="onboarding-mulai" class="form-control"
                                                                value="{{ \App\Support\Kalender::hariKerjaTerdekat(now())->format('Y-m-d') }}" @unless (auth()->user()->can('sistem.override')) min="{{ now()->format('Y-m-d') }}" @endunless required>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <label for="onboarding-durasi" class="form-label">Durasi (hari kerja)</label>
                                                            <input type="number" name="durasi_hari" id="onboarding-durasi" class="form-control"
                                                                value="{{ \App\Models\ScreeningOnboarding::DURASI_HARI }}" min="{{ \App\Support\OnboardingFlow::DURASI_MIN }}" max="{{ $admin ? 365 : \App\Support\OnboardingFlow::DURASI_MAX }}" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <x-alert tone="info" icon="bi-calendar-week" class="mb-0"><div id="onboarding-ringkasan"></div></x-alert>
                                                        </div>
                                                        <div class="col-12">
                                                            <label for="onboarding-lokasi" class="form-label">Lokasi <span class="hr-field-opsional">(opsional)</span></label>
                                                            <input type="text" name="lokasi" id="onboarding-lokasi" class="form-control" placeholder="Tempat onboarding, contoh: Kantor SDM Lt. 1" maxlength="255">
                                                        </div>
                                                        <div class="col-12">
                                                            <label for="onboarding-arahan" class="form-label">Arahan <span class="hr-field-opsional">(opsional)</span></label>
                                                            <textarea name="catatan" id="onboarding-arahan" rows="2" class="form-control" placeholder="Arahan untuk kandidat, contoh: hadir 07.30 berseragam putih" maxlength="1000"></textarea>
                                                        </div>
                                                        <div class="col-12 form-text mt-2">
                                                            Konfirmasi hasil: Kepala SDM{{ $kepalaTerkait ? ' & ' . $kepalaTerkait->name : '' }}.
                                                        </div>
                                                    </div>
                                                </form>
                                                <x-slot:footer>
                                                    <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
                                                    <x-button type="submit" form="form-mulai-onboarding" icon="bi-play-fill">Mulai Onboarding</x-button>
                                                </x-slot:footer>
                                            </x-modal>
                                        @endpush
                                    @endif
                                </section>

                                <section class="hr-detail-bagian" id="bagian-kontrak" aria-labelledby="judul-kontrak">
                                    <h2 class="hr-detail-judul" id="judul-kontrak"><i class="bi bi-pen" aria-hidden="true"></i> Jadwal Tanda Tangan Kontrak</h2>
                                    @if ($tanggalKontrakMinimal)
                                        <p class="small text-muted mb-3">Paling cepat {{ $tanggalKontrakMinimal->translatedFormat('d M Y') }} (boleh di hari terakhir onboarding).</p>
                                    @endif

                                    @forelse ($lamaran->kontrakJadwals as $jadwal)
                                        @php $bisaUbahKontrak = $admin || ($canReview && $jadwal->status === \App\Models\KontrakJadwal::STATUS_TERJADWAL && $tanggalKontrakMinimal); @endphp
                                        <article class="lm-item {{ $jadwal->status === \App\Models\KontrakJadwal::STATUS_TERJADWAL ? 'is-aktif' : '' }}" aria-labelledby="judul-kontrak-{{ $jadwal->id }}">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                                <div class="min-w-0">
                                                    <h3 class="hr-subbagian-judul" id="judul-kontrak-{{ $jadwal->id }}">
                                                        {{ $jadwal->tanggal->translatedFormat('l, d M Y') }}@if ($jadwal->waktu), {{ substr($jadwal->waktu, 0, 5) }}@endif
                                                    </h3>
                                                    @if ($jadwal->lokasi)
                                                        <p class="hr-subbagian-sub mb-0">@include('partials.lokasi', ['lokasi' => $jadwal->lokasi, 'tanpaIkon' => true])</p>
                                                    @endif
                                                    @if ($jadwal->catatan)
                                                        <p class="hr-subbagian-sub mb-0">{{ $jadwal->catatan }}</p>
                                                    @endif
                                                </div>
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <x-badge :tone="$toneJadwal($jadwal->status, true)">{{ $jadwal->statusLabel() }}</x-badge>
                                                    @if ($bisaUbahKontrak)
                                                        <x-button size="sm" variant="secondary" icon="bi-pencil" data-bs-toggle="collapse" :data-bs-target="'#ubah-kontrak-' . $jadwal->id"
                                                            aria-expanded="false" :aria-controls="'ubah-kontrak-' . $jadwal->id">Ubah</x-button>
                                                    @endif
                                                    @if ($admin)
                                                        <form action="{{ route('admin.lamaran.kontrak.destroy', [$lamaran, $jadwal]) }}" method="POST"
                                                            data-confirm="Hapus jadwal kontrak {{ $nama }} ({{ $jadwal->tanggal->translatedFormat('d M Y') }})?">
                                                            @csrf @method('DELETE')
                                                            <x-icon-button type="submit" icon="bi-trash" label="Hapus jadwal kontrak (Admin)" tone="danger" />
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($bisaUbahKontrak)
                                                <div class="collapse" id="ubah-kontrak-{{ $jadwal->id }}">
                                                    <form action="{{ route('admin.lamaran.kontrak.update', [$lamaran, $jadwal]) }}" method="POST" class="row g-3 mt-3 pt-3 border-top"
                                                        data-confirm="Simpan perubahan jadwal kontrak {{ $nama }}?">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="col-md-5">
                                                            <label class="form-label" for="uk-tanggal-{{ $jadwal->id }}">Tanggal</label>
                                                            <input type="date" name="tanggal" id="uk-tanggal-{{ $jadwal->id }}" class="form-control form-control-sm" value="{{ $jadwal->tanggal->format('Y-m-d') }}"
                                                                @unless (auth()->user()->can('sistem.override')) min="{{ $tanggalKontrakMinimal->format('Y-m-d') }}" @endunless required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label" for="uk-waktu-{{ $jadwal->id }}">Jam</label>
                                                            <input type="time" name="waktu" id="uk-waktu-{{ $jadwal->id }}" class="form-control form-control-sm" value="{{ $jadwal->waktu ? substr($jadwal->waktu, 0, 5) : '' }}">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="uk-lokasi-{{ $jadwal->id }}">Lokasi</label>
                                                            <input type="text" name="lokasi" id="uk-lokasi-{{ $jadwal->id }}" class="form-control form-control-sm" placeholder="Tempat, contoh: Ruang Rapat Yayasan" value="{{ $jadwal->lokasi }}">
                                                        </div>
                                                        @if ($admin)
                                                            <div class="col-md-5">
                                                                <label class="form-label" for="uk-status-{{ $jadwal->id }}">Status</label>
                                                                <select name="status" id="uk-status-{{ $jadwal->id }}" class="form-select form-select-sm">
                                                                    @foreach (['terjadwal' => 'Terjadwal', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $sv => $sl)
                                                                        <option value="{{ $sv }}" @selected($jadwal->status === $sv)>{{ $sl }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        @endif
                                                        <div class="col-12 d-flex justify-content-end">
                                                            <x-button type="submit" size="sm" icon="bi-check-lg">Simpan Jadwal</x-button>
                                                        </div>
                                                    </form>
                                                </div>
                                            @endif
                                        </article>
                                    @empty
                                        <x-empty-state icon="bi-pen" compact>
                                            @if ($onboardingLulus)
                                                Belum ada jadwal kontrak.
                                            @else
                                                Jadwal kontrak dibuat otomatis setelah onboarding dinyatakan Lulus.
                                            @endif
                                        </x-empty-state>
                                    @endforelse

                                    @if ($bisaJadwalKontrak)
                                        <div class="hr-subbagian mt-4">
                                            <h3 class="hr-subbagian-judul"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Jadwalkan Tanda Tangan Kontrak</h3>
                                            <p class="hr-subbagian-sub">Kandidat mendapat jadwal ini di halaman jadwalnya.</p>
                                            <form action="{{ route('admin.lamaran.kontrak.store', $lamaran) }}" method="POST" class="row g-3 hr-bagian" id="form-jadwal-kontrak"
                                                data-confirm="Jadwalkan tanda tangan kontrak {{ $nama }}?">
                                                @csrf
                                                <div class="col-md-4">
                                                    <label class="form-label" for="jk-tanggal">Tanggal</label>
                                                    <input type="date" name="tanggal" id="jk-tanggal" class="form-control form-control-sm" value="{{ ($tanggalKontrakMinimal ?? \App\Support\Kalender::hariKerjaTerdekat(now()))->format('Y-m-d') }}"
                                                        @unless (auth()->user()->can('sistem.override')) min="{{ $tanggalKontrakMinimal->format('Y-m-d') }}" @endunless required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label" for="jk-waktu">Jam <span class="hr-field-opsional">(opsional)</span></label>
                                                    <input type="time" name="waktu" id="jk-waktu" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label" for="jk-lokasi">Tempat <span class="hr-field-opsional">(opsional)</span></label>
                                                    <input type="text" name="lokasi" id="jk-lokasi" class="form-control form-control-sm" placeholder="Tempat, contoh: Ruang Rapat Yayasan">
                                                </div>
                                                <div class="col-12 d-flex justify-content-end">
                                                    <x-button type="submit" size="sm" icon="bi-calendar-check">Jadwalkan Kontrak</x-button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif
                                </section>
                            </x-tabs.pane>
                        @endif

                        {{-- ---------- Keputusan akhir ---------- --}}
                        @if ($adaKeputusan)
                            <x-tabs.pane tabs="lamaran" key="keputusan" :active="$tabAwal === 'keputusan'">
                                <section class="hr-detail-bagian" id="bagian-keputusan" aria-labelledby="judul-keputusan">
                                    <h2 class="hr-detail-judul" id="judul-keputusan"><i class="bi bi-flag" aria-hidden="true"></i> {{ $lamaran->isSelesai() ? 'Ubah Keputusan (Admin)' : 'Keputusan Akhir' }}</h2>

                                    <form action="{{ route('admin.lamaran.rekrut', $lamaran) }}" method="POST" class="row g-3" id="form-rekrut"
                                        data-confirm="{{ $lamaran->isKandidatPegawai() ? 'Tetapkan ' . $nama . ' ke posisi ' . $lamaran->jabatanTujuan() . ' (' . $lamaran->unitKerjaTujuan() . ')? Status lamaran berubah menjadi Diterima.' : 'Rekrut ' . $nama . ' menjadi pegawai (' . $posisiLengkap . ')? Status lamaran berubah menjadi Diterima.' }}">
                                        @csrf
                                        @php
                                            $adminBebas = auth()->user()->can('sistem.override') && ! $onboardingLulus;
                                            // Sama dengan aturan server: rekrut setelah tanda tangan kontrak & kuota lowongan belum penuh (Admin bebas).
                                            $kontrakBerikut = $lamaran->kontrakJadwals->where('status', \App\Models\KontrakJadwal::STATUS_TERJADWAL)->sortBy('tanggal')->first();
                                            $tungguKontrak = ! auth()->user()->can('sistem.override') && $kontrakBerikut && $kontrakBerikut->tanggal->gt(now()->startOfDay());
                                            $kuotaPenuh = ! auth()->user()->can('sistem.override') && $lamaran->lowongan?->isKuotaPenuh();
                                        @endphp
                                        @if ($adminBebas)
                                            <div class="col-12">
                                                <x-alert tone="warning" icon="bi-shield-lock" class="mb-0"><strong>Mode Admin:</strong> rekrut langsung tanpa menunggu interview/onboarding. Isi tanggal masuk manual.</x-alert>
                                            </div>
                                        @elseif (! $onboardingLulus)
                                            <div class="col-12">
                                                <x-alert tone="warning" icon="bi-lock" class="mb-0">Rekrut tersedia setelah onboarding dinyatakan <strong>Lulus</strong> (tanggal masuk diambil dari hari pertama onboarding).</x-alert>
                                            </div>
                                        @endif
                                        @if ($onboardingLulus && $tungguKontrak)
                                            <div class="col-12">
                                                <x-alert tone="info" icon="bi-calendar-check" class="mb-0">Rekrut bisa dilakukan mulai hari tanda tangan kontrak: <strong>{{ $kontrakBerikut->tanggal->translatedFormat('l, d F Y') }}</strong>.</x-alert>
                                            </div>
                                        @endif
                                        @if ($kuotaPenuh)
                                            <div class="col-12">
                                                <x-alert tone="warning" icon="bi-people-fill" class="mb-0">Kuota lowongan sudah terpenuhi ({{ $lamaran->lowongan->jumlah_terisi }}/{{ $lamaran->lowongan->jumlah_dibutuhkan }}). Tambah jumlah kebutuhan di lowongan, atau tolak kandidat ini.</x-alert>
                                            </div>
                                        @endif
                                        @if ($lamaran->isKandidatPegawai())
                                            <div class="col-12">
                                                <x-alert tone="info" icon="bi-arrow-left-right" class="mb-0">
                                                    Posisi akan diubah dari <strong>{{ $lamaran->dataPegawai?->kepegawaian?->jabatan ?? '-' }} ({{ $lamaran->dataPegawai?->kepegawaian?->unit_kerja ?? '-' }})</strong>
                                                    menjadi <strong>{{ $lamaran->jabatanTujuan() }} ({{ $lamaran->unitKerjaTujuan() }})</strong>.
                                                </x-alert>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="rekrut-tanggal-masuk" class="form-label">Tanggal Efektif</label>
                                                @if ($adminBebas)<input type="date" name="tanggal_masuk" id="rekrut-tanggal-masuk" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>@else<input type="date" id="rekrut-tanggal-masuk" class="form-control form-control-sm" value="{{ $onboardingLulus?->tanggal_mulai?->format('Y-m-d') }}" readonly>@endif
                                                <div class="form-text">{{ $adminBebas ? 'Diisi manual (mode Admin)' : 'Otomatis: hari pertama onboarding' }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="rekrut-status-kerja">Status Kerja</label>
                                                <select name="status_kerja" id="rekrut-status-kerja" class="form-select form-select-sm" required>
                                                    @foreach (['Tetap', 'Kontrak', 'Probation', 'Magang', 'Harian Lepas'] as $sk)
                                                        <option value="{{ $sk }}" {{ $lamaran->dataPegawai?->kepegawaian?->status_kerja === $sk ? 'selected' : '' }}>{{ $sk }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <div class="col-md-4">
                                                <label for="f-nip" class="form-label">NIP Baru</label>
                                                <input id="f-nip" type="text" name="nip" class="form-control form-control-sm" placeholder="Nomor induk pegawai baru, contoh: 2026110001" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="rekrut-tanggal-masuk" class="form-label">Tanggal Masuk</label>
                                                @if ($adminBebas)<input type="date" name="tanggal_masuk" id="rekrut-tanggal-masuk" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>@else<input type="date" id="rekrut-tanggal-masuk" class="form-control form-control-sm" value="{{ $onboardingLulus?->tanggal_mulai?->format('Y-m-d') }}" readonly>@endif
                                                <div class="form-text">{{ $adminBebas ? 'Diisi manual (mode Admin)' : 'Otomatis: hari pertama onboarding' }}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="rekrut-status-kerja">Status Kerja</label>
                                                <select name="status_kerja" id="rekrut-status-kerja" class="form-select form-select-sm" required>
                                                    <option value="Probation">Probation</option>
                                                    <option value="Kontrak">Kontrak</option>
                                                    <option value="Tetap">Tetap</option>
                                                    <option value="Magang">Magang</option>
                                                    <option value="Harian Lepas">Harian Lepas</option>
                                                </select>
                                            </div>
                                        @endif
                                        <div class="col-md-6">
                                            <label for="rekrut-masa-kontrak" class="form-label">Masa Kontrak <span class="hr-field-opsional">(opsional)</span></label>
                                            <select name="masa_kontrak_bulan" id="rekrut-masa-kontrak" class="form-select form-select-sm">
                                                <option value="">Tidak ditentukan</option>
                                                @foreach (range(3, 36, 3) as $bulan)
                                                    <option value="{{ $bulan }}">{{ $bulan }} Bulan</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="rekrut-tanggal-habis" class="form-label">Tanggal Habis Kontrak</label>
                                            <input type="text" id="rekrut-tanggal-habis" class="form-control form-control-sm" disabled placeholder="Terisi otomatis dari masa kontrak">
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                                                <x-button type="submit" variant="success" icon="bi-check-circle" :disabled="(! $onboardingLulus && ! $adminBebas) || $tungguKontrak || $kuotaPenuh">
                                                    {{ $lamaran->isKandidatPegawai() ? 'Tetapkan ke Posisi Baru' : 'Rekrut Jadi Pegawai' }}
                                                </x-button>
                                                <x-button variant="danger-outline" icon="bi-x-lg" form="form-tolak-lamaran" class="btn-reject-lamaran ms-auto" data-alasan="tolak"
                                                    :data-konteks="json_encode($konteksTolak, JSON_UNESCAPED_UNICODE)">Tolak Pelamar</x-button>
                                            </div>
                                        </div>
                                    </form>

                                    <form action="{{ route('admin.lamaran.tolak', $lamaran) }}" method="POST" id="form-tolak-lamaran" class="form-reject-lamaran d-none">
                                        @csrf
                                        <input type="hidden" name="catatan_admin" value="">
                                    </form>
                                </section>
                            </x-tabs.pane>
                        @endif
                    </div>
                </x-card>
            </div>

            {{-- ============ Kanan: Tindakan · Timeline · Info Lowongan (lengket di desktop) ============ --}}
            <div class="col-xl-4">
                <div class="hr-detail-samping lm-samping d-grid gap-4">
                    <x-card :title="$lamaran->isSelesai() ? 'Keputusan' : 'Tindakan'" :icon="$lamaran->isSelesai() ? 'bi-flag' : 'bi-lightning-charge'" class="hr-kartu-aksi" id="kartu-tindakan">
                        <dl class="hr-konteks hr-konteks-ringkas">
                            @if ($lamaran->isSelesai())
                                <dt>Keputusan</dt>
                                <dd class="text-wrap"><x-badge :tone="$toneStatus" dot>{{ $lamaran->statusLabel() }}</x-badge></dd>
                                @if ($lamaran->catatan_admin)
                                    <dt>{{ $lamaran->status === $L::STATUS_DITOLAK ? 'Alasan' : 'Catatan' }}</dt>
                                    <dd>{{ $lamaran->catatan_admin }}</dd>
                                @endif
                                <dt>Diputuskan</dt>
                                <dd>{{ optional($lamaran->diputuskan_pada)->translatedFormat('d M Y H:i') ?? '-' }}{{ $lamaran->diputuskanOleh ? ' · ' . $lamaran->diputuskanOleh->name : '' }}</dd>
                            @else
                                <dt>Tahap</dt>
                                <dd>{{ $lamaran->tahapLabel() }}</dd>
                                <dt>Langkah</dt>
                                <dd>{{ $langkah }}</dd>
                            @endif
                        </dl>

                        @if (! $canReview)
                            <x-alert tone="info" class="mt-3 mb-0">
                                Anda hanya bisa melihat data lamaran ini, kecuali mengisi hasil interview yang ditugaskan kepada Anda.
                                Aksi verifikasi, jadwal, kontrak, dan keputusan akhir hanya bisa dilakukan oleh Kepala SDM/GM.
                            </x-alert>
                        @endif

                        @if ($lamaran->isMenungguVerifikasi() && $canReview)
                            {{-- Form verifikasi (tanpa tombol sendiri): dikirim dari tombol Terima/Tolak di bawah & bilah cepat. --}}
                            <div id="bagian-verifikasi" class="hr-bagian">
                                <form action="{{ route('admin.lamaran.verifikasi.terima', $lamaran) }}" method="POST" id="form-verifikasi-terima"
                                    data-confirm="Terima verifikasi {{ $nama }} ({{ $posisiLengkap }}) dan lanjutkan ke tahap Interview?">
                                    @csrf
                                </form>
                                <form action="{{ route('admin.lamaran.verifikasi.tolak', $lamaran) }}" method="POST" id="form-verifikasi-tolak" class="form-reject-lamaran">
                                    @csrf
                                    <input type="hidden" name="catatan_admin" value="">
                                </form>
                            </div>
                        @endif

                        @if ($aksiTahap)
                            <div class="hr-kartu-aksi-tombol">
                                @include('admin.lamaran._aksi-tahap', ['ukuran' => 'md'])
                            </div>
                        @elseif ($tabLangkah && isset($labelTab[$tabLangkah]))
                            <div class="hr-kartu-aksi-tombol">
                                <x-button variant="secondary" icon="bi-arrow-right" :href="'#' . $keLangkah" :data-buka-tab="$tabLangkah">Lihat {{ $labelTab[$tabLangkah] }}</x-button>
                            </div>
                        @endif

                        @if ($canReview)
                            <section class="mt-4 pt-3 border-top" aria-labelledby="judul-link-jadwal">
                                <h3 class="hr-subbagian-judul" id="judul-link-jadwal"><i class="bi bi-link-45deg" aria-hidden="true"></i> Link Jadwal Kandidat</h3>
                                <p class="small text-muted mt-1 mb-2">Bagikan ke kandidat lewat WhatsApp/email. Bisa dibuka tanpa login &amp; selalu menampilkan jadwal terbaru.</p>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="link-jadwal" value="{{ $lamaran->linkJadwal() }}" readonly aria-label="Link jadwal kandidat">
                                    <button type="button" class="btn btn-outline-primary" id="btn-salin-link">
                                        <i class="bi bi-clipboard" aria-hidden="true"></i> Salin
                                    </button>
                                    <a href="{{ $lamaran->linkJadwal() }}" target="_blank" rel="noopener" class="btn btn-outline-secondary" title="Buka jadwal kandidat" aria-label="Buka jadwal kandidat di tab baru">
                                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </section>
                        @endif
                    </x-card>

                    @include('admin.lamaran._tahapan')

                    <x-card title="Info Lowongan" icon="bi-briefcase">
                        @if ($lamaran->lowongan)
                            <x-slot:actions>
                                <a href="{{ route('admin.lamaran.index', ['lowongan_id' => $lamaran->lowongan_id]) }}">Pelamar lain</a>
                            </x-slot:actions>
                        @endif
                        <dl class="dash-dl">
                            <div><dt>Lowongan</dt><dd>{{ $lamaran->lowongan?->judul ?? 'Tanpa lowongan' }}</dd></div>
                            <div><dt>Jabatan</dt><dd>{{ $lamaran->jabatanTujuan() ?? '-' }}</dd></div>
                            <div><dt>Bagian</dt><dd>{{ $unit ?? '-' }}</dd></div>
                            <div><dt>Tipe kerja</dt><dd>{{ $lamaran->lowongan?->tipe_kerja ?? '-' }}</dd></div>
                            <div><dt>Lokasi kerja</dt><dd>{{ $lamaran->lokasiKerjaTujuan() ?? '-' }}</dd></div>
                            @if ($lamaran->lowongan)
                                <div><dt>Kuota terisi</dt><dd>{{ $lamaran->lowongan->jumlah_terisi }} / {{ $lamaran->lowongan->jumlah_dibutuhkan }}</dd></div>
                                @if ($lamaran->lowongan->tanggal_tutup)
                                    <div><dt>Batas lamaran</dt><dd>{{ $lamaran->lowongan->tanggal_tutup->translatedFormat('d M Y') }}</dd></div>
                                @endif
                                <div><dt>Status lowongan</dt><dd><x-badge :tone="$lamaran->lowongan->isDipublikasikan() ? 'success' : 'neutral'">{{ $lamaran->lowongan->statusLabel() }}</x-badge></dd></div>
                            @endif
                        </dl>
                    </x-card>
                </div>
            </div>
        </div>
    </div>
</main>

{{-- ============ Dialog & modal (di luar tab agar bisa dibuka dari tab mana pun) ============ --}}
@if ($p)
    @include('partials.preview-file-modal')
@endif
@stack('modal-lamaran')

{{-- Dialog alasan: tolak pelamar / batalkan interview / batalkan onboarding. Mengisi input catatan tersembunyi form tujuan lalu mengirimnya. --}}
<x-modal id="modalAlasan" title="Tolak pelamar?" icon="bi-x-octagon" tone="danger">
    <p class="mb-3" data-alasan-teks></p>
    <dl class="hr-konteks" data-konteks></dl>
    <div class="mt-3">
        <label class="form-label required" for="alasan-isi" data-alasan-label>Alasan</label>
        <textarea id="alasan-isi" class="form-control" rows="3" maxlength="1000" required aria-describedby="alasan-galat"></textarea>
        <div class="invalid-feedback" id="alasan-galat" data-alasan-galat>Alasan wajib diisi.</div>
    </div>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Kembali</x-button>
        <x-button variant="danger" icon="bi-x-lg" data-alasan-kirim><span data-alasan-tombol>Ya, Tolak</span></x-button>
    </x-slot:footer>
</x-modal>

@if (auth()->user()->can('sistem.override'))
    <x-modal id="modalUbahAdmin" title="Ubah Langsung (Admin)" icon="bi-shield-lock" tone="warning" size="lg"
        subtitle="Pindahkan tahap/status lamaran tanpa mengikuti alur (mis. lompat ke onboarding, buka kembali lamaran yang ditolak).">
        <form action="{{ route('admin.lamaran.ubah-admin', $lamaran) }}" method="POST" class="row g-3"
            data-confirm="Ubah tahap/status lamaran {{ $nama }} secara langsung?">
            @csrf
            @method('PUT')
            <div class="col-md-6">
                <label for="f-tahap" class="form-label">Tahap</label>
                <select id="f-tahap" name="tahap" class="form-select form-select-sm">
                    @foreach (['verifikasi' => 'Verifikasi', 'interview' => 'Interview', 'screening' => 'Screening / Onboarding', 'kontrak' => 'Kontrak', 'selesai' => 'Selesai'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($lamaran->tahap === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="f-status-admin" class="form-label">Status</label>
                <select id="f-status-admin" name="status" class="form-select form-select-sm">
                    @foreach (['menunggu_verifikasi' => 'Menunggu Verifikasi', 'proses' => 'Proses', 'diterima' => 'Diterima', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($lamaran->status === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label for="f-catatan-admin" class="form-label">Catatan untuk pelamar <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" id="f-catatan-admin" name="catatan_admin" class="form-control form-control-sm" maxlength="1000" placeholder="Catatan yang dilihat pelamar, contoh: berkas lengkap" value="{{ $lamaran->catatan_admin }}">
            </div>
            <div class="col-12 d-flex justify-content-end">
                <x-button type="submit" size="sm" icon="bi-check-lg">Simpan Langsung</x-button>
            </div>
        </form>

        {{-- Semua data rekrutmen kandidat ini: bisa diubah/dihapus apa pun statusnya (selesai, dibatalkan, lewat tanggal). --}}
        <section class="mt-4 pt-3 border-top" aria-labelledby="judul-data-kandidat">
            <h3 class="hr-subbagian-judul mb-2" id="judul-data-kandidat"><i class="bi bi-database-gear" aria-hidden="true"></i> Semua Data Kandidat Ini</h3>
            <ul class="hr-list data-admin-lamaran">
                <li class="hr-list-item flex-wrap">
                    <div class="hr-list-utama"><span class="hr-list-judul">Lamaran #{{ $lamaran->id }}</span><span class="hr-list-sub">{{ $lamaran->statusLabel() }}</span></div>
                    @include('admin.editor-data._tombol', ['entitas' => 'lamaran', 'id' => $lamaran->id, 'teks' => 'Ubah', 'kembaliKe' => route('admin.lamaran.index')])
                </li>
                @foreach ($lamaran->interviewJadwals as $j)
                    <li class="hr-list-item flex-wrap">
                        <div class="hr-list-utama"><span class="hr-list-judul">{{ $j->tahapLabel() }}</span><span class="hr-list-sub">{{ $j->tanggal->translatedFormat('d M Y') }} · {{ $j->statusLabel() }}@if ($j->hasil_status) · {{ $j->hasilLabel() }}@endif</span></div>
                        @include('admin.editor-data._tombol', ['entitas' => 'interview', 'id' => $j->id, 'teks' => 'Ubah'])
                    </li>
                @endforeach
                @foreach ($lamaran->screeningOnboardings as $s)
                    <li class="hr-list-item flex-wrap">
                        <div class="hr-list-utama"><span class="hr-list-judul">Onboarding</span><span class="hr-list-sub">{{ $s->tanggal_mulai->translatedFormat('d M') }}–{{ $s->tanggal_selesai->translatedFormat('d M Y') }} · {{ $s->statusLabel() }}@if ($s->hasil_status) · {{ $s->hasilLabel() }}@endif</span></div>
                        @include('admin.editor-data._tombol', ['entitas' => 'onboarding', 'id' => $s->id, 'teks' => 'Ubah'])
                    </li>
                @endforeach
                @foreach ($lamaran->kontrakJadwals as $kj)
                    <li class="hr-list-item flex-wrap">
                        <div class="hr-list-utama"><span class="hr-list-judul">Kontrak</span><span class="hr-list-sub">{{ $kj->tanggal->translatedFormat('d M Y') }}{{ $kj->waktu ? ', ' . substr($kj->waktu, 0, 5) : '' }} · {{ $kj->statusLabel() }}</span></div>
                        @include('admin.editor-data._tombol', ['entitas' => 'kontrak', 'id' => $kj->id, 'teks' => 'Ubah'])
                    </li>
                @endforeach
                @if ($lamaran->dataPelamar)
                    <li class="hr-list-item flex-wrap">
                        <div class="hr-list-utama"><span class="hr-list-judul">Biodata pelamar</span><span class="hr-list-sub">{{ $lamaran->dataPelamar->nama_lengkap }}</span></div>
                        @include('admin.editor-data._tombol', ['entitas' => 'pelamar', 'id' => $lamaran->dataPelamar->id, 'teks' => 'Ubah', 'hapus' => false])
                    </li>
                @endif
            </ul>
        </section>
        <x-slot:footer>
            <x-button variant="secondary" data-bs-dismiss="modal">Tutup</x-button>
        </x-slot:footer>
    </x-modal>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ---------- Dialog alasan (pengganti dialog isian SweetAlert): konteks jelas + alasan wajib ----------
        const MODE_ALASAN = {
            tolak: {
                kolom: 'catatan_admin', judul: 'Tolak pelamar?', tombol: 'Ya, Tolak', label: 'Alasan penolakan', galat: 'Alasan penolakan wajib diisi.',
                teks: 'Lamaran berikut akan ditolak dan proses rekrutmennya berhenti. Alasan ditampilkan ke pelamar di halaman lamarannya.',
                placeholder: 'Tulis alasan yang jelas, contoh: kualifikasi pendidikan belum sesuai kebutuhan bagian.',
            },
            'batal-interview': {
                kolom: 'catatan', judul: 'Batalkan jadwal interview?', tombol: 'Ya, Batalkan', label: 'Alasan resmi pembatalan', galat: 'Alasan pembatalan wajib diisi.',
                teks: 'Jadwal interview berikut dibatalkan. Alasan dicatat pada jadwal.',
                placeholder: 'Tulis alasan pembatalan, contoh: interviewer berhalangan, jadwal akan diatur ulang.',
            },
            'batal-onboarding': {
                kolom: 'catatan', judul: 'Batalkan onboarding?', tombol: 'Ya, Batalkan', label: 'Alasan pembatalan', galat: 'Alasan pembatalan wajib diisi.',
                teks: 'Masa onboarding berikut dibatalkan. Alasan dicatat pada data onboarding.',
                placeholder: 'Tulis alasan pembatalan, contoh: kandidat sakit, onboarding dijadwalkan ulang.',
            },
        };
        const dialogAlasan = document.getElementById('modalAlasan');
        if (dialogAlasan) {
            const isian = document.getElementById('alasan-isi');
            const kirim = dialogAlasan.querySelector('[data-alasan-kirim]');
            let formTujuan = null;
            let kolomTujuan = null;

            document.addEventListener('click', function (e) {
                const pemicu = e.target.closest('[data-alasan]');
                if (!pemicu) return;
                const mode = MODE_ALASAN[pemicu.dataset.alasan] || MODE_ALASAN.tolak;
                const form = pemicu.form || pemicu.closest('form');
                if (!form || !form.elements[mode.kolom]) return;
                e.preventDefault();
                formTujuan = form;
                kolomTujuan = mode.kolom;

                dialogAlasan.querySelector('.modal-title').textContent = mode.judul;
                dialogAlasan.querySelector('[data-alasan-teks]').textContent = mode.teks;
                dialogAlasan.querySelector('[data-alasan-label]').textContent = mode.label;
                dialogAlasan.querySelector('[data-alasan-galat]').textContent = mode.galat;
                dialogAlasan.querySelector('[data-alasan-tombol]').textContent = mode.tombol;
                isian.placeholder = mode.placeholder;
                isian.value = '';
                isian.classList.remove('is-invalid');

                const dl = dialogAlasan.querySelector('[data-konteks]');
                dl.replaceChildren();
                let konteks = [];
                try { konteks = JSON.parse(pemicu.dataset.konteks || '[]'); } catch (err) { konteks = []; }
                konteks.forEach(function ([label, nilai]) {
                    if (nilai === null || nilai === undefined || String(nilai).trim() === '') return;
                    const dt = document.createElement('dt');
                    const dd = document.createElement('dd');
                    dt.textContent = label;
                    dd.textContent = nilai;
                    dl.append(dt, dd);
                });
                bootstrap.Modal.getOrCreateInstance(dialogAlasan).show();
            });

            dialogAlasan.addEventListener('shown.bs.modal', function () { isian.focus(); });
            isian.addEventListener('input', function () { isian.classList.remove('is-invalid'); });

            kirim.addEventListener('click', function () {
                const nilai = isian.value.trim();
                if (!nilai) {
                    isian.classList.add('is-invalid');
                    isian.focus();
                    return;
                }
                if (!formTujuan) return;
                formTujuan.elements[kolomTujuan].value = nilai;
                // Kunci tombol agar tidak terkirim dua kali.
                kirim.disabled = true;
                kirim.setAttribute('aria-busy', 'true');
                kirim.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>');
                if (typeof formTujuan.requestSubmit === 'function') {
                    formTujuan.requestSubmit();
                } else {
                    formTujuan.submit();
                }
            });

            // Kembali lewat tombol Back (halaman dari cache): lepas kunci tombol.
            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) return;
                kirim.disabled = false;
                kirim.removeAttribute('aria-busy');
                kirim.querySelector('.spinner-border')?.remove();
            });
        }

        // ---------- Tombol "buka tab" (kartu Tindakan / bilah cepat): buka tab lalu gulir ke bagian/form tujuan ----------
        function bukaTab(kunci, idTujuan) {
            const tombolTab = document.getElementById('lamaran-tab-' + kunci);
            const tujuan = idTujuan ? document.getElementById(idTujuan) : null;
            const gulir = function () {
                (tujuan || document.getElementById('detail-lamaran'))?.scrollIntoView({ block: 'start' });
                // Form & jadwal: fokus ke isian pertama; bagian lain: fokus ke tab.
                const isianPertama = tujuan && /^(form-|jadwal-)/.test(tujuan.id)
                    ? tujuan.querySelector('input:not([type="hidden"]):not([disabled]):not([readonly]), select:not([disabled]), textarea')
                    : null;
                (isianPertama || tombolTab)?.focus({ preventScroll: true });
            };
            if (tombolTab && window.bootstrap && !tombolTab.classList.contains('active')) {
                tombolTab.addEventListener('shown.bs.tab', gulir, { once: true });
                bootstrap.Tab.getOrCreateInstance(tombolTab).show();
            } else {
                gulir();
            }
        }
        document.addEventListener('click', function (e) {
            const tautan = e.target.closest('[data-buka-tab]');
            if (!tautan) return;
            e.preventDefault();
            bukaTab(tautan.dataset.bukaTab, (tautan.getAttribute('href') || '').replace(/^#/, ''));
        });

        // HP/tablet: bilah tab bergulir mendatar → geser agar tab yang terbuka terlihat.
        const navTab = document.querySelector('[data-hr-tabs="lamaran"]');
        function tampakkanTabAktif() {
            const aktif = navTab && navTab.querySelector('.hr-tab.active');
            if (aktif) navTab.scrollLeft += aktif.getBoundingClientRect().left - navTab.getBoundingClientRect().left - 8;
        }
        if (navTab) {
            tampakkanTabAktif();
            navTab.addEventListener('shown.bs.tab', tampakkanTabAktif);
        }

        // ---------- Salin link jadwal kandidat ----------
        const btnSalin = document.getElementById('btn-salin-link');
        if (btnSalin) {
            btnSalin.addEventListener('click', function () {
                const input = document.getElementById('link-jadwal');
                input.select();
                const selesai = function () {
                    btnSalin.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i> Tersalin';
                    setTimeout(function () { btnSalin.innerHTML = '<i class="bi bi-clipboard" aria-hidden="true"></i> Salin'; }, 2000);
                };
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(input.value).then(selesai, function () { document.execCommand('copy'); selesai(); });
                } else {
                    document.execCommand('copy');
                    selesai();
                }
            });
        }

        // Sama dengan App\Support\Kalender di server: hari kerja, libur, dan masa kontrak tanpa loncat bulan.
        const Kalender = {
            hariKerja: {{ \Illuminate\Support\Js::from(config('hrrab.hari_kerja')) }},
            libur: {{ \Illuminate\Support\Js::from(config('hrrab.hari_libur')) }},
            tanggal: function (s) { const p = s.split('-').map(Number); return new Date(p[0], p[1] - 1, p[2]); },
            iso: function (d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); },
            isHariKerja: function (d) { return this.hariKerja.includes(d.getDay() || 7) && ! this.libur.includes(this.iso(d)); },
            terdekat: function (d) { const h = new Date(d); while (! this.isHariKerja(h)) h.setDate(h.getDate() + 1); return h; },
            hariKerjaSetelah: function (d) { const h = new Date(d); h.setDate(h.getDate() + 1); return this.terdekat(h); },
            hariKerjaKe: function (mulai, n) { let h = this.terdekat(mulai); for (let i = 1; i < n; i++) h = this.hariKerjaSetelah(h); return h; },
            habisKontrak: function (masuk, bulan) {
                const t = new Date(masuk.getFullYear(), masuk.getMonth() + bulan, 1);
                t.setDate(Math.min(masuk.getDate(), new Date(t.getFullYear(), t.getMonth() + 1, 0).getDate()) - 1);
                return t;
            },
        };
        const namaHariKerja = {{ \Illuminate\Support\Js::from(\App\Support\Kalender::namaHariKerja()) }};

        const inputTanggalMasuk = document.getElementById('rekrut-tanggal-masuk');
        const selectMasaKontrak = document.getElementById('rekrut-masa-kontrak');
        const outputTanggalHabis = document.getElementById('rekrut-tanggal-habis');

        function hitungTanggalHabisKontrak() {
            if (!inputTanggalMasuk || !selectMasaKontrak || !outputTanggalHabis) return;

            const bulan = parseInt(selectMasaKontrak.value, 10);
            if (!inputTanggalMasuk.value || !bulan) {
                outputTanggalHabis.value = '';
                return;
            }

            outputTanggalHabis.value = Kalender.habisKontrak(Kalender.tanggal(inputTanggalMasuk.value), bulan)
                .toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        }

        if (inputTanggalMasuk && selectMasaKontrak) {
            inputTanggalMasuk.addEventListener('change', hitungTanggalHabisKontrak);
            selectMasaKontrak.addEventListener('change', hitungTanggalHabisKontrak);
        }

        const inputMulai = document.getElementById('onboarding-mulai');
        const inputDurasi = document.getElementById('onboarding-durasi');
        const ringkasan = document.getElementById('onboarding-ringkasan');

        function hitungOnboarding() {
            if (!inputMulai || !inputDurasi || !ringkasan || !inputMulai.value) return;

            const durasi = Math.max(1, parseInt(inputDurasi.value, 10) || 1);
            const format = { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' };
            const mulai = Kalender.tanggal(inputMulai.value);
            const selesai = Kalender.hariKerjaKe(mulai, durasi);
            const hariIni = new Date(); hariIni.setHours(0, 0, 0, 0);
            let kontrak = Kalender.hariKerjaSetelah(selesai);
            if (kontrak < hariIni) kontrak = Kalender.terdekat(hariIni);

            ringkasan.innerHTML = (Kalender.isHariKerja(mulai) ? '' : '<span class="text-danger">Hari pertama bukan hari kerja — pilih ' + namaHariKerja + '.</span><br>')
                + durasi + ' hari kerja (' + namaHariKerja + ', libur tidak dihitung)<br>'
                + 'Hari terakhir: <strong>' + selesai.toLocaleDateString('id-ID', format) + '</strong><br>'
                + 'Kontrak paling cepat: <strong>' + kontrak.toLocaleDateString('id-ID', format) + '</strong>';
        }

        if (inputMulai && inputDurasi) {
            inputMulai.addEventListener('change', hitungOnboarding);
            inputDurasi.addEventListener('input', hitungOnboarding);
            hitungOnboarding();
        }
    });
</script>
@endsection
