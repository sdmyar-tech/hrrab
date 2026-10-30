{{--
    Tabel: garis horizontal saja, header tegas berlatar abu, baris disorot, bergulir di dalam wadahnya.
    <x-table caption="Daftar pengajuan cuti" compact>
        <x-slot:head><tr><th>Nama</th><th class="text-end">Jumlah</th></tr></x-slot:head>
        <tr>...</tr>
    </x-table>
    Aturan perataan: teks rata kiri, angka rata kanan (class="text-end hr-angka"), aksi di kolom terakhir rata kanan.
    Di < 768px main.js otomatis mengubah baris menjadi kartu (label dari <th>).
    compact: sel lebih rapat · sticky: header menempel saat isi digulir (pakai bersama max-height pada wadah).
--}}
@props([
    'caption' => null,
    'compact' => false,
    'sticky' => false,
    'id' => null,
])
<div {{ $attributes->merge(['class' => 'table-responsive hr-table-wrap']) }}>
    <table class="table align-middle mb-0 hr-table {{ $compact ? 'hr-table-compact' : '' }}" @if ($id) id="{{ $id }}" @endif>
        @if ($caption)<caption class="visually-hidden">{{ $caption }}</caption>@endif
        @isset($head)<thead class="{{ $sticky ? 'sticky-top' : '' }}">{{ $head }}</thead>@endisset
        <tbody>{{ $slot }}</tbody>
        @isset($foot)<tfoot>{{ $foot }}</tfoot>@endisset
    </table>
</div>
