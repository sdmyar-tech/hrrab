{{--
    Badge status absensi + tanda tambahan. Param: $h (hasil PenilaianAbsensi::nilai),
    (opsional) $catatanPenuh = true → catatan ditulis lengkap; selain itu cukup ikon ⓘ (arahkan kursor untuk membaca).
--}}
@php $durasiS = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m); @endphp
<span class="d-inline-flex flex-wrap gap-1 align-items-center">
    <span class="badge {{ $h['badge'] }}">{{ $h['label'] }}@if (in_array($h['status'], ['telat', 'telat_izin'], true) && $h['telat_menit']) {{ $durasiS($h['telat_menit']) }}@endif</span>
    @if ($h['pulang_cepat_menit'])
        <span class="badge {{ $h['pulang_cepat_izin'] ? 'text-bg-light border' : 'text-bg-warning' }}">
            Pulang cepat {{ $durasiS($h['pulang_cepat_menit']) }}{{ $h['pulang_cepat_izin'] ? ' (izin)' : '' }}
        </span>
    @endif
    @if ($h['tanpa_scan_pulang'])
        <span class="badge text-bg-secondary">Tidak scan pulang</span>
    @endif
    @if (in_array($h['lembur_status'], ['terverifikasi', 'manual'], true))
        <span class="badge text-bg-purple" title="Surat lembur {{ $durasiS($h['lembur_surat_menit']) }}{{ $h['lembur_status'] === 'manual' ? ' · diisi manual SDM' : '' }}">
            <i class="bi bi-clock-history me-1"></i>Lembur {{ $durasiS($h['lembur_menit']) }}
        </span>
    @elseif ($h['lembur_status'] === 'belum')
        <span class="badge text-bg-light border border-warning text-warning-emphasis" title="Ada surat lembur {{ $durasiS($h['lembur_surat_menit']) }}, tetapi jam finger tidak mencakupnya">
            <i class="bi bi-exclamation-circle me-1"></i>Lembur belum dicek
        </span>
    @endif
    @if ($h['dikoreksi'])
        <span class="badge text-bg-light border" title="Dikoreksi: {{ $h['absensi']?->alasan_koreksi }}"><i class="bi bi-pencil-square me-1"></i>Dikoreksi</span>
    @endif
    @if ($h['catatan'] && ! ($catatanPenuh ?? false))
        <i class="bi bi-info-circle abs-info" tabindex="0" title="{{ implode("\n", $h['catatan']) }}" aria-label="Catatan: {{ implode(' ', $h['catatan']) }}"></i>
    @endif
</span>
@if ($catatanPenuh ?? false)
    @foreach ($h['catatan'] as $c)
        <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>{{ $c }}</small>
    @endforeach
@endif
