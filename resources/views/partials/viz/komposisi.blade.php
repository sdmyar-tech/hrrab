@php
    $total = collect($items)->sum('jumlah');
    $slot = 0;
    $peta = $palet ?? [];
    $warna = collect($items)->map(function ($i) use (&$slot, $peta) {
        return $i['label'] === 'Belum diisi' ? 'var(--viz-netral)' : 'var(--viz-' . ($peta[$i['label']] ?? ++$slot) . ')';
    })->all();
@endphp
<div class="viz">
    @if ($total === 0)
        <p class="viz-empty mb-0">Belum ada data.</p>
    @else
        <div class="viz-stack {{ ($kecil ?? false) ? 'viz-stack-sm' : '' }}" role="img"
            aria-label="{{ collect($items)->map(fn ($i) => $i['label'] . ' ' . $i['jumlah'])->implode(', ') }}">
            @foreach ($items as $k => $i)
                @if ($i['jumlah'] > 0)
                    <span style="flex: {{ $i['jumlah'] }}; background: {{ $warna[$k] }};" title="{{ $i['label'] }}: {{ $i['jumlah'] }} ({{ round($i['jumlah'] / $total * 100) }}%)"></span>
                @endif
            @endforeach
        </div>
        @unless ($tanpaLegenda ?? false)
            <ul class="viz-legend">
                @foreach ($items as $k => $i)
                    <li>
                        <span class="sw" style="background: {{ $warna[$k] }};"></span>
                        <span>{{ $i['label'] }}</span>
                        <span class="val"><strong>{{ $i['jumlah'] }}</strong> &middot; {{ round($i['jumlah'] / $total * 100) }}%</span>
                    </li>
                @endforeach
            </ul>
        @endunless
    @endif
</div>
