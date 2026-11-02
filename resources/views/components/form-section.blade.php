{{--
    Bagian form (Batch 4): judul + keterangan, lalu isian grid 12 kolom (2 kolom di desktop: col-md-6).
    Beberapa x-form-section di dalam satu <x-card> otomatis dipisah garis.
    <x-form-section title="Data Pribadi" subtitle="Sesuai KTP." icon="bi-person" id="bagian-pribadi">
        <div class="col-md-6"><x-input name="nama" label="Nama" /></div>
    </x-form-section>
--}}
@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'id' => null,
])
@php
    $idJudul = ($id ?: 'bagian-' . \Illuminate\Support\Str::slug($title)) . '-judul';
@endphp
<section {{ $attributes->merge(['class' => 'hr-form-section']) }} @if ($id) id="{{ $id }}" @endif aria-labelledby="{{ $idJudul }}">
    <header class="hr-form-section-kepala">
        <h2 class="hr-form-section-judul" id="{{ $idJudul }}">
            @if ($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif{{ $title }}
        </h2>
        @if ($subtitle)<p class="hr-form-section-sub">{{ $subtitle }}</p>@endif
    </header>
    <div class="row g-3">{{ $slot }}</div>
</section>
