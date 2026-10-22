{{--
    Diagram kipas (donat) interaktif: tiap potongan = satu kelompok (unit / area kerja), besar potongan = jumlah pegawai.
    Klik potongan atau nama di legenda → rincian tampil di samping (komposisi status, info, tautan).
    $items: list<array{kunci, nama, jumlah, status: list<{label, jumlah}>, peringatan?: list<[ikon, teks]>, detail?: list<[ikon, teks, kelas?]>, tautan?: [url, label] | list<[url, label]>, cari?}>.
    $id: id unik diagram. $palet: peta status → nomor warna. $judulTotal: label di tengah donat.
--}}
@php
    $total = max(1, collect($items)->sum('jumlah'));
    // 12 warna berselang agar potongan bersebelahan mudah dibedakan; berulang bila kelompok lebih banyak.
    $warnaKipas = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#4a3aa7', '#008b8b', '#a3292c', '#6b8e23', '#c05fd0', '#3d5a80', '#d4a373'];
    $warnaStatus = fn ($label) => $label === 'Belum diisi' ? 'var(--viz-netral)' : 'var(--viz-' . ($palet[$label] ?? 'netral') . ')';
    $mulai = 0;
@endphp
<div class="viz kipas" id="{{ $id }}" data-kipas>
    <div class="kipas-grafik">
        <svg viewBox="0 0 42 42" class="kipas-svg" role="img" aria-label="Diagram {{ $judulTotal }}: {{ collect($items)->map(fn ($i) => $i['nama'] . ' ' . $i['jumlah'])->implode(', ') }}">
            <circle cx="21" cy="21" r="15.915" fill="none" stroke="var(--viz-track)" stroke-width="7"></circle>
            @foreach ($items as $i)
                @php $pct = $i['jumlah'] / $total * 100; @endphp
                <circle class="kipas-potong" data-pilih="{{ $loop->index }}" cx="21" cy="21" r="15.915" fill="none"
                    stroke="{{ $warnaKipas[$loop->index % count($warnaKipas)] }}" stroke-width="7"
                    stroke-dasharray="{{ round(max($pct - 0.35, 0.15), 3) }} {{ round(100 - max($pct - 0.35, 0.15), 3) }}"
                    stroke-dashoffset="{{ round(25 - $mulai, 3) }}"><title>{{ $i['nama'] }}: {{ $i['jumlah'] }} ({{ round($pct) }}%)</title></circle>
                @php $mulai += $pct; @endphp
            @endforeach
        </svg>
        <div class="kipas-tengah" aria-hidden="true">
            <strong data-tengah-angka>{{ collect($items)->sum('jumlah') }}</strong>
            <span data-tengah-label>{{ $judulTotal }}</span>
        </div>
    </div>

    <div class="kipas-samping">
        <input type="search" class="form-control form-control-sm mb-2" placeholder="Cari…" data-kipas-cari aria-label="Cari di diagram">
        <ul class="kipas-legenda" role="listbox" aria-label="Daftar {{ $judulTotal }}">
            @foreach ($items as $i)
                <li>
                    <button type="button" class="kipas-item" data-pilih="{{ $loop->index }}" data-cari="{{ mb_strtolower($i['cari'] ?? $i['nama']) }}" role="option" aria-selected="false">
                        <span class="unit-sw" style="background: {{ $warnaKipas[$loop->index % count($warnaKipas)] }}"></span>
                        <span class="text-truncate">{{ $i['nama'] }}</span>
                        @foreach ($i['peringatan'] ?? [] as [$ikon, $teks])<i class="bi {{ $ikon }} baris-tanda" title="{{ $teks }}"></i>@endforeach
                        <span class="kipas-angka">{{ $i['jumlah'] }} <small>{{ round($i['jumlah'] / $total * 100) }}%</small></span>
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="kipas-detail" data-kipas-detail aria-live="polite">
            <p class="text-muted small mb-0" data-kosong><i class="bi bi-hand-index me-1"></i>Klik potongan diagram atau nama di daftar untuk melihat rinciannya.</p>
            @foreach ($items as $i)
                @php $sub = max(1, collect($i['status'])->sum('jumlah')); @endphp
                <div data-rinci="{{ $loop->index }}" hidden>
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <strong class="kipas-detail-nama">{{ $i['nama'] }}</strong>
                        <span class="text-nowrap"><strong>{{ $i['jumlah'] }}</strong> org</span>
                    </div>
                    <div class="viz-stack viz-stack-sm mb-2">
                        @foreach ($i['status'] as $s)
                            @if ($s['jumlah'] > 0)<span style="flex: {{ $s['jumlah'] }}; background: {{ $warnaStatus($s['label']) }};" title="{{ $s['label'] }}: {{ $s['jumlah'] }}"></span>@endif
                        @endforeach
                    </div>
                    <div class="baris-status small mb-2">
                        @foreach ($i['status'] as $s)
                            @if ($s['jumlah'] > 0)
                                <span><span class="unit-sw" style="background: {{ $warnaStatus($s['label']) }};"></span>{{ $s['label'] }} <strong>{{ $s['jumlah'] }}</strong> <span class="text-muted">({{ round($s['jumlah'] / $sub * 100) }}%)</span></span>
                            @endif
                        @endforeach
                    </div>
                    @if (! empty($i['detail']))
                        <ul class="baris-info small">
                            @foreach ($i['detail'] as $d)
                                <li class="{{ $d[2] ?? '' }}"><i class="bi {{ $d[0] }}" aria-hidden="true"></i><span>{{ $d[1] }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                    @if (! empty($i['tautan']))
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach (is_array($i['tautan'][0] ?? null) ? $i['tautan'] : [$i['tautan']] as [$url, $label])
                                <a href="{{ $url }}" class="btn btn-outline-primary btn-sm">{{ $label }} <i class="bi bi-arrow-right"></i></a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

@once
<style>
    .kipas { display: grid; grid-template-columns: minmax(170px, 210px) 1fr; gap: 1.1rem; align-items: start; }
    .kipas-grafik { position: relative; width: 100%; max-width: 210px; aspect-ratio: 1; margin-inline: auto; }
    .kipas-svg { width: 100%; height: 100%; transform: rotate(0deg); overflow: visible; }
    .kipas-potong { cursor: pointer; transition: stroke-width .15s, opacity .15s; }
    .kipas-potong:hover { stroke-width: 8.6; }
    .kipas.ada-pilihan .kipas-potong:not(.aktif) { opacity: .3; }
    .kipas-potong.aktif { stroke-width: 9; }
    .kipas-tengah { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; pointer-events: none; padding: 26%; }
    .kipas-tengah strong { font-size: 1.5rem; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .kipas-tengah span { font-size: .7rem; color: var(--viz-muted); line-height: 1.2; overflow-wrap: anywhere; }
    .kipas-legenda { list-style: none; padding: 0; margin: 0 0 .6rem; max-height: 210px; overflow-y: auto; display: grid; gap: 1px; scrollbar-width: thin; }
    .kipas-item { width: 100%; display: flex; align-items: center; gap: .45rem; padding: .3rem .45rem; border: 0; border-radius: .45rem; background: transparent; color: var(--viz-text); font-size: .8125rem; text-align: left; }
    .kipas-item:hover, .kipas-item[aria-selected="true"] { background: var(--admin-surface-soft, #f8f9fb); }
    .kipas-item[aria-selected="true"] { box-shadow: inset 3px 0 0 var(--hr-primary, #8b1215); font-weight: 600; }
    .kipas-angka { margin-left: auto; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .kipas-angka small { font-weight: 500; color: var(--viz-muted); margin-left: .2rem; }
    .kipas-detail { border: 1px solid var(--viz-track); border-radius: .7rem; padding: .7rem .8rem; min-height: 74px; }
    .kipas-detail-nama { color: var(--hr-primary, #8b1215); }
    .baris-tanda { flex: none; color: #d97706; font-size: .8rem; }
    .baris-status { display: flex; flex-wrap: wrap; gap: .3rem 1rem; }
    .baris-status > span { display: inline-flex; align-items: center; gap: .35rem; }
    .baris-info { list-style: none; padding: 0; margin: 0; display: grid; gap: .2rem; color: var(--viz-muted); }
    .baris-info li { display: flex; gap: .45rem; align-items: baseline; }
    .baris-info li.is-danger { color: #b42318; font-weight: 600; }
    .baris-info li.is-warning { color: #a15c00; font-weight: 600; }
    html[data-theme="dark"] .baris-info li.is-danger { color: #f19a9a; }
    html[data-theme="dark"] .baris-info li.is-warning { color: #f0b85a; }
    html[data-theme="dark"] .kipas-detail-nama { color: #f08a8c; }
    @media (max-width: 575.98px) { .kipas { grid-template-columns: 1fr; } .kipas-grafik { max-width: 190px; } }
</style>
<script>
    // Diagram kipas: pilih potongan/legenda → sorot & tampilkan rincian; klik lagi untuk batal.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-kipas]').forEach(function (kipas) {
            const potong = kipas.querySelectorAll('.kipas-potong');
            const item = kipas.querySelectorAll('.kipas-item');
            const rinci = kipas.querySelectorAll('[data-rinci]');
            const kosong = kipas.querySelector('[data-kosong]');
            const angka = kipas.querySelector('[data-tengah-angka]');
            const label = kipas.querySelector('[data-tengah-label]');
            const awal = [angka.textContent, label.textContent];
            let dipilih = null;

            function pilih(i) {
                dipilih = dipilih === i ? null : i;
                kipas.classList.toggle('ada-pilihan', dipilih !== null);
                potong.forEach((p) => p.classList.toggle('aktif', p.dataset.pilih === dipilih));
                item.forEach((b) => b.setAttribute('aria-selected', String(b.dataset.pilih === dipilih)));
                rinci.forEach((r) => { r.hidden = r.dataset.rinci !== dipilih; });
                kosong.hidden = dipilih !== null;
                const tombol = dipilih !== null ? kipas.querySelector('.kipas-item[data-pilih="' + dipilih + '"]') : null;
                angka.textContent = tombol ? tombol.querySelector('.kipas-angka').firstChild.textContent.trim() : awal[0];
                label.textContent = tombol ? tombol.querySelector('.text-truncate').textContent : awal[1];
                if (tombol) tombol.scrollIntoView({ block: 'nearest' });
            }

            potong.forEach((p) => p.addEventListener('click', () => pilih(p.dataset.pilih)));
            item.forEach((b) => b.addEventListener('click', () => pilih(b.dataset.pilih)));
            kipas.querySelector('[data-kipas-cari]').addEventListener('input', function () {
                const kata = this.value.trim().toLowerCase();
                item.forEach((b) => { b.parentElement.hidden = kata !== '' && !b.dataset.cari.includes(kata); });
            });
        });
    });
</script>
@endonce
