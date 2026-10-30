{{--
    Pembungkus isian untuk kontrol kustom (mis. input-group, unggah berkas, pemilih pegawai):
    <x-form-group label="Lampiran" for="lampiran" name="lampiran" required help="PDF/JPG maks. 2 MB">
        <input type="file" id="lampiran" name="lampiran" class="form-control @error('lampiran') is-invalid @enderror">
    </x-form-group>
    - name: kunci galat ($errors) yang ditampilkan di bawah kontrol (dengan ikon, warna merah).
    - Untuk input/select/textarea biasa pakai <x-input>/<x-select>/<x-textarea> (sudah berlabel & bergalat sendiri).
--}}
@props([
    'label' => null,
    'for' => null,
    'name' => null,
    'required' => false,
    'help' => null,
    'optional' => false,
])
@php
    $kunciGalat = $name ? str_replace(['[]', '[', ']'], ['', '.', ''], $name) : null;
    $pesanGalat = $kunciGalat && isset($errors) ? $errors->first($kunciGalat) : null;
@endphp
<div {{ $attributes->merge(['class' => 'hr-field']) }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="form-label {{ $required ? 'required' : '' }}">{{ $label }}@if ($optional) <span class="hr-field-opsional">(opsional)</span>@endif</label>
    @endif
    {{ $slot }}
    @if ($pesanGalat)
        <div class="invalid-feedback d-block" @if ($for) id="{{ $for }}-galat" @endif><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $pesanGalat }}</div>
    @elseif ($help)
        <div class="form-text" @if ($for) id="{{ $for }}-bantuan" @endif>{{ $help }}</div>
    @endif
</div>
