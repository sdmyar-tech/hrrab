@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $tgl = fn ($t) => $t ? \Carbon\Carbon::parse($t)->translatedFormat('d M Y') : '–';
    $akanDisimpan = $r['baru'] + $r['diperbarui'];
    $peringatan = count($r['belum_dipetakan']) + count($r['tidak_valid']) + count($r['tidak_ikut']);
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi {{ $ik['import'] }}"></i></span>
                <div>
                    <p class="eyebrow mb-1">Import Data Mesin</p>
                    <h1 class="h3 mb-1">Cek Data Sebelum Disimpan</h1>
                    <p class="text-muted mb-0 text-break"><i class="bi bi-file-earmark-spreadsheet me-1"></i>{{ $namaFile }}</p>
                </div>
            </div>
        </div>

        @include('admin.absensi._nav')

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xl-7">
                <section class="panel p-3 p-md-4">
                    @include('admin.absensi._tahap-import', ['tahap' => 2])

                    @if ($akanDisimpan)
                        <div class="d-flex gap-3 align-items-start mb-3">
                            <span class="absensi-kartu-ikon success"><i class="bi bi-clipboard-check"></i></span>
                            <div>
                                <h2 class="h5 fw-bold mb-1">Siap disimpan: {{ $akanDisimpan }} hari absensi</h2>
                                <p class="text-muted mb-0">Periode <strong>{{ $tgl($r['periode_dari']) }} – {{ $tgl($r['periode_sampai']) }}</strong> · {{ $r['pegawai_dinilai'] }} pegawai akan dinilai otomatis.</p>
                            </div>
                        </div>
                    @else
                        <div class="d-flex gap-3 align-items-start mb-3">
                            <span class="absensi-kartu-ikon secondary"><i class="bi bi-check2-all"></i></span>
                            <div>
                                <h2 class="h5 fw-bold mb-1">Tidak ada data baru</h2>
                                <p class="text-muted mb-0">Semua data di file ini sudah tersimpan sebelumnya.</p>
                            </div>
                        </div>
                    @endif

                    <ul class="list-unstyled small d-grid gap-2 mb-4">
                        <li><i class="bi bi-plus-circle text-success me-2"></i><strong>{{ $r['baru'] }}</strong> hari baru</li>
                        @if ($r['diperbarui'])<li><i class="bi bi-arrow-repeat text-primary me-2"></i><strong>{{ $r['diperbarui'] }}</strong> hari diperbarui (jam di mesin berubah)</li>@endif
                        @if ($r['sama'])<li><i class="bi bi-check2-all text-muted me-2"></i>{{ $r['sama'] }} hari sudah ada & sama — dilewati</li>@endif
                        @if ($r['dikoreksi'])<li><i class="bi bi-shield-check text-warning me-2"></i>{{ $r['dikoreksi'] }} hari sudah dikoreksi SDM — tidak ditimpa</li>@endif
                    </ul>

                    <form action="{{ route('admin.absensi.import.simpan') }}" method="POST" class="d-flex flex-wrap gap-2">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <a href="{{ route('admin.absensi.import') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Ganti file</a>
                        <button type="submit" class="btn btn-primary flex-grow-1" @disabled(! $akanDisimpan)>
                            <i class="bi bi-check-lg me-1"></i> Simpan {{ $akanDisimpan }} hari
                        </button>
                    </form>
                </section>
            </div>

            <div class="col-12 col-xl-5 d-grid gap-3">
                @if (! $peringatan)
                    <section class="panel p-3 p-md-4">
                        <p class="small text-success mb-0"><i class="bi bi-check-circle me-1"></i>Semua ID di file sudah dikenali dan tidak ada baris bermasalah.</p>
                    </section>
                @endif

                @if ($r['belum_dipetakan'])
                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <h2 class="h6 fw-bold mb-1"><i class="bi bi-question-circle text-warning me-1"></i>{{ count($r['belum_dipetakan']) }} ID belum dikenali</h2>
                                <p class="small text-muted mb-0">Tetap disimpan. Setelah import, hubungkan ke pegawainya agar ikut dinilai.</p>
                            </div>
                        </div>
                        <div class="list-group list-group-flush dash-scroll" style="max-height:260px;">
                            @foreach ($r['belum_dipetakan'] as $b)
                                <div class="list-group-item d-flex justify-content-between small">
                                    <span><span class="badge text-bg-light border me-1">ID {{ $b['finger_id'] }}</span>{{ $b['nama'] ?? 'tanpa nama' }}</span>
                                    <span class="text-muted">{{ $b['hari'] }} hari</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($r['tidak_ikut'])
                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <h2 class="h6 fw-bold mb-1"><i class="bi bi-person-dash me-1"></i>{{ count($r['tidak_ikut']) }} pegawai tidak ikut absensi</h2>
                                <p class="small text-muted mb-0">Datanya disimpan, tetapi tidak dinilai. Ubah di Pengaturan bila perlu.</p>
                            </div>
                        </div>
                        <div class="list-group list-group-flush dash-scroll" style="max-height:200px;">
                            @foreach ($r['tidak_ikut'] as $b)
                                <div class="list-group-item d-flex justify-content-between small"><span>{{ $b['nama'] }}</span><span class="text-muted">ID {{ $b['finger_id'] }}</span></div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($r['tidak_valid'])
                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <h2 class="h6 fw-bold mb-1"><i class="bi bi-x-circle text-danger me-1"></i>{{ count($r['tidak_valid']) }} baris rusak dilewati</h2>
                                <p class="small text-muted mb-0">Biasanya data sampah dari mesin (ID minus / nama aneh). Aman diabaikan.</p>
                            </div>
                        </div>
                        <div class="list-group list-group-flush dash-scroll" style="max-height:200px;">
                            @foreach ($r['tidak_valid'] as $t)
                                <div class="list-group-item small"><span class="text-muted">Baris {{ $t['baris'] }}:</span> {{ $t['alasan'] }} <span class="text-muted text-break">({{ $t['isi'] }})</span></div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>
</main>
@include('partials.viz.style')
@endsection
