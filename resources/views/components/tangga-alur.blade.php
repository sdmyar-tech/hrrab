{{--
    Tangga alur persetujuan (stepper): pengaju → tahap-tahap penyetuju → hasil akhir.
    <x-tangga-alur :tahap="[['label' => 'Koordinator SDM', 'oleh' => 'Mimi', 'status' => 'selesai', 'final' => false, 'semua' => false, 'kosong' => false, 'catatan' => null], ...]"
                   awal="Pegawai SDM" akhir="Disetujui" arah="horizontal|vertikal" />
    - status per tahap (opsional): selesai · berjalan · menunggu · ditolak · lewati. Tanpa status = pratinjau rute (nomor urut).
    - kosong: tahap tanpa penyetuju (ditandai kuning). semua: semua penyetuju wajib setuju.
    - arah horizontal otomatis menjadi vertikal di layar < 768px.
--}}
@props([
    'tahap' => [],
    'awal' => null,
    'akhir' => null,
    'arah' => 'horizontal',
    'label' => 'Tangga alur persetujuan',
])
@php
    $teksStatus = ['selesai' => 'sudah disetujui', 'berjalan' => 'sedang menunggu', 'menunggu' => 'belum sampai', 'ditolak' => 'ditolak', 'lewati' => 'dilewati'];
@endphp
<ol {{ $attributes->merge(['class' => 'hr-tangga hr-tangga-' . $arah]) }} aria-label="{{ $label }}">
    @if ($awal)
        <li class="hr-tangga-item is-awal">
            <span class="hr-tangga-penanda" aria-hidden="true"><i class="bi bi-person"></i></span>
            <div class="hr-tangga-isi">
                <span class="hr-tangga-label">{{ $awal }}</span>
                <span class="hr-tangga-oleh">Mengajukan</span>
            </div>
        </li>
    @endif
    @foreach ($tahap as $i => $t)
        @php
            $status = $t['status'] ?? null;
            $kosong = ! empty($t['kosong']);
        @endphp
        <li class="hr-tangga-item {{ $status ? 'is-' . $status : '' }} {{ $kosong ? 'is-kosong' : '' }}" @if ($status === 'berjalan') aria-current="step" @endif>
            <span class="hr-tangga-penanda" aria-hidden="true">
                @if ($status === 'selesai')<i class="bi bi-check-lg"></i>
                @elseif ($status === 'ditolak')<i class="bi bi-x-lg"></i>
                @elseif ($status === 'lewati')<i class="bi bi-dash"></i>
                @elseif ($kosong)<i class="bi bi-exclamation"></i>
                @else{{ $i + 1 }}@endif
            </span>
            <div class="hr-tangga-isi">
                <span class="hr-tangga-kepala">
                    <span class="hr-tangga-label">{{ $t['label'] }}</span>
                    @if (! empty($t['final']))<x-badge tone="success">Akhir</x-badge>@endif
                    @if (! empty($t['semua']))<x-badge tone="info" icon="bi-people">Semua setuju</x-badge>@endif
                </span>
                @if ($kosong)
                    <span class="hr-tangga-oleh hr-tangga-peringatan">Belum ada penyetuju</span>
                @elseif (! empty($t['oleh']))
                    <span class="hr-tangga-oleh">{{ $t['oleh'] }}</span>
                @endif
                @if (! empty($t['catatan']))<span class="hr-tangga-catatan">{{ $t['catatan'] }}</span>@endif
                @if ($status)<span class="visually-hidden">— {{ $teksStatus[$status] ?? $status }}</span>@endif
            </div>
        </li>
    @endforeach
    @if ($akhir)
        <li class="hr-tangga-item is-akhir">
            <span class="hr-tangga-penanda" aria-hidden="true"><i class="bi bi-flag"></i></span>
            <div class="hr-tangga-isi"><span class="hr-tangga-label">{{ $akhir }}</span></div>
        </li>
    @endif
</ol>
