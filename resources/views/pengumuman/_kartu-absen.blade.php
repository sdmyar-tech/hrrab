{{-- Blok absen acara di kartu pengumuman. $saya = baris peserta milik pengguna (null bila bukan peserta). --}}
@php $fase = $p->absenFase(); @endphp
<div class="kartu-absen">
    <span class="hr-stat-ikon hr-tone-primary dash-ikon-kecil"><i class="bi bi-qr-code-scan" aria-hidden="true"></i></span>
    <div class="small me-auto min-w-0">
        <strong class="d-block">Absen acara</strong>
        <span class="text-muted">{{ $p->absenJendelaLabel() }} · scan QR di lokasi</span>
    </div>

    @if ($saya)
        @if ($saya->sudahAbsen())
            <x-badge tone="success" icon="bi-check-circle-fill">Sudah absen {{ $saya->waktu_absen?->format('H:i') }}</x-badge>
        @elseif ($saya->berhalangan())
            @php $st = $saya->tampilan(); @endphp
            <x-status-badge :badge="$st['kelas']" :icon="$st['ikon']" title="{{ $saya->catatan }}">Tercatat {{ mb_strtolower($st['label']) }}</x-status-badge>
            @if ($fase === \App\Models\Pengumuman::ABSEN_BUKA)
                {{-- Ternyata bisa datang: scan tetap mengubah status menjadi hadir. --}}
                <x-button variant="outline" size="sm" icon="bi-qr-code-scan" data-scan-acara>Tetap hadir? Absen</x-button>
            @endif
        @elseif ($fase === \App\Models\Pengumuman::ABSEN_BUKA)
            <x-button size="sm" icon="bi-qr-code-scan" data-scan-acara>Absen Sekarang</x-button>
        @elseif ($fase === \App\Models\Pengumuman::ABSEN_BELUM_BUKA)
            <x-button variant="secondary" size="sm" icon="bi-clock" disabled title="Absen belum dibuka">
                Dibuka {{ $p->absen_buka->isToday() ? 'pukul ' . $p->absen_buka->format('H:i') : $p->absen_buka->translatedFormat('d M, H:i') }}
            </x-button>
        @else
            <x-badge tone="neutral" icon="bi-hourglass-bottom">Kedaluwarsa — tidak absen</x-badge>
        @endif
    @endif

    @if ($bisaPantauAbsen)
        <x-button :href="route('pengumuman.absen', $p)" variant="secondary" size="sm" icon="bi-people" title="Pantau kehadiran">{{ $p->peserta_sudah }}/{{ $p->peserta_total }} hadir</x-button>
    @endif
</div>
