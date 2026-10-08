{{-- Tombol pembuka modal koreksi. Param: $pegawai, $h, (opsional) $tombolKecil --}}
@php
    $a = $h['absensi'];
    $durasi = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m);
    $dataKoreksi = [
        'url' => route('admin.absensi.koreksi', $pegawai),
        'nama' => $pegawai->nama_lengkap,
        'tanggal' => $h['tanggal']->toDateString(),
        'label' => $h['tanggal']->translatedFormat('l, d M Y') . ($h['jadwal'] ? ' · jadwal ' . $h['jadwal']['masuk'] . '–' . $h['jadwal']['pulang'] : ''),
        'masuk' => $a?->jam_masuk ? substr($a->jam_masuk, 0, 5) : null,
        'keluar' => $a?->jam_keluar ? substr($a->jam_keluar, 0, 5) : null,
        'scan_masuk' => $h['scan_masuk'],
        'scan_keluar' => $h['scan_keluar'],
        'status' => $a?->status_manual,
        'lembur' => $a?->lembur_manual_menit !== null ? sprintf('%02d:%02d', intdiv($a->lembur_manual_menit, 60), $a->lembur_manual_menit % 60) : null,
        'alasan' => $a?->alasan_koreksi,
        'dikoreksi' => (bool) $a?->dikoreksi,
        'surat' => $h['lembur_surat_menit'] ? 'Surat lembur disetujui: ' . $durasi($h['lembur_surat_menit']) : '',
    ];
@endphp
@if ($h['tanggal']->lte(now()))
    <button type="button" class="btn btn-outline-secondary btn-sm" data-koreksi='@json($dataKoreksi)' title="Koreksi absensi">
        <i class="bi bi-pencil-square"></i>@unless ($tombolKecil ?? false) Koreksi @endunless
    </button>
@endif
