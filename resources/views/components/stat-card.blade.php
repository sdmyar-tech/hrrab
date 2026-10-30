{{--
    Kartu angka (KPI): ikon + label + angka besar + keterangan / tren.
    <x-stat-card label="Pegawai Aktif" :value="128" icon="bi-people" tone="primary" meta="96 tetap · 32 kontrak"
        :trend="12.5" trend-label="vs bulan lalu" href="{{ route(...) }}" />
    - value: angka (diformat ribuan otomatis) atau teks. unit: satuan kecil di samping angka (mis. "hari").
    - trend: persen perubahan (positif = naik). trend-good="down" bila turun itu baik (mis. keterlambatan).
    - href: seluruh kartu bisa diklik. Slot (opsional) tampil di bawah keterangan, mis. progress bar.
--}}
@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'primary',
    'unit' => null,
    'meta' => null,
    'trend' => null,
    'trendLabel' => null,
    'trendGood' => 'up',
    'href' => null,
])
@php
    $angka = is_numeric($value) ? number_format((float) $value, floor((float) $value) == (float) $value ? 0 : 1, ',', '.') : $value;
    $naik = ! is_null($trend) && $trend > 0;
    $datar = ! is_null($trend) && (float) $trend == 0.0;
    $baik = $datar ? null : ($trendGood === 'down' ? ! $naik : $naik);
@endphp
<article {{ $attributes->merge(['class' => 'hr-stat' . ($href ? ' hr-card-hover' : '')]) }}>
    <div class="hr-stat-kepala">
        <span class="hr-stat-label">{{ $label }}</span>
        @if ($icon)<span class="hr-stat-ikon hr-tone-{{ $tone }}"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>@endif
    </div>
    <div class="hr-stat-nilai">{{ $angka }}@if ($unit)<small>{{ $unit }}</small>@endif</div>
    @if (! is_null($trend) || $meta)
        <div class="hr-stat-kaki">
            @if (! is_null($trend))
                <span class="hr-tren {{ $datar ? 'is-datar' : ($baik ? 'is-baik' : 'is-buruk') }}">
                    <i class="bi {{ $datar ? 'bi-dash' : ($naik ? 'bi-arrow-up-right' : 'bi-arrow-down-right') }}" aria-hidden="true"></i>
                    <span class="visually-hidden">{{ $datar ? 'Tetap' : ($naik ? 'Naik' : 'Turun') }}</span>
                    {{ number_format(abs((float) $trend), abs((float) $trend) < 10 ? 1 : 0, ',', '.') }}%
                </span>
            @endif
            @if ($trendLabel || $meta)<span class="hr-stat-meta">{{ $trendLabel ?? $meta }}</span>@endif
        </div>
        @if ($trendLabel && $meta)<div class="hr-stat-meta mt-1">{{ $meta }}</div>@endif
    @endif
    {{ $slot }}
    @if ($href)<a href="{{ $href }}" class="stretched-link" aria-label="Buka {{ $label }}"></a>@endif
</article>
