{{--
    Pilihan urutan daftar (tanggal & A–Z). Data dari App\Support\Urutan::terapkan().
    Param: $urutOpsi, $urutAktif, (opsional) $urutForm = id form filter yang sudah ada di halaman.
    Tanpa $urutForm, partial membuat form GET sendiri dan mempertahankan filter lain di URL.
--}}
@php
    $urutSelect = function (?string $form) use ($urutOpsi, $urutAktif) {
        $html = '<select name="urut" class="form-select form-select-sm" aria-label="Urutkan" title="Urutkan"'
            . ($form ? ' form="' . e($form) . '"' : '') . ' onchange="this.form.submit()">';
        foreach ($urutOpsi as $kunci => $label) {
            $html .= '<option value="' . e($kunci) . '"' . ($kunci === $urutAktif ? ' selected' : '') . '>' . e($label) . '</option>';
        }

        return $html . '</select>';
    };
@endphp
@if (! empty($urutForm))
    <div class="input-group input-group-sm urut-pilih">
        <span class="input-group-text"><i class="bi bi-sort-down" aria-hidden="true"></i></span>
        {!! $urutSelect($urutForm) !!}
    </div>
@else
    <form method="GET" class="urut-pilih-form">
        {{-- Pertahankan filter lain (termasuk array) kecuali urutan & halaman. --}}
        @foreach (explode('&', http_build_query(request()->except(['urut', 'page']))) as $pasang)
            @continue($pasang === '')
            @php [$k, $v] = array_pad(explode('=', $pasang, 2), 2, ''); @endphp
            <input type="hidden" name="{{ urldecode($k) }}" value="{{ urldecode($v) }}">
        @endforeach
        <div class="input-group input-group-sm urut-pilih">
            <span class="input-group-text"><i class="bi bi-sort-down" aria-hidden="true"></i></span>
            {!! $urutSelect(null) !!}
        </div>
    </form>
@endif
@once
    <style>
        .urut-pilih { width: auto; flex-wrap: nowrap; }
        .urut-pilih .form-select { min-width: 170px; }
        .urut-pilih-form { display: inline-block; margin: 0; }
        @media (max-width: 575.98px) { .urut-pilih-form, .urut-pilih { width: 100%; } .urut-pilih .form-select { min-width: 0; } }
    </style>
@endonce
