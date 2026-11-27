{{--
    Isi jendela "Detail" pada kolom Aksi (dimuat lewat fetch, tanpa membuka halaman baru).
    Param: $pegawai, $rows (RiwayatKontrak::untuk), opsional $saatIni (EvaluasiKontrak), $urlLengkap (halaman riwayat), $urlEvaluasi.
--}}
@php
    $k = $pegawai->kepegawaian;
    $jumlah = collect($rows)->whereNotNull('ke')->count();
    $adaCatatan = \App\Models\KontrakPegawai::tersedia() && $pegawai->kontrak()->exists();
    $saatIni = $saatIni ?? null;
@endphp
<div class="d-flex flex-wrap gap-2 mb-3">
    <x-badge tone="info" icon="bi-journal-text">Dikontrak {{ $jumlah }} kali</x-badge>
    @if ($jumlah > 1)<x-badge tone="neutral">{{ $jumlah - 1 }} kali diperpanjang</x-badge>@endif
    @if ($k?->status_kerja)<x-badge tone="{{ $k->status_kerja === 'Tetap' ? 'success' : 'neutral' }}">{{ $k->status_kerja }}</x-badge>@endif
</div>
<x-info-list :items="array_filter([
    'Jabatan / bagian' => collect([$k?->jabatan, $k?->unit_kerja])->filter()->implode(' · '),
    'Tanggal masuk (TMT)' => $k?->tanggal_masuk?->translatedFormat('d F Y'),
    'Habis kontrak (Data Pegawai)' => $k?->tanggal_habis_kontrak?->translatedFormat('d F Y'),
])" />

<h3 class="h6 fw-bold mt-3 mb-1">Riwayat kontrak</h3>
<p class="hr-sel-sub mb-2">
    @if ($adaCatatan)
        Berdasarkan catatan riwayat kontrak pegawai.
    @else
        Dihitung dari evaluasi di sistem; perpanjangan sebelumnya belum tercatat.
    @endif
</p>
@include('admin.riwayat-kontrak._daftar', ['rows' => $rows, 'saatIni' => $saatIni])

<div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
    @if (! empty($urlEvaluasi))
        <a href="{{ $urlEvaluasi }}" class="btn btn-sm btn-primary"><i class="bi bi-clipboard-check me-1" aria-hidden="true"></i>Buka evaluasi</a>
    @endif
    @if (! empty($urlLengkap))
        <a href="{{ $urlLengkap }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-journal-bookmark me-1" aria-hidden="true"></i>{{ $adaCatatan ? 'Lihat / ubah riwayat' : 'Lengkapi riwayat kontrak' }}</a>
    @endif
</div>
