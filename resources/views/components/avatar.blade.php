{{--
    Foto profil bundar dengan cadangan inisial (bila tidak ada foto / foto gagal dimuat).
    <x-avatar :src="$user->fotoUrl()" :name="$user->name" size="sm|md|lg|xl" />
    Ukuran: sm 32 · md 40 · lg 56 · xl 80. Foto bawaan (avatar-fallback) diperlakukan sebagai "tanpa foto" → inisial.
--}}
@props([
    'src' => null,
    'name' => '',
    'size' => 'md',
])
@php
    $bersih = trim((string) \Illuminate\Support\Str::of((string) $name)->before(','));
    $kata = array_values(array_filter(preg_split('/\s+/u', preg_replace('/[^\pL\s]/u', '', $bersih)) ?: []));
    $inisial = mb_strtoupper(mb_substr($kata[0] ?? '?', 0, 1) . mb_substr($kata[1] ?? '', 0, 1));
    $adaFoto = $src && ! str_contains((string) $src, 'avatar-fallback');
    // Warna latar inisial tetap per nama (6 rona lembut dari palet status/primer).
    $rona = ['primary', 'info', 'success', 'warning', 'neutral', 'danger'][abs(crc32($bersih)) % 6];
@endphp
<span {{ $attributes->merge(['class' => "hr-avatar hr-avatar-{$size} hr-tone-{$rona}"]) }} @if (! $adaFoto) role="img" aria-label="{{ $bersih }}" @endif>
    @if ($adaFoto)
        <img src="{{ $src }}" alt="" loading="lazy" decoding="async"
            onerror="this.remove(); this.parentElement.dataset.gagal = '1';">
    @endif
    <span class="hr-avatar-inisial" aria-hidden="true">{{ $inisial }}</span>
</span>
