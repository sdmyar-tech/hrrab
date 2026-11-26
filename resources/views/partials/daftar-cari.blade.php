{{--
    Kotak cari di bilah filter daftar. Form dikirim lewat Enter atau tombol "Cari" (main.js: [data-cari-otomatis]), tidak otomatis saat mengetik.
    Param: $cariTeks (placeholder umum + contoh isian), $cariLabel (label pembaca layar & tooltip: kolom apa saja yang dicari),
    opsional $cariNama (default "q"), $cariId.
--}}
<div class="input-group input-group-sm">
    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search" aria-hidden="true"></i></span>
    <input type="search" name="{{ $cariNama ?? 'q' }}" @isset($cariId) id="{{ $cariId }}" @endisset
        class="form-control border-start-0" placeholder="{{ $cariTeks }}"
        aria-label="{{ $cariLabel ?? $cariTeks }}" title="{{ $cariLabel ?? $cariTeks }}"
        value="{{ request($cariNama ?? 'q') }}" autocomplete="off" data-cari-otomatis>
    <button type="submit" class="btn btn-outline-secondary" data-tombol-cari title="Cari (atau tekan Enter)">Cari</button>
</div>
