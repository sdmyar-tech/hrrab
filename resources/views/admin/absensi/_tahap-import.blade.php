{{-- Penanda tahapan import. Param: $tahap (1 = Unggah, 2 = Cek, 3 = Selesai) --}}
<div class="abs-tahap mb-3" aria-label="Tahapan import">
    @foreach ([1 => 'Unggah file', 2 => 'Cek data', 3 => 'Selesai'] as $no => $teks)
        @if ($no > 1)<span class="garis" aria-hidden="true"></span>@endif
        <span class="{{ $tahap === $no ? 'aktif' : ($tahap > $no ? 'selesai' : '') }}" @if ($tahap === $no) aria-current="step" @endif>
            <b>@if ($tahap > $no)<i class="bi bi-check-lg"></i>@else{{ $no }}@endif</b>{{ $teks }}
        </span>
    @endforeach
</div>
