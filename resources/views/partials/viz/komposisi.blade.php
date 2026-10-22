@php
    $total = collect($items)->sum('jumlah');
    $slot = 0;
    $peta = $palet ?? [];
    $warna = collect($items)->map(function ($i) use (&$slot, $peta) {
        return $i['label'] === 'Belum diisi' ? 'var(--viz-netral)' : 'var(--viz-' . ($peta[$i['label']] ?? ++$slot) . ')';
    })->all();
    // Item dengan 'url' bisa diklik (potongan batang & baris legenda) → halaman detail yang tersaring.
    $bisaKlik = ! ($tanpaTautan ?? false) && auth()->user()?->can('lihat-tabel-karyawan');
@endphp
<div class="viz">
    @if ($total === 0)
        @include('partials.kosong', ['teks' => 'Belum ada data.', 'ikonKosong' => 'bi-pie-chart'])
    @else
        <div class="viz-stack {{ ($kecil ?? false) ? 'viz-stack-sm' : '' }}" role="img"
            aria-label="{{ collect($items)->map(fn ($i) => $i['label'] . ' ' . $i['jumlah'])->implode(', ') }}">
            @foreach ($items as $k => $i)
                @if ($i['jumlah'] > 0)
                    @if ($bisaKlik && ! empty($i['url']))
                        <a href="{{ $i['url'] }}" style="flex: {{ $i['jumlah'] }}; background: {{ $warna[$k] }};" title="{{ $i['label'] }}: {{ $i['jumlah'] }} ({{ round($i['jumlah'] / $total * 100) }}%) — klik untuk melihat daftar"></a>
                    @else
                        <span style="flex: {{ $i['jumlah'] }}; background: {{ $warna[$k] }};" title="{{ $i['label'] }}: {{ $i['jumlah'] }} ({{ round($i['jumlah'] / $total * 100) }}%)"></span>
                    @endif
                @endif
            @endforeach
        </div>
        @unless ($tanpaLegenda ?? false)
            <ul class="viz-legend">
                @foreach ($items as $k => $i)
                    <li>
                        @if ($bisaKlik && ! empty($i['url']) && $i['jumlah'] > 0)
                            <a href="{{ $i['url'] }}" class="viz-legend-link">
                                <span class="sw" style="background: {{ $warna[$k] }};"></span>
                                <span>{{ $i['label'] }}</span>
                                <span class="val"><strong>{{ $i['jumlah'] }}</strong> &middot; {{ round($i['jumlah'] / $total * 100) }}%</span>
                            </a>
                        @else
                            <span class="sw" style="background: {{ $warna[$k] }};"></span>
                            <span>{{ $i['label'] }}</span>
                            <span class="val"><strong>{{ $i['jumlah'] }}</strong> &middot; {{ round($i['jumlah'] / $total * 100) }}%</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endunless
    @endif
</div>
