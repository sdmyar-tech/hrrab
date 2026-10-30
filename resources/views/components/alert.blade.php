{{--
    Pesan di halaman (banner). tone: info · success · warning · danger.
    <x-alert tone="warning" title="Data belum lengkap" dismissible>
        Lengkapi NIK dan No. KK.
        <x-slot:action><x-button size="sm" variant="secondary" href="...">Lengkapi</x-button></x-slot:action>
    </x-alert>
    Galat (danger) diberi role="alert" (dibacakan segera); lainnya role="status".
--}}
@props([
    'tone' => 'info',
    'title' => null,
    'icon' => null,
    'dismissible' => false,
])
@php
    $ikon = $icon ?? [
        'info' => 'bi-info-circle-fill',
        'success' => 'bi-check-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'danger' => 'bi-x-octagon-fill',
    ][$tone] ?? 'bi-info-circle-fill';
@endphp
<div {{ $attributes->merge(['class' => "alert alert-{$tone} hr-alert" . ($dismissible ? ' alert-dismissible fade show' : '')]) }}
    role="{{ $tone === 'danger' ? 'alert' : 'status' }}">
    <i class="bi {{ $ikon }} hr-alert-ikon" aria-hidden="true"></i>
    <div class="hr-alert-isi">
        @if ($title)<p class="hr-alert-judul">{{ $title }}</p>@endif
        <div>{{ $slot }}</div>
    </div>
    @isset($action)<div class="hr-alert-aksi">{{ $action }}</div>@endisset
    @if ($dismissible)<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup pesan"></button>@endif
</div>
