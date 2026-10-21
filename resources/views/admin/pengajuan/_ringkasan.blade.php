{{-- Ringkasan lengkap untuk penyetuju. $r = App\Support\RingkasanPengajuan::{cuti|lembur|formasi}(). --}}
@include('admin.pengajuan._ringkasan-gaya')
@php
    $ikonAlur = ['selesai' => 'bi-check-circle-fill', 'berjalan' => 'bi-hourglass-split', 'menunggu' => 'bi-circle', 'ditolak' => 'bi-x-circle-fill', 'lewati' => 'bi-dash-circle'];
@endphp
<div class="ringkasan">
    <div class="rk-kepala">
        <span class="rk-ikon"><i class="bi bi-person"></i></span>
        <div class="min-w-0">
            <div class="rk-nama">{{ $r['orang']['nama'] }}</div>
            <div class="rk-sub">{{ $r['orang']['sub'] }}</div>
            @if ($r['orang']['meta'])<div class="rk-meta">{{ $r['orang']['meta'] }}</div>@endif
        </div>
        <span class="rk-judul">{{ $r['judul'] }}</span>
    </div>

    @foreach ($r['peringatan'] as $p)
        <div class="rk-peringatan"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ $p }}</span></div>
    @endforeach

    <div class="rk-angka">
        @foreach ($r['angka'] as $a)
            <div class="rk-kartu {{ $a['nada'] }}">
                <span class="rk-label">{{ $a['label'] }}</span>
                <strong>{{ $a['nilai'] }}</strong>
                @if ($a['catatan'])<span class="rk-catatan">{{ $a['catatan'] }}</span>@endif
            </div>
        @endforeach
    </div>

    <div class="rk-grid">
        <section>
            <h3 class="rk-h">Pengajuan</h3>
            <dl class="rk-detail">
                @foreach ($r['detail'] as [$label, $nilai])
                    <dt>{{ $label }}</dt><dd>{{ $nilai }}</dd>
                @endforeach
            </dl>
            @if (($f = $r['formasi'] ?? null) && ($f->diganti || $f->slot_tambahan))
                @include('admin.pengajuan-formasi._bagan-gaya')
                <div class="small fw-semibold mt-2">{{ $f->diganti ? 'Pegawai yang digantikan' : 'Posisi baru' }}</div>
                <div class="bagan-deret">
                    @foreach ($f->diganti ?? [] as $o)
                        <span class="bagan-node {{ $o['aktif'] ? 'aktif' : 'nonaktif' }} diganti"><i class="bi {{ $o['aktif'] ? 'bi-person-fill' : 'bi-person-dash' }}"></i><span class="nm">{{ $o['nama'] }} · {{ $o['jabatan'] }}</span><span class="bagan-lencana"><i class="bi bi-arrow-repeat"></i></span></span>
                    @endforeach
                    @foreach ($f->slot_tambahan ?? [] as $i => $ket)
                        <span class="bagan-node kosong slot"><i class="bi bi-person-plus"></i><span class="nm">{{ $ket ?: 'Posisi baru #' . ($i + 1) }}</span><span class="bagan-lencana"><i class="bi bi-plus-lg"></i></span></span>
                    @endforeach
                </div>
            @endif
        </section>
        <section>
            <h3 class="rk-h">Alur persetujuan</h3>
            <ol class="rk-alur">
                @foreach ($r['alur'] as $l)
                    <li class="{{ $l['status'] }}">
                        <i class="bi {{ $ikonAlur[$l['status']] }}"></i>
                        <div>
                            <strong>{{ $l['label'] }}</strong>
                            @if ($l['oleh'])<span>{{ $l['status'] === 'menunggu' || $l['status'] === 'berjalan' ? 'oleh ' : '' }}{{ $l['oleh'] }}</span>@endif
                            @if ($l['waktu'])<span class="rk-catatan">{{ $l['waktu'] }}</span>@endif
                            @if ($l['catatan'])<span class="rk-catatan">{{ $l['status'] === 'ditolak' ? 'Alasan: ' : '' }}{{ $l['catatan'] }}</span>@endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    @if (isset($r['bagan']))
        {{-- Formasi: bagan unit seperti di formulir pengajuan (yang digantikan / posisi baru ditandai). --}}
        <section>
            <h3 class="rk-h">Bagan unit {{ $r['formasi']->unit_kerja }}</h3>
            @include('admin.pengajuan-formasi._bagan-statis', ['bagan' => $r['bagan'], 'unit' => $r['formasi']->unit_kerja])
        </section>
    @endif

    <section>
        <h3 class="rk-h">{{ $r['riwayatJudul'] }}</h3>
        @forelse ($r['riwayat'] as $h)
            <div class="rk-riwayat">
                <div class="min-w-0"><span>{{ $h['utama'] }}</span><span class="rk-catatan">{{ $h['sub'] }}</span></div>
                <span class="badge {{ $h['kelas'] }}">{{ $h['status'] }}</span>
            </div>
        @empty
            <p class="rk-catatan mb-0">Belum ada riwayat sebelumnya.</p>
        @endforelse
    </section>
</div>
