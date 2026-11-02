@extends(Auth::user()->panelLayout())

@section('title', 'Pengajuan Cuti')

@section('content')
@php
    $menunggu = $riwayat->filter(fn ($r) => $r->isPending())->count();
    $hariDisetujui = $riwayat->filter(fn ($r) => $r->status === \App\Models\PengajuanCuti::STATUS_APPROVED && $r->tanggal_mulai->year === now()->year)->sum('lama_cuti');

@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Pengajuan Cuti" eyebrow="Pengajuan Saya" subtitle="Ajukan cuti tahunan atau cuti alasan penting, lalu pantau persetujuannya.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'pegawai.pengajuan-cuti.export'])
            </x-slot:actions>
        </x-page-header>

        {{-- ============ Ringkasan saldo ============ --}}
        <section class="row g-3 mb-4" aria-label="Ringkasan cuti">
            <div class="col-6 col-xl-3">
                @if ($eligible)
                    <x-stat-card label="Sisa Cuti Tahunan" :value="$sisaCuti" :unit="'/ ' . $annualQuota . ' hari'" icon="bi-calendar2-check" :tone="$sisaCuti > 0 ? 'success' : 'danger'" meta="Berlaku tahun {{ now()->year }}" />
                @else
                    <x-stat-card label="Cuti Tahunan" value="—" icon="bi-calendar2-check" tone="neutral" meta="Tersedia setelah masa kerja 1 tahun" />
                @endif
            </div>
            <div class="col-6 col-xl-3">
                <x-stat-card label="Pengajuan Tahun Ini" :value="$jumlahPengajuanTahunIni" :unit="'/ ' . $maxPengajuanTahunan . ' kali'" icon="bi-send" tone="primary"
                    :meta="$jumlahPengajuanTahunIni >= $maxPengajuanTahunan ? 'Batas pengajuan tercapai' : ($maxPengajuanTahunan - $jumlahPengajuanTahunIni) . ' kali lagi'" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat-card label="Menunggu Persetujuan" :value="$menunggu" icon="bi-hourglass-split" :tone="$menunggu ? 'warning' : 'neutral'" meta="Belum diputuskan" />
            </div>
            <div class="col-6 col-xl-3">
                <x-stat-card label="Hari Cuti Disetujui" :value="$hariDisetujui" unit="hari" icon="bi-check2-circle" tone="info" meta="Tahun {{ now()->year }}" />
            </div>
        </section>

        @unless ($eligible)
            <x-alert tone="info" title="Cuti tahunan belum tersedia" class="mb-4">
                Masa kerja Anda belum 1 tahun. Anda tetap bisa mengajukan <strong>cuti alasan penting</strong> (menikah, duka, istri melahirkan, bencana, dll.).
            </x-alert>
        @endunless

        <div class="row g-4 align-items-start">
            {{-- ============ Form ============ --}}
            <div class="col-lg-7">
                <x-card title="Formulir Cuti" subtitle="Isian bertanda * wajib diisi." icon="bi-calendar-plus">
                    <form action="{{ route('pegawai.pengajuan-cuti.store') }}" method="POST" id="formCuti" data-confirm="Ajukan cuti ini?">
                        @csrf

                        <fieldset class="hr-field">
                            <legend class="form-label required">Jenis Cuti</legend>
                            <div class="hr-opsi-grup">
                                <label class="hr-opsi {{ $eligible ? '' : 'is-nonaktif' }}" for="jenisTahunan">
                                    <input class="form-check-input" type="radio" name="jenis_cuti" id="jenisTahunan" value="tahunan" @checked($eligible && old('jenis_cuti', 'tahunan') === 'tahunan') @disabled(! $eligible)>
                                    <span><strong>Cuti Tahunan</strong><small>{{ $eligible ? 'Memotong sisa cuti tahunan.' : 'Tersedia setelah 1 tahun bekerja.' }}</small></span>
                                </label>
                                <label class="hr-opsi" for="jenisBesar">
                                    <input class="form-check-input" type="radio" name="jenis_cuti" id="jenisBesar" value="besar" @checked(old('jenis_cuti') === 'besar' || ! $eligible)>
                                    <span><strong>Cuti Besar / Alasan Penting</strong><small>Menikah, duka, melahirkan, bencana, dll.</small></span>
                                </label>
                            </div>
                        </fieldset>

                        <div id="sectionBesar" class="hr-field d-none">
                            <label for="kategori_cuti_besar" class="form-label required">Jenis Cuti Besar</label>
                            <select name="kategori_cuti_besar" id="kategori_cuti_besar" class="form-select @error('kategori_cuti_besar') is-invalid @enderror" aria-describedby="durasiHint">
                                <option value="">Pilih jenis cuti besar</option>
                                @foreach ($kategoriBesarOptions as $value => $label)
                                    <option value="{{ $value }}" data-durasi="{{ \App\Models\PengajuanCuti::KATEGORI_BESAR_DURASI[$value] ?? '' }}" data-dadakan="{{ in_array($value, \App\Models\PengajuanCuti::KATEGORI_DADAKAN, true) ? 1 : 0 }}" {{ old('kategori_cuti_besar') == $value ? 'selected' : '' }} @disabled(! $eligible && in_array($value, \App\Models\PengajuanCuti::KATEGORI_PERLU_MASA_KERJA, true))>{{ $label }}{{ ! $eligible && in_array($value, \App\Models\PengajuanCuti::KATEGORI_PERLU_MASA_KERJA, true) ? ' (setelah 1 tahun)' : '' }}</option>
                                @endforeach
                            </select>
                            @error('kategori_cuti_besar')
                                <div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                            @enderror
                            <div id="durasiHint" class="form-text"></div>
                        </div>

                        <div class="row gx-3">
                            <div class="col-sm-6">
                                <div class="hr-field">
                                    <label for="tanggal_mulai" class="form-label required">Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                                        class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                        value="{{ old('tanggal_mulai', now()->addDays(7)->format('Y-m-d')) }}"
                                        min="{{ now()->addDays(7)->format('Y-m-d') }}" required aria-describedby="hintTanggalMulai"
                                        data-min-normal="{{ now()->addDays(7)->format('Y-m-d') }}"
                                        data-min-dadakan="{{ now()->format('Y-m-d') }}">
                                    @error('tanggal_mulai')
                                        <div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                    @enderror
                                    <div class="form-text" id="hintTanggalMulai">Tanggal mulai minimal 7 hari setelah tanggal pengajuan.</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="hr-field">
                                    <label for="tanggal_selesai" class="form-label required">Tanggal Selesai</label>
                                    <input type="date" name="tanggal_selesai" id="tanggal_selesai"
                                        class="form-control @error('tanggal_selesai') is-invalid @enderror"
                                        value="{{ old('tanggal_selesai') }}" required>
                                    @error('tanggal_selesai')
                                        <div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="hr-hitungan mb-3" aria-live="polite">
                            <i class="bi bi-calculator" aria-hidden="true"></i>
                            <label for="lama_cuti_display" class="mb-0">Lama cuti</label>
                            <input type="text" id="lama_cuti_display" readonly placeholder="Terisi otomatis">
                        </div>

                        <x-textarea name="keterangan" id="keterangan" label="Keterangan" optional rows="2"
                            placeholder="Contoh: menghadiri pernikahan saudara di Padang" maxlength="1000" />

                        <x-button type="submit" size="lg" block icon="bi-send">Ajukan Cuti</x-button>
                        <p class="small text-muted text-center mt-2 mb-0">Diajukan hari ini, {{ now()->translatedFormat('d F Y') }}.</p>
                    </form>
                </x-card>
            </div>

            {{-- ============ Samping: data pegawai + ketentuan ============ --}}
            <div class="col-lg-5 d-grid gap-3">
                <x-card title="Data Pegawai" icon="bi-person-vcard">
                    <dl class="dash-dl mb-0">
                        <div><dt>Nama</dt><dd>{{ $pegawai?->nama_lengkap ?? '-' }}</dd></div>
                        <div><dt>NIK</dt><dd class="angka-utuh">{{ $pegawai?->nik ?? '-' }}</dd></div>
                        <div><dt>Jabatan</dt><dd>{{ $kepegawaian?->jabatan ?? '-' }}</dd></div>
                        <div><dt>Bagian</dt><dd>{{ $kepegawaian?->unit_kerja ?? '-' }}</dd></div>
                        <div><dt>Mulai bekerja</dt><dd>{{ optional($kepegawaian?->tanggal_masuk)->translatedFormat('d M Y') ?? '-' }}</dd></div>
                    </dl>
                </x-card>
                <x-card title="Ketentuan" icon="bi-info-circle">
                    <ul class="hr-daftar-info">
                        <li><i class="bi bi-calendar-week" aria-hidden="true"></i>Cuti biasa diajukan paling lambat <strong>7 hari</strong> sebelum tanggal mulai. Cuti mendadak (duka, melahirkan, bencana) boleh mulai hari ini.</li>
                        <li><i class="bi bi-arrow-repeat" aria-hidden="true"></i>Maksimal <strong>{{ $maxPengajuanTahunan }} kali</strong> per tahun dan tidak boleh bentrok dengan cuti lain (minimal jeda 1 hari).</li>
                        <li><i class="bi bi-person-check" aria-hidden="true"></i>Pengganti tugas ditentukan Kepala Bagian saat menyetujui pengajuan.</li>
                    </ul>
                </x-card>
            </div>
        </div>

        {{-- ============ Riwayat ============ --}}
        <section class="hr-section">
            <x-card title="Riwayat Pengajuan Cuti" icon="bi-clock-history" :padding="false">
                @if ($urutOpsi)
                    <x-slot:actions>@include('partials.urut')</x-slot:actions>
                @endif
                <x-table caption="Riwayat pengajuan cuti">
                    <x-slot:head>
                        <tr><th>Jenis</th><th>Tanggal</th><th>Pengganti</th><th>Status</th><th>Catatan</th></tr>
                    </x-slot:head>
                    @forelse ($riwayat as $item)
                        <tr>
                            <td>
                                <span class="fw-semibold">{{ $item->jenisLabel() }}</span>
                                @if ($item->kategoriBesarLabel())<span class="small text-muted d-block">{{ $item->kategoriBesarLabel() }}</span>@endif
                            </td>
                            <td>
                                <span class="text-nowrap">{{ $item->tanggal_mulai->translatedFormat('d M Y') }}</span>
                                @unless ($item->tanggal_mulai->isSameDay($item->tanggal_selesai))
                                    – <span class="text-nowrap">{{ $item->tanggal_selesai->translatedFormat('d M Y') }}</span>
                                @endunless
                                <span class="small text-muted d-block">{{ $item->lama_cuti }} hari</span>
                            </td>
                            <td>{{ $item->pengganti_nama ?? '–' }}</td>
                            <td>
                                <x-status-badge :badge="$item->badgeClass()">{{ $item->statusLabel() }}</x-status-badge>
                                @if ($item->isPending())<span class="small text-muted d-block mt-1">Menunggu: {{ $item->currentLevelLabel() }}</span>@endif
                            </td>
                            <td class="text-muted">{{ $item->catatan_admin ?? '–' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state icon="bi-calendar2-check" title="Belum ada pengajuan cuti" compact>Ajukan lewat formulir di atas; statusnya akan tampil di sini.</x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </x-card>
        </section>
    </div>
</main>

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

        // Hari kerja & libur sama dengan App\Support\Kalender di server.
        const HARI_KERJA = @json(config('hrrab.hari_kerja'));
        const LIBUR = @json(config('hrrab.hari_libur'));
        function isHariKerja(d) {
            const iso = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            return HARI_KERJA.includes(d.getDay() || 7) && ! LIBUR.includes(iso);
        }

        function addWorkingDays(startDate, totalHariKerja) {
            const result = new Date(startDate.getTime());
            while (! isHariKerja(result)) result.setDate(result.getDate() + 1);
            let count = 1;
            while (count < totalHariKerja) {
                result.setDate(result.getDate() + 1);
                if (isHariKerja(result)) {
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
            keterangan.closest('.hr-field')?.querySelector('.form-label')?.classList.toggle('required', dadakan);
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
                    if (isHariKerja(cursor)) {
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
        // Tanggal selesai mengikuti tanggal mulai: tidak boleh lebih awal, dan langsung terisi (cuti minimal 1 hari).
        function sinkronSelesai() {
            if (!tanggalMulai.value) return;
            tanggalSelesai.min = tanggalMulai.value;
            if (!tanggalSelesai.value || tanggalSelesai.value < tanggalMulai.value) {
                tanggalSelesai.value = tanggalMulai.value;
            }
        }

        kategoriSelect.addEventListener('change', function () { updateKategori(); sinkronSelesai(); updateLamaCuti(); });
        tanggalMulai.addEventListener('change', function () {
            sinkronSelesai();
            if (!document.getElementById('jenisBesar').checked) { updateLamaCuti(); return; }
            const opsi = kategoriSelect.options[kategoriSelect.selectedIndex];
            const durasi = opsi ? parseInt(opsi.getAttribute('data-durasi'), 10) : NaN;
            if (!isNaN(durasi) && tanggalMulai.value) {
                tanggalSelesai.value = toDateInputValue(addWorkingDays(new Date(tanggalMulai.value + 'T00:00:00'), durasi));
            }
            updateLamaCuti();
        });
        tanggalSelesai.addEventListener('change', function () { sinkronSelesai(); updateLamaCuti(); });
        jenisRadios.forEach(function (radio) { radio.addEventListener('change', function () { sinkronSelesai(); updateLamaCuti(); }); });

        toggleJenis();
        sinkronSelesai();
        updateLamaCuti();
    });
</script>
@endsection
