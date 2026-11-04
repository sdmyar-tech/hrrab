{{--
    Kartu "Timeline Lamaran" (kolom kanan detail lamaran, Batch 6): lamar → verifikasi → interview → onboarding → kontrak → keputusan,
    lengkap dengan tanggal & pengguna. Hanya dipakai admin.lamaran.show; memakai $lamaran (relasi sudah dimuat controller).
    - Lamaran berjalan: tahap sebelum tahap sekarang = selesai, tahap sekarang = berjalan, sesudahnya = belum.
    - Lamaran selesai (diterima/ditolak/dibatalkan): hanya tahap yang benar-benar dilalui (punya data) + butir keputusan.
--}}
@php
    $L = \App\Models\Lamaran::class;
    $tgl = fn ($d, string $f = 'd M Y') => $d?->translatedFormat($f);
    $urutan = [$L::TAHAP_VERIFIKASI => 0, $L::TAHAP_INTERVIEW => 1, $L::TAHAP_SCREENING => 2, $L::TAHAP_KONTRAK => 3, $L::TAHAP_SELESAI => 4];
    $posisi = $urutan[$lamaran->tahap] ?? 0;
    $berjalan = $lamaran->isProses() || $lamaran->isMenungguVerifikasi();
    $diterima = $lamaran->status === $L::STATUS_DITERIMA;
    $interviews = $lamaran->interviewJadwals->sortBy([['tanggal', 'asc'], ['id', 'asc']]);
    $onboarding = $lamaran->screeningOnboardings->sortByDesc('id')->first();
    $kontrak = $lamaran->kontrakJadwals->sortByDesc('id')->first();
    // Ditolak langsung saat verifikasi: waktu verifikasi = waktu keputusan (lihat LamaranController::verifikasiTolak).
    $tolakDiVerifikasi = $lamaran->status === $L::STATUS_DITOLAK && $lamaran->diverifikasi_pada && $lamaran->diputuskan_pada
        && $lamaran->diverifikasi_pada->equalTo($lamaran->diputuskan_pada) && $interviews->isEmpty();
    // Status satu tahap: berjalan → menurut posisi tahap; selesai → tahap yang punya data dianggap sudah dilalui.
    $status = fn (int $i, bool $adaData) => $berjalan
        ? ($posisi > $i ? 'done' : ($posisi === $i ? 'active' : 'pending'))
        : ($adaData ? 'done' : null);

    $butir = [];
    $butir[] = [
        'state' => 'done', 'icon' => $lamaran->isJalurInternal() ? 'bi-person-check' : 'bi-send',
        'title' => $lamaran->isJalurInternal() ? 'Diusulkan (Jalur Langsung)' : 'Lamaran masuk',
        'meta' => $tgl($lamaran->created_at),
        'teks' => [$lamaran->isJalurInternal()
            ? 'Diusulkan ' . ($lamaran->direkomendasikan_oleh ?: '-') . ' · ditambahkan ' . ($lamaran->diverifikasiOleh?->name ?? '-')
            : 'Melamar lewat situs karier'],
    ];

    $verifikasi = $status(0, $lamaran->diverifikasi_pada !== null && ! $tolakDiVerifikasi);
    if ($verifikasi) {
        $butir[] = [
            'state' => $verifikasi, 'icon' => null, 'title' => 'Verifikasi data',
            'meta' => $verifikasi === 'done' ? $tgl($lamaran->diverifikasi_pada) : null,
            'teks' => [match (true) {
                $verifikasi === 'active' => 'Menunggu pemeriksaan data & berkas oleh Kepala SDM/GM.',
                $verifikasi === 'pending' => null,
                default => $lamaran->diverifikasiOleh ? 'Oleh ' . $lamaran->diverifikasiOleh->name : 'Data diterima',
            }],
        ];
    }

    $tahapInterview = $status(1, $interviews->isNotEmpty());
    if ($tahapInterview) {
        $butir[] = [
            'state' => $tahapInterview, 'icon' => null, 'title' => 'Interview',
            'meta' => $interviews->isNotEmpty() ? $interviews->where('hasil_status', 'lulus')->count() . ' lulus' : null,
            'teks' => $interviews->isNotEmpty()
                ? $interviews->map(fn ($j) => $j->tahapLabel() . ' · ' . $tgl($j->tanggal, 'd M') . ' · ' . ($j->status === 'dibatalkan' ? 'Dibatalkan' : $j->hasilLabel()))->all()
                : [$tahapInterview === 'active' ? 'Belum ada jadwal interview.' : null],
        ];
    }

    $tahapOnboarding = $status(2, $onboarding !== null);
    if ($tahapOnboarding) {
        $butir[] = [
            'state' => $tahapOnboarding, 'icon' => null, 'title' => 'Screening / Onboarding',
            'meta' => $onboarding ? $tgl($onboarding->tanggal_mulai, 'd M') . ' – ' . $tgl($onboarding->tanggal_selesai, 'd M') : null,
            'teks' => $onboarding
                ? [$onboarding->statusLabel() . ' · ' . $onboarding->hasilLabel(), 'Pengawas ' . ($onboarding->pengawas?->name ?? '-')]
                : [$tahapOnboarding === 'active' ? 'Onboarding belum dimulai.' : null],
        ];
    }

    $tahapKontrak = $status(3, $kontrak !== null);
    if ($tahapKontrak) {
        $butir[] = [
            'state' => $tahapKontrak, 'icon' => null, 'title' => 'Tanda tangan kontrak',
            'meta' => $kontrak ? $tgl($kontrak->tanggal) : null,
            'teks' => $kontrak
                ? [$kontrak->statusLabel() . ($kontrak->waktu ? ' · ' . substr($kontrak->waktu, 0, 5) : '') . ($kontrak->lokasi ? ' · ' . \App\Support\Lokasi::label($kontrak->lokasi) : '')]
                : [$tahapKontrak === 'active' ? 'Jadwal kontrak dibuat otomatis setelah onboarding lulus.' : null],
        ];
    }

    $oleh = $lamaran->diputuskanOleh?->name;
    $butir[] = match ($lamaran->status) {
        $L::STATUS_DITERIMA => ['state' => 'done', 'icon' => 'bi-trophy', 'title' => 'Diterima', 'meta' => $tgl($lamaran->diputuskan_pada),
            'teks' => [$lamaran->isKandidatPegawai() ? 'Ditetapkan ke posisi baru' : 'Direkrut menjadi pegawai', $oleh ? 'Oleh ' . $oleh : null], 'kelas' => ''],
        $L::STATUS_DITOLAK => ['state' => 'stopped', 'icon' => null, 'title' => $tolakDiVerifikasi ? 'Ditolak saat verifikasi' : 'Ditolak', 'meta' => $tgl($lamaran->diputuskan_pada),
            'teks' => [$lamaran->catatan_admin ? 'Alasan: ' . $lamaran->catatan_admin : null, $oleh ? 'Oleh ' . $oleh : null], 'kelas' => ''],
        $L::STATUS_DIBATALKAN => ['state' => 'stopped', 'icon' => 'bi-slash-circle', 'title' => 'Dibatalkan pelamar', 'meta' => $tgl($lamaran->diputuskan_pada),
            'teks' => [$lamaran->catatan_admin], 'kelas' => 'lm-batal'],
        default => ['state' => 'pending', 'icon' => null, 'title' => 'Keputusan akhir', 'meta' => null,
            'teks' => ['Rekrut atau tolak setelah onboarding lulus & kontrak ditandatangani.'], 'kelas' => ''],
    };
@endphp
<x-card title="Timeline Lamaran" icon="bi-signpost-split" id="timeline-lamaran">
    <x-timeline label="Timeline lamaran {{ $lamaran->namaKandidat() }}">
        @foreach ($butir as $b)
            <x-timeline.item :state="$b['state']" :icon="$b['icon']" :title="$b['title']" :meta="$b['meta']" :class="$b['kelas'] ?? ''">
                @foreach (array_filter($b['teks']) as $baris)
                    <span class="d-block">{{ $baris }}</span>
                @endforeach
            </x-timeline.item>
        @endforeach
    </x-timeline>
</x-card>
