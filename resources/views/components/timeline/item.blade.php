{{--
    Satu tahap timeline. state: done (selesai, hijau) · active (sedang berjalan, marun) · stopped (berhenti/ditolak, merah)
    · pending (belum, abu). icon menggantikan ikon bawaan state. meta: teks kecil di samping judul (mis. tanggal).
    Slot: rincian tahap (teks, sub-kartu jadwal, dll.).
--}}
@props([
    'state' => 'pending',
    'title',
    'meta' => null,
    'icon' => null,
])
@php
    $ikon = $icon ?? ['done' => 'bi-check-lg', 'active' => 'bi-arrow-right', 'stopped' => 'bi-x-lg', 'pending' => 'bi-circle'][$state] ?? 'bi-circle';
    $statusSr = ['done' => 'selesai', 'active' => 'sedang berjalan', 'stopped' => 'berhenti', 'pending' => 'belum dimulai'][$state] ?? '';
@endphp
<li {{ $attributes->merge(['class' => 'hr-timeline-item is-' . $state]) }} @if ($state === 'active') aria-current="step" @endif>
    <span class="hr-timeline-penanda" aria-hidden="true"><i class="bi {{ $ikon }}"></i></span>
    <div class="hr-timeline-isi">
        <div class="hr-timeline-kepala">
            <p class="hr-timeline-judul">{{ $title }}<span class="visually-hidden"> ({{ $statusSr }})</span></p>
            @if ($meta)<span class="hr-timeline-meta">{{ $meta }}</span>@endif
        </div>
        @if (trim($slot) !== '')<div class="hr-timeline-teks">{{ $slot }}</div>@endif
    </div>
</li>
