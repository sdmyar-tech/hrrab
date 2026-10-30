{{-- Isi satu tab untuk <x-tabs>. <x-tabs.pane tabs="profil" key="akun" active>...</x-tabs.pane> --}}
@props([
    'tabs',
    'key',
    'active' => false,
])
<div {{ $attributes->merge(['class' => 'tab-pane fade' . ($active ? ' show active' : '')]) }} id="{{ $tabs }}-{{ $key }}"
    role="tabpanel" aria-labelledby="{{ $tabs }}-tab-{{ $key }}" tabindex="0">
    {{ $slot }}
</div>
