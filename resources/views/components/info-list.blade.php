{{--
    Daftar label–nilai untuk halaman detail (Batch 4): grid 2 kolom di desktop, 1 kolom di HP.
    <x-info-list :items="['NIK' => $p->nik, 'Alamat' => $p->alamat]" />
    Nilai kosong → "Belum diisi" (redup). Nilai panjang (> 60 huruf) memakai lebar penuh.
    Nilai berupa HtmlString (mis. new HtmlString('<a …>')) ditampilkan apa adanya.
--}}
@props(['items' => []])
<dl {{ $attributes->merge(['class' => 'hr-info-list']) }}>
    @foreach ($items as $label => $nilai)
        @php
            $terisi = filled($nilai) && ! ($nilai instanceof \Illuminate\Support\HtmlString && trim((string) $nilai) === '');
            $panjang = is_string($nilai) && mb_strlen($nilai) > 60;
        @endphp
        <div class="hr-info-item {{ $panjang ? 'is-lebar' : '' }}">
            <dt>{{ $label }}</dt>
            <dd class="{{ $terisi ? '' : 'is-kosong' }}">{{ $terisi ? $nilai : 'Belum diisi' }}</dd>
        </div>
    @endforeach
</dl>
