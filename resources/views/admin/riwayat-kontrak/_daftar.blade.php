{{--
    Daftar riwayat kontrak (hasil App\Support\RiwayatKontrak::untuk) — dipakai di detail evaluasi & jendela detail pada kolom Aksi.
    Param: $rows (daftar baris), opsional $saatIni (EvaluasiKontrak yang sedang dibuka → ditandai "Evaluasi ini").
--}}
@php $saatIni = $saatIni ?? null; @endphp
@forelse ($rows as $rk)
    @php $iniSaatIni = $saatIni && $rk['evaluasi']?->is($saatIni); @endphp
    <div class="d-flex justify-content-between align-items-start gap-3 py-2 border-top {{ $iniSaatIni ? 'bg-body-tertiary rounded px-2' : '' }}">
        <div class="min-w-0">
            <div class="fw-semibold">
                {{ $rk['label'] }}{{ $rk['ke'] && ! str_starts_with($rk['label'], 'Kontrak') ? ' (kontrak ke-' . $rk['ke'] . ')' : '' }}
                <x-badge :tone="$rk['tone']">{{ ['selesai' => 'Selesai dievaluasi', 'evaluasi' => 'Sedang dievaluasi', 'berjalan' => 'Berjalan', 'catatan' => 'Tercatat'][$rk['status']] }}</x-badge>
                @if ($iniSaatIni)<x-badge tone="primary">Evaluasi ini</x-badge>@endif
            </div>
            <span class="hr-sel-sub">
                {{ $rk['mulai']?->translatedFormat('d M Y') ?? 'awal tidak tercatat' }} – {{ $rk['habis']?->translatedFormat('d M Y') ?? 'tanpa batas' }}{{ $rk['bulan'] ? ' · ± ' . $rk['bulan'] . ' bulan' : '' }}
            </span>
            <span class="d-block small mt-1">{{ $rk['keterangan'] }}</span>
            @if ($rk['catatan'])<span class="d-block small text-muted mt-1"><i class="bi bi-chat-left-text me-1" aria-hidden="true"></i>{{ $rk['catatan'] }}</span>@endif
        </div>
        @if ($rk['evaluasi'] && ! $iniSaatIni)
            <a href="{{ route('admin.evaluasi-kontrak.show', $rk['evaluasi']) }}" class="btn btn-sm btn-outline-secondary text-nowrap">Lihat</a>
        @endif
    </div>
@empty
    <p class="mb-0 text-muted">Belum ada data kontrak.</p>
@endforelse
