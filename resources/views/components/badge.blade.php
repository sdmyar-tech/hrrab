{{--
    Label status berbentuk pill dengan warna semantik:
    success = sukses/hadir/aktif · warning = menunggu/terlambat · danger = gagal/absen/ditolak · neutral = nonaktif/dibatalkan · info · primary.
    <x-badge tone="success" icon="bi-check2" dot>Disetujui</x-badge>
    size="lg": badge besar untuk status utama di kepala halaman (varian ditambahkan di Batch 2).
--}}
@props([
    'tone' => 'neutral',
    'icon' => null,
    'dot' => false,
    'size' => 'md',
])
<span {{ $attributes->merge(['class' => 'hr-badge hr-badge-' . $tone . ($size === 'lg' ? ' hr-badge-lg' : '')]) }}>
    @if ($dot)<span class="hr-badge-dot" aria-hidden="true"></span>@endif
    @if ($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
    {{ $slot }}
</span>
