{{--
    Diagram batang bertumpuk sederhana: satu baris per kelompok (unit / area kerja), panjang batang = jumlah pegawai,
    warna = status kerja. Klik baris untuk melihat rincian.
    $items: list<array{
        kunci: string, nama: string, jumlah: int, status: list<array{label: string, jumlah: int}>,
        peringatan?: list<array{0: string, 1: string}>,   // [ikon, teks] — tampil sebagai ikon di samping nama
        detail?: list<array{0: string, 1: string, 2?: string}>, // [ikon, teks, kelas]
        tautan?: array{0: string, 1: string},              // [url, label]
        cari?: string, perhatian?: int
    }>
    $palet: peta label status → nomor warna viz. $satuan: label jumlah (default "org").
--}}
@php
    $maks = max(1, collect($items)->max('jumlah'));
    $warnaStatus = fn ($label) => $label === 'Belum diisi' ? 'var(--viz-netral)' : 'var(--viz-' . ($palet[$label] ?? 'netral') . ')';
@endphp
<div class="viz viz-baris" data-baris-wrap>
    @foreach ($items as $i)
        @php $total = max(1, collect($i['status'])->sum('jumlah')); @endphp
        <details class="baris-item {{ ! empty($i['peringatan']) ? 'ada-peringatan' : '' }}" data-baris data-urut-asli="{{ $loop->index }}"
            data-nama="{{ mb_strtolower($i['nama']) }}" data-jumlah="{{ $i['jumlah'] }}" data-perhatian="{{ $i['perhatian'] ?? 0 }}"
            data-cari="{{ mb_strtolower($i['cari'] ?? $i['nama']) }}">
            <summary>
                <span class="baris-nama" title="{{ $i['nama'] }}">
                    <span class="text-truncate">{{ $i['nama'] }}</span>
                    @foreach ($i['peringatan'] ?? [] as [$ikon, $teks])
                        <i class="bi {{ $ikon }} baris-tanda" title="{{ $teks }}" aria-label="{{ $teks }}"></i>
                    @endforeach
                </span>
                <span class="baris-trek" aria-hidden="true">
                    <span class="baris-isi" style="width: {{ round($i['jumlah'] / $maks * 100, 1) }}%">
                        @foreach ($i['status'] as $s)
                            @if ($s['jumlah'] > 0)
                                <span style="flex: {{ $s['jumlah'] }}; background: {{ $warnaStatus($s['label']) }};"></span>
                            @endif
                        @endforeach
                    </span>
                </span>
                <span class="baris-num">{{ $i['jumlah'] }}</span>
                <i class="bi bi-chevron-down baris-panah" aria-hidden="true"></i>
            </summary>
            <div class="baris-detail">
                <div class="baris-status">
                    @foreach ($i['status'] as $s)
                        @if ($s['jumlah'] > 0)
                            <span><span class="unit-sw" style="background: {{ $warnaStatus($s['label']) }};"></span>{{ $s['label'] }} <strong>{{ $s['jumlah'] }}</strong> <span class="text-muted">({{ round($s['jumlah'] / $total * 100) }}%)</span></span>
                        @endif
                    @endforeach
                </div>
                @if (! empty($i['detail']))
                    <ul class="baris-info">
                        @foreach ($i['detail'] as $d)
                            <li class="{{ $d[2] ?? '' }}"><i class="bi {{ $d[0] }}" aria-hidden="true"></i><span>{{ $d[1] }}</span></li>
                        @endforeach
                    </ul>
                @endif
                @if (! empty($i['tautan']))
                    <a href="{{ $i['tautan'][0] }}" class="btn btn-outline-primary btn-sm mt-2">{{ $i['tautan'][1] }} <i class="bi bi-arrow-right"></i></a>
                @endif
            </div>
        </details>
    @endforeach
    <p class="text-muted small text-center py-3 mb-0" data-baris-kosong hidden>Tidak ada yang cocok.</p>
</div>

@once
<style>
    .viz-baris { display: grid; gap: 2px; }
    .baris-item { border-radius: .55rem; }
    .baris-item[open] { background: var(--admin-surface-soft, #f8f9fb); box-shadow: inset 0 0 0 1px var(--viz-track); }
    .baris-item > summary {
        list-style: none; cursor: pointer;
        display: grid; grid-template-columns: minmax(120px, 28%) 1fr 2.6rem 1rem; align-items: center; gap: .65rem;
        padding: .38rem .55rem; border-radius: .55rem; font-size: .8125rem; color: var(--viz-text);
    }
    .baris-item > summary::-webkit-details-marker { display: none; }
    .baris-item > summary:hover { background: var(--admin-surface-soft, #f8f9fb); }
    .baris-item > summary:focus-visible { outline: 2px solid rgba(139, 18, 21, .35); outline-offset: 1px; }
    .baris-nama { display: flex; align-items: center; gap: .35rem; min-width: 0; font-weight: 600; }
    .baris-tanda { flex: none; color: #d97706; font-size: .8rem; }
    .baris-trek { display: block; height: 12px; }
    .baris-isi { display: flex; gap: 1px; height: 100%; min-width: 4px; border-radius: 3px; overflow: hidden; }
    .baris-isi > span { display: block; height: 100%; min-width: 2px; }
    .baris-num { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }
    .baris-panah { font-size: .7rem; color: var(--viz-muted); transition: transform .15s; }
    .baris-item[open] .baris-panah { transform: rotate(180deg); }
    .baris-detail { padding: .15rem .75rem .75rem calc(.55rem); font-size: .8rem; }
    .baris-status { display: flex; flex-wrap: wrap; gap: .3rem 1rem; margin-bottom: .4rem; }
    .baris-status > span { display: inline-flex; align-items: center; gap: .35rem; }
    .baris-info { list-style: none; padding: 0; margin: 0; display: grid; gap: .2rem; color: var(--viz-muted); }
    .baris-info li { display: flex; gap: .45rem; align-items: baseline; }
    .baris-info li.is-danger { color: #b42318; font-weight: 600; }
    .baris-info li.is-warning { color: #a15c00; font-weight: 600; }
    html[data-theme="dark"] .baris-info li.is-danger { color: #f19a9a; }
    html[data-theme="dark"] .baris-info li.is-warning { color: #f0b85a; }
    @media (max-width: 575.98px) {
        /* HP: nama di atas, batang di bawah. */
        .baris-item > summary { grid-template-columns: 1fr 2.4rem 1rem; grid-template-areas: "nama num panah" "trek trek trek"; row-gap: .3rem; }
        .baris-nama { grid-area: nama; } .baris-num { grid-area: num; } .baris-panah { grid-area: panah; } .baris-trek { grid-area: trek; }
    }
</style>
<script>
    // Cari & urutkan baris diagram (dipakai Ringkasan per Unit & per Area Kerja).
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-baris-alat]').forEach(function (alat) {
            const wadah = document.getElementById(alat.dataset.barisAlat);
            if (!wadah) return;
            const baris = Array.from(wadah.querySelectorAll('[data-baris]'));
            const cari = alat.querySelector('[data-baris-cari]');
            const urut = alat.querySelector('[data-baris-urut]');
            const kosong = wadah.querySelector('[data-baris-kosong]');
            const banding = {
                asli: (a, b) => a.dataset.urutAsli - b.dataset.urutAsli,
                nama: (a, b) => a.dataset.nama.localeCompare(b.dataset.nama, 'id'),
                jumlah: (a, b) => b.dataset.jumlah - a.dataset.jumlah,
                perhatian: (a, b) => (b.dataset.perhatian - a.dataset.perhatian) || (b.dataset.jumlah - a.dataset.jumlah),
            };
            function terapkan() {
                const kata = (cari?.value || '').trim().toLowerCase();
                let tampil = 0;
                baris.sort(banding[urut?.value] || banding.asli).forEach(function (b) {
                    b.hidden = kata !== '' && !b.dataset.cari.includes(kata);
                    if (!b.hidden) tampil++;
                    kosong.parentNode.insertBefore(b, kosong); // baris & pesan kosong berada di .viz-baris yang sama
                });
                kosong.hidden = tampil > 0;
            }
            cari?.addEventListener('input', terapkan);
            urut?.addEventListener('change', terapkan);
        });
    });
</script>
@endonce
