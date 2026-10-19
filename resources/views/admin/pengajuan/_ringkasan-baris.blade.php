{{-- Ringkasan singkat di bawah baris daftar (pengajuan yang menunggu keputusan Anda): angka, peringatan, alur mini, riwayat; rincian lengkap bisa dibuka. --}}
@include('admin.pengajuan._ringkasan-gaya')
@php
    $ikonAlur = ['selesai' => 'bi-check-circle-fill', 'berjalan' => 'bi-hourglass-split', 'menunggu' => 'bi-circle', 'ditolak' => 'bi-x-circle-fill', 'lewati' => 'bi-dash-circle'];
@endphp
<tr class="rk-baris">
    <td colspan="{{ $kolom }}">
        <div class="rk-ringkas">
            <div class="rk-chips">
                @foreach ($r['angka'] as $a)
                    <span class="rk-chip {{ $a['nada'] }}">{{ $a['label'] }}: <b>{{ $a['nilai'] }}</b>@if ($a['catatan']) <span class="rk-catatan">({{ $a['catatan'] }})</span>@endif</span>
                @endforeach
            </div>
            @foreach ($r['peringatan'] as $p)
                <div class="rk-peringatan kecil"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ $p }}</span></div>
            @endforeach
            <div class="rk-alur-mini">
                @foreach ($r['alur'] as $l)
                    <span class="{{ $l['status'] }}" title="{{ trim(($l['oleh'] ?? '') . ' ' . ($l['waktu'] ?? '')) }}"><i class="bi {{ $ikonAlur[$l['status']] }}"></i>{{ $l['label'] }}@if ($l['status'] === 'selesai' && $l['oleh'] && ! $loop->first) <span class="rk-catatan">({{ $l['oleh'] }})</span>@endif</span>
                    @unless ($loop->last)<i class="bi bi-chevron-right rk-panah"></i>@endunless
                @endforeach
            </div>
            <div class="rk-riwayat-mini">
                <span class="rk-label">{{ $r['riwayatJudul'] }}:</span>
                @forelse (array_slice($r['riwayat'], 0, 3) as $h)
                    <span>{{ $h['utama'] }} <span class="badge {{ $h['kelas'] }}">{{ $h['status'] }}</span></span>
                @empty
                    <span class="rk-catatan">belum ada</span>
                @endforelse
            </div>
            <details class="rk-lengkap">
                <summary><i class="bi bi-card-list me-1"></i>Rincian lengkap</summary>
                @include('admin.pengajuan._ringkasan', ['r' => $r])
            </details>
        </div>
    </td>
</tr>
