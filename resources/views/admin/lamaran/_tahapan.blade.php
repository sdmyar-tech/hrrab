{{--
    Penanda tahap lamaran + "langkah berikutnya" di atas kolom aksi, supaya penyetuju langsung tahu posisi & apa yang dikerjakan.
    Memakai variabel halaman detail lamaran ($lamaran, $canReview, $tahapBerikutnya, $bisaKonfirmasiInterview, $onboardingLulus, $jumlahInterviewLulus).
--}}
@php
    $L = \App\Models\Lamaran::class;
    $urutan = [$L::TAHAP_VERIFIKASI => 'Verifikasi', $L::TAHAP_INTERVIEW => 'Interview', $L::TAHAP_SCREENING => 'Onboarding', $L::TAHAP_KONTRAK => 'Kontrak', $L::TAHAP_SELESAI => 'Selesai'];
    $kunci = array_keys($urutan);
    $posisi = array_search($lamaran->tahap, $kunci, true);
    $posisi = $posisi === false ? 0 : $posisi;
    $berhenti = in_array($lamaran->status, [$L::STATUS_DITOLAK, $L::STATUS_DIBATALKAN], true);
    $terjadwal = $lamaran->interviewJadwals->first(fn ($j) => $j->isTerjadwal() && ! $j->hasil_status);
    $onboardingJalan = $lamaran->screeningOnboardings->firstWhere('status', \App\Models\ScreeningOnboarding::STATUS_BERJALAN);

    // Langkah berikutnya: [teks, anchor bagian]. Hanya untuk yang bisa memproses (Kepala SDM/GM/Admin).
    [$langkah, $ke] = match (true) {
        $berhenti => ['Lamaran ' . strtolower($lamaran->statusLabel()) . '. Tidak ada langkah lanjutan.', null],
        $lamaran->status === $L::STATUS_DITERIMA => ['Kandidat sudah direkrut. Proses selesai.', null],
        ! $canReview => ['Tahap ini diproses Kepala SDM/GM.' . ($terjadwal && $terjadwal->interviewer_id === auth()->id() ? ' Isi hasil interview yang ditugaskan kepada Anda.' : ''), $terjadwal ? 'bagian-interview' : null],
        $lamaran->tahap === $L::TAHAP_VERIFIKASI => ['Periksa data pelamar, lalu Terima atau Tolak verifikasi.', 'bagian-verifikasi'],
        $lamaran->tahap === $L::TAHAP_INTERVIEW && $terjadwal => ['Isi hasil ' . $terjadwal->tahapLabel() . ' (' . $terjadwal->tanggal->translatedFormat('d M') . ').', 'bagian-interview'],
        $lamaran->tahap === $L::TAHAP_INTERVIEW && $bisaKonfirmasiInterview => ["{$jumlahInterviewLulus} interview lulus — konfirmasi interview selesai untuk lanjut ke onboarding.", 'bagian-interview'],
        $lamaran->tahap === $L::TAHAP_INTERVIEW && $tahapBerikutnya => ['Jadwalkan interview berikutnya.', 'bagian-interview'],
        $lamaran->tahap === $L::TAHAP_INTERVIEW => ['Tunggu hasil interview.', 'bagian-interview'],
        $lamaran->tahap === $L::TAHAP_SCREENING && ! $onboardingJalan => ['Mulai masa onboarding.', 'bagian-onboarding'],
        $lamaran->tahap === $L::TAHAP_SCREENING => ['Konfirmasi hasil onboarding (Lulus bisa mulai ' . $onboardingJalan->tanggal_selesai->translatedFormat('d M') . ').', 'bagian-onboarding'],
        $lamaran->tahap === $L::TAHAP_KONTRAK => ['Tanda tangan kontrak, lalu Rekrut di Keputusan Akhir.', 'bagian-keputusan'],
        default => ['Periksa status lamaran.', null],
    };
@endphp
<div class="panel p-3 mb-3 tahapan-lamaran">
    <ol class="tahapan-deret" aria-label="Tahapan lamaran">
        @foreach ($urutan as $kode => $label)
            @php
                $i = $loop->index;
                $kelas = $berhenti && $i === $posisi ? 'berhenti' : ($i < $posisi || $lamaran->status === $L::STATUS_DITERIMA ? 'selesai' : ($i === $posisi ? 'kini' : ''));
            @endphp
            <li class="{{ $kelas }}" @if ($kelas === 'kini') aria-current="step" @endif>
                <span class="tahapan-titik">@if ($kelas === 'selesai')<i class="bi bi-check-lg"></i>@elseif ($kelas === 'berhenti')<i class="bi bi-x-lg"></i>@else{{ $i + 1 }}@endif</span>
                <span class="tahapan-label">{{ $label }}</span>
            </li>
        @endforeach
    </ol>
    <div class="tahapan-langkah {{ $berhenti ? 'berhenti' : '' }}">
        <i class="bi {{ $berhenti ? 'bi-x-octagon' : 'bi-signpost-2' }}"></i>
        <div><strong>Langkah berikutnya:</strong> {{ $langkah }}</div>
        @if ($ke)
            <a href="#{{ $ke }}" class="btn btn-sm btn-outline-secondary ms-auto">Ke bagian ini <i class="bi bi-arrow-down"></i></a>
        @endif
    </div>
</div>
@once
<style>
    .tahapan-deret { display: flex; list-style: none; margin: 0 0 .75rem; padding: 0; counter-reset: none; }
    .tahapan-deret li { flex: 1 1 0; position: relative; display: flex; flex-direction: column; align-items: center; gap: .3rem; min-width: 0; }
    /* Garis penghubung antar-titik. */
    .tahapan-deret li + li::before { content: ''; position: absolute; top: 13px; right: 50%; width: 100%; height: 2px; background: var(--admin-border); z-index: 0; }
    .tahapan-deret li.selesai + li::before, .tahapan-deret li.selesai + li.kini::before { background: #198754; }
    .tahapan-titik { position: relative; z-index: 1; width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; font-size: .78rem; font-weight: 700;
        background: var(--admin-surface); border: 2px solid var(--admin-border); color: var(--admin-muted); }
    .tahapan-label { font-size: .74rem; font-weight: 600; color: var(--admin-muted); text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }
    .tahapan-deret li.selesai .tahapan-titik { background: #198754; border-color: #198754; color: #fff; }
    .tahapan-deret li.kini .tahapan-titik { background: var(--hr-primary); border-color: var(--hr-primary); color: #fff; box-shadow: 0 0 0 4px var(--hr-primary-soft); }
    .tahapan-deret li.kini .tahapan-label { color: var(--hr-primary); }
    .tahapan-deret li.berhenti .tahapan-titik { background: #dc3545; border-color: #dc3545; color: #fff; }
    .tahapan-langkah { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .6rem; padding: .6rem .75rem; border-radius: .6rem;
        background: var(--hr-primary-soft); font-size: .86rem; }
    .tahapan-langkah > .bi { color: var(--hr-primary); font-size: 1.1rem; }
    .tahapan-langkah > div { flex: 1 1 14rem; }
    .tahapan-langkah.berhenti { background: #fdecec; }
    .tahapan-langkah.berhenti > .bi { color: #b42318; }
    [id^="bagian-"] { scroll-margin-top: 84px; }
</style>
@endonce
