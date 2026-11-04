{{--
    Kartu bagan susunan pegawai satu bagian (hanya-baca) untuk dashboard: legenda, cari nama, zoom, Pas layar.
    Bentuk sama dengan bagan di formulir Pengajuan Formasi (App\Support\BaganStatis + admin.pengajuan-formasi._bagan-statis-cabang).
    Parameter: $unit (nama bagian), opsional $subjudul. Boleh dipasang lebih dari sekali dalam satu halaman.
--}}
@php
    $bagan = \App\Support\BaganStatis::susun(\App\Support\RekrutmenAccess::baganUnit($unit), null);
    $jumlahAktif = collect($bagan['baris'])->flatMap(fn ($r) => [...$r['kiri'], ...$r['kanan']])
        ->sum(fn ($c) => collect($c['simpul'])->where('kelas', 'aktif')->count()) + count($bagan['kepala']);
@endphp
@include('admin.pengajuan-formasi._bagan-statis-aset')
<x-card :title="'Bagan ' . $unit" icon="bi-diagram-3" :subtitle="$subjudul ?? ($jumlahAktif . ' pegawai aktif · susunan per jabatan di bagian ini')" data-bagan-dash>
    <div class="bagan-legenda mb-2" aria-label="Keterangan warna bagan">
        <span><i class="kotak aktif" aria-hidden="true"></i>Pegawai aktif</span>
        <span><i class="kotak nonaktif" aria-hidden="true"></i>Pegawai tidak aktif</span>
        <span><i class="bi bi-person-badge" aria-hidden="true"></i>Kepala bagian</span>
    </div>
    <div class="bagan bagan-statis bagan-dash">
        <div class="bagan-alat">
            <input type="search" class="form-control form-control-sm" placeholder="Cari di bagan, contoh: Siti" aria-label="Cari nama pegawai di bagan {{ $unit }}" data-bagan-cari>
            <div class="ms-auto d-flex align-items-center gap-1">
                <x-icon-button icon="bi-zoom-out" label="Perkecil bagan" data-zoom="-" />
                <span class="bagan-skala" data-bagan-skala aria-live="polite">100%</span>
                <x-icon-button icon="bi-zoom-in" label="Perbesar bagan" data-zoom="+" />
                <x-button variant="secondary" size="sm" icon="bi-arrows-angle-contract" data-zoom="pas" title="Muat seluruh bagan di layar">Pas layar</x-button>
            </div>
        </div>
        {{-- Wadah bergulir bisa difokus (panah keyboard) dan diseret dengan mouse. --}}
        <div class="bagan-layar" tabindex="0" role="region" aria-label="Bagan {{ $unit }}: geser untuk melihat semua">
            <div class="bagan-pohon" data-bagan-pohon>
                <div class="bagan-akar">
                    <div class="bagan-akar-label">Kepala · {{ $unit }}</div>
                    <div class="bagan-akar-deret">
                        @forelse ($bagan['kepala'] as $nama)
                            <span class="bagan-node aktif kepala" title="{{ $nama }} (kepala bagian)"><i class="bi bi-person-badge"></i><span class="nm">{{ $nama }}</span></span>
                        @empty
                            <span class="bagan-node nonaktif kepala"><i class="bi bi-person-badge"></i><span class="nm">Kepala bagian belum tercatat</span></span>
                        @endforelse
                    </div>
                </div>
                @if (! $bagan['baris'])
                    <div class="bagan-kosong-info">Belum ada pegawai tercatat di bagian ini.</div>
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
                <div class="bagan-kosong-info" data-bagan-tidak-cocok hidden>Tidak ada pegawai yang cocok. Coba kata lain atau kosongkan pencarian.</div>
            </div>
        </div>
    </div>
</x-card>

@once
<style>
    /* Bagan dashboard bisa diseret (bagan statis formulir memakai kursor biasa). */
    .bagan-dash .bagan-layar { cursor: grab; }
    .bagan-dash .bagan-layar.geser { cursor: grabbing; user-select: none; }
</style>
<script>
    // Alat bagan dashboard (cari, zoom, Pas layar, seret) — satu skrip untuk semua kartu [data-bagan-dash].
    document.addEventListener('DOMContentLoaded', function () {
        const MIN = 0.4, MAKS = 1.5;
        document.querySelectorAll('[data-bagan-dash]').forEach(function (kartu) {
            const layar = kartu.querySelector('.bagan-layar');
            const pohon = kartu.querySelector('[data-bagan-pohon]');
            const label = kartu.querySelector('[data-bagan-skala]');
            const kecil = kartu.querySelector('[data-zoom="-"]');
            const besar = kartu.querySelector('[data-zoom="+"]');
            const cari = kartu.querySelector('[data-bagan-cari]');
            const tidakCocok = kartu.querySelector('[data-bagan-tidak-cocok]');
            let skala = 1;

            function atur(s) {
                skala = Math.min(MAKS, Math.max(MIN, s));
                pohon.style.zoom = skala;
                label.textContent = Math.round(skala * 100) + '%';
                kecil.disabled = skala <= MIN;
                besar.disabled = skala >= MAKS;
            }
            // Perkecil secukupnya agar seluruh bagan muat lebar kartu (tidak memperbesar di atas 100%).
            function pas() {
                pohon.style.zoom = 1;
                const lebar = pohon.scrollWidth, ruang = layar.clientWidth - 24;
                atur(lebar > ruang ? Math.floor((ruang / lebar) * 20) / 20 : 1);
            }
            kartu.querySelectorAll('[data-zoom]').forEach(function (b) {
                b.addEventListener('click', function () {
                    if (b.dataset.zoom === 'pas') pas();
                    else atur(Math.round((skala + (b.dataset.zoom === '+' ? 0.1 : -0.1)) * 20) / 20);
                });
            });

            // Cari: sorot nama yang cocok, buka rak yang terlipat, gulir ke hasil pertama.
            cari.addEventListener('input', function () {
                const kata = cari.value.trim().toLowerCase();
                let pertama = null, jumlah = 0;
                kartu.querySelectorAll('.bagan-node').forEach(function (n) {
                    const cocok = kata !== '' && n.textContent.toLowerCase().includes(kata);
                    n.classList.toggle('cocok', cocok);
                    if (cocok) {
                        jumlah++;
                        pertama = pertama || n;
                        const rak = n.closest('.bagan-rak');
                        if (rak && n.classList.contains('lebih') && !rak.classList.contains('buka')) {
                            rak.classList.add('buka');
                            const lagi = rak.querySelector('[data-bagan-lagi]');
                            if (lagi) lagi.textContent = 'Ringkas';
                        }
                    }
                });
                tidakCocok.hidden = kata === '' || jumlah > 0;
                if (pertama) pertama.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
            });
            cari.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });

            // Geser bagan dengan menyeret (mouse); layar sentuh cukup digulir biasa.
            let seret = null;
            layar.addEventListener('pointerdown', function (e) {
                if (e.pointerType !== 'mouse' || e.button !== 0 || e.target.closest('button, input, a')) return;
                seret = { x: e.clientX, y: e.clientY, l: layar.scrollLeft, t: layar.scrollTop };
                layar.classList.add('geser');
            });
            window.addEventListener('pointermove', function (e) {
                if (!seret) return;
                layar.scrollLeft = seret.l - (e.clientX - seret.x);
                layar.scrollTop = seret.t - (e.clientY - seret.y);
            });
            window.addEventListener('pointerup', function () { seret = null; layar.classList.remove('geser'); });

            pas();
        });
    });
</script>
@endonce
