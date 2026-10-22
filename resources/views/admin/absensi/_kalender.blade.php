{{--
    Kalender absensi. Param: $hari ([Y-m-d => hasil penilaian]), $dari, $sampai (Carbon),
    (opsional) $pegawai + $bisaKoreksi = true → klik tanggal membuka modal koreksi.
--}}
@php
    $hariPerTgl = collect($hari)->keyBy(fn ($h) => $h['tanggal']->toDateString());
    $mulaiGrid = $dari->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $akhirGrid = $sampai->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
    $durasiK = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m);
@endphp
<div class="abs-kalender" role="grid" aria-label="Kalender absensi">
    @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $n)
        <div class="kepala" role="columnheader">{{ $n }}</div>
    @endforeach
    @for ($t = $mulaiGrid->copy(); $t->lte($akhirGrid); $t->addDay())
        @php $h = $hariPerTgl[$t->toDateString()] ?? null; @endphp
        @if (! $h)
            <div class="abs-hari luar" aria-hidden="true"></div>
        @else
            @php
                $judulHari = $t->translatedFormat('l, d M') . ' — ' . $h['label']
                    . ($h['masuk'] ? " · {$h['masuk']}–" . ($h['keluar'] ?? '?') : '')
                    . ($h['telat_menit'] && in_array($h['status'], ['telat', 'telat_izin'], true) ? ' · telat ' . $durasiK($h['telat_menit']) : '')
                    . ($h['lembur_menit'] ? ' · lembur ' . $durasiK($h['lembur_menit']) : '')
                    . ($h['tanpa_scan_pulang'] ? ' · tidak scan pulang' : '');
                $kelas = 'abs-hari abs-st-' . $h['status'] . ($t->isToday() ? ' hari-ini' : '');
                $isi = '<span class="tgl">' . $t->day . '</span><span class="titik"></span>'
                    . ($h['masuk'] ? '<span class="jam">' . e($h['masuk']) . '–' . e($h['keluar'] ?? '?') . '</span>' : '')
                    . '<span class="ket">' . e($h['status'] === 'belum' ? '' : $h['label']) . ($h['tanpa_scan_pulang'] ? ' · <b>!</b>' : '') . '</span>';
            @endphp
            @if (($bisaKoreksi ?? false) && ($t->lte(now()) || auth()->user()->isAdmin()))
                @include('admin.absensi._tombol-koreksi', [
                    'pegawai' => $pegawai, 'h' => $h, 'tombolKecil' => true, 'kelasTombol' => $kelas, 'isiTombol' => $isi, 'judulTombol' => $judulHari,
                ])
            @else
                <div class="{{ $kelas }}" title="{{ $judulHari }}" role="gridcell">{!! $isi !!}</div>
            @endif
        @endif
    @endfor
</div>
<div class="abs-legenda mt-2">
    <span><i style="background:#198754"></i>On Time</span>
    <span><i style="background:#dc3545"></i>Telat</span>
    <span><i style="background:#e0a100"></i>Telat Izin</span>
    <span><i style="background:#343a40"></i>Alpa</span>
    <span><i style="background:#0b7aa6"></i>Cuti / Izin / Sakit</span>
    <span><i style="background:#cbd5e1"></i>Libur / belum ada data</span>
</div>
