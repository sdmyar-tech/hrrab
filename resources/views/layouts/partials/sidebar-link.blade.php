{{-- Satu link menu sidebar. $item = [label, ikon, route, polaAktif, tampil, badge, (opsional) badgePeringatan, (opsional) eksternal] --}}
@php
    [$teks, $ikonMenu, $tujuan, $pola, , $jumlah] = $item;
    $peringatan = $item[6] ?? false;
    $eksternal = $item[7] ?? false;
    $aktif = ! $eksternal && request()->routeIs($pola);
@endphp
<a class="nav-link {{ $aktif ? 'active' : '' }}" href="{{ route($tujuan) }}" title="{{ $teks }}"
    @if ($aktif) aria-current="page" @endif>
    <span class="nav-icon"><i class="bi {{ $ikonMenu }}" aria-hidden="true"></i></span>
    <span class="nav-text">{{ $teks }}</span>
    @if ($eksternal)
        <i class="bi bi-box-arrow-up-right nav-ext" aria-hidden="true"></i>
    @endif
    @if ($jumlah)
        <span class="nav-badge {{ $peringatan ? 'is-warning' : '' }}" aria-label="{{ $jumlah }} perlu perhatian">{{ $jumlah > 99 ? '99+' : $jumlah }}</span>
    @endif
</a>
