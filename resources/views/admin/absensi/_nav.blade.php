{{-- Tab navigasi antarhalaman modul Absensi. --}}
@php
    $tabAbsensi = array_filter([
        ['admin.absensi.harian', 'admin.absensi.harian', 'bi-calendar-day', 'Harian', true],
        ['admin.absensi.rekap', 'admin.absensi.rekap*', 'bi-table', 'Rekap', true],
        ['admin.absensi.import', 'admin.absensi.import*', 'bi-upload', 'Import', auth()->user()->can('kelola-absensi')],
        ['admin.absensi.izin', 'admin.absensi.izin*', 'bi-cloud-drizzle', 'Izin Telat / Pulang', auth()->user()->can('kelola-absensi')],
        ['admin.absensi.pengaturan', 'admin.absensi.pengaturan*', 'bi-sliders', 'Pengaturan', auth()->user()->can('kelola-absensi')],
    ], fn ($t) => $t[4]);
@endphp
<ul class="nav nav-pills absensi-nav mb-3 flex-nowrap overflow-auto">
    @foreach ($tabAbsensi as [$rute, $pola, $ikon, $teks])
        <li class="nav-item">
            <a href="{{ route($rute) }}" class="nav-link text-nowrap {{ request()->routeIs($pola) ? 'active' : '' }}">
                <i class="bi {{ $ikon }} me-1"></i>{{ $teks }}
            </a>
        </li>
    @endforeach
</ul>
