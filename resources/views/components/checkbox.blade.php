{{--
    Kotak centang. <x-checkbox name="setuju" value="1" label="Saya menyetujui ketentuan" :checked="true" help="..." />
    Nilai lama (old) dihormati: tercentang bila old(name) sama dengan value (atau ada di array untuk name[]).
--}}
@props([
    'name',
    'label',
    'id' => null,
    'value' => '1',
    'checked' => false,
    'help' => null,
])
@php
    $id = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name) . '-' . \Illuminate\Support\Str::slug((string) $value);
    $kunci = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $lama = old($kunci);
    $tercentang = is_null($lama) ? (bool) $checked : (is_array($lama) ? in_array((string) $value, array_map('strval', $lama), true) : (string) $lama === (string) $value);
    $galat = isset($errors) && $errors->has($kunci);
@endphp
<div class="form-check hr-check">
    <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @checked($tercentang)
        @if ($galat) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => 'form-check-input' . ($galat ? ' is-invalid' : '')]) }}>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    @if ($help)<div class="form-text mt-0">{{ $help }}</div>@endif
</div>
