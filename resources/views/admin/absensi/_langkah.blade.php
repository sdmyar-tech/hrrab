{{-- Panduan langkah persiapan absensi (untuk SDM/Admin). Param: $setup dari TampilanAbsensi::langkahSetup() --}}
@if ($setup && $setup['selesai'] < $setup['total'])
    @php $persenSiap = (int) round($setup['selesai'] / $setup['total'] * 100); @endphp
    <x-card title="Persiapan Absensi" subtitle="Selesaikan langkah berikut agar absensi bisa dinilai otomatis." icon="bi-list-check" class="mb-4">
        <x-slot:actions><x-badge tone="primary">{{ $setup['selesai'] }} / {{ $setup['total'] }} selesai</x-badge></x-slot:actions>
        <div class="progress abs-langkah-progres" role="progressbar" aria-label="Kemajuan persiapan absensi"
            aria-valuenow="{{ $persenSiap }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: {{ $persenSiap }}%"></div>
        </div>
        <ol class="abs-langkah">
            @foreach ($setup['langkah'] as $i => $l)
                <li class="abs-langkah-item {{ $l['selesai'] ? 'is-selesai' : '' }}">
                    <span class="abs-langkah-no {{ $l['selesai'] ? 'hr-tone-success' : 'hr-tone-primary' }}" aria-hidden="true">
                        @if ($l['selesai'])<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif
                    </span>
                    <div class="min-w-0">
                        <p class="abs-langkah-judul">{{ $l['judul'] }}@if ($l['selesai'])<span class="visually-hidden"> (selesai)</span>@endif</p>
                        <p class="abs-langkah-teks">{{ $l['teks'] }}</p>
                        @unless ($l['selesai'])
                            <a href="{{ $l['url'] }}" class="abs-langkah-tautan">{{ $l['tombol'] }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        @endunless
                    </div>
                </li>
            @endforeach
        </ol>
    </x-card>
@endif
