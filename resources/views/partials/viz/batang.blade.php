@php $maks = max(1, collect($items)->max('jumlah')); @endphp
<div class="viz">
    @forelse ($items as $i)
        {{-- Seluruh baris bisa diklik bila item punya 'url' (nama, batang, dan angkanya). --}}
        @php $tautan = ! empty($i['url']) && $i['jumlah'] > 0; @endphp
        <{{ $tautan ? 'a' : 'div' }} class="viz-hbar {{ $tautan ? 'viz-hbar-link' : '' }}" @if ($tautan) href="{{ $i['url'] }}" @endif
            title="{{ $i['label'] }}: {{ $i['jumlah'] }}{{ isset($satuan) ? ' ' . $satuan : '' }}{{ $tautan ? ' — klik untuk melihat daftar' : '' }}">
            <span class="lbl">{{ $i['label'] }}</span>
            <span class="track"><span class="fill" style="width: {{ round($i['jumlah'] / $maks * 100, 1) }}%"></span></span>
            <span class="num">{{ $i['jumlah'] }}</span>
        </{{ $tautan ? 'a' : 'div' }}>
    @empty
        @include('partials.kosong', ['teks' => 'Belum ada data.', 'ikonKosong' => 'bi-bar-chart'])
    @endforelse
</div>
@once
    <style>
        .viz-hbar-link { color: inherit !important; text-decoration: none !important; border-radius: .35rem; padding: .1rem .3rem; margin-inline: -.3rem; }
        .viz-hbar-link:hover { background: var(--admin-surface-soft, #f8f9fb); }
        .viz-hbar-link:hover .lbl { color: var(--hr-primary, #8b1215); }
        .viz-hbar-link:hover .fill { filter: brightness(1.15); }
    </style>
@endonce
