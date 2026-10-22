{{-- Panduan langkah persiapan absensi (untuk SDM/Admin). Param: $setup dari TampilanAbsensi::langkahSetup() --}}
@if ($setup && $setup['selesai'] < $setup['total'])
    <section class="panel p-3 p-md-4 mb-3" aria-labelledby="judulLangkah">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div>
                <h2 class="h6 fw-bold mb-0" id="judulLangkah"><i class="bi bi-list-check me-1"></i>Persiapan Absensi</h2>
                <small class="text-muted">Selesaikan langkah berikut agar absensi bisa dinilai otomatis.</small>
            </div>
            <span class="small fw-semibold">{{ $setup['selesai'] }} / {{ $setup['total'] }} selesai</span>
        </div>
        <div class="abs-progres mb-3"><span style="width: {{ round($setup['selesai'] / $setup['total'] * 100) }}%"></span></div>
        <div class="abs-langkah">
            @foreach ($setup['langkah'] as $i => $l)
                <div class="abs-langkah-item {{ $l['selesai'] ? 'selesai' : '' }}">
                    <span class="abs-langkah-no">@if ($l['selesai'])<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif</span>
                    <div class="min-w-0">
                        <div class="fw-semibold small">{{ $l['judul'] }}</div>
                        <div class="small text-muted mb-1">{{ $l['teks'] }}</div>
                        @unless ($l['selesai'])
                            <a href="{{ $l['url'] }}" class="small fw-semibold">{{ $l['tombol'] }} <i class="bi bi-arrow-right"></i></a>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
