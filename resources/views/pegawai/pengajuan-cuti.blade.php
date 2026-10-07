@extends(Auth::user()->panelLayout())

@section('content')
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-calendar2-check"></i></span>
                <div>
                    <div class="eyebrow">Pegawai</div>
                    <h1 class="mb-0">Permohonan Cuti</h1>
                </div>
            </div>
        </div>

        <div class="panel p-4 mb-3">
            <h2 class="h6 fw-bold mb-3">Data Pegawai</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-muted small">Nama Lengkap / NIK</label>
                    <input class="form-control form-control-sm" value="{{ $pegawai?->nama_lengkap ?? '-' }} / {{ $pegawai?->nik ?? '-' }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small">Jabatan</label>
                    <input class="form-control form-control-sm" value="{{ $kepegawaian?->jabatan ?? '-' }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small">Divisi / Unit Kerja</label>
                    <input class="form-control form-control-sm" value="{{ $kepegawaian?->unit_kerja ?? '-' }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small">Mulai Bekerja di Yayasan Abdurrab</label>
                    <input class="form-control form-control-sm" value="{{ optional($kepegawaian?->tanggal_masuk)->format('d-m-Y') ?? '-' }}" readonly>
                </div>
            </div>
        </div>

        @if (! $eligible)
            <div class="alert alert-warning">
                <strong>Belum bisa mengajukan cuti.</strong> Pengajuan cuti hanya untuk pegawai dengan masa kerja lebih dari 1 tahun.
            </div>
        @endif

        <div class="row g-3">
            @if ($eligible)
                <div class="col-12 col-xl-6">
                    <div class="panel p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h6 fw-bold mb-0">Keterangan Cuti</h2>
                            <div class="text-end">
                                <span class="badge text-bg-info d-block mb-1">Sisa Cuti Tahunan: {{ $sisaCuti }} / {{ $annualQuota }} hari</span>
                                <span class="badge text-bg-secondary">Pengajuan Tahun Ini: {{ $jumlahPengajuanTahunIni }} / {{ $maxPengajuanTahunan }} kali</span>
                            </div>
                        </div>

                        <form action="{{ route('pegawai.pengajuan-cuti.store') }}" method="POST" id="formCuti" data-confirm="Ajukan cuti ini?">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Jenis Cuti</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="jenis_cuti" id="jenisTahunan" value="tahunan" @checked(old('jenis_cuti', 'tahunan') === 'tahunan')>
                                        <label class="form-check-label" for="jenisTahunan">Cuti Tahunan</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="jenis_cuti" id="jenisBesar" value="besar" @checked(old('jenis_cuti') === 'besar')>
                                        <label class="form-check-label" for="jenisBesar">Cuti Besar</label>
                                    </div>
                                </div>
                            </div>

                            <div id="sectionBesar" class="mb-3 d-none">
                                <label for="kategori_cuti_besar" class="form-label">Jenis Cuti Besar</label>
                                <select name="kategori_cuti_besar" id="kategori_cuti_besar" class="form-select @error('kategori_cuti_besar') is-invalid @enderror">
                                    <option value="">-- Pilih --</option>
                                    @foreach ($kategoriBesarOptions as $value => $label)
                                        <option value="{{ $value }}" data-durasi="{{ \App\Models\PengajuanCuti::KATEGORI_BESAR_DURASI[$value] ?? '' }}" data-dadakan="{{ in_array($value, \App\Models\PengajuanCuti::KATEGORI_DADAKAN, true) ? 1 : 0 }}" {{ old('kategori_cuti_besar') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small id="durasiHint" class="text-muted d-block mt-1"></small>
                                @error('kategori_cuti_besar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <label for="tanggal_mulai" class="form-label">Tgl Mulai</label>
                                    <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                                        class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                        value="{{ old('tanggal_mulai', now()->addDays(7)->format('Y-m-d')) }}"
                                        min="{{ now()->addDays(7)->format('Y-m-d') }}" required
                                        data-min-normal="{{ now()->addDays(7)->format('Y-m-d') }}"
                                        data-min-dadakan="{{ now()->format('Y-m-d') }}">
                                    <small class="text-muted" id="hintTanggalMulai">Tanggal mulai minimal 7 hari setelah tanggal pengajuan.</small>
                                    @error('tanggal_mulai')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-6">
                                    <label for="tanggal_selesai" class="form-label">Tgl Selesai</label>
                                    <input type="date" name="tanggal_selesai" id="tanggal_selesai"
                                        class="form-control @error('tanggal_selesai') is-invalid @enderror"
                                        value="{{ old('tanggal_selesai') }}" required>
                                    @error('tanggal_selesai')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label for="lama_cuti_display" class="form-label">Lama Cuti</label>
                                <input type="text" id="lama_cuti_display" class="form-control" readonly placeholder="Terisi otomatis">
                            </div>

                            <div class="mb-3">
                                <label for="keterangan" class="form-label">Keterangan Lain dari Pemohon</label>
                                <textarea name="keterangan" id="keterangan" rows="2"
                                    class="form-control @error('keterangan') is-invalid @enderror">{{ old('keterangan') }}</textarea>
                                @error('keterangan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tanggal Pengajuan Cuti</label>
                                <input type="text" class="form-control" value="{{ now()->format('d-m-Y') }}" readonly>
                            </div>

                            <p class="text-muted small mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Pengganti tugas selama cuti akan ditentukan oleh Kepala Bagian Anda saat menyetujui pengajuan ini.
                            </p>

                            <p class="text-muted small mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Cuti maksimal {{ $maxPengajuanTahunan }} kali per tahun dan tidak boleh berturut-turut/bentrok dengan cuti lain yang masih menunggu atau sudah disetujui (minimal jeda 1 hari).
                            </p>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send me-1"></i> Ajukan Cuti
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="col-12 {{ $eligible ? 'col-xl-6' : '' }}">
                <div class="panel p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h6 fw-bold mb-0">Riwayat Pengajuan Cuti</h2>
                        @include('partials.tombol-ekspor', ['route' => 'pegawai.pengajuan-cuti.export'])
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th>Tanggal</th>
                                    <th>Lama</th>
                                    <th>Pengganti</th>
                                    <th>Status</th>
                                    <th>Catatan Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($riwayat as $item)
                                    <tr>
                                        <td>
                                            {{ $item->jenisLabel() }}
                                            @if ($item->kategoriBesarLabel())
                                                <small class="text-muted d-block">{{ $item->kategoriBesarLabel() }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $item->tanggal_mulai->format('d M Y') }} - {{ $item->tanggal_selesai->format('d M Y') }}</td>
                                        <td>{{ $item->lama_cuti }} hari</td>
                                        <td>{{ $item->pengganti_nama ?? '-' }}</td>
                                        <td>
                                            <span class="badge {{ $item->badgeClass() }}">{{ $item->statusLabel() }}</span>
                                            @if ($item->isPending())
                                                <small class="text-muted d-block">Menunggu: {{ $item->currentLevelLabel() }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $item->catatan_admin ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            Belum ada pengajuan cuti.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($eligible)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const jenisRadios = document.querySelectorAll('input[name="jenis_cuti"]');
        const sectionBesar = document.getElementById('sectionBesar');
        const kategoriSelect = document.getElementById('kategori_cuti_besar');
        const durasiHint = document.getElementById('durasiHint');
        const tanggalMulai = document.getElementById('tanggal_mulai');
        const tanggalSelesai = document.getElementById('tanggal_selesai');
        const lamaCutiDisplay = document.getElementById('lama_cuti_display');

        function toggleJenis() {
            const isBesar = document.getElementById('jenisBesar').checked;
            sectionBesar.classList.toggle('d-none', !isBesar);
            kategoriSelect.required = isBesar;
            if (!isBesar) {
                tanggalSelesai.readOnly = false;
                durasiHint.textContent = '';
            }
            updateKategori();
        }

        function addWorkingDays(startDate, totalHariKerja) {
            const result = new Date(startDate.getTime());
            let count = 1;
            while (count < totalHariKerja) {
                result.setDate(result.getDate() + 1);
                const day = result.getDay();
                if (day !== 0 && day !== 6) {
                    count++;
                }
            }
            return result;
        }

        function toDateInputValue(date) {
            const tzOffset = date.getTimezoneOffset() * 60000;
            return new Date(date.getTime() - tzOffset).toISOString().slice(0, 10);
        }

        const hintMulai = document.getElementById('hintTanggalMulai');
        const keterangan = document.getElementById('keterangan');

        // Cuti besar mendadak (duka, melahirkan, bencana, panggilan) boleh mulai hari ini, tanpa aturan H-7.
        function updateAturanTanggal() {
            const opsi = kategoriSelect.options[kategoriSelect.selectedIndex];
            const dadakan = document.getElementById('jenisBesar').checked && opsi && opsi.dataset.dadakan === '1';
            const minBaru = dadakan ? tanggalMulai.dataset.minDadakan : tanggalMulai.dataset.minNormal;
            const minLama = tanggalMulai.min;

            tanggalMulai.min = minBaru;
            if (dadakan && tanggalMulai.value === minLama) {
                tanggalMulai.value = toDateInputValue(new Date());
            } else if (!dadakan && tanggalMulai.value < minBaru) {
                tanggalMulai.value = minBaru;
            }
            hintMulai.textContent = dadakan
                ? 'Cuti mendadak: boleh mulai di hari yang sama dengan pengajuan. Keterangan wajib diisi.'
                : 'Tanggal mulai minimal 7 hari setelah tanggal pengajuan.';
            keterangan.required = dadakan;
        }

        function updateKategori() {
            updateAturanTanggal();
            if (!document.getElementById('jenisBesar').checked) {
                return;
            }
            const selectedOption = kategoriSelect.options[kategoriSelect.selectedIndex];
            const durasi = selectedOption ? parseInt(selectedOption.getAttribute('data-durasi'), 10) : NaN;

            if (!isNaN(durasi)) {
                durasiHint.textContent = 'Kategori ini wajib ' + durasi + ' hari kerja.';
                tanggalSelesai.readOnly = true;
                if (tanggalMulai.value) {
                    const mulai = new Date(tanggalMulai.value + 'T00:00:00');
                    tanggalSelesai.value = toDateInputValue(addWorkingDays(mulai, durasi));
                }
            } else {
                tanggalSelesai.readOnly = false;
                durasiHint.textContent = selectedOption && selectedOption.value
                    ? 'Lama cuti sesuai kebutuhan, isi tanggal selesai secara manual.'
                    : '';
            }
            updateLamaCuti();
        }

        function updateLamaCuti() {
            if (!tanggalMulai.value || !tanggalSelesai.value) {
                lamaCutiDisplay.value = '';
                return;
            }
            const mulai = new Date(tanggalMulai.value + 'T00:00:00');
            const selesai = new Date(tanggalSelesai.value + 'T00:00:00');

            if (document.getElementById('jenisBesar').checked) {
                let hariKerja = 0;
                const cursor = new Date(mulai.getTime());
                while (cursor <= selesai) {
                    const day = cursor.getDay();
                    if (day !== 0 && day !== 6) {
                        hariKerja++;
                    }
                    cursor.setDate(cursor.getDate() + 1);
                }
                lamaCutiDisplay.value = hariKerja > 0 ? hariKerja + ' hari kerja' : '';
            } else {
                const diffDays = Math.round((selesai - mulai) / (1000 * 60 * 60 * 24)) + 1;
                lamaCutiDisplay.value = diffDays > 0 ? diffDays + ' hari' : '';
            }
        }

        jenisRadios.forEach(function (radio) {
            radio.addEventListener('change', toggleJenis);
        });
        kategoriSelect.addEventListener('change', updateKategori);
        tanggalMulai.addEventListener('change', function () {
            if (!document.getElementById('jenisBesar').checked) { updateLamaCuti(); return; }
            const opsi = kategoriSelect.options[kategoriSelect.selectedIndex];
            const durasi = opsi ? parseInt(opsi.getAttribute('data-durasi'), 10) : NaN;
            if (!isNaN(durasi) && tanggalMulai.value) {
                tanggalSelesai.value = toDateInputValue(addWorkingDays(new Date(tanggalMulai.value + 'T00:00:00'), durasi));
            }
            updateLamaCuti();
        });
        tanggalSelesai.addEventListener('change', updateLamaCuti);

        toggleJenis();
        updateLamaCuti();
    });
</script>
@endif
@endsection
