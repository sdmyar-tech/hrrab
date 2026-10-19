{{-- Rekomendasi & penilaian atasan (tampilan baca saja). $e = EvaluasiKontrak. --}}
<p class="mb-2 fw-semibold">{{ \App\Models\EvaluasiKontrak::labelPilihan($e->rekomendasi, $e->lama_bulan) }}</p>
<div class="d-grid gap-1 small mb-2">
    @foreach (\App\Models\EvaluasiKontrak::ASPEK as $kolom => $label)
        <div class="d-flex justify-content-between align-items-center gap-2">
            <span>{{ $label }}</span>
            <span class="text-nowrap" aria-label="{{ $e->$kolom }} dari 5">
                @for ($i = 1; $i <= 5; $i++)<i class="bi {{ $i <= $e->$kolom ? 'bi-star-fill text-warning' : 'bi-star text-muted' }}"></i>@endfor
                <b class="ms-1">{{ $e->$kolom }}</b>
            </span>
        </div>
    @endforeach
    <div class="d-flex justify-content-between border-top pt-1"><span class="fw-semibold">Rata-rata</span><b>{{ $e->rataNilai() }}/5</b></div>
</div>
@if ($e->catatan)<p class="small mb-2" style="white-space: pre-line;">{{ $e->catatan }}</p>@endif
<p class="small text-muted mb-0">{{ $e->pengisi?->name }} · {{ $e->diisi_pada?->translatedFormat('d M Y H:i') }}</p>
