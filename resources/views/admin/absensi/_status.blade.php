{{-- Badge status absensi + tanda tambahan. Param: $h (hasil PenilaianAbsensi::nilai) --}}
@php $durasi = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m); @endphp
<span class="d-inline-flex flex-wrap gap-1 align-items-center">
    <span class="badge {{ $h['badge'] }}">{{ $h['label'] }}@if (in_array($h['status'], ['telat', 'telat_izin'], true) && $h['telat_menit']) · {{ $durasi($h['telat_menit']) }}@endif</span>
    @if ($h['pulang_cepat_menit'])
        <span class="badge {{ $h['pulang_cepat_izin'] ? 'text-bg-light border' : 'text-bg-warning' }}" title="{{ $h['pulang_cepat_izin'] ? 'Pulang cepat dengan izin' : 'Pulang sebelum jam pulang' }}">
            Pulang cepat {{ $durasi($h['pulang_cepat_menit']) }}{{ $h['pulang_cepat_izin'] ? ' (izin)' : '' }}
        </span>
    @endif
    @if ($h['tanpa_scan_pulang'])
        <span class="badge text-bg-secondary" title="Tidak ada scan pulang — perlu dikoreksi SDM">Tidak scan pulang</span>
    @endif
    @if ($h['lembur_status'] === 'terverifikasi' || $h['lembur_status'] === 'manual')
        <span class="badge text-bg-purple" title="Lembur {{ $h['lembur_status'] === 'manual' ? 'diisi manual SDM' : 'sesuai jam finger' }} (surat {{ $durasi($h['lembur_surat_menit']) }})">
            <i class="bi bi-clock-history me-1"></i>Lembur {{ $durasi($h['lembur_menit']) }}{{ $h['lembur_status'] === 'manual' ? ' (manual)' : '' }}
        </span>
    @elseif ($h['lembur_status'] === 'belum')
        <span class="badge text-bg-light border border-warning text-warning-emphasis" title="Ada surat lembur {{ $durasi($h['lembur_surat_menit']) }}, tetapi jam finger tidak mencakupnya">
            <i class="bi bi-exclamation-circle me-1"></i>Lembur belum terverifikasi
        </span>
    @endif
    @if ($h['dikoreksi'])
        <span class="badge text-bg-light border" title="Dikoreksi: {{ $h['absensi']?->alasan_koreksi }}"><i class="bi bi-pencil-square me-1"></i>Dikoreksi</span>
    @endif
</span>
