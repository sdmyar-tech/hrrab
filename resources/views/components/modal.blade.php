{{--
    Modal Bootstrap bergaya HRRAB (tengah, sudut 12, latar buram, meluncur ke atas).
    <x-modal id="modalTolak" title="Tolak pengajuan?" size="md" icon="bi-x-octagon" tone="danger">
        ...isi...
        <x-slot:footer>
            <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
            <x-button variant="danger" type="submit" form="formTolak">Ya, Tolak</x-button>
        </x-slot:footer>
    </x-modal>
    Buka dengan: data-bs-toggle="modal" data-bs-target="#modalTolak".
    size: sm (400) · md (480) · lg (640) · xl (800). static: tidak tertutup saat klik luar/Esc.
--}}
@props([
    'id',
    'title',
    'subtitle' => null,
    'icon' => null,
    'tone' => 'primary',
    'size' => 'md',
    'static' => false,
    'scrollable' => false,
])
@php
    $kelasDialog = 'modal-dialog modal-dialog-centered hr-modal-' . $size . ($scrollable ? ' modal-dialog-scrollable' : '');
@endphp
<div {{ $attributes->merge(['class' => 'modal fade hr-modal']) }} id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-judul" aria-hidden="true"
    @if ($static) data-bs-backdrop="static" data-bs-keyboard="false" @endif>
    <div class="{{ $kelasDialog }}">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-start gap-3 min-w-0">
                    @if ($icon)<span class="hr-modal-ikon hr-tone-{{ $tone }}"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>@endif
                    <div class="min-w-0">
                        <h2 class="modal-title" id="{{ $id }}-judul">{{ $title }}</h2>
                        @if ($subtitle)<p class="small text-muted mb-0 mt-1">{{ $subtitle }}</p>@endif
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">{{ $slot }}</div>
            @isset($footer)<div class="modal-footer">{{ $footer }}</div>@endisset
        </div>
    </div>
</div>
