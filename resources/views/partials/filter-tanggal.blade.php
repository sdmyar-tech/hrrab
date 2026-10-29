{{--
    Filter rentang tanggal untuk bilah filter daftar (App\Support\FilterTanggal): [Label] [dari] s/d [sampai].
    Param: $tanggalLabel (singkat, mis. "Tgl masuk"), opsional $tanggalForm (id form bila kontrol di luar <form>).
    Halaman dikirim ulang saat tanggal diganti.
--}}
<div class="input-group input-group-sm filter-tanggal" role="group" aria-label="Saring {{ mb_strtolower($tanggalLabel) }}">
    <span class="input-group-text" title="{{ $tanggalLabel }}"><i class="bi bi-calendar-range" aria-hidden="true"></i><span class="filter-tanggal-teks ms-1">{{ $tanggalLabel }}</span></span>
    <input type="date" name="tanggal_dari" class="form-control" value="{{ request('tanggal_dari') }}"
        aria-label="{{ $tanggalLabel }} dari" title="{{ $tanggalLabel }} dari" @isset($tanggalForm) form="{{ $tanggalForm }}" @endisset
        onchange="this.form.submit()">
    <span class="input-group-text">s/d</span>
    <input type="date" name="tanggal_sampai" class="form-control" value="{{ request('tanggal_sampai') }}"
        aria-label="{{ $tanggalLabel }} sampai" title="{{ $tanggalLabel }} sampai" @isset($tanggalForm) form="{{ $tanggalForm }}" @endisset
        onchange="this.form.submit()">
</div>
@once
    <style>
        .filter-tanggal { flex-wrap: nowrap; }
        .filter-tanggal .form-control { min-width: 0; }
        .filter-tanggal .input-group-text { white-space: nowrap; }
        /* HP: cukup ikon kalender (label tetap dibacakan lewat aria-label) agar dua tanggal muat satu baris. */
        @media (max-width: 575.98px) { .filter-tanggal .filter-tanggal-teks { display: none; } }
    </style>
@endonce
