{{-- Satu link menu sidebar. $item = [label, ikon, route, polaAktif, tampil, badge, (opsional) badgePeringatan, (opsional) eksternal]; $bagian = nama menu induk (untuk palet Cari menu). --}}
@php
    [$teks, $ikonMenu, $tujuan, $pola, , $jumlah] = $item;
    $peringatan = $item[6] ?? false;
    $eksternal = $item[7] ?? false;
    $aktif = ! $eksternal && request()->routeIs($pola);
@endphp
<a class="app-nav-item {{ $aktif ? 'is-active' : '' }}" href="{{ route($tujuan) }}" title="{{ $teks }}"
    data-bagian="{{ $bagian ?? '' }}" @if ($aktif) aria-current="page" @endif>
    <i class="bi {{ $ikonMenu }} app-nav-ikon" aria-hidden="true"></i>
    <span class="app-nav-teks">{{ $teks }}</span>
    @if ($eksternal)
        <i class="bi bi-box-arrow-up-right app-nav-ext" aria-hidden="true"></i>
    @endif
    @if ($jumlah)
        <span class="app-nav-badge {{ $peringatan ? 'is-warning' : '' }}" aria-label="{{ $jumlah }} perlu perhatian">{{ $jumlah > 99 ? '99+' : $jumlah }}</span>
    @endif
</a>
