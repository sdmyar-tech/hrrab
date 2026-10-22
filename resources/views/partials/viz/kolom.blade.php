@php
    $nilai = collect($items)->pluck('jumlah');
    $maks = max(1, $nilai->max());
    $iMaks = $nilai->search($nilai->max());
    $iAkhir = count($items) - 1;
    $format = fn ($n) => rtrim(rtrim(number_format($n, 1, ',', '.'), '0'), ',');
@endphp
@if ($nilai->sum() == 0)
    @include('partials.kosong', ['teks' => 'Belum ada ' . strtolower($judulGrafik) . ' yang disetujui dalam 6 bulan terakhir.', 'ikonKosong' => 'bi-bar-chart'])
@else
<div class="viz">
    <div class="viz-cols" aria-hidden="true">
        @foreach ($items as $k => $i)
            {{-- Kolom dengan 'url' bisa diklik → daftar pengajuan bulan itu. --}}
            @php $tag = ! empty($i['url']) && $i['jumlah'] > 0 ? 'a' : 'div'; @endphp
            <{{ $tag }} class="viz-col" tabindex="0" @if ($tag === 'a') href="{{ $i['url'] }}" @endif>
                @if ($i['jumlah'] > 0 && ($k === $iMaks || $k === $iAkhir))
                    <span class="tag">{{ $format($i['jumlah']) }}</span>
                @endif
                <span class="tip">{{ $i['judul'] }}: {{ $format($i['jumlah']) }} {{ $satuan }}{{ $tag === 'a' ? ' — klik untuk daftar' : '' }}</span>
                <span class="bar" style="height: {{ max(1, round($i['jumlah'] / $maks * 100)) }}%"></span>
            </{{ $tag }}>
        @endforeach
    </div>
    <div class="viz-xlab" aria-hidden="true">
        @foreach ($items as $i)
            <span>{{ $i['label'] }}</span>
        @endforeach
    </div>
    <table class="visually-hidden">
        <caption>{{ $judulGrafik }} per bulan ({{ $satuan }})</caption>
        @foreach ($items as $i)
            <tr><th scope="row">{{ $i['judul'] }}</th><td>{{ $format($i['jumlah']) }}</td></tr>
        @endforeach
    </table>
</div>
@endif
