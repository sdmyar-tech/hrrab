{{--
    Bilah filter daftar (Batch 4): cari + saringan (pilihan/chip/tanggal/urutan) + ringkasan jumlah + Reset filter.
    Dipasang paling atas di dalam <x-card :padding="false">, tepat sebelum <x-table>.

    Server (form GET, cari otomatis 0,5 dtk):
        <x-filter-bar :action="route('admin.pegawai.index')" id="filterPegawai" search="Cari pegawai, contoh: Siti"
            :count="$pegawais->total()" unit="pegawai" :filtered="$adaFilter" :reset="route('admin.pegawai.index')">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">…</select>
        </x-filter-bar>
    Klien (tanpa muat ulang, baris [data-cari] di dalam target):
        <x-filter-bar target="#daftar-unit" search="Cari unit, contoh: TK" :count="$unit->count()" unit="unit" />
    Slot opsional: chips (baris chip saring cepat), hidden (input tersembunyi), summary (isi tambahan di baris ringkasan).
    search-id: id kotak cari (untuk skrip halaman sendiri; tanpa target maka tidak memakai data-saring global).
--}}
@props([
    'action' => null,
    'id' => null,
    'search' => null,
    'searchLabel' => null,
    'searchName' => 'q',
    'searchId' => null,
    'target' => null,
    'count' => null,
    'unit' => 'data',
    'filtered' => false,
    'reset' => null,
])
@php
    $tag = $action ? 'form' : 'div';
    $kataCari = $action ? (string) request($searchName) : '';
@endphp
<{{ $tag }} {{ $attributes->merge(['class' => 'hr-filter-bar' . ($action ? ' panel-filter' : '')]) }}
    @if ($action) action="{{ $action }}" method="GET" role="search" @endif @if ($id) id="{{ $id }}" @endif>
    {{ $hidden ?? '' }}
    @if ($search || trim($slot) !== '')
        <div class="hr-filter-bar-baris">
            @if ($search)
                <div class="input-group input-group-sm hr-filter-cari">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input type="search" class="form-control" placeholder="{{ $search }}" aria-label="{{ $searchLabel ?? $search }}"
                        title="{{ $searchLabel ?? $search }}" autocomplete="off" @if ($searchId) id="{{ $searchId }}" @endif
                        @if ($action) name="{{ $searchName }}" value="{{ $kataCari }}" data-cari-otomatis @elseif ($target) data-saring="{{ $target }}" @endif>
                </div>
            @endif
            {{ $slot }}
        </div>
    @endif
    @isset($chips)
        <div class="hr-filter-bar-chips" role="group" aria-label="Saring cepat">{{ $chips }}</div>
    @endisset
    @if ($count !== null || isset($summary))
        <div class="hr-filter-bar-ringkas">
            @if ($count !== null)
                <p class="hr-filter-bar-jumlah" aria-live="polite">
                    @if ($target)
                        <strong data-saring-jumlah="{{ $target }}">{{ number_format($count, 0, ',', '.') }}</strong> dari {{ number_format($count, 0, ',', '.') }} {{ $unit }}
                    @else
                        <strong>{{ number_format($count, 0, ',', '.') }}</strong> {{ $unit }}{{ $filtered ? ' ditemukan' : '' }}
                        @if ($kataCari !== '') untuk "<strong>{{ $kataCari }}</strong>" @endif
                    @endif
                </p>
            @endif
            {{ $summary ?? '' }}
            @if ($filtered && $reset)
                <a href="{{ $reset }}" class="btn btn-ghost btn-sm hr-filter-reset"><i class="bi bi-x-circle" aria-hidden="true"></i>Reset filter</a>
            @endif
        </div>
    @endif
</{{ $tag }}>
