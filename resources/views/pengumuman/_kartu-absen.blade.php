{{-- Blok absen acara di kartu pengumuman. $saya = baris peserta milik pengguna (null bila bukan peserta). --}}
@php $fase = $p->absenFase(); @endphp
<div class="kartu-absen d-flex flex-wrap align-items-center gap-2 rounded-3 border p-2 mb-3">
    <span class="kartu-absen-ikon"><i class="bi bi-qr-code-scan"></i></span>
    <div class="small me-auto">
        <strong>Absen acara</strong>
        <span class="d-block text-muted">{{ $p->absenJendelaLabel() }} · radius {{ $p->absen_radius }} m</span>
    </div>

    @if ($saya)
        @if ($saya->sudahAbsen())
            <span class="badge text-bg-success py-2"><i class="bi bi-check-circle-fill me-1"></i>Sudah absen {{ $saya->waktu_absen?->format('H:i') }}</span>
        @elseif ($fase === \App\Models\Pengumuman::ABSEN_BUKA)
            <button type="button" class="btn btn-primary btn-sm" data-scan-acara><i class="bi bi-qr-code-scan me-1"></i>Absen Sekarang</button>
        @elseif ($fase === \App\Models\Pengumuman::ABSEN_BELUM_BUKA)
            <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Absen belum dibuka">
                <i class="bi bi-clock me-1"></i>Dibuka {{ $p->absen_buka->isToday() ? 'pukul ' . $p->absen_buka->format('H:i') : $p->absen_buka->translatedFormat('d M, H:i') }}
            </button>
        @else
            <span class="badge text-bg-secondary py-2"><i class="bi bi-hourglass-bottom me-1"></i>Kedaluwarsa — tidak absen</span>
        @endif
    @endif

    @if ($bisaPantauAbsen)
        <a href="{{ route('pengumuman.absen', $p) }}" class="btn btn-outline-primary btn-sm" title="Pantau kehadiran">
            <i class="bi bi-people me-1"></i>{{ $p->peserta_sudah }}/{{ $p->peserta_total }} hadir
        </a>
    @endif
</div>
@once
    <style>
        .kartu-absen { background: var(--bs-tertiary-bg); }
        .kartu-absen-ikon { display: grid; place-items: center; width: 2.1rem; height: 2.1rem; border-radius: .6rem; background: rgba(136, 15, 18, .1); color: #880f12; font-size: 1.1rem; }
    </style>
@endonce
