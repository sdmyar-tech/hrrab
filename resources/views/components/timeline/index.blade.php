{{--
    Timeline vertikal (tahapan proses). Isi dengan <x-timeline.item>.
    <x-timeline label="Tahapan lamaran">
        <x-timeline.item state="done" title="Verifikasi data" meta="12 Okt 2026">Terverifikasi.</x-timeline.item>
        <x-timeline.item state="active" title="Interview">...</x-timeline.item>
        <x-timeline.item state="pending" title="Keputusan akhir" />
    </x-timeline>
--}}
@props(['label' => 'Tahapan'])
<ol {{ $attributes->merge(['class' => 'hr-timeline']) }} aria-label="{{ $label }}">
    {{ $slot }}
</ol>
