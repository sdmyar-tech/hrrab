{{--
    Badge status absensi + tanda tambahan (x-badge, warna status seragam). Param: $h (hasil PenilaianAbsensi::nilai),
    (opsional) $catatanPenuh = true → catatan ditulis lengkap; selain itu cukup ikon ⓘ (arahkan kursor / fokus untuk membaca).
--}}
@php $durasiS = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m); @endphp
<span class="abs-status">
    <x-status-badge :badge="$h['badge']" dot>{{ $h['label'] }}@if (in_array($h['status'], ['telat', 'telat_izin'], true) && $h['telat_menit']) {{ $durasiS($h['telat_menit']) }}@endif</x-status-badge>
    @if ($h['pulang_cepat_menit'])
        <x-badge :tone="$h['pulang_cepat_izin'] ? 'info' : 'warning'" icon="bi-door-open">
            Pulang cepat {{ $durasiS($h['pulang_cepat_menit']) }}{{ $h['pulang_cepat_izin'] ? ' (izin)' : '' }}
        </x-badge>
    @endif
    @if ($h['tanpa_scan_pulang'])
        <x-badge tone="neutral" icon="bi-fingerprint">Tidak scan pulang</x-badge>
    @endif
    @if (in_array($h['lembur_status'], ['terverifikasi', 'manual', 'pengganti'], true))
        <x-badge tone="info" :icon="\App\Support\Absensi\TampilanAbsensi::IKON['lembur']"
            title="{{ $h['lembur_surat_menit'] ? 'Surat lembur ' . $durasiS($h['lembur_surat_menit']) : '' }}{{ $h['lembur_status'] === 'manual' ? ' · diisi manual SDM' : '' }}{{ $h['lembur_ganti_menit'] ? ' · pengganti cuti ' . $durasiS($h['lembur_ganti_menit']) : '' }}">
            Lembur {{ $durasiS($h['lembur_menit']) }}
        </x-badge>
    @elseif ($h['lembur_status'] === 'belum')
        <x-badge tone="warning" icon="bi-exclamation-circle" title="Ada surat lembur {{ $durasiS($h['lembur_surat_menit']) }}, tetapi jam finger tidak mencakupnya">
            Lembur belum dicek
        </x-badge>
    @endif
    @if ($h['dikoreksi'])
        <x-badge tone="neutral" icon="bi-pencil-square" title="Dikoreksi: {{ $h['absensi']?->alasan_koreksi }}">Dikoreksi</x-badge>
    @endif
    @if ($h['catatan'] && ! ($catatanPenuh ?? false))
        <i class="bi bi-info-circle abs-info" tabindex="0" role="img" data-hr-tooltip="{{ implode(' · ', $h['catatan']) }}"
            aria-label="Catatan: {{ implode(' ', $h['catatan']) }}"></i>
    @endif
</span>
@if ($catatanPenuh ?? false)
    @foreach ($h['catatan'] as $c)
        <span class="hr-sel-sub mt-1"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>{{ $c }}</span>
    @endforeach
@endif
