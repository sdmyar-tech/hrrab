{{--
    Bilah pemilih periode (Rekap & Riwayat Pegawai): ‹ Bulan › + pilihan cepat + rentang Dari–Sampai.
    Semua tautan/form tetap memakai parameter lama ?dari=&sampai= (tanpa parameter baru).
    Param: $rute (nama rute), $dari, $sampai (Carbon), $preset ([label => [dari, sampai]]),
           (opsional) $ruteParam (parameter jalur, mis. ['pegawai' => $p]), (opsional) $tetap (query yang dipertahankan, mis. unit/q/urut).
--}}
@php
    $ruteParam ??= [];
    $tetap = array_filter($tetap ?? [], fn ($v) => $v !== null && $v !== '');
    $hariIni = now()->startOfDay();
    $urlPeriode = fn ($d, $s) => route($rute, $ruteParam + $tetap + ['dari' => $d->toDateString(), 'sampai' => $s->toDateString()]);
    $awalBulan = $dari->copy()->startOfMonth();
    // Satu bulan penuh (atau bulan berjalan sampai hari ini) → tampil "Oktober 2026"; selain itu rentang tanggal.
    $satuBulan = $dari->isSameDay($awalBulan) && $sampai->isSameMonth($dari)
        && ($sampai->isSameDay($dari->copy()->endOfMonth()) || $sampai->isSameDay($hariIni));
    $labelPeriode = $satuBulan ? $dari->translatedFormat('F Y') : $dari->translatedFormat('d M') . ' – ' . $sampai->translatedFormat('d M Y');
    $bulanLalu = $awalBulan->copy()->subMonthNoOverflow();
    $bulanDepan = $awalBulan->copy()->addMonthNoOverflow();
    $urlSebelum = $urlPeriode($bulanLalu, $bulanLalu->copy()->endOfMonth());
    $urlBerikut = $bulanDepan->lte($hariIni) ? $urlPeriode($bulanDepan, $bulanDepan->copy()->endOfMonth()->startOfDay()->min($hariIni)) : null;
    $jumlahHari = (int) $dari->diffInDays($sampai) + 1;
    $rentangBebas = ! $satuBulan && ! collect($preset)->contains(fn ($p) => $dari->isSameDay($p[0]) && $sampai->isSameDay($p[1]));
@endphp
<section class="abs-periode" aria-label="Pilih periode">
    <div class="abs-periode-nav">
        <x-icon-button icon="bi-chevron-left" label="Bulan sebelumnya" :href="$urlSebelum" />
        <div class="abs-periode-judul">
            <p class="abs-periode-label" aria-live="polite">{{ $labelPeriode }}</p>
            <span class="abs-periode-sub">{{ $dari->translatedFormat('d M Y') }} – {{ $sampai->translatedFormat('d M Y') }} · {{ $jumlahHari }} hari</span>
        </div>
        @if ($urlBerikut)
            <x-icon-button icon="bi-chevron-right" label="Bulan berikutnya" :href="$urlBerikut" />
        @else
            <x-icon-button icon="bi-chevron-right" label="Bulan berikutnya belum tersedia" disabled />
        @endif
    </div>
    <div class="abs-periode-cepat" role="group" aria-label="Periode cepat">
        @foreach ($preset as $teks => [$pDari, $pSampai])
            <x-chip :href="$urlPeriode($pDari, $pSampai)" :active="$dari->isSameDay($pDari) && $sampai->isSameDay($pSampai)">{{ $teks }}</x-chip>
        @endforeach
        {{-- Rentang bebas: panel tarik-turun berisi Dari–Sampai (tetap memakai ?dari=&sampai=). --}}
        <x-dropdown align="end" class="abs-periode-lain">
            <x-slot:trigger>
                <button type="button" class="hr-chip {{ $rentangBebas ? 'is-active' : '' }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <i class="bi bi-calendar-range" aria-hidden="true"></i><span>Rentang lain</span><i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
            </x-slot:trigger>
            <form method="GET" action="{{ route($rute, $ruteParam) }}" class="abs-periode-rentang" aria-label="Rentang tanggal lain">
                @foreach ($tetap as $kunci => $nilai)<input type="hidden" name="{{ $kunci }}" value="{{ $nilai }}">@endforeach
                <div>
                    <label class="form-label" for="dari">Dari</label>
                    <input type="date" name="dari" id="dari" class="form-control form-control-sm" value="{{ $dari->toDateString() }}" max="{{ $hariIni->toDateString() }}">
                </div>
                <div>
                    <label class="form-label" for="sampai">Sampai</label>
                    <input type="date" name="sampai" id="sampai" class="form-control form-control-sm" value="{{ $sampai->toDateString() }}" max="{{ $hariIni->toDateString() }}">
                </div>
                <x-button type="submit" size="sm" icon="bi-funnel">Tampilkan</x-button>
            </form>
        </x-dropdown>
    </div>
</section>
