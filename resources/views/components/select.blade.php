{{--
    Pilihan. <x-select name="unit" label="Bagian" :options="['SDM' => 'SDM', 'Keuangan' => 'Keuangan']" placeholder="Pilih bagian" required />
    - options: [nilai => teks]. Atau isi slot dengan <option> sendiri.
    - selected: nilai awal (dikalahkan old()) · field-class: kelas pembungkus.
--}}
@props([
    'name',
    'label' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'help' => null,
    'size' => 'md',
    'required' => false,
    'optional' => false,
    'fieldClass' => '',
])
@php
    $id = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $kunci = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $pesanGalat = isset($errors) ? $errors->first($kunci) : null;
    $nilai = (string) old($kunci, $selected);
    $kelasUkuran = ['sm' => 'form-select-sm', 'lg' => 'form-select-lg'][$size] ?? '';
    $terkait = trim(($pesanGalat ? $id . '-galat ' : '') . ($help && ! $pesanGalat ? $id . '-bantuan' : ''));
@endphp
@if ($label)
<div class="hr-field {{ $fieldClass }}">
    <label for="{{ $id }}" class="form-label {{ $required ? 'required' : '' }}">{{ $label }}@if ($optional) <span class="hr-field-opsional">(opsional)</span>@endif</label>
@endif
    <select name="{{ $name }}" id="{{ $id }}" @if ($required) required @endif
        @if ($pesanGalat) aria-invalid="true" @endif @if ($terkait) aria-describedby="{{ $terkait }}" @endif
        {{ $attributes->merge(['class' => trim("form-select {$kelasUkuran}" . ($pesanGalat ? ' is-invalid' : ''))]) }}>
        @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $nilaiOpsi => $teksOpsi)
            <option value="{{ $nilaiOpsi }}" @selected($nilai === (string) $nilaiOpsi)>{{ $teksOpsi }}</option>
        @endforeach
        {{ $slot }}
    </select>
@if ($label)
    @if ($pesanGalat)
        <div class="invalid-feedback d-block" id="{{ $id }}-galat"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $pesanGalat }}</div>
    @elseif ($help)
        <div class="form-text" id="{{ $id }}-bantuan">{{ $help }}</div>
    @endif
</div>
@endif
