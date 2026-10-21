{{-- Bagan unit hanya baca. $bagan = App\Support\BaganStatis::susun(), $unit = nama unit, $legendaBaru = label kotak merah (opsional). --}}
@include('admin.pengajuan-formasi._bagan-statis-aset')
<div class="bagan-legenda">
    <span><i class="kotak" style="background:#15803d"></i>Pegawai aktif</span>
    <span><i class="kotak" style="background:#9ca3af"></i>Pegawai tidak aktif</span>
    <span><i class="kotak" style="background:#b42318"></i>{{ $legendaBaru ?? 'Posisi baru' }}</span>
    <span><i class="bi bi-arrow-repeat"></i>digantikan</span>
</div>
<div class="bagan bagan-statis">
    <div class="bagan-layar">
        <div class="bagan-pohon">
            <div class="bagan-akar">
                <div class="bagan-akar-label">Kepala · {{ $unit }}</div>
                <div class="bagan-akar-deret">
                    @forelse ($bagan['kepala'] as $nama)
                        <span class="bagan-node aktif kepala"><i class="bi bi-person-badge"></i><span class="nm">{{ $nama }}</span></span>
                    @empty
                        <span class="bagan-node nonaktif kepala"><i class="bi bi-person-badge"></i><span class="nm">Kepala unit belum tercatat</span></span>
                    @endforelse
                </div>
            </div>

            @if (! $bagan['baris'])
                <div class="bagan-kosong-info">Belum ada pegawai tercatat di unit ini.</div>
            @elseif ($bagan['tunggal'])
                <div class="bagan-sisi tunggal">@include('admin.pengajuan-formasi._bagan-statis-cabang', ['c' => $bagan['baris'][0]['kiri'][0]])</div>
            @else
                @foreach ($bagan['baris'] as $r)
                    <div class="bagan-sisi kiri">
                        @foreach ($r['kiri'] as $c)@include('admin.pengajuan-formasi._bagan-statis-cabang')@endforeach
                    </div>
                    <div class="bagan-tulang {{ $loop->last ? 'akhir' : '' }} {{ $r['kanan'] ? '' : 'hanya-kiri' }}"></div>
                    <div class="bagan-sisi kanan">
                        @foreach ($r['kanan'] as $c)@include('admin.pengajuan-formasi._bagan-statis-cabang')@endforeach
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
