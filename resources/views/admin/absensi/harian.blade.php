@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $statusAktif = request('status');
    $tglStr = $tanggal->toDateString();
    $terakhir = $dataTerakhir ? \Carbon\Carbon::parse($dataTerakhir) : null;
    $urlTanggal = fn ($t) => route('admin.absensi.harian', ['tanggal' => $t] + request()->except(['tanggal', 'page']));
    $urlStatus = fn ($s) => route('admin.absensi.harian', ['tanggal' => $tglStr, 'status' => $statusAktif === $s ? null : $s] + request()->only(['unit', 'q', 'urut']));
    $adaFilter = $statusAktif || request()->filled('q') || request()->filled('unit');
    $reset = route('admin.absensi.harian', ['tanggal' => $tglStr]);
    $hadirTotal = ($hitung['on_time'] ?? 0) + ($hitung['telat'] ?? 0) + ($hitung['telat_izin'] ?? 0);
    // Kartu angka (klik = saring kelompok itu; klik lagi = semua). Warna: hijau hadir, kuning telat, biru izin, merah alpa.
    $kartu = [
        ['on_time', 'Tepat Waktu', 'bi-check-circle', 'success', $hadirTotal . ' dari ' . $jumlahPegawai . ' pegawai hadir'],
        ['telat', 'Telat', 'bi-alarm', 'warning', ($hitung['telat_izin'] ?? 0) ? '+' . $hitung['telat_izin'] . ' telat berizin' : 'Lewat batas toleransi'],
        ['cuti_izin', 'Izin / Cuti', $ik['izin'], 'info', 'Izin, sakit, cuti, dinas luar'],
        ['alpa', 'Alpa', 'bi-x-octagon', 'danger', 'Hari kerja tanpa scan'],
    ];
    // Saring cepat untuk semua kelompok (termasuk Telat Izin & Perlu Dicek yang tidak punya kartu).
    $chip = ['on_time' => 'On Time', 'telat' => 'Telat', 'telat_izin' => 'Telat Izin', 'alpa' => 'Alpa', 'cuti_izin' => 'Cuti / Izin', 'perlu_cek' => 'Perlu Dicek'];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Absensi Harian" eyebrow="Absensi" subtitle="Siapa yang hadir, telat, izin, atau tidak masuk pada tanggal yang dipilih.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.absensi.harian.export', 'eksporParam' => ['tanggal' => $tglStr]])
                @if ($bisaKelola)
                    <x-button variant="secondary" :href="route('admin.absensi.izin', ['tanggal' => $tglStr])" :icon="$ik['izin']">Beri Izin Telat</x-button>
                    <x-button :href="route('admin.absensi.import')" :icon="$ik['import']">Import Data Mesin</x-button>
                @endif
            </x-slot:actions>
        </x-page-header>

        @include('admin.absensi._nav')
        @include('admin.absensi._langkah', ['setup' => $setup])

        {{-- Pemilih tanggal: panah hari sebelum/sesudah, isian tanggal besar, pintasan. --}}
        <form method="GET" class="abs-periode" id="formTanggal" aria-label="Pilih tanggal absensi">
            @foreach (request()->only(['unit', 'q', 'status', 'urut']) as $kunci => $nilai)<input type="hidden" name="{{ $kunci }}" value="{{ $nilai }}">@endforeach
            <div class="abs-periode-nav">
                <x-icon-button icon="bi-chevron-left" label="Hari sebelumnya" :href="$urlTanggal($tanggal->copy()->subDay()->toDateString())" />
                <label class="visually-hidden" for="pilihTanggal">Pilih tanggal</label>
                <input type="date" name="tanggal" id="pilihTanggal" class="form-control form-control-lg abs-tanggal-isian" value="{{ $tglStr }}"
                    max="{{ now()->toDateString() }}" onchange="this.form.submit()">
                @if ($tanggal->lt(now()->startOfDay()))
                    <x-icon-button icon="bi-chevron-right" label="Hari berikutnya" :href="$urlTanggal($tanggal->copy()->addDay()->toDateString())" />
                @else
                    <x-icon-button icon="bi-chevron-right" label="Hari berikutnya belum tersedia" disabled />
                @endif
            </div>
            <div class="abs-periode-judul">
                <p class="abs-periode-label">{{ $tanggal->translatedFormat('l, d F Y') }}</p>
                <span class="abs-periode-sub">{{ $jumlahPegawai }} pegawai diabsen</span>
            </div>
            @if (! $tanggal->isToday() || ($terakhir && ! $tanggal->isSameDay($terakhir)))
                <div class="abs-periode-cepat" role="group" aria-label="Pintasan tanggal">
                    @unless ($tanggal->isToday())
                        <x-chip :href="$urlTanggal(now()->toDateString())" icon="bi-calendar-check">Hari ini</x-chip>
                    @endunless
                    @if ($terakhir && ! $tanggal->isSameDay($terakhir))
                        <x-chip :href="$urlTanggal($terakhir->toDateString())" icon="bi-clock-history">Data terakhir · {{ $terakhir->translatedFormat('d M') }}</x-chip>
                    @endif
                </div>
            @endif
        </form>

        @if ($pesanTanggal)
            <x-alert tone="warning" class="mb-4">{{ $pesanTanggal }}</x-alert>
        @elseif ($tanggalOtomatis)
            <x-alert tone="info" class="mb-4">Data mesin untuk hari ini belum diimport, jadi yang ditampilkan adalah data terakhir: <strong>{{ $tanggal->translatedFormat('d F Y') }}</strong>.</x-alert>
        @elseif ($terakhir && $tanggal->gt($terakhir))
            <x-alert tone="warning" class="mb-4">
                Data mesin baru diimport sampai <strong>{{ $terakhir->translatedFormat('d M Y') }}</strong>.
                @if ($bisaKelola)
                    <x-slot:action><x-button size="sm" variant="secondary" :href="route('admin.absensi.import')" :icon="$ik['import']">Import data terbaru</x-button></x-slot:action>
                @endif
            </x-alert>
        @endif

        <section class="row g-3 mb-4" aria-label="Ringkasan absensi {{ $tanggal->translatedFormat('d F Y') }}">
            @foreach ($kartu as [$k, $label, $ikon, $tone, $meta])
                <div class="col-6 col-xl-3">
                    <x-stat-card :label="$label" :value="$hitung[$k] ?? 0" :icon="$ikon" :tone="$tone" :meta="$meta"
                        :href="$urlStatus($k)" @class(['hr-stat-aktif' => $statusAktif === $k]) />
                </div>
            @endforeach
        </section>

        <x-card :padding="false" aria-label="Daftar absensi harian">
            <x-filter-bar :action="route('admin.absensi.harian')" id="filterHarian"
                search="Cari pegawai, contoh: Siti atau SMK" search-label="Cari absensi menurut nama, NIP, ID mesin, bagian, atau jabatan"
                :count="count($baris)" unit="pegawai" :filtered="$adaFilter" :reset="$reset">
                <x-slot:hidden>
                    <input type="hidden" name="tanggal" value="{{ $tglStr }}">
                    @if ($statusAktif)<input type="hidden" name="status" value="{{ $statusAktif }}">@endif
                </x-slot:hidden>
                @if (count($unitOpsi) > 1)
                    <select name="unit" class="form-select form-select-sm" aria-label="Saring bagian" onchange="this.form.submit()">
                        <option value="">Semua bagian</option>
                        @foreach ($unitOpsi as $u)<option value="{{ $u }}" @selected(request('unit') === $u)>{{ $u }}</option>@endforeach
                    </select>
                @endif
                @include('partials.urut', ['urutForm' => 'filterHarian'])
                <x-slot:chips>
                    <x-chip :href="route('admin.absensi.harian', ['tanggal' => $tglStr] + request()->only(['unit', 'q', 'urut']))" :active="! $statusAktif" :count="$jumlahPegawai">Semua</x-chip>
                    @foreach ($chip as $k => $label)
                        <x-chip :href="$urlStatus($k)" :active="$statusAktif === $k" :count="$hitung[$k] ?? 0">{{ $label }}</x-chip>
                    @endforeach
                </x-slot:chips>
            </x-filter-bar>

            <x-table caption="Absensi harian {{ $tanggal->translatedFormat('d F Y') }}" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Pegawai</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($baris as ['pegawai' => $p, 'h' => $h])
                    @php $bedaMesin = $h['dikoreksi'] && ($h['scan_masuk'] !== $h['masuk'] || $h['scan_keluar'] !== $h['keluar']); @endphp
                    <tr>
                        <td>
                            <div class="hr-sel-utama">
                                <x-avatar :name="$p->nama_lengkap" size="sm" />
                                <div class="min-w-0">
                                    <a href="{{ route('admin.absensi.pegawai', $p) }}" class="hr-sel-judul">{{ $p->nama_lengkap }}</a>
                                    <span class="hr-sel-sub">{{ collect([$p->kepegawaian?->jabatan, $p->kepegawaian?->unit_kerja])->filter()->implode(' · ') ?: '-' }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($h['masuk'])<span class="abs-jam">{{ $h['masuk'] }}</span>@else<span class="abs-jam is-kosong">–</span>@endif
                            @if ($h['jadwal'])
                                <span class="hr-sel-sub">Jadwal {{ $h['jadwal']['masuk'] }}</span>
                            @elseif (! $p->jadwalKerja)
                                <span class="hr-sel-sub abs-teks-peringatan"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Jam kerja belum diatur</span>
                            @else
                                <span class="hr-sel-sub">Bukan hari kerja</span>
                            @endif
                        </td>
                        <td>
                            @if ($h['keluar'])<span class="abs-jam">{{ $h['keluar'] }}</span>@else<span class="abs-jam is-kosong">–</span>@endif
                            @if ($h['jadwal'])<span class="hr-sel-sub">Jadwal {{ $h['jadwal']['pulang'] }}</span>@endif
                        </td>
                        <td>
                            @include('admin.absensi._status', ['h' => $h])
                            @if ($bedaMesin)<span class="hr-sel-sub mt-1">Data mesin {{ $h['scan_masuk'] ?? '–' }} → {{ $h['scan_keluar'] ?? '–' }}</span>@endif
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                @if ($bisaKelola)
                                    @include('admin.absensi._tombol-koreksi', ['pegawai' => $p, 'h' => $h])
                                @endif
                                <x-icon-button icon="bi-calendar3" label="Riwayat absensi" aria-label="Riwayat absensi {{ $p->nama_lengkap }}" :href="route('admin.absensi.pegawai', $p)" />
                            </div>
                        </td>
                    </tr>
                @empty
                    @if (! $jumlahPegawai && ! request()->filled('q') && ! request()->filled('unit'))
                        <x-table-empty :colspan="5" icon="bi-people" title="Belum ada pegawai yang diabsen">
                            Pilih pegawai yang ikut absensi terlebih dahulu di Pengaturan Absensi.
                            @if ($bisaKelola)
                                <x-slot:action><x-button :href="route('admin.absensi.pengaturan', ['tempel' => 1])" icon="bi-person-plus">Pilih pegawai</x-button></x-slot:action>
                            @endif
                        </x-table-empty>
                    @else
                        <x-table-empty :colspan="5" icon="bi-search" title="Tidak ada pegawai yang cocok">
                            Coba kata kunci, bagian, atau keterangan lain.
                            <x-slot:action><x-button :href="$reset" variant="secondary" icon="bi-x-circle">Reset filter</x-button></x-slot:action>
                        </x-table-empty>
                    @endif
                @endforelse
            </x-table>
        </x-card>
    </div>
</main>

@if ($bisaKelola)
    @include('admin.absensi._koreksi')
@endif
@endsection
