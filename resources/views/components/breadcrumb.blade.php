{{--
    Jejak halaman. <x-breadcrumb :items="[['label' => 'Beranda', 'url' => route('admin.dashboard'), 'ikon' => 'bi-house'], ['label' => 'Data Pegawai']]" />
    Item terakhir = halaman aktif (aria-current). Item tanpa url tampil sebagai teks.
--}}
@props(['items' => []])
<nav {{ $attributes }} aria-label="Breadcrumb">
    <ol class="breadcrumb">
        @foreach ($items as $item)
            @if ($loop->last)
                <li class="breadcrumb-item active" aria-current="page" title="{{ $item['label'] }}">{{ $item['label'] }}</li>
            @else
                <li class="breadcrumb-item" title="{{ $item['label'] }}">
                    @if (! empty($item['url']))
                        <a href="{{ $item['url'] }}">@if (! empty($item['ikon']))<i class="bi {{ $item['ikon'] }} me-1" aria-hidden="true"></i>@endif{{ $item['label'] }}</a>
                    @else
                        {{ $item['label'] }}
                    @endif
                </li>
            @endif
        @endforeach
    </ol>
</nav>
