{{--
    Chip filter (pill yang bisa dipilih). Dipakai untuk filter cepat di atas daftar.
    <x-chip :href="route('lowongan.index', ['unit_kerja' => 'SDM'])" :active="$aktif" icon="bi-diagram-3" :count="12">SDM</x-chip>
    Tanpa href → <button type="button"> (untuk filter di sisi klien; pasang data-* sendiri).
    Aktif: latar marun muda + aria-current / aria-pressed.
--}}
@props([
    'href' => null,
    'active' => false,
    'icon' => null,
    'count' => null,
])
@php $kelas = 'hr-chip' . ($active ? ' is-active' : ''); @endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas]) }} @if ($active) aria-current="true" @endif>
        @if ($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
        <span>{{ $slot }}</span>
        @if (! is_null($count))<span class="hr-chip-jumlah">{{ $count }}</span>@endif
    </a>
@else
    <button type="button" {{ $attributes->merge(['class' => $kelas]) }} aria-pressed="{{ $active ? 'true' : 'false' }}">
        @if ($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
        <span>{{ $slot }}</span>
        @if (! is_null($count))<span class="hr-chip-jumlah">{{ $count }}</span>@endif
    </button>
@endif
