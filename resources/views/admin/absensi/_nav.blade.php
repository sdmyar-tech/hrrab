{{-- Tab navigasi antarhalaman modul Absensi (ikon dari satu sumber: TampilanAbsensi::IKON). --}}
@php
    $ikAbs = \App\Support\Absensi\TampilanAbsensi::IKON;
    $kelolaAbs = auth()->user()->can('kelola-absensi');
    $tabAbsensi = array_filter([
        ['admin.absensi.harian', ['admin.absensi.harian', 'admin.absensi.pegawai'], $ikAbs['harian'], 'Harian', true],
        ['admin.absensi.rekap', 'admin.absensi.rekap*', $ikAbs['rekap'], 'Rekap', true],
        ['admin.absensi.import', 'admin.absensi.import*', $ikAbs['import'], 'Import', $kelolaAbs],
        ['admin.absensi.izin', 'admin.absensi.izin*', $ikAbs['izin'], 'Izin Telat', $kelolaAbs],
        ['admin.absensi.pengaturan', 'admin.absensi.pengaturan*', $ikAbs['pengaturan'], 'Pengaturan', $kelolaAbs],
    ], fn ($t) => $t[4]);
@endphp
<nav aria-label="Menu absensi">
    <ul class="nav absensi-nav mb-3 flex-nowrap overflow-auto">
        @foreach ($tabAbsensi as [$rute, $pola, $ikon, $teks])
            @php $aktifTab = request()->routeIs(...(array) $pola); @endphp
            <li class="nav-item">
                <a href="{{ route($rute) }}" class="nav-link text-nowrap {{ $aktifTab ? 'active' : '' }}" @if ($aktifTab) aria-current="page" @endif>
                    <i class="bi {{ $ikon }} me-1" aria-hidden="true"></i>{{ $teks }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
@include('admin.absensi._gaya')
