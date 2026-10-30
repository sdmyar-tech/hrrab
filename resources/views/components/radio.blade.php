{{--
    Grup pilihan tunggal. <x-radio name="jenis" label="Jenis Cuti" :options="['tahunan' => 'Tahunan', 'sakit' => 'Sakit']" selected="tahunan" inline />
    Dirender sebagai <fieldset> + <legend> agar pembaca layar membacakan judul grup.
--}}
@props([
    'name',
    'options' => [],
    'label' => null,
    'selected' => null,
    'inline' => false,
    'required' => false,
    'help' => null,
])
@php
    $kunci = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $nilai = (string) old($kunci, $selected);
    $galat = isset($errors) ? $errors->first($kunci) : null;
    $dasarId = str_replace(['[', ']', '.'], ['-', '', '-'], $name);
@endphp
<fieldset {{ $attributes->merge(['class' => 'hr-field']) }}>
    @if ($label)<legend class="form-label {{ $required ? 'required' : '' }}">{{ $label }}</legend>@endif
    <div class="{{ $inline ? 'd-flex flex-wrap gap-3' : 'd-grid gap-2' }}">
        @foreach ($options as $nilaiOpsi => $teksOpsi)
            <div class="form-check hr-check m-0">
                <input type="radio" class="form-check-input {{ $galat ? 'is-invalid' : '' }}" name="{{ $name }}" id="{{ $dasarId }}-{{ $loop->index }}"
                    value="{{ $nilaiOpsi }}" @checked($nilai === (string) $nilaiOpsi) @if ($required) required @endif>
                <label class="form-check-label" for="{{ $dasarId }}-{{ $loop->index }}">{{ $teksOpsi }}</label>
            </div>
        @endforeach
    </div>
    @if ($galat)
        <div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $galat }}</div>
    @elseif ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</fieldset>
