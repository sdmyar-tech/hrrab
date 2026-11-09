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
                                    {{-- Melahirkan/keguguran sendiri hanya untuk pegawai perempuan (jenis kelamin kosong: tampil). "Istri melahirkan" selalu tampil. --}}
                                    @continue($pegawai?->jenis_kelamin === 'Laki-laki' && in_array($value, \App\Models\PengajuanCuti::KATEGORI_KHUSUS_PEREMPUAN, true))
                                    <option value="{{ $value }}" data-durasi="{{ \App\Models\PengajuanCuti::KATEGORI_BESAR_DURASI[$value] ?? '' }}" data-maks-kalender="{{ \App\Models\PengajuanCuti::KATEGORI_BESAR_MAKS_KALENDER[$value] ?? '' }}" data-dadakan="{{ in_array($value, \App\Models\PengajuanCuti::KATEGORI_DADAKAN, true) ? 1 : 0 }}" {{ old('kategori_cuti_besar') == $value ? 'selected' : '' }} @disabled(! $eligible && in_array($value, \App\Models\PengajuanCuti::KATEGORI_PERLU_MASA_KERJA, true))>{{ $label }}{{ ! $eligible && in_array($value, \App\Models\PengajuanCuti::KATEGORI_PERLU_MASA_KERJA, true) ? ' (setelah 1 tahun)' : '' }}</option>
                                @endforeach
                            </select>
                            @error('kategori_cuti_besar')
                                <div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                            @enderror
                            <div id="durasiHint" class="form-text"></div>
                        </div>

                        <div class="row gx-3">
                            <div class="col-sm-5">
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
                            {{-- Bantuan isi saja (tidak dikirim): server menghitung ulang dari tanggal mulai–selesai. --}}
                            <div class="col-sm-2">
                                <div class="hr-field">
                                    <label for="jumlah_hari" class="form-label">Jumlah Hari</label>
                                    <input type="number" id="jumlah_hari" class="form-control" min="1" max="365" step="1"
                                        placeholder="Contoh: 2" inputmode="numeric">
                                </div>
                            </div>
                            <div class="col-sm-5">
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
                        <p class="form-text mt-n2 mb-3" id="hintLewati">Sabtu–Minggu dan tanggal merah tidak dihitung sebagai hari cuti.</p>

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
                @if ($alur)
                    <x-card title="Alur Persetujuan" icon="bi-signpost-split" subtitle="Pengajuan Anda melewati tahap berikut secara berurutan.">
                        <x-tangga-alur :tahap="$alur" awal="Anda mengajukan" akhir="Cuti disetujui" arah="vertikal" label="Alur persetujuan cuti" />
                    </x-card>
                @endif
                <x-card title="Ketentuan" icon="bi-info-circle">
                    <ul class="hr-daftar-info">
                        <li><i class="bi bi-calendar-week" aria-hidden="true"></i>Cuti biasa diajukan paling lambat <strong>7 hari</strong> sebelum tanggal mulai. Cuti mendadak (duka, melahirkan, bencana) boleh mulai hari ini.</li>
                        <li><i class="bi bi-calendar-x" aria-hidden="true"></i>Lama cuti dihitung <strong>hari kerja</strong>: Sabtu–Minggu dan tanggal merah dilewati. Contoh: cuti 2 hari mulai Jumat = Jumat &amp; Senin.</li>
                        <li><i class="bi bi-arrow-repeat" aria-hidden="true"></i>Maksimal <strong>{{ $maxPengajuanTahunan }} kali</strong> per tahun dan tidak boleh bentrok dengan cuti lain (minimal jeda 1 hari).</li>
                        <li><i class="bi bi-person-check" aria-hidden="true"></i>Pengganti tugas ditentukan penyetuju tahap pertama saat menyetujui pengajuan.</li>
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
        const jenisBesar = document.getElementById('jenisBesar');
        const sectionBesar = document.getElementById('sectionBesar');
        const kategoriSelect = document.getElementById('kategori_cuti_besar');
        const durasiHint = document.getElementById('durasiHint');
        const tanggalMulai = document.getElementById('tanggal_mulai');
        const tanggalSelesai = document.getElementById('tanggal_selesai');
        const jumlahHari = document.getElementById('jumlah_hari');
        const lamaCutiDisplay = document.getElementById('lama_cuti_display');
        const hintLewati = document.getElementById('hintLewati');
        const hintMulai = document.getElementById('hintTanggalMulai');
        const keterangan = document.getElementById('keterangan');
        const MAKS_TAHUNAN = @json($maxPerRequest);
        const TEKS_LEWATI = hintLewati.textContent;

        // Hari kerja & libur sama dengan App\Support\Kalender di server (tanggal merah dari menu Kalender).
        const HARI_KERJA = @json(config('hrrab.hari_kerja'));
        const LIBUR = @json(config('hrrab.hari_libur'));
        const keIso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        const dariIso = (s) => new Date(s + 'T00:00:00');
        const isHariKerja = (d) => HARI_KERJA.includes(d.getDay() || 7) && ! LIBUR.includes(keIso(d));
        const fmtPendek = new Intl.DateTimeFormat('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });

        /** Hari kerja ke-n dari tanggal mulai (mulai ikut dihitung bila hari kerja). 2 hari mulai Jumat = Senin. */
        function hariKerjaKe(mulai, n) {
            const hasil = new Date(mulai.getTime());
            while (! isHariKerja(hasil)) hasil.setDate(hasil.getDate() + 1);
            for (let i = 1; i < n; i++) {
                do { hasil.setDate(hasil.getDate() + 1); } while (! isHariKerja(hasil));
            }
            return hasil;
        }

        /** Rincian rentang: jumlah hari kerja + tanggal yang dilewati (Sabtu–Minggu / tanggal merah). */
        function rincian(mulai, selesai) {
            let kerja = 0;
            const lewati = [];
            for (const t = new Date(mulai.getTime()); t <= selesai; t.setDate(t.getDate() + 1)) {
                if (isHariKerja(t)) kerja++;
                else lewati.push(fmtPendek.format(t) + (LIBUR.includes(keIso(t)) ? ' (tanggal merah)' : ''));
            }
            return { kerja, lewati };
        }

        const durasiTetap = function () {
            if (! jenisBesar.checked) return NaN;
            const opsi = kategoriSelect.options[kategoriSelect.selectedIndex];
            return opsi ? parseInt(opsi.dataset.durasi, 10) : NaN;
        };

        // Jumlah hari → tanggal selesai (melewati Sabtu–Minggu & tanggal merah).
        function isiSelesaiDariJumlah() {
            const n = parseInt(jumlahHari.value, 10);
            if (! tanggalMulai.value || ! (n >= 1)) return;
            tanggalSelesai.value = keIso(hariKerjaKe(dariIso(tanggalMulai.value), n));
        }

        // Tanggal selesai → jumlah hari (bila diubah manual).
        function isiJumlahDariSelesai() {
            if (! tanggalMulai.value || ! tanggalSelesai.value) return;
            const { kerja } = rincian(dariIso(tanggalMulai.value), dariIso(tanggalSelesai.value));
            jumlahHari.value = kerja > 0 ? kerja : '';
        }

        function updateLamaCuti() {
            hintLewati.textContent = TEKS_LEWATI;
            hintLewati.classList.remove('text-danger');
            if (! tanggalMulai.value || ! tanggalSelesai.value || tanggalSelesai.value < tanggalMulai.value) {
                lamaCutiDisplay.value = '';
                return;
            }
            const { kerja, lewati } = rincian(dariIso(tanggalMulai.value), dariIso(tanggalSelesai.value));
            lamaCutiDisplay.value = kerja > 0 ? kerja + ' hari kerja' : '';
            if (kerja === 0) {
                hintLewati.textContent = 'Rentang ini tidak berisi hari kerja. Pilih tanggal lain.';
                hintLewati.classList.add('text-danger');
            } else if (lewati.length) {
                hintLewati.textContent = 'Tidak dihitung: ' + lewati.join(', ') + '.';
            }
        }

        // Cuti besar mendadak (duka, melahirkan, bencana, panggilan) boleh mulai hari ini, tanpa aturan H-7.
        function updateAturanTanggal() {
            const opsi = kategoriSelect.options[kategoriSelect.selectedIndex];
            const dadakan = jenisBesar.checked && opsi && opsi.dataset.dadakan === '1';
            const minBaru = dadakan ? tanggalMulai.dataset.minDadakan : tanggalMulai.dataset.minNormal;
            const minLama = tanggalMulai.min;

            tanggalMulai.min = minBaru;
            if (dadakan && tanggalMulai.value === minLama) {
                tanggalMulai.value = keIso(new Date());
            } else if (! dadakan && tanggalMulai.value < minBaru) {
                tanggalMulai.value = minBaru;
            }
            hintMulai.textContent = dadakan
                ? 'Cuti mendadak: boleh mulai di hari yang sama dengan pengajuan. Keterangan wajib diisi.'
                : 'Tanggal mulai minimal 7 hari setelah tanggal pengajuan.';
            keterangan.required = dadakan;
            keterangan.closest('.hr-field')?.querySelector('.form-label')?.classList.toggle('required', dadakan);
        }

        // Jenis/kategori menentukan apakah jumlah hari bebas diisi atau tetap (durasi kategori cuti besar).
        function updateJenis() {
            sectionBesar.classList.toggle('d-none', ! jenisBesar.checked);
            kategoriSelect.required = jenisBesar.checked;
            updateAturanTanggal();

            const durasi = durasiTetap();
            const tetap = ! isNaN(durasi);
            jumlahHari.readOnly = tetap;
            tanggalSelesai.readOnly = tetap;
            jumlahHari.max = jenisBesar.checked ? 365 : MAKS_TAHUNAN;
            if (tetap) jumlahHari.value = durasi;

            const opsi = kategoriSelect.options[kategoriSelect.selectedIndex];
            const maks = maksKalender();
            durasiHint.textContent = ! jenisBesar.checked ? ''
                : tetap ? 'Kategori ini wajib ' + durasi + ' hari kerja.'
                : maks ? 'Paling lama ' + maks + ' hari kalender (UU Ketenagakerjaan Pasal 82). Tanggal selesai terisi batas maksimal; boleh dipersingkat.'
                : opsi && opsi.value ? 'Lama cuti sesuai kebutuhan, isi jumlah hari atau tanggal selesai.' : '';

            if (maks) isiSelesaiMaksKalender();
            else { tanggalSelesai.removeAttribute('max'); isiSelesaiDariJumlah(); }
            updateLamaCuti();
        }

        // Melahirkan/keguguran (pegawai perempuan): batas dalam hari kalender, bukan hari kerja.
        function maksKalender() {
            if (! jenisBesar.checked) return 0;
            const opsi = kategoriSelect.options[kategoriSelect.selectedIndex];
            return opsi ? parseInt(opsi.dataset.maksKalender, 10) || 0 : 0;
        }
        function batasKalender() {
            const t = dariIso(tanggalMulai.value);
            t.setDate(t.getDate() + maksKalender() - 1);
            return keIso(t);
        }
        function isiSelesaiMaksKalender() {
            if (! tanggalMulai.value) return;
            tanggalSelesai.max = batasKalender();
            tanggalSelesai.value = tanggalSelesai.max;
            isiJumlahDariSelesai();
        }

        jenisRadios.forEach((radio) => radio.addEventListener('change', updateJenis));
        kategoriSelect.addEventListener('change', updateJenis);
        tanggalMulai.addEventListener('change', function () {
            tanggalSelesai.min = tanggalMulai.value;
            if (maksKalender()) isiSelesaiMaksKalender();
            else if (jumlahHari.value) isiSelesaiDariJumlah();
            else if (! tanggalSelesai.value || tanggalSelesai.value < tanggalMulai.value) tanggalSelesai.value = tanggalMulai.value;
            updateLamaCuti();
        });
        jumlahHari.addEventListener('input', function () {
            isiSelesaiDariJumlah();
            if (maksKalender() && tanggalSelesai.value > batasKalender()) { tanggalSelesai.value = batasKalender(); isiJumlahDariSelesai(); }
            updateLamaCuti();
        });
        tanggalSelesai.addEventListener('change', function () {
            if (tanggalSelesai.value < tanggalMulai.value) tanggalSelesai.value = tanggalMulai.value;
            if (maksKalender() && tanggalSelesai.value > batasKalender()) tanggalSelesai.value = batasKalender();
            isiJumlahDariSelesai();
            updateLamaCuti();
        });

        // Awal: pertahankan tanggal lama (gagal validasi), selain itu mulai dari 1 hari kerja.
        tanggalSelesai.min = tanggalMulai.value;
        if (tanggalSelesai.value) isiJumlahDariSelesai();
        else jumlahHari.value = 1;
        updateJenis();
    });
</script>
@endsection
