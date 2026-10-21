{{-- Satu cabang jabatan bagan hanya baca: ≤ 4 orang berjajar, lebih dari itu rak grid (bisa dilipat). $c dari RingkasanPengajuan::baganFormasi(). --}}
@php
    $simpul = fn ($s) => '<span class="bagan-node ' . e($s['kelas']) . ($s['lebih'] ? ' lebih' : '') . '" title="' . e($s['judul']) . '"><i class="bi ' . e($s['ikon']) . '"></i><span class="nm">' . e($s['nama']) . '</span>'
        . ($s['lencana'] ? '<span class="bagan-lencana"><i class="bi ' . e($s['lencana']) . '"></i></span>' : '') . '</span>';
@endphp
<div class="bagan-cabang {{ $c['terpilih'] ? 'terpilih' : '' }}">
    <div class="bagan-jabatan">{{ $c['jab'] }} ({{ $c['total'] }})</div>
    <div class="bagan-turun"></div>
    @if (count($c['simpul']) <= 4)
        <div class="bagan-anak">
            @foreach ($c['simpul'] as $s)<div class="bagan-daun">{!! $simpul($s) !!}</div>@endforeach
        </div>
    @else
        <div class="bagan-rak" style="--kol: 4">
            @foreach ($c['simpul'] as $s){!! $simpul($s) !!}@endforeach
            @if ($c['sisa'])
                <button type="button" class="btn btn-sm btn-link bagan-lagi" data-bagan-lagi="+{{ $c['sisa'] }} lainnya — tampilkan semua">+{{ $c['sisa'] }} lainnya — tampilkan semua</button>
            @endif
        </div>
    @endif
</div>
