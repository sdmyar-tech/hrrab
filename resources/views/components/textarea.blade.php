{{--
    Isian panjang. <x-textarea name="alasan" label="Alasan" rows="4" placeholder="Tulis alasan, contoh: acara keluarga" maxlength="500" />
    maxlength diisi → penghitung karakter tampil di kanan bawah. field-class: kelas pembungkus.
--}}
@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'rows' => 3,
    'help' => null,
    'required' => false,
    'optional' => false,
    'fieldClass' => '',
])
@php
    $id = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $kunci = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $pesanGalat = isset($errors) ? $errors->first($kunci) : null;
    $isi = old($kunci, $value);
    $batas = $attributes->get('maxlength');
    $terkait = trim(($pesanGalat ? $id . '-galat ' : '') . ($help && ! $pesanGalat ? $id . '-bantuan' : ''));
@endphp
@if ($label)
<div class="hr-field {{ $fieldClass }}">
    <label for="{{ $id }}" class="form-label {{ $required ? 'required' : '' }}">{{ $label }}@if ($optional) <span class="hr-field-opsional">(opsional)</span>@endif</label>
@endif
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}" @if ($required) required @endif
        @if ($pesanGalat) aria-invalid="true" @endif @if ($terkait) aria-describedby="{{ $terkait }}" @endif
        @if ($batas) data-hitung-karakter="{{ $id }}-hitung" @endif
        {{ $attributes->merge(['class' => 'form-control' . ($pesanGalat ? ' is-invalid' : '')]) }}>{{ $isi }}</textarea>
    @if ($batas)
        <div class="hr-hitung" id="{{ $id }}-hitung" aria-live="polite">{{ mb_strlen((string) $isi) }}/{{ $batas }}</div>
    @endif
@if ($label)
    @if ($pesanGalat)
        <div class="invalid-feedback d-block" id="{{ $id }}-galat"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $pesanGalat }}</div>
    @elseif ($help)
        <div class="form-text" id="{{ $id }}-bantuan">{{ $help }}</div>
    @endif
</div>
@endif
@once
    <script>
        document.addEventListener('input', function (e) {
            const t = e.target.closest('[data-hitung-karakter]');
            if (!t) return;
            const el = document.getElementById(t.dataset.hitungKarakter);
            if (el) el.textContent = t.value.length + '/' + t.maxLength;
        });
    </script>
@endonce
