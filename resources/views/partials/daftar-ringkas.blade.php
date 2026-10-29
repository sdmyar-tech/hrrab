{{--
    Baris di bawah filter daftar: jumlah data (+ kata kunci pencarian) dan tautan "Tampilkan semua" bila ada saringan aktif.
    Param: $ringkasJumlah (int), $ringkasSatuan (mis. "pegawai"), $ringkasFilter (bool), $ringkasReset (URL tanpa filter).
--}}
<div class="d-flex flex-wrap align-items-center gap-2 mt-2">
    <p class="small text-muted mb-0" aria-live="polite">
        <strong class="text-body">{{ number_format($ringkasJumlah, 0, ',', '.') }}</strong> {{ $ringkasSatuan }}{{ $ringkasFilter ? ' ditemukan' : '' }}
        @if (request('q'))
            untuk "<strong class="text-body">{{ request('q') }}</strong>"
        @endif
    </p>
    @if ($ringkasFilter)
        <a href="{{ $ringkasReset }}" class="btn btn-link btn-sm"><i class="bi bi-x-circle" aria-hidden="true"></i>Tampilkan semua</a>
    @endif
</div>
