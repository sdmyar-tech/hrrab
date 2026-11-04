@extends('layouts.admin.app')

{{-- Galat isian ditampilkan di halaman ini (ringkasan + di bawah isian); banner global tidak perlu. --}}
@section('galat-di-halaman', '1')

@section('content')
@php
    $pengguna = auth()->user();
    $isAdmin = $pengguna->isAdmin();
    $unitAwal = old('unit_kerja', $unitOptions[0] ?? '');
    $jenis = old('jenis_kebutuhan');
    $opsi = fn (array $daftar) => array_combine($daftar, $daftar);

    // Pilihan "kebutuhan karyawan karena": ikon + keterangan alur persetujuan.
    $jenisInfo = [
        \App\Models\PengajuanFormasi::JENIS_REPLACEMENT => ['bi-arrow-repeat', 'Mengganti karyawan yang keluar/berhenti. Direview ' . ($pengguna->isKepalaSdm() ? 'GM' : 'Kepala SDM atau GM') . ' (bagian SMK oleh Pembina).'],
        \App\Models\PengajuanFormasi::JENIS_PENAMBAHAN => ['bi-person-plus', 'Menambah posisi baru. Disetujui bertahap: GM bagian, lalu Owner.'],
    ];

    // Tugas & tanggung jawab: satu baris per tugas (bawaan 1 baris). Disimpan server sebagai teks per baris.
    $tugasLama = old('tugas');
    $barisTugas = is_array($tugasLama) ? $tugasLama : array_values(array_filter(preg_split('/\r?\n/', (string) $tugasLama), 'strlen'));
    $barisTugas = $barisTugas ?: [''];

    // Navigasi bagian: bagian yang memuat galat server diberi tanda.
    $bagian = [
        ['id' => 'bagian-info', 'label' => 'Info Formasi', 'icon' => 'bi-clipboard-plus',
            'kolom' => ['unit_kerja', 'jabatan', 'target_waktu', 'atasan_langsung', 'jenis_kebutuhan', 'jumlah_laki', 'jumlah_perempuan', 'jumlah_bebas']],
        ['id' => 'bagian-alasan', 'label' => 'Alasan & Justifikasi', 'icon' => 'bi-diagram-3', 'kolom' => ['diganti', 'diganti.*', 'slot_tambahan', 'slot_tambahan.*']],
        ['id' => 'bagian-persyaratan', 'label' => 'Persyaratan', 'icon' => 'bi-list-check', 'kolom' => ['min_pendidikan', 'min_usia', 'kualifikasi']],
        ['id' => 'bagian-tugas', 'label' => 'Tugas', 'icon' => 'bi-card-checklist', 'kolom' => ['tugas', 'tugas.*']],
    ];
    $nav = array_map(fn ($b) => ['id' => $b['id'], 'label' => $b['label'], 'icon' => $b['icon'], 'status' => $errors->hasAny($b['kolom']) ? false : null], $bagian);
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Ajukan Formasi" eyebrow="Rekrutmen" :back="route('admin.pengajuan-formasi.index')" back-label="Pengajuan Formasi"
            subtitle="Permintaan kekurangan pegawai untuk bagian Anda. Bila disetujui, lowongan otomatis terbit dan pelamar bisa mulai melamar. Kolom bertanda * wajib diisi." />

        @if ($errors->any())
            <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-3">
                Pengajuan belum terkirim. Perbaiki isian berikut, lalu ajukan lagi:
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form data-fokus-awal action="{{ route('admin.pengajuan-formasi.store') }}" method="POST" id="formFormasi" novalidate
            data-confirm="Ajukan formasi ini sekarang? Pengajuan langsung dikirim ke penyetuju.">
            @csrf

            <div class="row g-4 align-items-start">
                <aside class="col-lg-3 hr-samping">
                    <x-section-nav label="Bagian formulir pengajuan formasi" :items="$nav" />
                </aside>

                <div class="col-lg-9">
                    <x-card>
                        {{-- ================= 1. Info formasi ================= --}}
                        <x-form-section id="bagian-info" class="hr-bagian" title="Info Formasi" icon="bi-clipboard-plus"
                            subtitle="Bagian dan jabatan yang membutuhkan pegawai, waktu dibutuhkan, alasan, serta jumlahnya.">
                            <div class="col-md-6">
                                @if ($isAdmin || count($unitOptions) > 1)
                                    {{-- Admin: semua bagian. Kepala beberapa bagian: hanya bagian yang dipimpinnya. --}}
                                    <x-select name="unit_kerja" id="f-unit" label="Bagian" :options="$opsi($unitOptions)" :selected="$unitAwal"
                                        :placeholder="$isAdmin ? 'Pilih bagian' : null" required field-class="mb-0"
                                        :help="$isAdmin ? 'Admin dapat mengajukan untuk bagian mana pun.' : 'Hanya bagian yang Anda pimpin.'" />
                                @else
                                    {{-- Hanya satu bagian → terkunci (server juga memaksa nilai ini). --}}
                                    <x-input name="unit_kerja" id="f-unit" label="Bagian" :value="$unitOptions[0] ?? ''" icon="bi-lock-fill" readonly
                                        help="Bagian Anda; tidak bisa diubah." field-class="mb-0" />
                                @endif
                            </div>
                            <div class="col-md-6">
                                {{-- Pilihan diisi skrip sesuai bagian; ikut terisi saat pegawai / posisi kosong di bagan diklik. --}}
                                <x-select name="jabatan" id="f-jabatan" label="Jabatan / posisi" placeholder="Pilih bagian dulu" required field-class="mb-0"
                                    help="Hanya jabatan di bagian terpilih. Terisi otomatis saat Anda mengklik bagan." />
                            </div>
                            <div class="col-md-6">
                                <x-input name="target_waktu" id="f-tanggal" type="date" label="Tanggal dibutuhkan" required field-class="mb-0"
                                    :min="$isAdmin ? null : now()->toDateString()"
                                    data-pesan="Tanggal dibutuhkan tidak boleh sebelum hari ini."
                                    help="Menjadi batas tutup lowongan bila pengajuan disetujui." />
                            </div>
                            <div class="col-md-6">
                                @if ($isAdmin)
                                    {{-- Admin: pilih dari Kepala Bagian, Kepala SDM, dan GM. --}}
                                    <x-select name="atasan_langsung" id="f-atasan" label="Atasan langsung" :options="$pilihanAtasan"
                                        placeholder="Pilih kepala bagian / GM" required field-class="mb-0"
                                        help="Kepala bagian, Kepala SDM, atau GM yang membawahi posisi ini." />
                                @else
                                    {{-- Pengaju = atasan langsung; tidak bisa diubah (server juga memaksa nilai ini). --}}
                                    <x-input name="atasan_langsung" id="f-atasan" label="Atasan langsung" :value="$atasanBawaan" icon="bi-lock-fill" readonly
                                        help="Otomatis Anda sebagai pengaju." field-class="mb-0" />
                                @endif
                            </div>

                            <div class="col-12">
                                <fieldset class="hr-field mb-0">
                                    <legend class="form-label required">Kebutuhan karyawan karena</legend>
                                    <div class="hr-opsi-grup">
                                        @foreach (\App\Models\PengajuanFormasi::JENIS_LABEL as $kode => $label)
                                            <label class="hr-opsi" for="jenis-{{ $kode }}">
                                                <input type="radio" class="form-check-input @error('jenis_kebutuhan') is-invalid @enderror" name="jenis_kebutuhan"
                                                    id="jenis-{{ $kode }}" value="{{ $kode }}" @checked($jenis === $kode) required
                                                    data-pesan-kosong="Pilih alasan kebutuhan: replacement atau penambahan karyawan.">
                                                <i class="bi {{ $jenisInfo[$kode][0] ?? 'bi-person' }} hr-opsi-ikon" aria-hidden="true"></i>
                                                <span><strong>{{ \Illuminate\Support\Str::before($label, ' / ') }}</strong><small>{{ $jenisInfo[$kode][1] ?? $label }}</small></span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('jenis_kebutuhan')
                                        <div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                    @enderror
                                    {{-- Langkah berikutnya sesuai pilihan (span[data-jenis] diatur skrip). --}}
                                    <p class="form-text mb-0" data-info-jenis>
                                        <span data-jenis="" @if ($jenis) hidden @endif>Pilih salah satu; bagan di bagian Alasan &amp; Justifikasi menyesuaikan pilihan Anda.</span>
                                        <span data-jenis="replacement" @if ($jenis !== 'replacement') hidden @endif><i class="bi bi-person-check me-1" aria-hidden="true"></i>Lanjutkan dengan mengklik pegawai yang digantikan di bagan. Jumlah &amp; jenis kelamin terisi otomatis.</span>
                                        <span data-jenis="penambahan" @if ($jenis !== 'penambahan') hidden @endif><i class="bi bi-people me-1" aria-hidden="true"></i>Lanjutkan dengan mengklik “Posisi kosong” pada jabatan yang dibutuhkan di bagan.</span>
                                    </p>
                                </fieldset>
                            </div>

                            <div class="col-12">
                                <fieldset class="hr-field mb-0" data-wadah-jumlah aria-describedby="f-total-jumlah">
                                    <legend class="form-label">Jumlah dibutuhkan</legend>
                                    <div class="row g-3">
                                        @foreach ([['jumlah_laki', 'f-laki', 'Laki-laki', true], ['jumlah_perempuan', 'f-perempuan', 'Perempuan', true], ['jumlah_bebas', 'f-bebas', 'Bebas (L/P)', false]] as [$nama, $id, $label, $wajib])
                                            <div class="col-6 col-sm-4">
                                                {{-- Galat jumlah_laki (termasuk "total 1–50 orang") tampil di bawah grup, bukan di bawah kolom ini. --}}
                                                <x-form-group :label="$label" :for="$id" :name="$nama === 'jumlah_laki' ? null : $nama" :required="$wajib" class="mb-0">
                                                    <div class="input-group">
                                                        <input type="number" name="{{ $nama }}" id="{{ $id }}" class="form-control @error($nama) is-invalid @enderror"
                                                            min="0" max="50" step="1" inputmode="numeric" value="{{ old($nama, 0) }}" @if ($wajib) required @endif
                                                            data-satuan="orang" @if ($nama === 'jumlah_bebas') title="Laki-laki atau perempuan sama saja" @endif
                                                            @error($nama) aria-invalid="true" @enderror>
                                                        <span class="input-group-text">orang</span>
                                                    </div>
                                                </x-form-group>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('jumlah_laki')
                                        <div class="invalid-feedback d-block" data-galat-server="jumlah"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                    @enderror
                                    <p class="form-text mb-0" id="f-total-jumlah"><span data-total-jumlah></span></p>
                                </fieldset>
                            </div>
                        </x-form-section>

                        {{-- ================= 2. Alasan & justifikasi (bagan unit) ================= --}}
                        <x-form-section id="bagian-alasan" class="hr-bagian" title="Alasan & Justifikasi" icon="bi-diagram-3"
                            subtitle="Tandai di bagan pegawai yang digantikan atau posisi baru yang diajukan. Pilihan ini tercatat sebagai alasan pengajuan.">
                            <div class="col-12" data-wadah-bagan>
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <x-badge tone="primary" data-bagan-judul>Pilih kebutuhan karyawan dulu</x-badge>
                                    <div class="bagan-legenda mb-0" aria-label="Keterangan warna bagan">
                                        <span><i class="kotak aktif" aria-hidden="true"></i>Pegawai aktif</span>
                                        <span><i class="kotak nonaktif" aria-hidden="true"></i>Pegawai tidak aktif</span>
                                        <span><i class="kotak kosong" aria-hidden="true"></i>Posisi kosong (baru)</span>
                                        <span><i class="bi bi-arrow-repeat" aria-hidden="true"></i>digantikan</span>
                                    </div>
                                </div>
                                <p class="small mb-2" data-bagan-petunjuk aria-live="polite"></p>
                                <div class="bagan" data-bagan>
                                    <div class="bagan-alat">
                                        <input type="search" class="form-control form-control-sm" placeholder="Cari nama pegawai, contoh: Siti"
                                            aria-label="Cari nama pegawai di bagan" data-bagan-cari>
                                        <div class="ms-auto d-flex align-items-center gap-1">
                                            <x-icon-button icon="bi-zoom-out" label="Perkecil bagan" data-zoom="-" />
                                            <span class="bagan-skala" data-bagan-skala aria-live="polite">100%</span>
                                            <x-icon-button icon="bi-zoom-in" label="Perbesar bagan" data-zoom="+" />
                                            <x-button variant="secondary" size="sm" icon="bi-arrows-angle-contract" data-zoom="pas"
                                                title="Muat seluruh bagan di layar">Pas layar</x-button>
                                        </div>
                                    </div>
                                    {{-- Wadah bergulir bisa difokus (panah keyboard untuk menggeser bagan lebar). --}}
                                    <div class="bagan-layar" data-bagan-layar tabindex="0" role="region" aria-label="Bagan bagian: geser untuk melihat semua">
                                        <div class="bagan-pohon" id="baganUnit"></div>
                                    </div>
                                </div>
                                {{-- Nilai terkirim dari bagan: diganti[] (replacement) / slot_tambahan[] (penambahan), diisi skrip. --}}
                                <div data-bagan-input></div>
                                @error('diganti')
                                    <div class="invalid-feedback d-block" data-galat-server="bagan"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                @enderror
                                @error('diganti.*')
                                    <div class="invalid-feedback d-block" data-galat-server="bagan"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                @enderror
                            </div>
                        </x-form-section>

                        {{-- ================= 3. Persyaratan ================= --}}
                        <x-form-section id="bagian-persyaratan" class="hr-bagian" title="Persyaratan" icon="bi-list-check"
                            subtitle="Menjadi kualifikasi lowongan bila pengajuan disetujui.">
                            <div class="col-md-6">
                                <x-select name="min_pendidikan" id="f-pendidikan" label="Minimal pendidikan" :options="$opsi(\App\Models\PengajuanFormasi::PENDIDIKAN)"
                                    placeholder="Pilih jenjang pendidikan" required field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-form-group label="Minimal usia" for="f-usia" name="min_usia" optional help="Kosongkan bila usia tidak dibatasi." class="mb-0">
                                    <div class="input-group">
                                        <input type="number" name="min_usia" id="f-usia" class="form-control @error('min_usia') is-invalid @enderror" min="15" max="70" step="1"
                                            inputmode="numeric" value="{{ old('min_usia') }}" placeholder="Usia minimal, contoh: 20"
                                            data-pesan="Isi usia 15–70 tahun, atau kosongkan bila tidak dibatasi." aria-describedby="{{ $errors->has('min_usia') ? 'f-usia-galat' : 'f-usia-bantuan' }}"
                                            @error('min_usia') aria-invalid="true" @enderror>
                                        <span class="input-group-text">tahun</span>
                                    </div>
                                </x-form-group>
                            </div>
                            <div class="col-12">
                                <x-textarea name="kualifikasi" id="f-keahlian" label="Minimal kemampuan / keahlian" rows="3" maxlength="2000" required field-class="mb-0"
                                    placeholder="Kemampuan yang wajib dimiliki pelamar, contoh: menguasai Excel, pengalaman administrasi keuangan 1 tahun" />
                            </div>
                        </x-form-section>

                        {{-- ================= 4. Tugas & tanggung jawab ================= --}}
                        <x-form-section id="bagian-tugas" class="hr-bagian" title="Tugas & Tanggung Jawab" icon="bi-card-checklist"
                            subtitle="Menjadi deskripsi pekerjaan di lowongan.">
                            <div class="col-12">
                                <fieldset class="hr-field mb-0" aria-describedby="f-tugas-bantuan">
                                    <legend class="form-label required">Daftar tugas</legend>
                                    <div data-daftar-tugas data-tempat-galat>
                                        @foreach ($barisTugas as $i => $isi)
                                            <div class="input-group mb-2 tugas-baris">
                                                <span class="input-group-text tugas-no" aria-hidden="true">{{ $i + 1 }}</span>
                                                <input type="text" name="tugas[]" class="form-control @error('tugas.' . $i) is-invalid @enderror" value="{{ $isi }}" maxlength="500"
                                                    placeholder="Tulis satu tugas, contoh: menginput transaksi harian ke sistem keuangan" aria-label="Tugas {{ $i + 1 }}"
                                                    data-pesan-kosong="Isi minimal satu tugas, contoh: menginput transaksi harian ke sistem keuangan." @if ($i === 0) required @endif>
                                                <button type="button" class="btn btn-outline-danger" data-hapus-tugas aria-label="Hapus tugas {{ $i + 1 }}" title="Hapus tugas ini">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('tugas')
                                        <div class="invalid-feedback d-block mb-2"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                    @enderror
                                    @error('tugas.*')
                                        <div class="invalid-feedback d-block mb-2"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                    @enderror
                                    <x-button variant="outline" size="sm" icon="bi-plus-lg" data-tambah-tugas>Tambah tugas</x-button>
                                    <div class="form-text" id="f-tugas-bantuan">
                                        Satu baris untuk satu tugas (maks. 30). Tekan <kbd>Enter</kbd> di sebuah baris untuk menambah baris baru di bawahnya.
                                    </div>
                                </fieldset>
                            </div>
                        </x-form-section>
                    </x-card>

                    {{-- Tidak ada "Simpan sebagai draft": backend selalu menyimpan pengajuan berstatus Menunggu (tanpa status draft). --}}
                    <div class="hr-bilah-aksi mt-3">
                        <x-button variant="secondary" :href="route('admin.pengajuan-formasi.index')">Batal</x-button>
                        <x-button type="submit" icon="bi-send">Ajukan</x-button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>
@include('admin.pengajuan-formasi._bagan-gaya')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formFormasi');
        const unit = document.getElementById('f-unit');
        const jabatan = document.getElementById('f-jabatan');
        const perUnit = @json($jabatanPerUnit);
        const baganPerUnit = @json($baganPerUnit);
        const jabatanBagan = @json($jabatanBaganPerUnit);
        const ADMIN = @json($isAdmin);
        const lama = @json(old('jabatan'));
        const digantiLama = ({{ \Illuminate\Support\Js::from(old('diganti', [])) }}).map(Number);
        const slotLama = {{ \Illuminate\Support\Js::from(old('slot_tambahan', [])) }};
        // Warna tombol dialog = warna merek dari token ui.css (tanpa hex di sini).
        const WARNA = getComputedStyle(document.documentElement).getPropertyValue('--hr-primary').trim() || undefined;
        const MAKS_TUGAS = 30;   // sama dengan aturan server (tugas max:30)
        const wadah = document.getElementById('baganUnit');
        const masukan = form.querySelector('[data-bagan-input]');
        const petunjuk = form.querySelector('[data-bagan-petunjuk]');
        const judulBagan = form.querySelector('[data-bagan-judul]');
        const cari = form.querySelector('[data-bagan-cari]');
        const kotakBagan = form.querySelector('[data-bagan]');
        const wadahBagan = form.querySelector('[data-wadah-bagan]');
        const wadahJumlah = form.querySelector('[data-wadah-jumlah]');
        const dipilih = new Set(digantiLama);   // id pegawai yang digantikan
        let slot = Array.isArray(slotLama) ? slotLama.slice() : [];  // keterangan posisi baru

        const jenis = () => (form.querySelector('[name=jenis_kebutuhan]:checked') || {}).value || '';
        const total = () => ['jumlah_laki', 'jumlah_perempuan', 'jumlah_bebas'].reduce((n, k) => n + (+form[k].value || 0), 0);
        const el = (tag, kelas, teks) => { const e = document.createElement(tag); if (kelas) e.className = kelas; if (teks !== undefined) e.textContent = teks; return e; };

        // Dropdown jabatan mengikuti unit: hanya jabatan yang ada di unit tersebut.
        function isiJabatan() {
            const daftar = perUnit[unit.value] || [];
            const nilai = jabatan.value || lama;
            jabatan.innerHTML = '';
            jabatan.add(new Option(unit.value ? (daftar.length ? 'Pilih jabatan' : 'Belum ada jabatan di bagian ini') : 'Pilih bagian dulu', ''));
            daftar.forEach((j) => jabatan.add(new Option(j, j, false, j === nilai)));
            jabatan.disabled = !daftar.length;
        }

        const layar = form.querySelector('[data-bagan-layar]');
        const labelSkala = form.querySelector('[data-bagan-skala]');
        const tombolKecil = form.querySelector('[data-zoom="-"]');
        const tombolBesar = form.querySelector('[data-zoom="+"]');
        // Ukuran harus sama dengan CSS (--node, --b-tulang, padding cabang) agar pembagian baris tepat.
        const LEBAR_NODE = 148, TULANG = 28, MAKS_SEJAJAR = 4, BATAS_LIPAT = 12;
        const SKALA_MIN = 0.4, SKALA_MAKS = 1.5;
        const terbuka = new Set();          // jabatan yang raknya dibuka penuh
        let skala = 1, skalaOtomatis = true;

        function node(kelas, ikon, nama, judul) {
            const b = el('button', 'bagan-node ' + kelas);
            b.type = 'button';
            b.title = judul || nama;
            const i = el('i', 'bi ' + ikon);
            i.setAttribute('aria-hidden', 'true');
            b.append(i, el('span', 'nm', nama));
            return b;
        }

        // ---- Zoom & geser (untuk struktur besar) ----
        function setSkala(s, otomatis) {
            skala = Math.min(SKALA_MAKS, Math.max(SKALA_MIN, s));
            skalaOtomatis = !!otomatis;
            wadah.style.zoom = skala;
            labelSkala.textContent = Math.round(skala * 100) + '%';
            // Batas zoom tercapai → tombolnya nonaktif.
            tombolKecil.disabled = skala <= SKALA_MIN;
            tombolBesar.disabled = skala >= SKALA_MAKS;
        }
        // Perkecil secukupnya agar seluruh bagan muat lebar layar (tidak memperbesar di atas 100%).
        function pasLayar() {
            wadah.style.zoom = 1;
            const lebar = wadah.scrollWidth, ruang = layar.clientWidth - 24;
            setSkala(lebar > ruang ? Math.floor((ruang / lebar) * 20) / 20 : 1, true);
        }

        // Perkiraan lebar satu cabang jabatan: anggota berjajar atau rak grid (bila tidak muat berjajar).
        const lebarCabang = (jumlah, uk) => 16 + Math.max(LEBAR_NODE, jumlah > uk.sejajar
            ? Math.min(uk.rak, jumlah) * (LEBAR_NODE + 8) + 14
            : jumlah * (LEBAR_NODE + 8));

        // Titik belah baris: kiri & kanan tulang punggung selebar mungkin seimbang (kiri minimal satu cabang).
        function belah(baris) {
            const jumlah = baris.reduce((s, c) => s + c.lebar, 0);
            let kiri = 0, terbaik = 1, selisih = Infinity;
            baris.forEach((c, i) => {
                kiri += c.lebar;
                const d = Math.abs(jumlah - 2 * kiri);
                if (d < selisih) { selisih = d; terbaik = i + 1; }
            });
            return terbaik;
        }

        // Jabatan cabang bagan (huruf besar) ↔ isian dropdown jabatan.
        const samaJabatan = (a, b) => !!a && !!b && a.toUpperCase() === b.toUpperCase();
        const jabatanDari = (id) => Object.keys((baganPerUnit[unit.value] || { grup: {} }).grup)
            .find((j) => baganPerUnit[unit.value].grup[j].some((o) => o.id === id));

        // Isi dropdown jabatan sesuai cabang bagan (nama mengikuti Master Jabatan).
        function setJabatan(jab) {
            const nama = (jabatanBagan[unit.value] || {})[jab];
            if (!nama) return;
            let opsi = [...jabatan.options].find((op) => samaJabatan(op.value, nama));
            if (!opsi) { opsi = new Option(nama, nama); jabatan.add(opsi); }
            jabatan.disabled = false;
            jabatan.value = opsi.value;
            bersihkanIsian(jabatan);
        }

        // Replacement: jumlah dibutuhkan = kuota pegawai yang bisa dipilih di bagan.
        // Jumlah L/P/bebas mengikuti jenis kelamin pegawai yang dipilih (belum diisi → bebas), tetapi tetap bisa diubah pengguna.
        const orangDari = (id) => { const j = jabatanDari(id); return j ? baganPerUnit[unit.value].grup[j].find((o) => o.id === id) : null; };
        const jkIndeks = (o) => (o && o.jk === 'L' ? 0 : (o && o.jk === 'P' ? 1 : 2));
        const komposisi = () => { const n = [0, 0, 0]; dipilih.forEach((id) => n[jkIndeks(orangDari(id))]++); return n; };
        // true bila isian jumlah persis komposisi pilihan (belum diubah manual) → boleh ikut berubah otomatis.
        const jumlahIkutPilihan = () => komposisi().every((v, i) => v === (+form[JK[i][0]].value || 0));
        function jumlahDariPilihan() {
            komposisi().forEach((v, i) => { form[JK[i][0]].value = v; });
            hitung();
        }
        // Total kebutuhan tidak boleh melebihi isi bagian: kelebihan dikurangi dari bebas, perempuan, lalu laki-laki.
        // `utama` (kolom yang sedang diubah pengguna) dikurangi paling akhir, mis. bebas 2 → isi laki-laki 1 = bebas 1, laki-laki 1.
        function potongKeBatas(utama) {
            let lebih = total() - batasGanti().n;
            [2, 1, 0].map((i) => form[JK[i][0]]).sort((a, b) => (a === utama) - (b === utama)).forEach((f) => {
                const k = Math.min(lebih, +f.value || 0);
                if (k > 0) { f.value = (+f.value || 0) - k; lebih -= k; }
            });
            hitung();
        }
        // Pilihan melebihi jumlah dibutuhkan → pilihan paling lama dilepas (pilihan "pindah" ke yang terbaru).
        function rapikanPilihan() {
            while (dipilih.size > total()) dipilih.delete(dipilih.values().next().value);
        }

        // Replacement: klik pegawai → pilih/batal (jabatan terisi otomatis). Bila kuota penuh, pilihan pindah ke yang diklik.
        // Satu pengajuan untuk satu jabatan.
        function pilihGanti(o, jab) {
            buangGalatServerBagan();
            if (dipilih.has(o.id)) { dipilih.delete(o.id); gambar(); return; }   // batal: jumlah tetap, tinggal klik pegawai lain
            const lain = [...dipilih].filter((id) => jabatanDari(id) !== jab);
            const pilih = () => {
                const kosong = total() === 0, ikut = jumlahIkutPilihan();
                lain.forEach((id) => dipilih.delete(id));
                dipilih.add(o.id);
                setJabatan(jab);
                potongKeBatas();
                if (kosong) {
                    jumlahDariPilihan();   // jumlah belum diisi: klik pertama = 1 orang sesuai jenis kelaminnya
                } else {
                    rapikanPilihan();
                    if (ikut) jumlahDariPilihan();   // pindah pilihan: L/P ikut pegawai baru bila belum diubah manual
                }
                gambar();
            };
            if (!lain.length) return pilih();
            Swal.fire({
                icon: 'question', titleText: 'Ganti jabatan?',
                text: 'Satu pengajuan untuk satu jabatan: ' + lain.length + ' pegawai yang dipilih di ' + jabatanDari(lain[0]) + ' akan dibatalkan dan diganti ' + o.nama + ' (' + jab + ').',
                showCancelButton: true, reverseButtons: true, confirmButtonText: 'Ya, ganti', cancelButtonText: 'Batal', confirmButtonColor: WARNA,
            }).then((r) => { if (r.isConfirmed) pilih(); });
        }

        function simpulPegawai(o, mode, kata, jab) {
            // Tanda "digantikan" hanya di mode replacement (pilihan tetap diingat bila pengguna kembali).
            const ganti = mode === 'replacement' && dipilih.has(o.id);
            const b = node((o.aktif ? 'aktif' : 'nonaktif') + (ganti ? ' diganti' : '') + (kata && o.nama.toLowerCase().includes(kata) ? ' cocok' : ''),
                o.aktif ? 'bi-person-fill' : 'bi-person-dash', o.nama,
                o.nama + (o.aktif ? ' (aktif)' : ' (tidak aktif)') + (mode === 'replacement' ? ' — klik untuk '
                    + (ganti ? 'batal' : (total() > 0 && dipilih.size >= total() ? 'memindahkan pilihan ke sini' : 'pilih sebagai yang digantikan')) : ''));
            if (ganti) { const l = el('span', 'bagan-lencana'); l.append(el('i', 'bi bi-arrow-repeat')); b.append(l); }
            if (mode === 'replacement') {
                b.setAttribute('aria-pressed', String(ganti));
                b.addEventListener('click', () => pilihGanti(o, jab));
            } else {
                b.disabled = true;
            }
            return { b, penting: ganti };
        }

        // Jenis kelamin posisi baru = isian jumlah. Urutan slot: laki-laki, perempuan, lalu bebas.
        const JK = [['jumlah_laki', 'Laki-laki', 'L'], ['jumlah_perempuan', 'Perempuan', 'P'], ['jumlah_bebas', 'Bebas (L/P)', 'L/P']];
        const jkSlot = (i) => {
            const l = +form.jumlah_laki.value || 0, p = +form.jumlah_perempuan.value || 0;
            return i < l ? 0 : (i < l + p ? 1 : 2);
        };
        const ubahJumlah = (jk, d) => { const f = form[JK[jk][0]]; f.value = Math.max(0, (+f.value || 0) + d); hitung(); };

        // Posisi baru yang sudah ditambahkan (kotak merah): klik untuk ubah keterangan atau hapus.
        function simpulBaru(i) {
            const jk = JK[jkSlot(i)];
            const b = node('kosong slot', 'bi-person-plus', (slot[i] || 'Posisi baru #' + (i + 1)) + ' · ' + jk[2], 'Klik untuk ubah keterangan atau hapus posisi ini');
            const l = el('span', 'bagan-lencana'); l.append(el('i', 'bi bi-plus-lg')); b.append(l);
            b.addEventListener('click', () => {
                Swal.fire({
                    titleText: 'Posisi baru #' + (i + 1) + ' (' + jk[1] + ')', input: 'text', inputValue: slot[i] || '',
                    inputLabel: 'Keterangan (opsional)', inputPlaceholder: 'Keterangan posisi, contoh: untuk shift malam di gedung B', inputAttributes: { maxlength: 150 },
                    showCancelButton: true, showDenyButton: true, reverseButtons: true,
                    confirmButtonText: 'Simpan', denyButtonText: 'Hapus posisi', cancelButtonText: 'Batal', confirmButtonColor: WARNA,
                }).then((r) => {
                    if (r.isConfirmed) { slot[i] = r.value.trim(); gambar(); }
                    if (r.isDenied) { slot.splice(i, 1); ubahJumlah(jkSlot(i), -1); gambar(); }
                });
            });
            return { b, penting: true };
        }

        // Simpul merah kosong "Posisi kosong" (+) di tiap jabatan: pilih jabatan itu, tanya jenis kelamin & keterangan, jumlah bertambah.
        function simpulTambah(jab) {
            const b = node('kosong tambah', 'bi-person', 'Posisi kosong', 'Klik untuk mengajukan pegawai baru di ' + jab);
            b.querySelector('.nm').append(el('small', 'sub', 'klik untuk ajukan'));
            const l = el('span', 'bagan-lencana'); l.append(el('i', 'bi bi-plus-lg')); b.append(l);
            b.addEventListener('click', () => tambahSlot(jab));
            return { b, penting: true };
        }

        function tambahSlot(jab) {
            const pindah = total() > 0 && jabatan.value && jabatan.value !== jab;
            const wadahSwal = el('div', 'text-start');
            wadahSwal.innerHTML = '<div class="small fw-semibold mb-1">Jenis kelamin</div><div class="d-flex flex-wrap gap-2 mb-3" data-jk></div>'
                + '<label class="small fw-semibold mb-1" for="swal-ket">Keterangan <span class="fw-normal text-muted">(opsional)</span></label>'
                + '<input id="swal-ket" class="form-control" maxlength="150" placeholder="Keterangan posisi, contoh: untuk shift malam di gedung B">'
                + (pindah ? '<div class="alert alert-warning small mt-3 mb-0" data-pindah></div>' : '');
            JK.forEach(([nama, label], k) => {
                const r = el('input', 'btn-check'); r.type = 'radio'; r.name = 'swal-jk'; r.id = 'swal-jk-' + k; r.value = k; r.checked = k === 2;
                const lb = el('label', 'btn btn-sm btn-outline-secondary', label); lb.htmlFor = r.id;
                wadahSwal.querySelector('[data-jk]').append(r, lb);
            });
            if (pindah) wadahSwal.querySelector('[data-pindah]').textContent = 'Satu pengajuan untuk satu jabatan: ' + total() + ' posisi baru di ' + jabatan.value + ' akan dipindah ke ' + jab + '.';
            Swal.fire({
                titleText: 'Ajukan pegawai baru — ' + jab, html: wadahSwal, focusConfirm: false,
                showCancelButton: true, reverseButtons: true, confirmButtonText: 'Tambah', cancelButtonText: 'Batal', confirmButtonColor: WARNA,
                didOpen: () => document.getElementById('swal-ket').focus(),
                preConfirm: () => ({ jk: +document.querySelector('[name=swal-jk]:checked').value, ket: document.getElementById('swal-ket').value.trim() }),
            }).then((r) => {
                if (!r.isConfirmed) return;
                buangGalatServerBagan();
                if (![...jabatan.options].some((o) => o.value === jab)) jabatan.add(new Option(jab, jab));
                jabatan.value = jab;
                bersihkanIsian(jabatan);
                // Sisipkan di akhir kelompok jenis kelaminnya agar urutan slot tetap L, P, bebas.
                const l = +form.jumlah_laki.value || 0, p = +form.jumlah_perempuan.value || 0;
                slot.splice([l, l + p, total()][r.value.jk], 0, r.value.ket);
                ubahJumlah(r.value.jk, 1);
                gambar();
            });
        }

        // Satu cabang jabatan: label → anggota berjajar (garis masing-masing) atau rak grid bila banyak.
        function buatCabang(c, mode, kata, uk) {
            const kotak = el('div', 'bagan-cabang' + (samaJabatan(c.jab, jabatan.value) ? ' terpilih' : ''));
            kotak.append(el('div', 'bagan-jabatan', c.jab + ' (' + c.total + ')'), el('div', 'bagan-turun'));
            const simpul = c.isi.map((o) => simpulPegawai(o, mode, kata, c.jab));
            for (let i = 0; i < c.baru; i++) simpul.push(simpulBaru(i));
            if (c.tambah) simpul.push(simpulTambah(c.jab));

            if (simpul.length <= uk.sejajar) {
                const anak = el('div', 'bagan-anak');
                simpul.forEach((s) => { const d = el('div', 'bagan-daun'); d.append(s.b); anak.append(d); });
                kotak.append(anak);
                return kotak;
            }

            const rak = el('div', 'bagan-rak');
            rak.style.setProperty('--kol', Math.min(uk.rak, simpul.length));
            // Dilipat bila banyak (kecuali sedang mencari/dibuka); yang dipilih & posisi baru selalu tampil.
            const bisaLipat = !kata && simpul.length > BATAS_LIPAT;
            const lipat = bisaLipat && !terbuka.has(c.jab);
            let biasa = 0;
            simpul.forEach((s) => { if (!lipat || s.penting || biasa++ < BATAS_LIPAT - 1) rak.append(s.b); });
            if (bisaLipat) {
                const sisa = simpul.length - rak.children.length;
                const t = el('button', 'btn btn-sm btn-link bagan-lagi', lipat ? '+' + sisa + ' lainnya — tampilkan semua' : 'Ringkas');
                t.type = 'button';
                t.addEventListener('click', () => { lipat ? terbuka.add(c.jab) : terbuka.delete(c.jab); gambar(); });
                rak.append(t);
            }
            kotak.append(rak);
            return kotak;
        }

        // Gambar ulang bagan sesuai unit, jenis kebutuhan, jabatan, jumlah, dan pencarian.
        function gambar() {
            const data = baganPerUnit[unit.value] || { kepala: [], grup: {} };
            const mode = jenis();
            const n = total();
            const kata = (cari.value || '').trim().toLowerCase();
            // Ukur sebelum dikosongkan agar posisi gulir tidak terlempar.
            const ruang = Math.max(560, layar.clientWidth - 24);
            const gulir = [layar.scrollLeft, layar.scrollTop];
            // Satu sisi tulang punggung = setengah lebar; berjajar & kolom rak disesuaikan agar muat di situ.
            const setengah = (ruang - TULANG) / 2;
            const uk = {
                sejajar: Math.max(1, Math.min(MAKS_SEJAJAR, Math.floor((setengah - 16) / (LEBAR_NODE + 8)))),
                rak: Math.max(2, Math.min(4, Math.floor((setengah - 30) / (LEBAR_NODE + 8)))),
            };
            wadah.innerHTML = '';

            // Akar: kepala unit (lebih dari satu → berjajar).
            const akar = el('div', 'bagan-akar');
            const deretKepala = el('div', 'bagan-akar-deret');
            // Kepala = pegawai aktif (hijau); belum ada kepala tercatat → abu.
            (data.kepala.length ? data.kepala : ['Kepala bagian belum tercatat']).forEach((nm) => { const k = node((data.kepala.length ? 'aktif' : 'nonaktif') + ' kepala', 'bi-person-badge', nm); k.disabled = true; deretKepala.append(k); });
            akar.append(el('div', 'bagan-akar-label', 'Kepala · ' + (unit.value || '-')), deretKepala);
            wadah.append(akar);

            // Cabang per jabatan: anggota (tersaring pencarian) + posisi baru (penambahan) di jabatan terpilih.
            const grup = Object.assign({}, data.grup);
            if (mode === 'penambahan' && jabatan.value && !grup[jabatan.value]) grup[jabatan.value] = [];
            // Penambahan: tiap jabatan yang boleh diajukan mendapat simpul merah kosong "Posisi kosong"
            // (jabatan unit yang belum berpegawai ikut tampil; Admin tidak, karena daftarnya semua jabatan).
            const bolehJabatan = new Set(perUnit[unit.value] || []);
            if (mode === 'penambahan' && !ADMIN) bolehJabatan.forEach((j) => { if (!grup[j]) grup[j] = []; });
            const cabang = [];
            Object.keys(grup).forEach((jab) => {
                const isi = grup[jab].filter((o) => !kata || o.nama.toLowerCase().includes(kata) || (mode === 'replacement' && dipilih.has(o.id)));
                const baru = mode === 'penambahan' && jab === jabatan.value ? n : 0;
                const tambah = mode === 'penambahan' && bolehJabatan.has(jab) && (!kata || isi.length > 0 || baru > 0);
                if (isi.length || baru || tambah) cabang.push({ jab, isi, baru, tambah, total: grup[jab].length, lebar: lebarCabang(isi.length + baru + (tambah ? 1 : 0), uk) });
            });

            if (!cabang.length) {
                wadah.append(el('div', 'bagan-kosong-info', kata ? 'Tidak ada pegawai yang cocok. Coba kata lain atau kosongkan pencarian.' : 'Belum ada pegawai tercatat di bagian ini.'));
            } else if (cabang.length === 1) {
                // Satu jabatan saja: pakai lebar penuh (bukan setengah) untuk berjajar/rak.
                const lebarPenuh = Math.max(layar.clientWidth - 24, 200);
                const sisi = el('div', 'bagan-sisi tunggal');
                sisi.append(buatCabang(cabang[0], mode, kata, {
                    sejajar: Math.max(1, Math.min(MAKS_SEJAJAR, Math.floor((lebarPenuh - 16) / (LEBAR_NODE + 8)))),
                    rak: Math.max(1, Math.min(4, Math.floor((lebarPenuh - 30) / (LEBAR_NODE + 8)))),
                }));
                wadah.append(sisi);
            } else {
                // Bagi ke beberapa baris: tiap sisi (kiri/kanan tulang punggung) maksimal setengah lebar layar, urutan tetap.
                const baris = [];
                let kini = { kiri: [], kanan: [], lk: 0, lka: 0 };
                cabang.forEach((c) => {
                    if (!kini.kanan.length && (!kini.kiri.length || kini.lk + c.lebar <= setengah)) { kini.kiri.push(c); kini.lk += c.lebar; return; }
                    if (!kini.kanan.length || kini.lka + c.lebar <= setengah) { kini.kanan.push(c); kini.lka += c.lebar; return; }
                    baris.push(kini);
                    kini = { kiri: [c], kanan: [], lk: c.lebar, lka: 0 };
                });
                baris.push(kini);
                baris.forEach((r, i) => {
                    // Seimbangkan kiri/kanan bila kedua sisi tetap muat; jika tidak, pakai pembagian berurutan.
                    const b = [...r.kiri, ...r.kanan];
                    let k = belah(b);
                    const lebarSisi = (a) => a.reduce((s, c) => s + c.lebar, 0);
                    if (lebarSisi(b.slice(0, k)) > setengah || lebarSisi(b.slice(k)) > setengah) k = r.kiri.length;
                    const kiri = el('div', 'bagan-sisi kiri');
                    const kanan = el('div', 'bagan-sisi kanan');
                    b.slice(0, k).forEach((c) => kiri.append(buatCabang(c, mode, kata, uk)));
                    b.slice(k).forEach((c) => kanan.append(buatCabang(c, mode, kata, uk)));
                    wadah.append(kiri, el('div', 'bagan-tulang' + (i === baris.length - 1 ? ' akhir' : '') + (k === b.length ? ' hanya-kiri' : '')), kanan);
                });
            }
            [layar.scrollLeft, layar.scrollTop] = gulir;
            perbaruiMasukan(mode, n);
        }

        // Nilai terkirim (id yang digantikan / keterangan posisi baru) + judul & petunjuk bagan.
        function perbaruiMasukan(mode, n) {
            masukan.innerHTML = '';
            if (mode === 'replacement') {
                dipilih.forEach((id) => { const i = el('input'); i.type = 'hidden'; i.name = 'diganti[]'; i.value = id; masukan.append(i); });
            } else if (mode === 'penambahan') {
                for (let i = 0; i < n; i++) { const x = el('input'); x.type = 'hidden'; x.name = 'slot_tambahan[]'; x.value = slot[i] || ''; masukan.append(x); }
            }

            judulBagan.textContent = mode === 'replacement' ? 'Klik pegawai yang digantikan' : mode === 'penambahan' ? 'Klik “Posisi kosong” (kotak merah)' : 'Pilih kebutuhan karyawan dulu';
            petunjuk.className = 'small mb-2 ' + (mode === 'replacement' && dipilih.size !== n ? 'text-danger fw-semibold' : 'text-muted');
            petunjuk.textContent = mode === 'replacement'
                ? 'Dipilih ' + dipilih.size + ' dari ' + n + ' orang yang dibutuhkan. Pegawai yang bisa diklik sebanyak jumlah dibutuhkan; bila sudah penuh, klik pegawai lain untuk memindahkan pilihan. Jabatan terisi otomatis; jenis kelamin pengganti boleh diubah asal totalnya sama. Hijau = masih aktif, abu = sudah tidak aktif.'
                : mode === 'penambahan'
                    ? 'Klik kotak merah "Posisi kosong" (+) pada jabatan yang dibutuhkan untuk mengajukan pegawai baru (jabatan & jumlah terisi otomatis).'
                        + (n && jabatan.value ? ' Sekarang ' + n + ' posisi baru di ' + jabatan.value + '; klik kotaknya untuk ubah keterangan atau hapus.' : '')
                    : 'Bagan menampilkan susunan pegawai bagian Anda. Pilih "Kebutuhan karyawan karena" di atas untuk mulai menandai.';
            segarkanAturan();
        }

        form.querySelectorAll('[data-zoom]').forEach((b) => b.addEventListener('click', () => {
            if (b.dataset.zoom === 'pas') pasLayar();
            else setSkala(Math.round((skala + (b.dataset.zoom === '+' ? 0.1 : -0.1)) * 20) / 20, false);
        }));
        // Geser bagan dengan menyeret (mouse); di layar sentuh cukup digulir biasa.
        let seret = null;
        layar.addEventListener('pointerdown', (e) => {
            if (e.pointerType !== 'mouse' || e.button !== 0 || e.target.closest('button, input, a')) return;
            seret = { x: e.clientX, y: e.clientY, l: layar.scrollLeft, t: layar.scrollTop };
            layar.classList.add('geser');
        });
        window.addEventListener('pointermove', (e) => {
            if (!seret) return;
            layar.scrollLeft = seret.l - (e.clientX - seret.x);
            layar.scrollTop = seret.t - (e.clientY - seret.y);
        });
        window.addEventListener('pointerup', () => { seret = null; layar.classList.remove('geser'); });
        let jedaUkur;
        window.addEventListener('resize', () => { clearTimeout(jedaUkur); jedaUkur = setTimeout(() => { gambar(); if (skalaOtomatis) pasLayar(); }, 200); });
        cari.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });   // Enter tidak mengirim form

        unit.addEventListener('change', () => {
            dipilih.clear(); slot = []; terbuka.clear(); isiJabatan();
            if (jenis() === 'replacement') potongKeBatas();
            gambar(); pasLayar();
        });
        jabatan.addEventListener('change', () => {
            // Replacement: pilihan di jabatan lain dilepas, jumlah disesuaikan dengan isi jabatan baru.
            if (jenis() === 'replacement') {
                dipilih.forEach((id) => { if (!samaJabatan(jabatanDari(id), jabatan.value)) dipilih.delete(id); });
                potongKeBatas();
            }
            hitung(); gambar();
        });
        cari.addEventListener('input', gambar);
        // Pindah ke replacement: jumlah dihitung dari pegawai yang dipilih. Kembali: isian jumlah sebelumnya dipulihkan.
        let modeLama = jenis();
        let jumlahSebelum = null;
        form.querySelectorAll('[name=jenis_kebutuhan]').forEach((r) => r.addEventListener('change', () => {
            form.querySelectorAll('[data-jenis]').forEach((s) => { s.hidden = s.dataset.jenis !== r.value; });
            buangGalatServerBagan();
            if (r.value === 'replacement') {
                jumlahSebelum = JK.map(([nama]) => form[nama].value);
                // Pilihan lama masih diingat → jumlah mengikutinya; belum ada → isian jumlah dipertahankan (dibatasi isi bagian).
                dipilih.size ? jumlahDariPilihan() : potongKeBatas();
            } else if (modeLama === 'replacement') {
                JK.forEach(([nama], i) => { form[nama].value = jumlahSebelum ? jumlahSebelum[i] : 0; });
                hitung();
            }
            modeLama = r.value;
            gambar();
        }));
        const totalTeks = form.querySelector('[data-total-jumlah]');
        // Replacement: total kebutuhan maksimal = jumlah pegawai di jabatan (bagian) yang diganti; belum ada jabatan → seluruh unit.
        function batasGanti() {
            const grup = (baganPerUnit[unit.value] || { grup: {} }).grup;
            const jab = dipilih.size ? jabatanDari([...dipilih][0]) : Object.keys(grup).find((j) => samaJabatan(j, jabatan.value));
            return jab
                ? { n: grup[jab].length, di: jab }
                : { n: Object.values(grup).reduce((s, g) => s + g.length, 0), di: 'bagian ' + (unit.value || '-') };
        }
        const hitung = () => {
            const n = total();
            // Merah hanya bila melebihi batas; total 0 ditangani validasi saat kirim (tidak merah sebelum diisi).
            let teks = 'Total kebutuhan: ' + n + ' orang', salah = false;
            if (jenis() === 'replacement') {
                const b = batasGanti();
                teks += ' (maks ' + b.n + ', sesuai jumlah pegawai di ' + b.di + ')';
                salah = n > b.n;
                // Batas tiap kolom = isi bagian (bukan sisa kuota) agar tetap bisa dinaikkan; kelebihan diambil dari kolom lain.
                JK.forEach(([nama]) => { form[nama].max = Math.min(50, b.n); });
            } else {
                JK.forEach(([nama]) => { form[nama].max = 50; });
            }
            totalTeks.textContent = teks;
            totalTeks.classList.toggle('text-danger', salah);
            segarkanAturan();
            return n;
        };
        ['jumlah_laki', 'jumlah_perempuan', 'jumlah_bebas'].forEach((k) => form[k].addEventListener('input', () => {
            // Galat server "total jumlah" dibuang begitu pengguna mulai memperbaiki.
            form.querySelectorAll('[data-galat-server="jumlah"]').forEach((g) => g.remove());
            // Replacement: total tidak boleh melebihi jumlah pegawai yang ada — kelebihan diambil dari kolom lain dulu;
            // jumlah diturunkan → pilihan di bagan yang paling lama dilepas.
            if (jenis() === 'replacement') {
                potongKeBatas(form[k]);
                rapikanPilihan();
            }
            hitung(); gambar();
        }));
        // Kolom dikosongkan dianggap 0 (kolom wajib diisi, kosong akan ditolak saat kirim).
        ['jumlah_laki', 'jumlah_perempuan', 'jumlah_bebas'].forEach((k) => form[k].addEventListener('change', () => {
            if (form[k].value === '') { form[k].value = 0; hitung(); gambar(); }
        }));

        // ================= Validasi ringan di halaman =================
        // Dicek sebelum dialog konfirmasi global (data-confirm). Galat tampil di bawah isiannya + fokus ke galat pertama.
        let uid = 0;
        const grupIsian = (x) => (x.type === 'radio' ? [...form.querySelectorAll('input[type=radio][name="' + x.name + '"]')] : [x]);
        const wadahIsian = (x) => x.closest('.hr-field') || x.parentElement;
        // Teks label isian (tanpa "(opsional)"): label[for], legend grup radio, atau aria-label.
        function namaIsian(x) {
            if (x.type === 'radio') return (x.closest('fieldset')?.querySelector('legend')?.firstChild?.textContent || 'Pilihan ini').trim();
            const label = x.id && form.querySelector('label[for="' + x.id + '"]');
            return ((label && label.firstChild && label.firstChild.textContent) || x.getAttribute('aria-label') || 'Isian ini').trim();
        }
        // Pesan Bahasa Indonesia yang memberi tahu cara memperbaikinya.
        function pesanIsian(x) {
            const v = x.validity, nama = namaIsian(x), satuan = x.dataset.satuan ? ' ' + x.dataset.satuan : '';
            if (v.valueMissing) return x.dataset.pesanKosong || (x.tagName === 'SELECT' ? 'Pilih ' + nama.toLowerCase() + '.' : nama + ' wajib diisi.');
            if (x.dataset.pesan) return x.dataset.pesan;
            if (v.badInput || v.stepMismatch) return nama + ' harus berupa bilangan bulat, contoh: 1.';
            if (v.rangeUnderflow) return nama + ' minimal ' + x.min + satuan + '.';
            if (v.rangeOverflow) return nama + ' maksimal ' + x.max + satuan + (jenis() === 'replacement' && satuan ? ', sesuai jumlah pegawai yang bisa digantikan.' : '.');
            if (v.tooLong) return nama + ' maksimal ' + x.maxLength + ' karakter.';
            return x.validationMessage;
        }
        function tandai(daftar, idPesan) {
            daftar.forEach((x) => {
                x.classList.add('is-invalid');
                x.setAttribute('aria-invalid', 'true');
                if (idPesan && !(x.getAttribute('aria-describedby') || '').split(' ').includes(idPesan)) {
                    x.setAttribute('aria-describedby', ((x.getAttribute('aria-describedby') || '') + ' ' + idPesan).trim());
                }
            });
        }
        function lepasTanda(daftar) {
            daftar.forEach((x) => { x.classList.remove('is-invalid'); x.removeAttribute('aria-invalid'); });
        }
        // Pesan galat klien ber-kunci di dalam wadah; disisipkan setelah [data-tempat-galat] bila ada.
        function tampilPesan(wadahPesan, kunci, teks) {
            let umpan = wadahPesan.querySelector('[data-galat-klien="' + kunci + '"]');
            if (!umpan) {
                umpan = el('div', 'invalid-feedback d-block');
                umpan.dataset.galatKlien = kunci;
                umpan.id = 'galat-klien-' + kunci.replace(/[^\w-]+/g, '-');
                const jangkar = wadahPesan.querySelector('[data-tempat-galat]');
                jangkar ? jangkar.after(umpan) : wadahPesan.append(umpan);
            }
            const ikon = el('i', 'bi bi-exclamation-circle');
            ikon.setAttribute('aria-hidden', 'true');
            umpan.replaceChildren(ikon, document.createTextNode(' ' + teks));
            return umpan.id;
        }
        const buangPesan = (wadahPesan, kunci) => wadahPesan.querySelector('[data-galat-klien="' + kunci + '"]')?.remove();
        const kunciIsian = (x) => (x.type === 'radio' ? 'r-' + x.name : (x.id || (x.id = 'isian-' + (++uid))));

        // Cek satu isian (bawaan browser): tandai merah + pesan di bawahnya. Galat server yang masih tampil tidak digandakan.
        function periksaIsian(x) {
            const grup = grupIsian(x), wadahPesan = wadahIsian(x), kunci = kunciIsian(x);
            if (x.checkValidity()) {
                if (wadahPesan.querySelector('[data-galat-klien="' + kunci + '"]')) { buangPesan(wadahPesan, kunci); lepasTanda(grup); }
                return true;
            }
            if (wadahPesan.querySelector('.invalid-feedback:not([data-galat-klien])')) { tandai(grup); return false; }
            tandai(grup, tampilPesan(wadahPesan, kunci, pesanIsian(x)));
            return false;
        }
        // Isian diperbaiki dari luar (mis. jabatan terisi lewat bagan): lepas tanda & pesan galatnya.
        function bersihkanIsian(x) {
            if (!x.classList.contains('is-invalid')) return;
            const wadahPesan = wadahIsian(x);
            wadahPesan.querySelectorAll('.invalid-feedback').forEach((g) => g.remove());
            lepasTanda(grupIsian(x));
        }

        // Aturan lintas isian yang tidak tercakup validasi bawaan browser.
        const aturan = [
            {
                kunci: 'jumlah', wadah: wadahJumlah, isian: () => JK.map(([nama]) => form[nama]),
                lolos: () => total() >= 1,
                pesan: () => 'Isi jumlah kebutuhan minimal 1 orang: laki-laki, perempuan, atau bebas (L/P).',
            },
            {
                kunci: 'bagan', wadah: wadahBagan, isian: () => [], kotak: kotakBagan, fokus: () => layar,
                lolos: () => jenis() !== 'replacement' || (total() > 0 && dipilih.size === total()),
                pesan: () => (total() > 0
                    ? 'Klik ' + total() + ' pegawai yang digantikan di bagan (sekarang ' + dipilih.size + ' dipilih).'
                    : 'Klik pegawai yang digantikan di bagan; jumlah kebutuhan terisi otomatis.'),
            },
            {
                // Dropdown jabatan nonaktif (bagian tanpa jabatan) tidak ikut validasi browser.
                kunci: 'jabatan', wadah: wadahIsian(jabatan), isian: () => [jabatan], fokus: () => (jabatan.disabled ? unit : jabatan),
                lolos: () => !jabatan.disabled || !!jabatan.value,
                pesan: () => 'Bagian ini belum punya jabatan terdaftar. Pilih bagian lain, atau minta Admin/SDM menambahkan jabatannya.',
            },
        ];
        // Aturan yang sudah tampil diperbarui setiap ada perubahan: hilang bila sudah benar, teks ikut angka terbaru.
        function terapkanAturan(a) {
            const isian = a.isian();
            if (a.tampil && !a.lolos()) {
                tandai(isian, tampilPesan(a.wadah, 'aturan-' + a.kunci, a.pesan()));
                if (a.kotak) a.kotak.classList.add('is-invalid');
                return;
            }
            a.tampil = false;
            buangPesan(a.wadah, 'aturan-' + a.kunci);
            // Isian yang masih punya galat sendiri tetap merah.
            lepasTanda(isian.filter((x) => !wadahIsian(x).querySelector('.invalid-feedback')));
            if (a.kotak) a.kotak.classList.remove('is-invalid');
        }
        function segarkanAturan() {
            if (typeof aturan === 'undefined') return;
            aturan.forEach((a) => { if (a.tampil) terapkanAturan(a); });
        }
        function buangGalatServerBagan() {
            form.querySelectorAll('[data-galat-server="bagan"]').forEach((g) => g.remove());
        }

        // Saat isian bertanda merah diubah: galat browser dicek ulang; pesan galat server dibuang (sedang diperbaiki).
        ['input', 'change'].forEach((t) => form.addEventListener(t, (e) => {
            const x = e.target;
            if (x.name && x.classList && x.classList.contains('is-invalid')) {
                const wadahPesan = wadahIsian(x);
                if (wadahPesan.querySelector('[data-galat-klien="' + kunciIsian(x) + '"]')) {
                    periksaIsian(x);
                } else {
                    wadahPesan.querySelectorAll('.invalid-feedback:not([data-galat-klien])').forEach((g) => g.remove());
                    lepasTanda(grupIsian(x));
                }
            }
            segarkanAturan();
        }));

        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmed === '1') return;   // kiriman ulang setelah dialog konfirmasi disetujui
            hitung();
            const salah = [];
            Array.from(form.elements).filter((x) => x.willValidate && x.name).forEach((x) => {
                if (x.type === 'radio' && salah.some((s) => s.name === x.name)) return;   // satu grup radio cukup sekali
                if (!periksaIsian(x)) salah.push(x);
            });
            aturan.forEach((a) => {
                a.tampil = !a.lolos();
                terapkanAturan(a);
                if (a.tampil) salah.push(a.fokus ? a.fokus() : a.isian()[0]);
            });
            if (!salah.length) return;   // lanjut ke dialog konfirmasi global → tombol Ajukan berstatus memproses
            e.preventDefault();
            e.stopImmediatePropagation();
            const pertama = salah.filter(Boolean).sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1))[0];
            pertama.focus({ preventScroll: true });
            pertama.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }, true);

        // ---- Tugas & tanggung jawab: tambah/hapus baris (minimal 1, maksimal 30 baris) ----
        const daftarTugas = form.querySelector('[data-daftar-tugas]');
        const tombolTambahTugas = form.querySelector('[data-tambah-tugas]');
        function rapikanTugas() {
            const semua = Array.from(daftarTugas.querySelectorAll('.tugas-baris'));
            semua.forEach((b, i) => {
                b.querySelector('.tugas-no').textContent = i + 1;
                const isian = b.querySelector('input');
                isian.required = i === 0;
                isian.setAttribute('aria-label', 'Tugas ' + (i + 1));
                const hapus = b.querySelector('[data-hapus-tugas]');
                hapus.disabled = semua.length === 1;
                hapus.setAttribute('aria-label', 'Hapus tugas ' + (i + 1));
            });
            tombolTambahTugas.disabled = semua.length >= MAKS_TUGAS;
        }
        function tambahTugas(setelah) {
            if (daftarTugas.querySelectorAll('.tugas-baris').length >= MAKS_TUGAS) return;
            const baru = daftarTugas.querySelector('.tugas-baris').cloneNode(true);
            const isian = baru.querySelector('input');
            isian.value = '';
            isian.classList.remove('is-invalid');
            ['id', 'aria-invalid', 'aria-describedby'].forEach((a) => isian.removeAttribute(a));
            setelah ? setelah.after(baru) : daftarTugas.append(baru);
            rapikanTugas();
            isian.focus();
        }
        tombolTambahTugas.addEventListener('click', () => tambahTugas());
        daftarTugas.addEventListener('click', (e) => {
            const tombol = e.target.closest('[data-hapus-tugas]');
            if (!tombol || daftarTugas.querySelectorAll('.tugas-baris').length === 1) return;
            const baris = tombol.closest('.tugas-baris');
            const tujuan = baris.previousElementSibling || baris.nextElementSibling;
            baris.remove();
            rapikanTugas();
            tujuan?.querySelector('input')?.focus();   // fokus tidak hilang setelah baris dihapus
        });
        // Enter di sebuah baris: tambah baris baru di bawahnya (bukan mengirim formulir).
        daftarTugas.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' || !e.target.matches('input[name="tugas[]"]')) return;
            e.preventDefault();
            if (e.target.value.trim()) tambahTugas(e.target.closest('.tugas-baris'));
        });
        rapikanTugas();

        isiJabatan();
        // Kembali dari validasi gagal: jabatan pegawai yang digantikan (mis. yang sudah tidak aktif) dipulihkan.
        if (jenis() === 'replacement' && dipilih.size) setJabatan(jabatanDari([...dipilih][0]));
        hitung();
        gambar();
        pasLayar();
    });
</script>
@endsection
