{{--
    Kalender absensi bulanan: CSS Grid 7 kolom yang dirender server (tanpa pustaka). Dipakai Riwayat Pegawai & Absensi Saya.
    Param: $hari ([Y-m-d => hasil penilaian]), $dari, $sampai (Carbon),
    (opsional) $pegawai + $bisaKoreksi = true → klik tanggal membuka modal koreksi.
    Warna sel: hijau hadir · kuning telat · biru izin/cuti/sakit/dinas luar/telat berizin · merah alpa · abu libur/belum ada data.
--}}
@php
    $hariPerTgl = collect($hari)->keyBy(fn ($h) => $h['tanggal']->toDateString());
    $mulaiGrid = $dari->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $akhirGrid = $sampai->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
    $durasiK = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m);
    $nada = fn (string $status) => match ($status) {
        'on_time', 'hadir' => 'hadir',
        'telat' => 'telat',
        'telat_izin', 'cuti', 'izin', 'sakit', 'dinas_luar' => 'izin',
        'alpa' => 'alpa',
        default => 'libur',
    };
    $legenda = [
        'hadir' => 'Hadir tepat waktu',
        'telat' => 'Telat',
        'izin' => 'Izin, cuti, sakit, dinas luar, telat berizin',
        'alpa' => 'Alpa',
        'libur' => 'Libur / belum ada data',
    ];
@endphp
<div class="abs-kal-kepala" aria-hidden="true">
    @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $n)<span>{{ $n }}</span>@endforeach
</div>
<div class="abs-kal" aria-label="Kalender absensi {{ $dari->translatedFormat('d M') }} – {{ $sampai->translatedFormat('d M Y') }}">
    @for ($t = $mulaiGrid->copy(); $t->lte($akhirGrid); $t->addDay())
        @php $h = $hariPerTgl[$t->toDateString()] ?? null; @endphp
        @if (! $h)
            <span class="abs-kal-hari is-luar" aria-hidden="true"></span>
        @else
            @php
                $judulHari = $t->translatedFormat('l, d M') . ' — ' . $h['label']
                    . ($h['masuk'] ? " · {$h['masuk']}–" . ($h['keluar'] ?? '?') : '')
                    . ($h['telat_menit'] && in_array($h['status'], ['telat', 'telat_izin'], true) ? ' · telat ' . $durasiK($h['telat_menit']) : '')
                    . ($h['lembur_menit'] ? ' · lembur ' . $durasiK($h['lembur_menit']) : '')
                    . ($h['tanpa_scan_pulang'] ? ' · tidak scan pulang' : '')
                    . ($h['dikoreksi'] ? ' · dikoreksi' : '');
                $kelas = 'abs-kal-hari is-' . $nada($h['status']) . ($t->isToday() ? ' is-hari-ini' : '');
                // Tanggal 1 & awal rentang diberi nama bulan agar rentang lintas bulan tetap jelas.
                $teksTgl = $t->day === 1 || $t->isSameDay($dari) ? $t->translatedFormat('j M') : $t->day;
                $tanda = ($h['tanpa_scan_pulang'] ? '<i class="bi bi-exclamation-circle" title="Tidak scan pulang"></i>' : '')
                    . ($h['lembur_menit'] ? '<i class="bi bi-clock-history" title="Lembur"></i>' : '')
                    . ($h['dikoreksi'] ? '<i class="bi bi-pencil-square" title="Dikoreksi"></i>' : '');
                $isi = '<span class="abs-kal-isi" aria-hidden="true">'
                    . '<span class="abs-kal-tgl">' . e($teksTgl) . ($tanda ? '<span class="abs-kal-tanda">' . $tanda . '</span>' : '') . '</span>'
                    . ($h['masuk'] ? '<span class="abs-kal-jam">' . e($h['masuk']) . '–' . e($h['keluar'] ?? '?') . '</span>' : '')
                    . '<span class="abs-kal-ket">' . e($h['status'] === 'belum' ? '' : $h['label']) . '</span>'
                    . '</span>';
            @endphp
            @if (($bisaKoreksi ?? false) && ($t->lte(now()) || auth()->user()->can('sistem.override')))
                @include('admin.absensi._tombol-koreksi', [
                    'pegawai' => $pegawai, 'h' => $h, 'kelasTombol' => $kelas, 'isiTombol' => $isi,
                    'judulTombol' => $judulHari, 'labelTombol' => $judulHari . '. Klik untuk mengoreksi.',
                ])
            @else
                <div class="{{ $kelas }}" title="{{ $judulHari }}">{!! $isi !!}<span class="visually-hidden">{{ $judulHari }}</span></div>
            @endif
        @endif
    @endfor
</div>
<ul class="abs-legenda" aria-label="Keterangan warna">
    @foreach ($legenda as $kunci => $teks)
        <li><span class="abs-legenda-warna is-{{ $kunci }}" aria-hidden="true"></span>{{ $teks }}</li>
    @endforeach
</ul>
