{{--
    Tombol ikon saja + tooltip (Batch 4), untuk kolom aksi tabel: lihat / ubah / hapus.
    <x-icon-button icon="bi-eye" label="Lihat detail" :href="route('admin.pegawai.show', $p)" />
    <x-icon-button icon="bi-pencil" label="Ubah" data-bs-toggle="modal" data-bs-target="#modalUbah" data-url="…" />
    <form method="POST" action="…">@csrf @method('DELETE')
        <x-icon-button type="submit" icon="bi-trash" label="Hapus" tone="danger" data-confirm="true" data-title="Hapus …?" />
    </form>
    label wajib: dipakai untuk aria-label & tooltip (data-hr-tooltip, diaktifkan main.js). tone: primary | danger | warning.
--}}
@props([
    'icon',
    'label',
    'href' => null,
    'type' => 'button',
    'tone' => null,
])
@php
    $kelas = 'btn hr-ikon-btn' . ($tone ? ' hr-ikon-btn-' . $tone : '');
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas, 'aria-label' => $label, 'data-hr-tooltip' => $label]) }}><i class="bi {{ $icon }}" aria-hidden="true"></i></a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $kelas, 'aria-label' => $label, 'data-hr-tooltip' => $label]) }}><i class="bi {{ $icon }}" aria-hidden="true"></i></button>
@endif
