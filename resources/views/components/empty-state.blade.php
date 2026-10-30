{{--
    Keadaan kosong: ikon besar + judul + teks + ajakan (CTA).
    <x-empty-state icon="bi-inbox" title="Belum ada pengajuan">
        Pengajuan cuti & lembur Anda akan tampil di sini.
        <x-slot:action><x-button href="..." icon="bi-plus-lg">Ajukan Cuti</x-button></x-slot:action>
    </x-empty-state>
    compact: versi rapat untuk kartu kecil / sel tabel.
--}}
@props([
    'icon' => 'bi-inbox',
    'title' => null,
    'compact' => false,
])
<div {{ $attributes->merge(['class' => 'hr-empty' . ($compact ? ' hr-empty-compact' : '')]) }}>
    <span class="hr-empty-ikon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
    @if ($title)<p class="hr-empty-judul">{{ $title }}</p>@endif
    @if (trim($slot) !== '')<p class="hr-empty-teks">{{ $slot }}</p>@endif
    @isset($action)<div class="hr-empty-aksi">{{ $action }}</div>@endisset
</div>
