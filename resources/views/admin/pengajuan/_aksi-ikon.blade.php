{{--
    Kolom aksi baris daftar pengajuan (Batch 5, pola seragam cuti / lembur / izin keluar / formasi):
    [Setujui] [Tolak] (hanya bila Anda penyetuju tahap ini) · [tambahan…] · [Lihat detail] — selalu sejajar antarbaris.
    Parameter: $item, $detail (URL halaman detail) ATAU $detailData (id <template> → dialog detail, untuk formasi),
    $setujui, $tolak (URL), $judul ("pengajuan cuti"), $konteks ([[label, nilai], …]),
    opsional $pengganti (koleksi nama → wajib pilih pengganti tugas), $tambahan ([[url, label, ikon]]),
    $formAksi ([[url, label, ikon, tone, method, judulKonfirmasi, teksKonfirmasi, kataKonfirmasi]]) untuk aksi berupa form (Batalkan, Hapus),
    $slotTambahan (jumlah slot tambahan halaman ini agar ikon sejajar; bawaan 0),
    $bisaProses (bawaan: pending & PengajuanWorkflow::canAct).
--}}
@php
    $bisaProses ??= $item->isPending() && \App\Support\PengajuanWorkflow::canAct(auth()->user(), $item);
    $tambahan ??= [];
    $formAksi ??= [];
    $slotTambahan ??= 0;
    $konteksJson = json_encode($konteks, JSON_UNESCAPED_UNICODE);
@endphp
<div class="hr-aksi-baris">
    @if ($bisaProses)
        <x-icon-button icon="bi-check-lg" tone="success" label="Setujui" data-persetujuan="setujui" :data-url="$setujui"
            :data-judul="$judul" :data-konteks="$konteksJson" :data-pengganti="isset($pengganti) ? json_encode(array_values(collect($pengganti)->all()), JSON_UNESCAPED_UNICODE) : null" />
        <x-icon-button icon="bi-x-lg" tone="danger" label="Tolak" data-persetujuan="tolak" :data-url="$tolak"
            :data-judul="$judul" :data-konteks="$konteksJson" />
    @else
        {{-- Pengganti selebar dua tombol agar ikon Lihat tetap sejajar dengan baris yang bisa diproses. --}}
        <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
        <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
    @endif
    @foreach ($tambahan as [$url, $label, $ikon])
        <x-icon-button :icon="$ikon" :label="$label" :href="$url" tone="primary" />
    @endforeach
    @foreach ($formAksi as [$url, $label, $ikon, $tone, $method, $judulKonfirmasi, $teksKonfirmasi, $kataKonfirmasi])
        <form action="{{ $url }}" method="POST">
            @csrf
            @if (strtoupper($method) !== 'POST') @method($method) @endif
            <x-icon-button type="submit" :icon="$ikon" :label="$label" :tone="$tone" data-confirm="true" data-icon="warning"
                :data-title="$judulKonfirmasi" :data-text="$teksKonfirmasi" :data-confirm-text="'Ya, ' . $kataKonfirmasi" data-cancel-text="Kembali" />
        </form>
    @endforeach
    @for ($i = count($tambahan) + count($formAksi); $i < $slotTambahan; $i++)
        <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
    @endfor
    @if (isset($detailData))
        <x-icon-button icon="bi-eye" label="Lihat detail" :data-detail="$detailData" />
    @else
        <x-icon-button icon="bi-eye" label="Lihat detail" :href="$detail" />
    @endif
</div>
