{{--
    Tombol pembuka modal koreksi (_koreksi) ber-atribut data-koreksi='{...}'. Param: $pegawai, $h.
    Bawaan: x-icon-button (kolom aksi tabel). Mode sel kalender: $kelasTombol + $isiTombol (HTML) + (opsional) $judulTombol, $labelTombol.
    Tanggal mendatang hanya untuk Admin; selain itu (mode ikon) diganti pengganti selebar tombol agar ikon antarbaris sejajar.
--}}
@php
    $a = $h['absensi'];
    $durasiK = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m);
    $dataKoreksi = [
        'url' => route('admin.absensi.koreksi', $pegawai),
        'nama' => $pegawai->nama_lengkap,
        'tanggal' => $h['tanggal']->toDateString(),
        'label' => $h['tanggal']->translatedFormat('l, d F Y') . ($h['jadwal'] ? ' · jam kerja ' . $h['jadwal']['masuk'] . '–' . $h['jadwal']['pulang'] : ''),
        'masuk' => $a?->jam_masuk ? substr($a->jam_masuk, 0, 5) : null,
        'keluar' => $a?->jam_keluar ? substr($a->jam_keluar, 0, 5) : null,
        'scan_masuk' => $h['scan_masuk'],
        'scan_keluar' => $h['scan_keluar'],
        'status' => $a?->status_manual,
        'lembur' => $a?->lembur_manual_menit !== null ? sprintf('%02d:%02d', intdiv($a->lembur_manual_menit, 60), $a->lembur_manual_menit % 60) : null,
        'perlu_lembur' => $h['lembur_status'] === 'belum',
        'alasan' => $a?->alasan_koreksi,
        'dikoreksi' => (bool) $a?->dikoreksi,
        'surat' => $h['lembur_surat_menit'] ? 'Surat lembur ' . $durasiK($h['lembur_surat_menit']) : '',
    ];
    $labelBawaan = 'Koreksi absensi ' . $pegawai->nama_lengkap . ' ' . $h['tanggal']->translatedFormat('d M');
@endphp
@if ($h['tanggal']->lte(now()) || auth()->user()->isAdmin())
    @isset($isiTombol)
        <button type="button" class="{{ $kelasTombol ?? 'btn btn-outline-secondary btn-sm' }}" data-koreksi='@json($dataKoreksi)'
            title="{{ $judulTombol ?? $labelBawaan }}" aria-label="{{ $labelTombol ?? $labelBawaan }}">{!! $isiTombol !!}</button>
    @else
        <x-icon-button :icon="\App\Support\Absensi\TampilanAbsensi::IKON['koreksi']" label="Koreksi absensi" tone="primary"
            :aria-label="$labelBawaan" :data-koreksi="json_encode($dataKoreksi)" />
    @endisset
@elseif (! isset($isiTombol))
    <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
@endif
