{{--
    Kolom isian lengkap (label + kontrol + bantuan + galat). Nilai lama (old()) & status galat otomatis dari name.
    <x-input name="email" type="email" label="Email" placeholder="Contoh: nama@gmail.com" icon="bi-envelope" required />
    <x-input name="password" type="password" label="Kata Sandi" toggle />   ← tombol tampil/sembunyikan
    - Tanpa label → hanya kontrol (untuk input-group/filter); beri aria-label sendiri.
    - size: sm|md|lg · value: nilai awal (dikalahkan old()) · field-class: kelas pembungkus (mis. "mb-0").
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'id' => null,
    'value' => null,
    'help' => null,
    'icon' => null,
    'toggle' => false,
    'size' => 'md',
    'required' => false,
    'optional' => false,
    'fieldClass' => '',
])
@php
    $id = $id ?? str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $kunci = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $pesanGalat = isset($errors) ? $errors->first($kunci) : null;
    $nilai = $type === 'password' ? null : old($kunci, $value);
    $kelasUkuran = ['sm' => 'form-control-sm', 'lg' => 'form-control-lg'][$size] ?? '';
    $terkait = trim(($pesanGalat ? $id . '-galat ' : '') . ($help && ! $pesanGalat ? $id . '-bantuan' : ''));
    $berkelompok = $icon || ($toggle && $type === 'password');
@endphp
@if ($label)
<div class="hr-field {{ $fieldClass }}">
    <label for="{{ $id }}" class="form-label {{ $required ? 'required' : '' }}">{{ $label }}@if ($optional) <span class="hr-field-opsional">(opsional)</span>@endif</label>
@endif
    @if ($berkelompok)<div class="input-group hr-input-group {{ $pesanGalat ? 'has-validation' : '' }}">@endif
        @if ($icon)<span class="input-group-text"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>@endif
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" @if (! is_null($nilai)) value="{{ $nilai }}" @endif
            @if ($required) required @endif
            @if ($pesanGalat) aria-invalid="true" @endif
            @if ($terkait) aria-describedby="{{ $terkait }}" @endif
            {{ $attributes->merge(['class' => trim("form-control {$kelasUkuran}" . ($pesanGalat ? ' is-invalid' : ''))]) }}>
        @if ($toggle && $type === 'password')
            <button type="button" class="btn hr-lihat-sandi" data-lihat-sandi="{{ $id }}" aria-label="Tampilkan kata sandi" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
        @endif
    @if ($berkelompok)</div>@endif
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
        // Tampil/sembunyikan kata sandi untuk semua isian sandi bertombol [data-lihat-sandi].
        document.addEventListener('click', function (e) {
            const tombol = e.target.closest('[data-lihat-sandi]');
            if (!tombol) return;
            const isian = document.getElementById(tombol.dataset.lihatSandi);
            if (!isian) return;
            const tampil = isian.type === 'password';
            isian.type = tampil ? 'text' : 'password';
            tombol.setAttribute('aria-pressed', String(tampil));
            tombol.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            tombol.querySelector('i').className = tampil ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    </script>
@endonce
