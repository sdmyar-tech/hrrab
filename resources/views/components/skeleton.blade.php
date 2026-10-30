{{--
    Kerangka pemuatan (shimmer) untuk konten async.
    <x-skeleton variant="text" :lines="3" /> · <x-skeleton variant="card" /> · <x-skeleton variant="table" :rows="5" />
    <x-skeleton variant="avatar" /> · <x-skeleton variant="block" height="240px" />
    Diberi role="status" + teks tersembunyi "Memuat…" untuk pembaca layar.
--}}
@props([
    'variant' => 'text',
    'lines' => 3,
    'rows' => 4,
    'height' => null,
])
<div {{ $attributes->merge(['class' => 'hr-skeleton hr-skeleton-' . $variant]) }} role="status" aria-live="polite">
    <span class="visually-hidden">Memuat…</span>
    @switch($variant)
        @case('avatar')
            <span class="hr-skel hr-skel-bulat" aria-hidden="true"></span>
            @break
        @case('block')
            <span class="hr-skel" style="height: {{ $height ?? '160px' }}" aria-hidden="true"></span>
            @break
        @case('card')
            <span class="hr-skel hr-skel-kecil" style="width: 40%" aria-hidden="true"></span>
            <span class="hr-skel hr-skel-besar" style="width: 60%" aria-hidden="true"></span>
            <span class="hr-skel" style="width: 85%" aria-hidden="true"></span>
            @break
        @case('table')
            @for ($i = 0; $i < $rows; $i++)
                <span class="hr-skel-baris" aria-hidden="true">
                    <span class="hr-skel hr-skel-bulat"></span>
                    <span class="hr-skel" style="width: {{ [55, 40, 65, 48, 60][$i % 5] }}%"></span>
                    <span class="hr-skel ms-auto" style="width: 14%"></span>
                </span>
            @endfor
            @break
        @default
            @for ($i = 0; $i < $lines; $i++)
                <span class="hr-skel" style="width: {{ $i === $lines - 1 ? 60 : [100, 92, 96][$i % 3] }}%" aria-hidden="true"></span>
            @endfor
    @endswitch
</div>
