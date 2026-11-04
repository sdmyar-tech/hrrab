{{-- Tab navigasi antarhalaman modul Absensi (ikon dari satu sumber: TampilanAbsensi::IKON). Gaya: .hr-tabs (ui.css). --}}
@php
    $ikAbs = \App\Support\Absensi\TampilanAbsensi::IKON;
    $kelolaAbs = auth()->user()->can('absensi.manage');
    $tabAbsensi = array_filter([
        ['admin.absensi.harian', ['admin.absensi.harian', 'admin.absensi.pegawai'], $ikAbs['harian'], 'Harian', true],
        ['admin.absensi.rekap', 'admin.absensi.rekap*', $ikAbs['rekap'], 'Rekap', true],
        ['admin.absensi.import', 'admin.absensi.import*', $ikAbs['import'], 'Import', $kelolaAbs],
        ['admin.absensi.izin', 'admin.absensi.izin*', $ikAbs['izin'], 'Izin Telat', $kelolaAbs],
        ['admin.absensi.pengaturan', 'admin.absensi.pengaturan*', $ikAbs['pengaturan'], 'Pengaturan', $kelolaAbs],
    ], fn ($t) => $t[4]);
@endphp
{{-- Kelas absensi-nav tetap dipakai skrip geser-tab (tepi memudar bila tab bisa digeser di HP). --}}
<nav class="hr-tabs absensi-nav" aria-label="Menu absensi">
    @foreach ($tabAbsensi as [$rute, $pola, $ikon, $teks])
        @php $aktifTab = request()->routeIs(...(array) $pola); @endphp
        <a href="{{ route($rute) }}" class="hr-tab {{ $aktifTab ? 'active' : '' }}" @if ($aktifTab) aria-current="page" @endif>
            <i class="bi {{ $ikon }}" aria-hidden="true"></i>{{ $teks }}
        </a>
    @endforeach
</nav>
