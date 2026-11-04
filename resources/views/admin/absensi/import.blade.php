@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $hasil = session('hasil_import');
    $tgl = fn ($t) => $t ? \Carbon\Carbon::parse($t)->translatedFormat('d M Y') : '–';
    $admin = auth()->user()->can('sistem.override');
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Import Data Mesin" eyebrow="Absensi"
            subtitle="Unggah file dari flashdisk mesin absen. Data dicek dulu sebelum disimpan, dan aman diimport berulang.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.absensi.import.export', 'eksporTeks' => 'Ekspor Riwayat'])
            </x-slot:actions>
        </x-page-header>

        @include('admin.absensi._nav')

        <div class="row g-4 align-items-start mb-4">
            <div class="col-12 col-xl-8">
                @if ($hasil)
                    <x-card class="mb-4" aria-label="Hasil import">
                        @include('admin.absensi._tahap-import', ['tahap' => 3])
                        <div class="abs-hasil" role="status">
                            <span class="abs-hasil-ikon hr-tone-success" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                            <h2 class="abs-hasil-judul">Import berhasil</h2>
                            <p class="abs-hasil-teks">Periode {{ $tgl($hasil['dari']) }} – {{ $tgl($hasil['sampai']) }}:
                                <strong>{{ $hasil['baru'] }}</strong> hari baru, <strong>{{ $hasil['diperbarui'] }}</strong> diperbarui, {{ $hasil['dilewati'] }} dilewati.</p>
                            <div class="abs-hasil-aksi">
                                @if ($hasil['belum_dipetakan'])
                                    <x-button variant="outline" :href="route('admin.absensi.pengaturan', ['tab' => 'pemetaan'])" icon="bi-link-45deg">Hubungkan {{ $hasil['belum_dipetakan'] }} ID mesin baru</x-button>
                                @endif
                                <x-button variant="secondary" :href="route('admin.absensi.rekap', ['dari' => $hasil['dari'], 'sampai' => $hasil['sampai']])" :icon="$ik['rekap']">Lihat rekap</x-button>
                                <x-button :href="route('admin.absensi.harian', ['tanggal' => $hasil['sampai']])" :icon="$ik['harian']">Lihat absensi</x-button>
                            </div>
                        </div>
                    </x-card>
                @endif

                <x-card :title="$hasil ? 'Import file lain' : 'Unggah file mesin'" icon="bi-cloud-arrow-up"
                    subtitle="Laporan absensi dari mesin (.xls, .xlsx, .csv) atau log mentah (.dat, .txt), maksimal 20 MB.">
                    @unless ($hasil)
                        @include('admin.absensi._tahap-import', ['tahap' => 1])
                    @endunless

                    <form action="{{ route('admin.absensi.import.pratinjau') }}" method="POST" enctype="multipart/form-data" id="formImport">
                        @csrf
                        {{-- Area unggah: input file asli menutupi seluruh area (klik, seret-lepas, & Tab/Spasi tetap berfungsi). --}}
                        <label class="abs-dropzone @error('file') is-galat @enderror" data-dropzone>
                            <input type="file" name="file" class="abs-dropzone-input" accept=".xls,.xlsx,.csv,.dat,.txt" required data-input-file
                                aria-describedby="dropzoneBantuan @error('file') dropzoneGalat @enderror">
                            <span class="abs-dropzone-ikon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span>
                            <span class="abs-dropzone-judul" data-dropzone-judul>Seret file ke sini, atau <span class="abs-tautan">klik untuk memilih</span></span>
                            <span class="abs-dropzone-sub" id="dropzoneBantuan">File laporan mesin: .xls, .xlsx, .csv, .dat, atau .txt · maks. 20 MB</span>
                        </label>
                        @error('file')
                            <div class="invalid-feedback d-block mt-2" id="dropzoneGalat"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback mt-2" data-galat-berkas role="alert"></div>

                        <div class="abs-berkas" data-berkas hidden>
                            <span class="abs-berkas-ikon hr-tone-success" aria-hidden="true"><i class="bi bi-file-earmark-spreadsheet"></i></span>
                            <div class="min-w-0 flex-grow-1">
                                <p class="abs-berkas-nama" data-nama-file></p>
                                <p class="abs-berkas-ukuran" data-ukuran-file></p>
                            </div>
                            <x-button variant="ghost" size="sm" icon="bi-x-lg" data-hapus-berkas>Hapus pilihan</x-button>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <x-button type="submit" icon="bi-search" disabled data-tombol-cek>Cek Data</x-button>
                        </div>
                    </form>
                </x-card>
            </div>

            <div class="col-12 col-xl-4">
                <x-card title="Cara mengambil file" icon="bi-usb-drive" subtitle="Dari mesin absen ke komputer.">
                    <ol class="hr-daftar-info mb-4">
                        <li><i class="bi bi-1-circle" aria-hidden="true"></i>Colok flashdisk ke mesin absen.</li>
                        <li><i class="bi bi-2-circle" aria-hidden="true"></i>Buka <strong>Menu → Flashdisk / USB</strong>, lalu unduh <strong>laporan absensi</strong> periode yang diinginkan.</li>
                        <li><i class="bi bi-3-circle" aria-hidden="true"></i>Colok flashdisk ke komputer, lalu pilih atau seret file tersebut ke area unggah.</li>
                    </ol>
                    <ul class="hr-daftar-info">
                        <li><i class="bi bi-info-circle" aria-hidden="true"></i>Yang diambil hanya <strong>ID (AC-No.)</strong>, <strong>tanggal</strong>, dan <strong>jam finger</strong>. Telat & lembur dihitung otomatis sesuai jam kerja tiap pegawai.</li>
                        <li><i class="bi bi-shield-check" aria-hidden="true"></i>Aman diimport berulang: data tidak dobel, dan hasil koreksi SDM tidak tertimpa.</li>
                    </ul>
                </x-card>
            </div>
        </div>

        <x-card :padding="false" title="Riwayat Import" icon="bi-clock-history" subtitle="File mesin yang sudah disimpan, terbaru di atas.">
            <x-table caption="Riwayat import file mesin" sticky striped>
                <x-slot:head>
                    <tr>
                        <th>Waktu Import</th>
                        <th>Berkas</th>
                        <th class="hr-angka">Jumlah Baris</th>
                        <th>Status</th>
                        <th>Diimport Oleh</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($riwayat as $r)
                    @php $tersimpan = $r->jumlah_baru + $r->jumlah_diperbarui; @endphp
                    <tr>
                        <td class="text-nowrap">
                            <span class="d-block">{{ $r->created_at->translatedFormat('d M Y') }}</span>
                            <span class="hr-sel-sub">{{ $r->created_at->format('H:i') }}</span>
                        </td>
                        <td>
                            <span class="hr-sel-judul"><i class="bi bi-file-earmark-spreadsheet me-1 text-muted" aria-hidden="true"></i>{{ $r->nama_file }}</span>
                            @if ($r->periode_dari)
                                <span class="hr-sel-sub">Periode {{ $r->periode_dari->translatedFormat('d M') }} – {{ $r->periode_sampai->translatedFormat('d M Y') }}</span>
                            @endif
                        </td>
                        <td class="hr-angka">{{ number_format($r->jumlah_baris, 0, ',', '.') }}</td>
                        <td>
                            <x-badge :tone="$tersimpan ? 'success' : 'neutral'" dot>{{ $tersimpan ? 'Tersimpan' : 'Tanpa data baru' }}</x-badge>
                            <span class="hr-sel-sub mt-1">{{ collect([$r->jumlah_baru . ' baru', $r->jumlah_diperbarui ? $r->jumlah_diperbarui . ' diperbarui' : null, $r->jumlah_dilewati ? $r->jumlah_dilewati . ' dilewati' : null])->filter()->implode(' · ') }}</span>
                        </td>
                        <td>{{ $r->diimportOleh?->name ?? '-' }}</td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                @if ($r->periode_dari)
                                    <x-icon-button :icon="$ik['rekap']" label="Lihat rekap periode ini"
                                        :href="route('admin.absensi.rekap', ['dari' => $r->periode_dari->toDateString(), 'sampai' => $r->periode_sampai->toDateString()])" />
                                @else
                                    <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                @endif
                                @if ($admin)
                                    <form action="{{ route('admin.absensi.import.hapus', $r) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <x-icon-button type="submit" icon="bi-arrow-counterclockwise" label="Batalkan import" tone="danger"
                                            data-confirm="true" data-icon="warning" data-title="Batalkan import?"
                                            data-text="Baris absensi dari file {{ $r->nama_file }} akan dihapus (baris yang sudah dikoreksi tetap dipertahankan)."
                                            data-confirm-text="Ya, Batalkan Import" data-cancel-text="Kembali" />
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="6" :icon="$ik['import']" title="Belum ada file yang diimport">
                        Riwayat muncul di sini setelah file mesin pertama disimpan.
                    </x-table-empty>
                @endforelse
            </x-table>
            @if ($riwayat->hasPages())
                <x-slot:footer><x-pagination :paginator="$riwayat" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const zona = document.querySelector('[data-dropzone]');
        if (!zona) return;
        const input = zona.querySelector('[data-input-file]');
        const judul = zona.querySelector('[data-dropzone-judul]');
        const berkas = document.querySelector('[data-berkas]');
        const nama = document.querySelector('[data-nama-file]');
        const ukuran = document.querySelector('[data-ukuran-file]');
        const galat = document.querySelector('[data-galat-berkas]');
        const tombol = document.querySelector('[data-tombol-cek]');
        const judulAwal = judul.innerHTML;
        const JENIS = ['xls', 'xlsx', 'csv', 'dat', 'txt'];
        const MAKS = 20 * 1024 * 1024; // sama dengan validasi server (max:20480 KB)
        const ukuranTeks = (b) => b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';

        // Perbarui tampilan sesuai berkas terpilih: nama & ukuran, cek format/ukuran di sisi klien, tombol Cek Data.
        const perbarui = function () {
            const f = input.files[0];
            const ext = f ? f.name.split('.').pop().toLowerCase() : '';
            const pesan = !f ? '' : (!JENIS.includes(ext) ? 'Format file harus .xls, .xlsx, .csv, .dat, atau .txt (hasil unduhan mesin).'
                : (f.size > MAKS ? 'Ukuran file maksimal 20 MB.' : ''));
            zona.classList.toggle('is-ada', !!f && !pesan);
            zona.classList.toggle('is-galat', !!pesan);
            berkas.hidden = !f;
            nama.textContent = f ? f.name : '';
            ukuran.textContent = f ? ukuranTeks(f.size) + (pesan ? '' : ' · siap dicek') : '';
            galat.textContent = pesan;
            galat.classList.toggle('d-block', !!pesan);
            input.setCustomValidity(pesan);
            input.setAttribute('aria-invalid', pesan ? 'true' : 'false');
            judul.innerHTML = f ? 'File terpilih. Seret file lain atau <span class="abs-tautan">klik untuk mengganti</span>' : judulAwal;
            tombol.disabled = !f || !!pesan;
        };
        input.addEventListener('change', perbarui);

        // Seret & lepas: area tersorot selama berkas di atasnya.
        ['dragenter', 'dragover'].forEach((ev) => zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.add('is-seret'); }));
        zona.addEventListener('dragleave', function (e) { if (!zona.contains(e.relatedTarget)) zona.classList.remove('is-seret'); });
        zona.addEventListener('drop', function (e) {
            e.preventDefault();
            zona.classList.remove('is-seret');
            if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; perbarui(); }
        });

        document.querySelector('[data-hapus-berkas]').addEventListener('click', function () {
            input.value = '';
            perbarui();
            input.focus();
        });
        perbarui(); // berkas bisa tetap terpilih saat kembali lewat tombol Back
    });
</script>
@endsection
