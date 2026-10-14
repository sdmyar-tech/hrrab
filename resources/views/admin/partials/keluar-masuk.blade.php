{{--
    Pegawai masuk & keluar: ringkasan 30 hari terakhir + grafik 12 bulan (masuk vs keluar) + daftar nama.
    $data dari DashboardData::keluarMasuk().
--}}
@php
    $m30 = $data['masuk30']->count();
    $k30 = $data['keluar30']->count();
    $selisih = $m30 - $k30;
    $maks = max(1, collect($data['bulan'])->max(fn ($b) => max($b['masuk'], $b['keluar'])));
    $bisaBuka = auth()->user()->can('lihat-tabel-karyawan');
@endphp
<section class="panel mt-3" id="keluar-masuk">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="h6 fw-bold mb-0"><i class="bi bi-arrow-left-right"></i>Pegawai Masuk &amp; Keluar</h2>
            <p class="small text-muted mb-0">30 hari terakhir &amp; tren 12 bulan &middot; {{ $labelLingkup }}</p>
        </div>
        <div class="d-flex flex-wrap gap-3 small text-muted viz">
            <span class="d-inline-flex align-items-center gap-1"><span class="unit-sw km-masuk"></span>Masuk</span>
            <span class="d-inline-flex align-items-center gap-1"><span class="unit-sw km-keluar"></span>Keluar</span>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="row g-2 viz">
                <div class="col-4 col-lg-12">
                    <div class="dash-mini-stat km-stat is-masuk"><div class="n">+{{ $m30 }}</div><div class="l">Masuk (30 hari)</div></div>
                </div>
                <div class="col-4 col-lg-12">
                    <div class="dash-mini-stat km-stat is-keluar"><div class="n">−{{ $k30 }}</div><div class="l">Keluar (30 hari)</div></div>
                </div>
                <div class="col-4 col-lg-12">
                    <div class="dash-mini-stat km-stat"><div class="n {{ $selisih > 0 ? 'text-success' : ($selisih < 0 ? 'text-danger' : '') }}">{{ $selisih > 0 ? '+' : '' }}{{ $selisih }}</div><div class="l">Perubahan bersih</div></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="viz">
                <div class="km-cols" role="img" aria-label="Pegawai masuk dan keluar per bulan, 12 bulan terakhir">
                    @foreach ($data['bulan'] as $b)
                        <div class="km-col {{ $loop->last ? 'is-kini' : '' }}" tabindex="0">
                            <span class="tip">{{ $b['judul'] }}: masuk {{ $b['masuk'] }}, keluar {{ $b['keluar'] }}</span>
                            <span class="bar km-masuk" style="height: {{ $b['masuk'] ? max(3, round($b['masuk'] / $maks * 100)) : 0 }}%"></span>
                            <span class="bar km-keluar" style="height: {{ $b['keluar'] ? max(3, round($b['keluar'] / $maks * 100)) : 0 }}%"></span>
                        </div>
                    @endforeach
                </div>
                <div class="viz-xlab" aria-hidden="true">
                    @foreach ($data['bulan'] as $b)
                        <span>{{ $b['label'] }}</span>
                    @endforeach
                </div>
                <table class="visually-hidden">
                    <caption>Pegawai masuk dan keluar per bulan</caption>
                    <tr><th>Bulan</th><th>Masuk</th><th>Keluar</th></tr>
                    @foreach ($data['bulan'] as $b)
                        <tr><th scope="row">{{ $b['judul'] }}</th><td>{{ $b['masuk'] }}</td><td>{{ $b['keluar'] }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>

        @foreach ([['Masuk', $data['masuk30'], 'bi-box-arrow-in-right', 'is-masuk'], ['Keluar', $data['keluar30'], 'bi-box-arrow-right', 'is-keluar']] as [$judul, $daftar, $ikon, $kelas])
            <div class="col-12 col-md-6">
                <div class="km-daftar {{ $kelas }}">
                    <div class="fw-semibold small mb-1"><i class="bi {{ $ikon }} me-1"></i>{{ $judul }} 30 hari terakhir</div>
                    @forelse ($daftar as $p)
                        <div class="km-orang">
                            <div class="min-w-0">
                                @if ($bisaBuka && $p['id'])
                                    <a href="{{ route('admin.pegawai.show', $p['id']) }}" class="fw-semibold text-truncate d-block">{{ $p['nama'] }}</a>
                                @else
                                    <span class="fw-semibold text-truncate d-block">{{ $p['nama'] }}</span>
                                @endif
                                <span class="small text-muted">{{ $p['jabatan'] ?: '-' }} · {{ $p['unit'] ?: '-' }}</span>
                            </div>
                            <span class="small text-muted text-nowrap">{{ $p['tanggal']?->translatedFormat('d M') }}</span>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">Tidak ada pegawai {{ strtolower($judul) }}.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    @if ($data['tanpaTanggal'])
        <p class="small text-muted mt-3 mb-0">
            <i class="bi bi-info-circle me-1"></i>{{ $data['tanpaTanggal'] }} pegawai nonaktif belum punya tanggal keluar (data lama), jadi tidak dihitung.
            Isi lewat Ubah Data Pegawai bila diketahui. Pegawai yang dinonaktifkan mulai sekarang tercatat otomatis.
        </p>
    @endif
</section>

@once
<style>
    .km-masuk { background: #1baf7a; }
    .km-keluar { background: #e34948; }
    html[data-theme="dark"] .km-masuk { background: #199e70; }
    html[data-theme="dark"] .km-keluar { background: #e66767; }
    .km-stat { height: 100%; }
    .km-stat.is-masuk .n { color: #15803d; }
    .km-stat.is-keluar .n { color: #c0392b; }
    .km-cols { display: flex; align-items: flex-end; gap: 6px; height: 150px; padding-top: .5rem; border-bottom: 1px solid var(--viz-track); }
    .km-col { flex: 1; height: 100%; display: flex; align-items: flex-end; justify-content: center; gap: 2px; position: relative; outline: none; border-radius: 4px 4px 0 0; }
    .km-col.is-kini { background: var(--admin-surface-soft, #f8f9fb); }
    .km-col .bar { flex: 1; max-width: 16px; border-radius: 3px 3px 0 0; transition: opacity .15s; }
    .km-cols:hover .km-col .bar { opacity: .5; }
    .km-cols .km-col:hover .bar, .km-col:focus-visible .bar { opacity: 1; }
    .km-col .tip { position: absolute; bottom: calc(100% + 4px); left: 50%; transform: translateX(-50%); white-space: nowrap; background: var(--viz-text); color: var(--viz-surface);
        font-size: .72rem; padding: .25rem .5rem; border-radius: 4px; pointer-events: none; opacity: 0; transition: opacity .12s; z-index: 2; }
    .km-col:hover .tip, .km-col:focus-visible .tip { opacity: 1; }
    /* Kolom di ujung: tooltip ditahan di dalam grafik agar tidak membuat halaman bergeser. */
    .km-col:nth-child(-n+2) .tip { left: 0; transform: none; }
    .km-col:nth-last-child(-n+2) .tip { left: auto; right: 0; transform: none; }
    .km-daftar { border: 1px solid var(--admin-border); border-radius: 10px; padding: .7rem .85rem; height: 100%; max-height: 260px; overflow-y: auto; }
    .km-daftar.is-masuk { border-top: 3px solid #1baf7a; }
    .km-daftar.is-keluar { border-top: 3px solid #e34948; }
    .km-orang { display: flex; justify-content: space-between; align-items: center; gap: .75rem; padding: .35rem 0; border-bottom: 1px dashed var(--admin-border); font-size: .8125rem; }
    .km-orang:last-child { border-bottom: 0; }
    @media (max-width: 575.98px) { .km-cols { gap: 3px; } .km-col .bar { max-width: 9px; } .viz-xlab span { font-size: .62rem; } }
</style>
@endonce
