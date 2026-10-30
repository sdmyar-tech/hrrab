{{--
    Kartu. <x-card title="Pengajuan Terbaru" subtitle="..." icon="bi-send" :padding="false" hover href="...">
               <x-slot:actions><a href="#">Lihat semua</a></x-slot:actions>
               ...isi...
               <x-slot:footer>...</x-slot:footer>
           </x-card>
    - padding=false: isi menempel tepi (mis. tabel); kepala & kaki tetap berpadding.
    - href: seluruh kartu bisa diklik (melayang saat disorot). hover: efek melayang tanpa tautan.
    - title-tag: tingkat judul (bawaan h2; pakai h3 untuk kartu di dalam bagian ber-h2, mis. kartu lowongan). Varian Batch 2.
--}}
@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'padding' => true,
    'hover' => false,
    'href' => null,
    'as' => 'section',
    'titleTag' => 'h2',
])
@php
    $kelas = 'hr-card' . (($hover || $href) ? ' hr-card-hover' : '') . ($padding ? '' : ' hr-card-flush');
@endphp
<{{ $as }} {{ $attributes->merge(['class' => $kelas]) }}>
    @if ($title || isset($actions))
        <header class="hr-card-head">
            <div class="min-w-0">
                @if ($title)
                    <{{ $titleTag }} class="hr-card-title">
                        @if ($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
                        @if ($href)<a href="{{ $href }}" class="stretched-link">{{ $title }}</a>@else{{ $title }}@endif
                    </{{ $titleTag }}>
                @endif
                @if ($subtitle)<p class="hr-card-subtitle">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="hr-card-actions">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div class="hr-card-body">{{ $slot }}</div>
    @isset($footer)<footer class="hr-card-foot">{{ $footer }}</footer>@endisset
</{{ $as }}>
