@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $durasi = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m);
    $total = collect($baris)->pluck('r');
    $hariKerjaTotal = $total->sum('hari_kerja');
    $hadirTotal = $total->sum('hadir_total');
    $persen = fn ($hadir, $kerja) => $kerja ? (int) round($hadir / $kerja * 100) : null;
    $persenTotal = $persen($hadirTotal, $hariKerjaTotal);
    $rentangParam = ['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()];
    $adaFilter = request()->filled('q') || request()->filled('unit');
    $reset = route('admin.absensi.rekap', $rentangParam);
    $kelola = auth()->user()->can('absensi.manage');
    // Angka 0 diredupkan agar angka yang perlu perhatian menonjol.
    $angka = fn ($n, $kelas = '') => $n ? '<span class="' . $kelas . '">' . $n . '</span>' : '<span class="abs-nol">0</span>';
    // Judul kolom yang bisa diklik untuk mengurutkan (memakai pilihan urutan yang sudah ada: URUT_REKAP).
    $urut = function (string $kunci, string $teks) use ($urutAktif, $urutOpsi, $rentangParam) {
        $aktif = $urutAktif === $kunci;
        $url = route('admin.absensi.rekap', ['urut' => $kunci] + $rentangParam + request()->only(['unit', 'q']));

        return '<a href="' . e($url) . '" class="abs-urut' . ($aktif ? ' is-aktif' : '') . '" title="Urutkan: ' . e($urutOpsi[$kunci]) . '"'
            . ($aktif ? ' aria-current="true"' : '') . '>' . e($teks) . '<i class="bi bi-sort-down" aria-hidden="true"></i></a>';
    };
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Rekap Absensi" eyebrow="Absensi" subtitle="Ringkasan kehadiran per pegawai pada periode yang dipilih.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.absensi.rekap.export', 'eksporParam' => request()->only(['unit', 'q', 'urut']) + $rentangParam])
            </x-slot:actions>
        </x-page-header>

        @include('admin.absensi._nav')

        @foreach ($pesanRentang as $p)
            <x-alert tone="warning" class="mb-4">{{ $p }}</x-alert>
        @endforeach

        @include('admin.absensi._periode', ['rute' => 'admin.absensi.rekap', 'dari' => $dari, 'sampai' => $sampai, 'preset' => $preset,
            'tetap' => request()->only(['unit', 'q', 'urut'])])

        <section class="row g-3 mb-4" aria-label="Ringkasan periode">
            <div class="col-6 col-xl-3">
                <x-stat-card label="Kehadiran" :value="$persenTotal !== null ? $persenTotal . '%' : '–'" icon="bi-graph-up" tone="primary"
                    :meta="number_format($hadirTotal, 0, ',', '.') . ' dari ' . number_format($hariKerjaTotal, 0, ',', '.') . ' hari kerja pegawai'" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat-card label="Hadir" :value="$hadirTotal" unit="hari" icon="bi-person-check" tone="success"
                    :meta="number_format($total->sum('on_time'), 0, ',', '.') . ' hari tepat waktu'" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat-card label="Telat" :value="$total->sum('telat')" unit="kali" icon="bi-alarm" tone="warning"
                    :meta="'Total ' . $durasi($total->sum('telat_menit')) . ($total->sum('telat_izin') ? ' · +' . $total->sum('telat_izin') . ' berizin' : '')" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat-card label="Alpa" :value="$total->sum('alpa')" unit="hari" icon="bi-x-octagon" tone="danger" meta="Hari kerja tanpa scan & keterangan" />
            </div>
        </section>

        <x-card :padding="false" aria-label="Rekap per pegawai">
            <x-filter-bar :action="route('admin.absensi.rekap')" id="filterRekap"
                search="Cari pegawai, contoh: Siti atau Guru" search-label="Cari rekap menurut nama, NIP, bagian, atau jabatan"
                :count="count($baris)" unit="pegawai" :filtered="$adaFilter" :reset="$reset">
                <x-slot:hidden>
                    <input type="hidden" name="dari" value="{{ $dari->toDateString() }}">
                    <input type="hidden" name="sampai" value="{{ $sampai->toDateString() }}">
                </x-slot:hidden>
                @if (count($unitOpsi) > 1)
                    <select name="unit" class="form-select form-select-sm" aria-label="Saring bagian" onchange="this.form.submit()">
                        <option value="">Semua bagian</option>
                        @foreach ($unitOpsi as $u)<option value="{{ $u }}" @selected(request('unit') === $u)>{{ $u }}</option>@endforeach
                    </select>
                @endif
                @include('partials.urut', ['urutForm' => 'filterRekap'])
                <x-slot:summary>
                    <p class="hr-filter-bar-jumlah"><i class="bi {{ $ik['lembur'] }} me-1" aria-hidden="true"></i>Lembur terverifikasi <strong>{{ $durasi($total->sum('lembur_menit')) }}</strong></p>
                </x-slot:summary>
            </x-filter-bar>

            <x-table caption="Rekap absensi per pegawai" class="abs-rekap" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th @if ($urutAktif === 'nama') aria-sort="ascending" @endif>{!! $urut('nama', 'Pegawai') !!}</th>
                        <th @if ($urutAktif === 'hadir_sedikit') aria-sort="ascending" @endif>{!! $urut('hadir_sedikit', 'Kehadiran') !!}</th>
                        <th class="hr-angka">Hadir</th>
                        <th class="hr-angka" @if ($urutAktif === 'telat') aria-sort="descending" @endif>{!! $urut('telat', 'Telat') !!}</th>
                        <th class="hr-angka" title="Izin, sakit, dinas luar">Izin</th>
                        <th class="hr-angka">Cuti</th>
                        <th class="hr-angka" @if ($urutAktif === 'alpa') aria-sort="descending" @endif>{!! $urut('alpa', 'Alpa') !!}</th>
                        <th class="hr-angka" title="Pulang cepat tanpa izin & tidak scan pulang">Perlu Dicek</th>
                        <th class="hr-angka" @if ($urutAktif === 'lembur') aria-sort="descending" @endif>{!! $urut('lembur', 'Lembur') !!}</th>
                    </tr>
                </x-slot:head>
                @forelse ($baris as ['pegawai' => $p, 'r' => $r])
                    @php
                        $pr = $persen($r['hadir_total'], $r['hari_kerja']);
                        $izin = $r['izin'] + $r['sakit'] + $r['dinas_luar'];
                        $rincianIzin = collect(['izin' => $r['izin'], 'sakit' => $r['sakit'], 'dinas' => $r['dinas_luar']])->filter()->map(fn ($n, $k) => $n . ' ' . $k);
                        $cek = $r['pulang_cepat'] + $r['tanpa_scan_pulang'];
                    @endphp
                    <tr>
                        <td>
                            <div class="hr-sel-utama">
                                <x-avatar :name="$p->nama_lengkap" size="sm" />
                                <div class="min-w-0">
                                    <a href="{{ route('admin.absensi.pegawai', ['pegawai' => $p] + $rentangParam) }}" class="hr-sel-judul">{{ $p->nama_lengkap }}</a>
                                    <span class="hr-sel-sub">{{ $p->kepegawaian?->unit_kerja ?? '-' }}@unless ($p->jadwalKerja) · <span class="abs-teks-peringatan">jam kerja belum diatur</span>@endunless</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($pr !== null)
                                <div class="abs-persen">
                                    <div class="progress" role="progressbar" aria-label="Kehadiran {{ $p->nama_lengkap }}" aria-valuenow="{{ min(100, $pr) }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar {{ $pr >= 90 ? '' : ($pr >= 75 ? 'is-sedang' : 'is-buruk') }}" style="width: {{ min(100, $pr) }}%"></div>
                                    </div>
                                    <span class="abs-persen-angka">{{ $pr }}%</span>
                                </div>
                                <span class="hr-sel-sub">{{ $r['hadir_total'] }} / {{ $r['hari_kerja'] }} hari kerja</span>
                            @else
                                <span class="abs-nol">–</span><span class="hr-sel-sub">Belum ada hari kerja</span>
                            @endif
                        </td>
                        <td class="hr-angka">
                            {!! $angka($r['hadir_total'], 'fw-semibold') !!}
                            @if ($r['on_time'])<span class="hr-sel-sub">{{ $r['on_time'] }} tepat waktu</span>@endif
                        </td>
                        <td class="hr-angka">
                            {!! $angka($r['telat'], 'abs-angka-telat') !!}
                            @if ($r['telat'])<span class="hr-sel-sub">{{ $durasi($r['telat_menit']) }}</span>@endif
                            @if ($r['telat_izin'])<span class="hr-sel-sub">+{{ $r['telat_izin'] }} berizin</span>@endif
                        </td>
                        <td class="hr-angka">
                            {!! $angka($izin) !!}
                            @if ($rincianIzin->count() > 1 || ($izin && ! $r['izin']))
                                @foreach ($rincianIzin as $teks)<span class="hr-sel-sub">{{ $teks }}</span>@endforeach
                            @endif
                        </td>
                        <td class="hr-angka">{!! $angka($r['cuti']) !!}</td>
                        <td class="hr-angka">{!! $angka($r['alpa'], 'abs-angka-alpa') !!}</td>
                        <td class="hr-angka">
                            {!! $angka($cek, 'abs-angka-telat') !!}
                            @if ($r['pulang_cepat'])<span class="hr-sel-sub">{{ $r['pulang_cepat'] }} pulang cepat</span>@endif
                            @if ($r['tanpa_scan_pulang'])<span class="hr-sel-sub">{{ $r['tanpa_scan_pulang'] }} tak scan pulang</span>@endif
                        </td>
                        <td class="hr-angka">
                            @if ($r['lembur_surat_menit'])
                                <span class="fw-semibold">{{ $durasi($r['lembur_menit']) }}</span>
                                <span class="hr-sel-sub">surat {{ $durasi($r['lembur_surat_menit']) }}@if ($r['lembur_belum']) · <span class="abs-teks-peringatan">{{ $r['lembur_belum'] }} belum dicek</span>@endif</span>
                            @else
                                <span class="abs-nol">–</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    @if ($adaFilter)
                        <x-table-empty :colspan="9" icon="bi-search" title="Tidak ada pegawai yang cocok">
                            Coba kata kunci atau bagian lain.
                            <x-slot:action><x-button :href="$reset" variant="secondary" icon="bi-x-circle">Reset filter</x-button></x-slot:action>
                        </x-table-empty>
                    @else
                        <x-table-empty :colspan="9" :icon="$ik['rekap']" title="Belum ada pegawai yang ikut absensi">
                            Rekap muncul setelah pegawai dicentang ikut absensi dan data mesin diimport.
                            @if ($kelola)
                                <x-slot:action><x-button :href="route('admin.absensi.pengaturan', ['tempel' => 1])" icon="bi-person-plus">Pilih pegawai</x-button></x-slot:action>
                            @endif
                        </x-table-empty>
                    @endif
                @endforelse
            </x-table>
        </x-card>
    </div>
</main>
@endsection
