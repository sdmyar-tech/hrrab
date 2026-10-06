@php $maks = max(1, collect($items)->max('jumlah')); @endphp
<div class="viz">
    @forelse ($items as $i)
        <div class="viz-hbar" title="{{ $i['label'] }}: {{ $i['jumlah'] }}{{ isset($satuan) ? ' ' . $satuan : '' }}">
            @if (! empty($i['url']))
                <a href="{{ $i['url'] }}" class="lbl text-reset">{{ $i['label'] }}</a>
            @else
                <span class="lbl">{{ $i['label'] }}</span>
            @endif
            <span class="track"><span class="fill" style="width: {{ round($i['jumlah'] / $maks * 100, 1) }}%"></span></span>
            <span class="num">{{ $i['jumlah'] }}</span>
        </div>
    @empty
        <p class="viz-empty mb-0">Belum ada data.</p>
    @endforelse
</div>
