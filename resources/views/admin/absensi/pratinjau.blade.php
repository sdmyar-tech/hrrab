@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $tgl = fn ($t) => $t ? \Carbon\Carbon::parse($t)->translatedFormat('d M Y') : '–';
    $akanDisimpan = $r['baru'] + $r['diperbarui'];
    $jumlahMasalah = count($r['belum_dipetakan']) + count($r['tidak_valid']) + count($r['tidak_ikut']);
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Cek Data Sebelum Disimpan" eyebrow="Import Data Mesin" :back="route('admin.absensi.import')" back-label="Import Data Mesin">
            <x-badge tone="neutral" icon="bi-file-earmark-spreadsheet" class="abs-badge-panjang">{{ $namaFile }}</x-badge>
            <x-badge tone="info">{{ $r['format'] }}</x-badge>
        </x-page-header>

        @include('admin.absensi._nav')

        <x-card class="mb-4" aria-label="Ringkasan pemeriksaan">
            @include('admin.absensi._tahap-import', ['tahap' => 2])
            <div class="abs-ringkas-impor">
                <span class="abs-ringkas-impor-ikon {{ $akanDisimpan ? 'hr-tone-success' : 'hr-tone-neutral' }}" aria-hidden="true">
                    <i class="bi {{ $akanDisimpan ? 'bi-clipboard-check' : 'bi-check2-all' }}"></i>
                </span>
                <div class="min-w-0">
                    @if ($akanDisimpan)
                        <h2 class="abs-ringkas-impor-judul">Siap disimpan: {{ $akanDisimpan }} hari absensi</h2>
                        <p class="abs-ringkas-impor-teks">Periode <strong>{{ $tgl($r['periode_dari']) }} – {{ $tgl($r['periode_sampai']) }}</strong> · {{ $r['pegawai_dinilai'] }} pegawai akan dinilai otomatis.</p>
                    @else
                        <h2 class="abs-ringkas-impor-judul">Tidak ada data baru</h2>
                        <p class="abs-ringkas-impor-teks">Semua data di file ini sudah tersimpan sebelumnya.</p>
                    @endif
                </div>
            </div>
        </x-card>

        <section class="row g-3 mb-4" aria-label="Rincian hasil pemeriksaan">
            <div class="col-6 col-xl-3"><x-stat-card label="Hari baru" :value="$r['baru']" icon="bi-plus-circle" tone="success" meta="Akan ditambahkan" /></div>
            <div class="col-6 col-xl-3"><x-stat-card label="Diperbarui" :value="$r['diperbarui']" icon="bi-arrow-repeat" tone="info" meta="Jam di mesin berubah" /></div>
            <div class="col-6 col-xl-3"><x-stat-card label="Sudah ada" :value="$r['sama']" icon="bi-check2-all" tone="neutral" meta="Sama persis, dilewati" /></div>
            <div class="col-6 col-xl-3"><x-stat-card label="Dikoreksi SDM" :value="$r['dikoreksi']" icon="bi-shield-check" tone="warning" meta="Tidak ditimpa" /></div>
        </section>

        <x-card :padding="false" title="Pemeriksaan per Baris" icon="bi-list-check" class="abs-kartu-periksa"
            subtitle="Baris rusak ditandai merah dan dilewati. ID yang belum dikenali tetap disimpan.">
            @if ($jumlahMasalah)
                <x-slot:actions>
                    @if (count($r['tidak_valid']))<x-badge tone="danger">{{ count($r['tidak_valid']) }} baris rusak</x-badge>@endif
                    @if (count($r['belum_dipetakan']))<x-badge tone="warning">{{ count($r['belum_dipetakan']) }} ID belum dikenali</x-badge>@endif
                    @if (count($r['tidak_ikut']))<x-badge tone="neutral">{{ count($r['tidak_ikut']) }} tidak ikut</x-badge>@endif
                </x-slot:actions>
            @endif
            <x-table caption="Baris bermasalah di file import" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Baris / ID</th>
                        <th>Isi di File</th>
                        <th>Hasil</th>
                        <th>Keterangan</th>
                    </tr>
                </x-slot:head>
                @foreach ($r['tidak_valid'] as $t)
                    <tr class="abs-baris-galat">
                        <td class="text-nowrap"><span class="hr-sel-judul">Baris {{ $t['baris'] }}</span></td>
                        <td><span class="text-break">{{ $t['isi'] }}</span></td>
                        <td><x-badge tone="danger" icon="bi-x-circle">Dilewati</x-badge></td>
                        <td>{{ $t['alasan'] }}<span class="hr-sel-sub">Biasanya data sampah dari mesin (ID minus / nama aneh). Aman diabaikan.</span></td>
                    </tr>
                @endforeach
                @foreach ($r['belum_dipetakan'] as $b)
                    <tr class="abs-baris-peringatan">
                        <td class="text-nowrap"><span class="hr-sel-judul">ID {{ $b['finger_id'] }}</span></td>
                        <td>{{ $b['nama'] ?? 'Tanpa nama' }}<span class="hr-sel-sub">{{ $b['hari'] }} hari data</span></td>
                        <td><x-badge tone="warning" icon="bi-question-circle">Belum dikenali</x-badge></td>
                        <td>Tetap disimpan. Setelah import, hubungkan ke pegawainya agar ikut dinilai.</td>
                    </tr>
                @endforeach
                @foreach ($r['tidak_ikut'] as $b)
                    <tr>
                        <td class="text-nowrap"><span class="hr-sel-judul">ID {{ $b['finger_id'] }}</span></td>
                        <td>{{ $b['nama'] }}<span class="hr-sel-sub">{{ $b['hari'] }} hari data</span></td>
                        <td><x-badge tone="neutral" icon="bi-person-dash">Tidak dinilai</x-badge></td>
                        <td>Pegawai tidak ikut absensi. Datanya disimpan, tetapi tidak dinilai. Ubah di Pengaturan bila perlu.</td>
                    </tr>
                @endforeach
                @unless ($jumlahMasalah)
                    <x-table-empty :colspan="4" icon="bi-check-circle" title="Semua baris valid">
                        Semua ID di file sudah dikenali dan tidak ada baris bermasalah.
                    </x-table-empty>
                @endunless
            </x-table>
        </x-card>

        <form action="{{ route('admin.absensi.import.simpan') }}" method="POST" class="hr-bilah-aksi mt-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <p class="hr-filter-bar-jumlah mb-0 d-none d-sm-block" aria-live="polite">
                @if ($akanDisimpan)<strong>{{ $akanDisimpan }}</strong> hari absensi akan disimpan.@else Tidak ada data baru untuk disimpan.@endif
            </p>
            <div class="d-flex flex-wrap gap-2 ms-auto">
                <x-button variant="secondary" :href="route('admin.absensi.import')">Batal</x-button>
                <x-button type="submit" icon="bi-check-lg" :disabled="! $akanDisimpan">Konfirmasi Import{{ $akanDisimpan ? ' (' . $akanDisimpan . ' hari)' : '' }}</x-button>
            </div>
        </form>
    </div>
</main>
@endsection
