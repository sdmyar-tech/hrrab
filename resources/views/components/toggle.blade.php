{{--
    Sakelar hidup/mati. <x-toggle name="aktif" label="Akun aktif" :checked="$user->aktif" help="Akun nonaktif tidak bisa masuk." />
    Mengirim value (default "1") saat hidup; sertakan hidden "0" sendiri bila server butuh nilai saat mati (atau pakai with-hidden).
--}}
@props([
    'name',
    'label',
    'id' => null,
    'value' => '1',
    'checked' => false,
    'help' => null,
    'withHidden' => false,
])
@php
    $id = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $kunci = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $lama = old($kunci);
    $hidup = is_null($lama) ? (bool) $checked : (string) $lama === (string) $value;
@endphp
<div class="form-check form-switch hr-toggle">
    @if ($withHidden)<input type="hidden" name="{{ $name }}" value="0">@endif
    <input type="checkbox" role="switch" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @checked($hidup)
        {{ $attributes->merge(['class' => 'form-check-input']) }}>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    @if ($help)<div class="form-text mt-0">{{ $help }}</div>@endif
</div>
