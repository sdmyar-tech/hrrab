@extends($layout)

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-upload"></i></span>
                <div>
                    <p class="eyebrow mb-1">Absensi</p>
                    <h1 class="h3 mb-1">Import Data Mesin</h1>
                    <p class="text-muted mb-0">Unggah file absensi dari flashdisk mesin Solution X105. Data dicek dulu sebelum disimpan.</p>
                </div>
            </div>
        </div>

        @include('admin.absensi._nav')

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xl-5">
                <section class="panel p-4">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Unggah File</h2>
                    <form action="{{ route('admin.absensi.import.pratinjau') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xls,.xlsx,.csv,.dat,.txt" required>
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Format: .xls / .xlsx (laporan mesin) atau .dat / .txt (log mentah). Maks. 20 MB.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Cek Data</button>
                    </form>

                    <hr>
                    <h3 class="h6 fw-bold">Cara ambil file dari mesin</h3>
                    <ol class="small text-muted ps-3 mb-3">
                        <li>Colok flashdisk ke mesin absen.</li>
                        <li>Menu → <strong>Flashdisk / USB</strong> → unduh <strong>laporan absensi</strong> periode yang diinginkan.</li>
                        <li>Unggah file tersebut di sini.</li>
                    </ol>
                    <h3 class="h6 fw-bold">Yang diambil dari file</h3>
                    <ul class="small text-muted ps-3 mb-0">
                        <li>Hanya <strong>AC-No.</strong> (ID mesin), <strong>Date</strong>, <strong>Clock In</strong>, <strong>Clock Out</strong>.</li>
                        <li>Kolom Late / Early / OT dari mesin <strong>diabaikan</strong> — penilaian dihitung HRRAB dari jam kerja tiap pegawai.</li>
                        <li>Aman diimport ulang: data yang sama tidak dobel, hari yang sudah dikoreksi SDM tidak ditimpa.</li>
                    </ul>
                </section>
            </div>

            <div class="col-12 col-xl-7">
                <section class="panel">
                    <div class="panel-header">
                        <h2 class="h6 fw-bold mb-0"><i class="bi bi-clock-history me-1"></i>Riwayat Import</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Periode</th>
                                    <th class="text-end">Baru</th>
                                    <th class="text-end">Diperbarui</th>
                                    <th class="text-end">Dilewati</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($riwayat as $r)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold d-block text-break">{{ $r->nama_file }}</span>
                                            <small class="text-muted">{{ $r->created_at->translatedFormat('d M Y H:i') }} &middot; {{ $r->diimportOleh?->name ?? '-' }}</small>
                                        </td>
                                        <td class="small text-nowrap">
                                            @if ($r->periode_dari)
                                                <a href="{{ route('admin.absensi.rekap', ['dari' => $r->periode_dari->toDateString(), 'sampai' => $r->periode_sampai->toDateString()]) }}">
                                                    {{ $r->periode_dari->format('d/m/Y') }} – {{ $r->periode_sampai->format('d/m/Y') }}
                                                </a>
                                            @else – @endif
                                        </td>
                                        <td class="text-end">{{ $r->jumlah_baru }}</td>
                                        <td class="text-end">{{ $r->jumlah_diperbarui }}</td>
                                        <td class="text-end text-muted">{{ $r->jumlah_dilewati }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">@include('partials.kosong', ['teks' => 'Belum ada file yang diimport.', 'ikonKosong' => 'bi-upload'])</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($riwayat->hasPages())
                        <div class="panel-footer px-3 py-2 border-top">{{ $riwayat->links('pagination::bootstrap-5') }}</div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</main>
@include('admin.absensi._gaya')
@endsection
