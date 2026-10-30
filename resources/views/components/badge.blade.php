{{--
    Label status berbentuk pill dengan warna semantik:
    success = sukses/hadir/aktif · warning = menunggu/terlambat · danger = gagal/absen/ditolak · neutral = nonaktif/dibatalkan · info · primary.
    <x-badge tone="success" icon="bi-check2" dot>Disetujui</x-badge>
--}}
@props([
    'tone' => 'neutral',
    'icon' => null,
    'dot' => false,
])
<span {{ $attributes->merge(['class' => 'hr-badge hr-badge-' . $tone]) }}>
    @if ($dot)<span class="hr-badge-dot" aria-hidden="true"></span>@endif
    @if ($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
    {{ $slot }}
</span>
