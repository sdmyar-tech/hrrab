{{--
    Menu tarik-turun. Isi slot dengan <a class="dropdown-item">, <button class="dropdown-item">, atau <div class="dropdown-divider"></div>.
    <x-dropdown label="Aksi lain" icon="bi-three-dots" variant="ghost" size="sm" align="end" icon-only>
        <a class="dropdown-item" href="..."><i class="bi bi-pencil"></i>Ubah</a>
    </x-dropdown>
    Atau pemicu kustom: <x-slot:trigger>...</x-slot:trigger> (harus berisi elemen ber-data-bs-toggle="dropdown").
--}}
@props([
    'label' => 'Menu',
    'icon' => null,
    'variant' => 'secondary',
    'size' => 'md',
    'align' => 'end',
    'iconOnly' => false,
])
@php
    $kelasVarian = ['primary' => 'btn-primary', 'secondary' => 'btn-outline-secondary', 'ghost' => 'btn-ghost'][$variant] ?? 'btn-outline-secondary';
    $kelasUkuran = ['sm' => 'btn-sm', 'lg' => 'btn-lg'][$size] ?? '';
@endphp
<div {{ $attributes->merge(['class' => 'dropdown hr-dropdown']) }}>
    @isset($trigger)
        {{ $trigger }}
    @else
        <button type="button" class="btn {{ $kelasVarian }} {{ $kelasUkuran }} {{ $iconOnly ? 'btn-ikon' : 'dropdown-toggle' }}"
            data-bs-toggle="dropdown" aria-expanded="false" @if ($iconOnly) aria-label="{{ $label }}" title="{{ $label }}" @endif>
            @if ($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
            @unless ($iconOnly){{ $label }}@endunless
        </button>
    @endisset
    <div class="dropdown-menu {{ $align === 'end' ? 'dropdown-menu-end' : '' }}">
        {{ $slot }}
    </div>
</div>
