{{--
    Timeline vertikal riwayat persetujuan (Batch 5) dari RingkasanPengajuan::…()['alur'].
    Parameter: $alur. Status: selesai → done · berjalan → active · ditolak → stopped · menunggu/lewati → pending.
--}}
@php
    $state = ['selesai' => 'done', 'berjalan' => 'active', 'ditolak' => 'stopped', 'menunggu' => 'pending', 'lewati' => 'pending'];
@endphp
<x-card title="Riwayat Persetujuan" icon="bi-clock-history" subtitle="Urutan tahap dari pengajuan sampai keputusan akhir.">
    <x-timeline>
        @foreach ($alur as $l)
            <x-timeline.item :state="$state[$l['status']] ?? 'pending'" :title="$l['label']" :meta="$l['waktu']"
                :icon="$l['status'] === 'lewati' ? 'bi-dash' : null">
                @if ($l['oleh'])
                    <span class="d-block">{{ in_array($l['status'], ['menunggu', 'berjalan'], true) ? 'Penyetuju: ' : '' }}{{ $l['oleh'] }}</span>
                @endif
                @if ($l['catatan'])
                    <span class="d-block {{ $l['status'] === 'ditolak' ? 'text-danger' : 'text-muted' }}">{{ $l['status'] === 'ditolak' ? 'Alasan: ' : '' }}{{ $l['catatan'] }}</span>
                @endif
            </x-timeline.item>
        @endforeach
    </x-timeline>
</x-card>
