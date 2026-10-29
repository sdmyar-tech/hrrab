@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $hasil = session('hasil_import');
    $tgl = fn ($t) => $t ? \Carbon\Carbon::parse($t)->translatedFormat('d M Y') : '–';
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi {{ $ik['import'] }}"></i></span>
                <div>
                    <p class="eyebrow mb-1">Absensi</p>
                    <h1 class="h3 mb-1">Import Data Mesin</h1>
                    <p class="text-muted mb-0">Masukkan file dari flashdisk mesin absen. Data dicek dulu sebelum disimpan.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.absensi.import.export', 'eksporTeks' => 'Ekspor Riwayat'])
            </div>
        </div>

        @include('admin.absensi._nav')

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xl-7">
                <section class="panel p-3 p-md-4">
                    @if ($hasil)
                        @include('admin.absensi._tahap-import', ['tahap' => 3])
                        <div class="text-center py-2">
                            <span class="absensi-kartu-ikon success mx-auto mb-2" style="width:56px;height:56px;font-size:1.6rem;"><i class="bi bi-check-lg"></i></span>
                            <h2 class="h5 fw-bold mb-1">Import berhasil</h2>
                            <p class="text-muted mb-3">Periode {{ $tgl($hasil['dari']) }} – {{ $tgl($hasil['sampai']) }}:
                                <strong>{{ $hasil['baru'] }}</strong> hari baru, <strong>{{ $hasil['diperbarui'] }}</strong> diperbarui, {{ $hasil['dilewati'] }} dilewati.</p>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                @if ($hasil['belum_dipetakan'])
                                    <a href="{{ route('admin.absensi.pengaturan', ['tab' => 'pemetaan']) }}" class="btn btn-warning">
                                        <i class="bi bi-link-45deg me-1"></i> Hubungkan {{ $hasil['belum_dipetakan'] }} ID mesin baru
                                    </a>
                                @endif
                                <a href="{{ route('admin.absensi.harian', ['tanggal' => $hasil['sampai']]) }}" class="btn btn-primary"><i class="bi {{ $ik['harian'] }} me-1"></i> Lihat absensi</a>
                                <a href="{{ route('admin.absensi.rekap', ['dari' => $hasil['dari'], 'sampai' => $hasil['sampai']]) }}" class="btn btn-outline-secondary"><i class="bi {{ $ik['rekap'] }} me-1"></i> Lihat rekap</a>
                            </div>
                        </div>
                        <hr class="my-4">
                        <p class="small fw-semibold mb-2">Import file lain</p>
                    @else
                        @include('admin.absensi._tahap-import', ['tahap' => 1])
                    @endif

                    <form action="{{ route('admin.absensi.import.pratinjau') }}" method="POST" enctype="multipart/form-data" id="formImport">
                        @csrf
                        <label class="abs-dropzone mb-3 @error('file') border-danger @enderror" data-dropzone>
                            <input type="file" name="file" accept=".xls,.xlsx,.csv,.dat,.txt" required data-input-file>
                            <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                            <span class="fw-semibold" data-nama-file>Klik untuk pilih file, atau seret file ke sini</span>
                            <small class="text-muted">File laporan dari mesin (.xls / .xlsx / .csv / .dat) · maks. 20 MB</small>
                        </label>
                        @error('file')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-primary w-100" disabled data-tombol-cek>
                            <i class="bi bi-search me-1"></i> Cek Data
                        </button>
                    </form>

                    <details class="mt-3 small">
                        <summary class="fw-semibold" style="cursor:pointer;">Cara mengambil file dari mesin absen</summary>
                        <ol class="text-muted ps-3 mt-2 mb-2">
                            <li>Colok flashdisk ke mesin absen.</li>
                            <li>Buka <strong>Menu → Flashdisk / USB</strong>, lalu unduh <strong>laporan absensi</strong> periode yang diinginkan.</li>
                            <li>Colok flashdisk ke komputer, lalu pilih file tersebut di atas.</li>
                        </ol>
                        <p class="text-muted mb-0">Yang diambil hanya <strong>ID (AC-No.)</strong>, <strong>tanggal</strong>, dan <strong>jam finger</strong>. Telat/lembur dihitung otomatis oleh sistem sesuai jam kerja tiap pegawai. Aman diimport berulang — data tidak akan dobel, dan hasil koreksi SDM tidak tertimpa.</p>
                    </details>
                </section>
            </div>

            <div class="col-12 col-xl-5">
                <section class="panel">
                    <div class="panel-header">
                        <h2 class="h6 fw-bold mb-0"><i class="bi bi-clock-history me-1"></i>Riwayat Import</h2>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($riwayat as $r)
                            <div class="list-group-item d-flex justify-content-between gap-2">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate" title="{{ $r->nama_file }}"><i class="bi bi-file-earmark-spreadsheet me-1 text-success"></i>{{ $r->nama_file }}</div>
                                    <small class="text-muted d-block">{{ $r->created_at->translatedFormat('d M Y H:i') }} · {{ $r->diimportOleh?->name ?? '-' }}</small>
                                    @if ($r->periode_dari)
                                        <a href="{{ route('admin.absensi.rekap', ['dari' => $r->periode_dari->toDateString(), 'sampai' => $r->periode_sampai->toDateString()]) }}" class="small">
                                            {{ $r->periode_dari->format('d/m/Y') }} – {{ $r->periode_sampai->format('d/m/Y') }}
                                        </a>
                                    @endif
                                </div>
                                <div class="text-end small text-nowrap">
                                    <span class="badge text-bg-success">+{{ $r->jumlah_baru }}</span>
                                    @if ($r->jumlah_diperbarui)<span class="badge text-bg-primary">{{ $r->jumlah_diperbarui }} diperbarui</span>@endif
                                    @if (auth()->user()->isAdmin())
                                        <form action="{{ route('admin.absensi.import.hapus', $r) }}" method="POST" class="mt-1">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0"
                                                data-confirm="true" data-icon="warning" data-title="Batalkan import?"
                                                data-text="Baris absensi dari file {{ $r->nama_file }} akan dihapus (baris yang sudah dikoreksi tetap dipertahankan)."
                                                data-confirm-text="Ya, Batalkan Import" data-confirm-color="#dc3545" data-cancel-text="Kembali"><i class="bi bi-arrow-counterclockwise me-1"></i>Batalkan import</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            @include('partials.kosong', ['teks' => 'Belum ada file yang diimport.', 'ikonKosong' => $ik['import']])
                        @endforelse
                    </div>
                    @if ($riwayat->hasPages())
                        <div class="panel-footer px-3 py-2 border-top">{{ $riwayat->links('pagination::bootstrap-5') }}</div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const zona = document.querySelector('[data-dropzone]');
        const input = document.querySelector('[data-input-file]');
        const label = document.querySelector('[data-nama-file]');
        const tombol = document.querySelector('[data-tombol-cek]');
        const perbarui = function () {
            const f = input.files[0];
            zona.classList.toggle('ada-file', !!f);
            label.textContent = f ? f.name + ' (' + Math.max(1, Math.round(f.size / 1024)) + ' KB)' : 'Klik untuk pilih file, atau seret file ke sini';
            tombol.disabled = !f;
        };
        input.addEventListener('change', perbarui);
        ['dragenter', 'dragover'].forEach((ev) => zona.addEventListener(ev, (e) => { e.preventDefault(); zona.classList.add('seret'); }));
        ['dragleave', 'drop'].forEach((ev) => zona.addEventListener(ev, () => zona.classList.remove('seret')));
        zona.addEventListener('drop', function (e) {
            e.preventDefault();
            if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; perbarui(); }
        });
    });
</script>
@endsection
