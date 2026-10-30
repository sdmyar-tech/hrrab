{{--
    Tombol. <x-button variant="primary|secondary|outline|ghost|danger|link" size="sm|md|lg" icon="bi-check-lg" href="..." block loading>Simpan</x-button>
    - href diisi → <a>; selain itu <button type="button"> (atur type="submit" untuk form).
    - icon / icon-right: nama ikon Bootstrap Icons. loading → spinner + nonaktif.
    - Atribut lain (data-confirm, name, value, form, dll.) diteruskan apa adanya.
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconRight' => null,
    'href' => null,
    'type' => 'button',
    'block' => false,
    'loading' => false,
])
@php
    $kelasVarian = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-outline-secondary',
        'outline' => 'btn-outline-primary',
        'ghost' => 'btn-ghost',
        'danger' => 'btn-danger',
        'danger-outline' => 'btn-outline-danger',
        'success' => 'btn-success',
        'link' => 'btn-link',
    ][$variant] ?? 'btn-primary';
    $kelasUkuran = ['sm' => 'btn-sm', 'lg' => 'btn-lg'][$size] ?? '';
    $kelas = trim("btn {$kelasVarian} {$kelasUkuran}" . ($block ? ' w-100' : ''));
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas]) }}>
        @if ($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
        @if ($iconRight)<i class="bi {{ $iconRight }}" aria-hidden="true"></i>@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $kelas]) }} @disabled($loading) @if ($loading) aria-busy="true" @endif>
        @if ($loading)
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        @elseif ($icon)
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $slot }}
        @if ($iconRight && ! $loading)<i class="bi {{ $iconRight }}" aria-hidden="true"></i>@endif
    </button>
@endif
