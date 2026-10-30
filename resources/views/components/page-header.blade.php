{{--
    Kepala halaman: (eyebrow) · judul 32px · subjudul 16px · aksi di kanan (utama paling kanan).
    <x-page-header title="Data Pegawai" subtitle="Kelola data seluruh pegawai." eyebrow="Kepegawaian">
        <x-slot:actions>
            <x-button variant="secondary" icon="bi-file-earmark-arrow-down">Ekspor</x-button>
            <x-button icon="bi-person-plus">Tambah Pegawai</x-button>
        </x-slot:actions>
    </x-page-header>
    Slot bawaan (opsional) tampil di bawah subjudul, mis. badge filter aktif. back + back-label: tautan "Kembali ke …" di atas judul.
    Slot media (opsional): elemen di kiri judul, mis. <x-avatar size="xl">.
--}}
@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'back' => null,
    'backLabel' => null,
])
<header {{ $attributes->merge(['class' => 'hr-page-header']) }}>
    @isset($media)<div class="hr-page-header-media">{{ $media }}</div>@endisset
    <div class="hr-page-header-teks">
        @if ($back)
            <a href="{{ $back }}" class="hr-kembali"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali{{ $backLabel ? ' ke ' . $backLabel : '' }}</a>
        @endif
        @if ($eyebrow)<p class="eyebrow mb-1">{{ $eyebrow }}</p>@endif
        <h1 class="hr-page-title">{{ $title }}</h1>
        @if ($subtitle)<p class="hr-page-subtitle">{{ $subtitle }}</p>@endif
        @if (trim($slot) !== '')<div class="hr-page-header-ekstra">{{ $slot }}</div>@endif
    </div>
    @isset($actions)<div class="hr-page-actions">{{ $actions }}</div>@endisset
</header>
