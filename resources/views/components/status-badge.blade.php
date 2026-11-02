{{--
    Badge status dari kelas Bootstrap yang dikembalikan model (badgeClass(): "text-bg-success", "text-bg-warning", …)
    → x-badge bertone semantik yang sama dengan seluruh aplikasi.
    <x-status-badge :badge="$item->badgeClass()">{{ $item->statusLabel() }}</x-status-badge>
--}}
@props([
    'badge' => '',
    'icon' => null,
    'dot' => false,
    'size' => 'md',
])
@php
    $tone = match (true) {
        str_contains($badge, 'success') => 'success',
        str_contains($badge, 'danger') => 'danger',
        str_contains($badge, 'warning') => 'warning',
        str_contains($badge, 'info') => 'info',
        str_contains($badge, 'primary') => 'primary',
        default => 'neutral',
    };
@endphp
<x-badge :tone="$tone" :icon="$icon" :dot="$dot" :size="$size" {{ $attributes }}>{{ $slot }}</x-badge>
