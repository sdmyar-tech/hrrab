@extends($layout)

@section('content')
@php $tgl = fn ($t) => $t ? \Carbon\Carbon::parse($t)->translatedFormat('d M Y') : '–'; @endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-clipboard-check"></i></span>
                <div>
                    <p class="eyebrow mb-1">Import Absensi</p>
                    <h1 class="h3 mb-1">Cek Data Sebelum Disimpan</h1>
                    <p class="text-muted mb-0 text-break">{{ $namaFile }} &middot; {{ $r['format'] }} &middot; periode {{ $tgl($r['periode_dari']) }} – {{ $tgl($r['periode_sampai']) }}</p>
                </div>
            </div>
        </div>

        <div class="row g-2 g-md-3 mb-3">
            @foreach ([
                ['Hari baru', $r['baru'], 'bi-plus-circle', 'success'],
                ['Diperbarui', $r['diperbarui'], 'bi-arrow-repeat', 'primary'],
                ['Sudah ada (sama)', $r['sama'], 'bi-check2-all', 'secondary'],
                ['Dikoreksi SDM (tidak ditimpa)', $r['dikoreksi'], 'bi-shield-check', 'warning'],
            ] as [$label, $nilai, $ikon, $warna])
                <div class="col-6 col-md-3">
                    <div class="panel p-3 h-100 d-flex align-items-center gap-3">
                        <span class="absensi-kartu-ikon text-{{ $warna }}"><i class="bi {{ $ikon }}"></i></span>
                        <span><span class="h4 mb-0 d-block">{{ $nilai }}</span><small class="text-muted">{{ $label }}</small></span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xl-5">
                <section class="panel p-4">
                    <h2 class="h6 fw-bold mb-3">Ringkasan</h2>
                    <ul class="list-unstyled small mb-3 d-grid gap-2">
                        <li><i class="bi bi-people text-success me-2"></i><strong>{{ $r['pegawai_dinilai'] }}</strong> pegawai ikut absensi akan dinilai</li>
                        <li><i class="bi bi-calendar3 me-2"></i>{{ $r['total'] }} hari ber-scan &middot; {{ $r['kosong'] }} baris tanpa scan (dinilai sistem: libur/cuti/alpa)</li>
                        @if ($r['tidak_ikut'])<li><i class="bi bi-person-dash text-secondary me-2"></i>{{ count($r['tidak_ikut']) }} ID milik pegawai yang <strong>tidak ikut absensi</strong> — disimpan, tidak dinilai</li>@endif
                        @if ($r['belum_dipetakan'])<li><i class="bi bi-question-circle text-warning me-2"></i>{{ count($r['belum_dipetakan']) }} ID <strong>belum dipetakan</strong> ke pegawai — disimpan, bisa dipetakan nanti</li>@endif
                        @if ($r['tidak_valid'])<li><i class="bi bi-x-circle text-danger me-2"></i>{{ count($r['tidak_valid']) }} baris rusak/tidak valid dilewati</li>@endif
                    </ul>

                    <form action="{{ route('admin.absensi.import.simpan') }}" method="POST" class="d-flex gap-2">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <a href="{{ route('admin.absensi.import') }}" class="btn btn-outline-secondary flex-fill">Batal</a>
                        <button type="submit" class="btn btn-primary flex-fill" @disabled($r['baru'] + $r['diperbarui'] === 0)>
                            <i class="bi bi-save me-1"></i> Simpan {{ $r['baru'] + $r['diperbarui'] }} hari
                        </button>
                    </form>
                    @if ($r['baru'] + $r['diperbarui'] === 0)
                        <p class="small text-muted mt-2 mb-0">Semua data di file ini sudah tersimpan sebelumnya.</p>
                    @endif
                </section>
            </div>

            <div class="col-12 col-xl-7 d-grid gap-3">
                @if ($r['belum_dipetakan'])
                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <h2 class="h6 fw-bold mb-1"><i class="bi bi-question-circle text-warning me-1"></i>ID Belum Dipetakan</h2>
                                <p class="small text-muted mb-0">Setelah disimpan, hubungkan ID ini ke pegawai di <strong>Pengaturan → ID belum dipetakan</strong>.</p>
                            </div>
                        </div>
                        <div class="table-responsive dash-scroll" style="max-height:300px;">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>ID Mesin</th><th>Nama di mesin</th><th class="text-end">Hari</th></tr></thead>
                                <tbody>
                                    @foreach ($r['belum_dipetakan'] as $b)
                                        <tr><td class="fw-semibold">{{ $b['finger_id'] }}</td><td>{{ $b['nama'] ?? '–' }}</td><td class="text-end">{{ $b['hari'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                @if ($r['tidak_valid'])
                    <section class="panel">
                        <div class="panel-header"><h2 class="h6 fw-bold mb-0"><i class="bi bi-x-circle text-danger me-1"></i>Baris Dilewati</h2></div>
                        <div class="table-responsive dash-scroll" style="max-height:240px;">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Baris</th><th>Alasan</th><th>Isi</th></tr></thead>
                                <tbody>
                                    @foreach ($r['tidak_valid'] as $t)
                                        <tr><td>{{ $t['baris'] }}</td><td>{{ $t['alasan'] }}</td><td class="small text-muted text-break">{{ $t['isi'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                @if ($r['tidak_ikut'])
                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <h2 class="h6 fw-bold mb-1"><i class="bi bi-person-dash me-1"></i>Pegawai Tidak Ikut Absensi</h2>
                                <p class="small text-muted mb-0">Ada di file, tetapi tidak dicentang ikut absensi. Ubah di Pengaturan bila perlu dinilai.</p>
                            </div>
                        </div>
                        <div class="table-responsive dash-scroll" style="max-height:240px;">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>ID</th><th>Pegawai</th><th class="text-end">Hari</th></tr></thead>
                                <tbody>
                                    @foreach ($r['tidak_ikut'] as $b)
                                        <tr><td>{{ $b['finger_id'] }}</td><td>{{ $b['nama'] }}</td><td class="text-end">{{ $b['hari'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>
</main>
@include('admin.absensi._gaya')
@include('partials.viz.style')
@endsection
