@if ($pengumuman->isNotEmpty())
    <section class="panel mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0 section-title"><i class="bi bi-megaphone" aria-hidden="true"></i><span>Pengumuman</span></h2>
            <a href="{{ route('pengumuman.index') }}" class="small dash-link">Lihat semua</a>
        </div>
        <div class="row g-3">
            @foreach ($pengumuman as $p)
                @php $k = $p->kategoriInfo(); @endphp
                <div class="{{ $kolom ?? 'col-12 col-md-6 col-xl-3' }}">
                    <a href="{{ route('pengumuman.index') }}#pengumuman-{{ $p->id }}" class="d-block h-100 border rounded p-3 text-reset text-decoration-none {{ $p->penting ? 'border-danger-subtle' : '' }}">
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            <span class="badge {{ $k['badge'] }}"><i class="bi {{ $k['ikon'] }} me-1"></i>{{ $k['label'] }}</span>
                            @if ($p->penting)<span class="badge text-bg-danger"><i class="bi bi-pin-angle-fill"></i></span>@endif
                        </div>
                        <div class="fw-semibold mb-1">{{ $p->judul }}</div>
                        @if ($p->jadwalLabel())
                            <div class="small text-muted"><i class="bi {{ $p->isRutin() ? 'bi-arrow-repeat' : 'bi-calendar-event' }} me-1"></i>{{ $p->jadwalLabel() }}</div>
                        @endif
                        @if ($p->lokasi)
                            <div class="small text-muted">@include('partials.lokasi', ['lokasi' => $p->lokasi])</div>
                        @endif
                        @unless ($p->jadwalLabel())
                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($p->isi, 90) }}</div>
                        @endunless
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif
