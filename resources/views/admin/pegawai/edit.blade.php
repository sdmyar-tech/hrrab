@extends(Auth::user()->panelLayout())

@section('content')
@php
    $p = $pegawai;
    // Admin boleh menyimpan data belum lengkap: hanya kolom ini yang tetap wajib (sama dengan aturan server).
    $longgar = auth()->user()->can('sistem.override');
    $wajib = fn (string $kolom) => ! $longgar || in_array($kolom, ['nama_lengkap', 'jabatan', 'status_rekrutmen', 'status_kerja'], true);
    $opsi = fn (array $daftar) => array_combine($daftar, $daftar);

    $bagian = [
        ['id' => 'bagian-pribadi', 'label' => 'Data Pribadi', 'icon' => 'bi-person', 'kolom' => ['nama_lengkap', 'email', 'no_hp', 'nik', 'no_kk', 'npwp', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama', 'golongan_darah', 'status_pernikahan', 'jumlah_tanggungan', 'nama_ibu_kandung', 'pendidikan_terakhir']],
        ['id' => 'bagian-alamat', 'label' => 'Alamat', 'icon' => 'bi-house', 'kolom' => ['alamat_ktp', 'alamat_domisili']],
        ['id' => 'bagian-kepegawaian', 'label' => 'Kepegawaian', 'icon' => 'bi-briefcase', 'kolom' => ['nip', 'unit_kerja', 'jabatan', 'jabatan_tambahan', 'unit_organisasi', 'lokasi_kerja', 'tanggal_masuk', 'status_kerja', 'tanggal_habis_kontrak', 'status_rekrutmen', 'tanggal_keluar']],
        ['id' => 'bagian-kontak', 'label' => 'Kontak Darurat', 'icon' => 'bi-telephone', 'kolom' => ['nama_kerabat', 'no_hp_kerabat', 'hubungan_kerabat']],
        ['id' => 'bagian-bpjs', 'label' => 'BPJS', 'icon' => 'bi-heart-pulse', 'kolom' => ['no_bpjs_kesehatan', 'no_bpjs_ketenagakerjaan', 'faskes_tingkat_1']],
        ['id' => 'bagian-dokumen', 'label' => 'Dokumen', 'icon' => 'bi-folder2-open', 'kolom' => ['pas_foto', 'upload_ktp', 'upload_kk', 'upload_cv', 'upload_ijazah_terakhir', 'upload_buku_tabungan']],
    ];
    // Navigasi bagian: bagian yang memuat galat server diberi tanda.
    $nav = array_map(fn ($b) => ['id' => $b['id'], 'label' => $b['label'], 'icon' => $b['icon'], 'status' => $errors->hasAny($b['kolom']) ? false : null], $bagian);

    // [name, id, label, accept, bantuan] — format & ukuran mengikuti aturan server.
    $berkas = [
        ['pas_foto', 'f-pas-foto', 'Pas Foto', '.jpg,.jpeg,.png', 'JPG/PNG, maks. 2 MB.'],
        ['upload_ktp', 'f-upload-ktp', 'KTP', '.pdf,.jpg,.jpeg,.png', 'PDF/JPG/PNG, maks. 2 MB.'],
        ['upload_kk', 'f-upload-kk', 'Kartu Keluarga', '.pdf,.jpg,.jpeg,.png', 'PDF/JPG/PNG, maks. 2 MB.'],
        ['upload_cv', 'f-upload-cv', 'CV', '.pdf', 'PDF, maks. 2 MB.'],
        ['upload_ijazah_terakhir', 'f-upload-ijazah-terakhir', 'Ijazah Terakhir', '.pdf,.jpg,.jpeg,.png', 'PDF/JPG/PNG, maks. 2 MB.'],
        ['upload_buku_tabungan', 'f-upload-buku-tabungan', 'Buku Tabungan', '.pdf,.jpg,.jpeg,.png', 'PDF/JPG/PNG, maks. 2 MB.'],
    ];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Ubah Data Pegawai" eyebrow="Kepegawaian" :back="route('admin.pegawai.show', $pegawai->id)" back-label="Detail Pegawai"
            :subtitle="'Perbarui data ' . ($pegawai->nama_lengkap ?: 'pegawai') . '. ' . ($longgar ? 'Cukup isi kolom bertanda *; data lain bisa dilengkapi nanti.' : 'Kolom bertanda * wajib diisi.')" />

        {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
        @section('galat-di-halaman', '1')
        @if ($errors->any())
            <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-3">
                Perubahan belum tersimpan. Perbaiki isian berikut, lalu simpan lagi:
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form action="{{ route('admin.pegawai.update', $pegawai->id) }}"
            method="POST"
            enctype="multipart/form-data"
            id="wizardPegawaiForm"
            data-confirm="Simpan perubahan data pegawai ini?"
            novalidate>
            @csrf
            @method('PUT')

            <div class="row g-4 align-items-start">
                <aside class="col-lg-3 hr-samping">
                    <x-section-nav label="Bagian formulir pegawai" :items="$nav" />
                </aside>

                <div class="col-lg-9">
                    <x-card>
                        <x-form-section id="bagian-pribadi" class="hr-bagian" title="Data Pribadi" subtitle="Identitas pegawai sesuai KTP dan Kartu Keluarga." icon="bi-person">
                            <div class="col-md-6">
                                <x-input name="nama_lengkap" id="f-nama-lengkap" label="Nama Lengkap & Gelar" :value="$p->nama_lengkap" maxlength="255"
                                    placeholder="Nama beserta gelar, contoh: Siti Aminah, S.Pd" :required="$wajib('nama_lengkap')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="email" id="f-email" type="email" label="Email" :value="$p->email" maxlength="255"
                                    placeholder="Email aktif, contoh: siti.aminah@gmail.com" data-pesan="Format email belum benar. Contoh: siti.aminah@gmail.com"
                                    :required="$wajib('email')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="no_hp" id="f-no-hp" type="tel" label="No. HP (Aktif/WA)" :value="$p->no_hp" inputmode="tel" maxlength="20"
                                    placeholder="Nomor aktif/WhatsApp, contoh: 081234567890" :required="$wajib('no_hp')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="nik" id="f-nik" label="NIK (No. KTP)" :value="$p->nik" inputmode="numeric" maxlength="16" minlength="16" pattern="\d{16}"
                                    placeholder="16 digit angka, contoh: 1471010101010001" data-pesan="NIK harus 16 digit angka, contoh: 1471010101010001."
                                    :required="$wajib('nik')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="no_kk" id="f-no-kk" label="No. KK" :value="$p->no_kk" inputmode="numeric" maxlength="20"
                                    placeholder="Nomor Kartu Keluarga, contoh: 1471010101010002" :required="$wajib('no_kk')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="npwp" id="f-npwp" label="No. NPWP" optional :value="$p->npwp" maxlength="25"
                                    placeholder="Contoh: 12.345.678.9-012.000" help="Hanya bagi yang sudah memiliki NPWP." field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="tempat_lahir" id="f-tempat-lahir" label="Tempat Lahir" :value="$p->tempat_lahir" maxlength="255"
                                    placeholder="Kota kelahiran, contoh: Pekanbaru" :required="$wajib('tempat_lahir')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                {{-- Batas usia minimal 15 tahun hanya untuk peran selain Admin (server juga melonggarkan Admin). --}}
                                <x-input name="tanggal_lahir" id="f-tanggal-lahir" type="date" label="Tanggal Lahir" :max="$longgar ? null : now()->subYears(15)->format('Y-m-d')"
                                    :value="isset($p->tanggal_lahir) ? $p->tanggal_lahir->format('Y-m-d') : ''"
                                    data-pesan="Tanggal lahir belum benar: usia pegawai minimal 15 tahun." :required="$wajib('tanggal_lahir')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-select name="jenis_kelamin" id="f-jenis-kelamin" label="Jenis Kelamin" :options="$opsi(['Laki-laki', 'Perempuan'])"
                                    :selected="$p->jenis_kelamin" placeholder="Pilih jenis kelamin" :required="$wajib('jenis_kelamin')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="agama" id="f-agama" label="Agama" :value="$p->agama" maxlength="50"
                                    placeholder="Contoh: Islam" :required="$wajib('agama')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-select name="golongan_darah" id="f-golongan-darah" label="Golongan Darah" optional :options="$opsi(\App\Models\DataPegawai::GOLONGAN_DARAH)"
                                    :selected="$p->golongan_darah" help="Pilih “Belum Diketahui” bila belum pernah cek." field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-select name="status_pernikahan" id="f-status-pernikahan" label="Status Pernikahan" :options="$opsi(['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'])"
                                    :selected="$p->status_pernikahan" :required="$wajib('status_pernikahan')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="jumlah_tanggungan" id="f-jumlah-tanggungan" type="number" label="Jumlah Tanggungan" optional min="0" max="20"
                                    :value="$p->jumlah_tanggungan" help="Jumlah orang yang dinafkahi pegawai (0–20)."
                                    data-pesan="Isi angka 0 sampai 20." field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="nama_ibu_kandung" id="f-nama-ibu-kandung" label="Nama Ibu Kandung" :value="$p->nama_ibu_kandung" maxlength="255"
                                    placeholder="Sesuai Kartu Keluarga, contoh: Aminah" :required="$wajib('nama_ibu_kandung')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="pendidikan_terakhir" id="f-pendidikan-terakhir" label="Pendidikan Terakhir" :value="$p->pendidikan_terakhir" maxlength="100"
                                    placeholder="Jenjang dan jurusan, contoh: S1 Pendidikan" :required="$wajib('pendidikan_terakhir')" field-class="mb-0" />
                            </div>
                        </x-form-section>

                        <x-form-section id="bagian-alamat" class="hr-bagian" title="Alamat" subtitle="Alamat domisili diisi hanya bila berbeda dari alamat KTP." icon="bi-house">
                            <div class="col-12">
                                <x-textarea name="alamat_ktp" id="f-alamat-ktp" label="Alamat KTP" rows="2" :value="$p->alamat_ktp"
                                    placeholder="Jalan, RT/RW, kelurahan, kecamatan, kota. Contoh: Jl. Riau No. 10, RT 02/RW 03, Padang Terubuk, Senapelan, Pekanbaru"
                                    :required="$wajib('alamat_ktp')" field-class="mb-0" />
                            </div>
                            <div class="col-12">
                                <x-textarea name="alamat_domisili" id="f-alamat-domisili" label="Alamat Domisili" optional rows="2" :value="$p->alamat_domisili"
                                    placeholder="Kosongkan bila sama dengan KTP. Contoh: Jl. Melati No. 5, Pekanbaru" field-class="mb-0" />
                            </div>
                        </x-form-section>

                        <x-form-section id="bagian-kepegawaian" class="hr-bagian" title="Kepegawaian" subtitle="Penempatan, jabatan, dan status kerja." icon="bi-briefcase">
                            <div class="col-md-6">
                                <x-input name="nip" id="f-nip" label="NIP (No. Induk Pegawai)" :value="$p->nip" maxlength="50"
                                    placeholder="Nomor induk pegawai, contoh: 0101199001012020001" optional field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                {{-- Selain Admin, bagian utama hanya berubah lewat Mutasi. Admin boleh koreksi langsung. --}}
                                @if ($longgar)
                                    <x-select name="unit_kerja" id="f-unit-kerja" label="Bagian (Utama)" :options="$opsi(config('kepegawaian.unit_kerja'))"
                                        :selected="$p->kepegawaian->unit_kerja ?? ''" aria-describedby="f-unit-kerja-info" field-class="mb-0" />
                                @else
                                    <div class="hr-field mb-0">
                                        <label for="f-unit-kerja" class="form-label">Bagian (Utama)</label>
                                        <input id="f-unit-kerja" type="text" class="form-control" value="{{ $p->kepegawaian->unit_kerja ?? '-' }}" disabled aria-describedby="f-unit-kerja-info">
                                    </div>
                                @endif
                                <div class="form-text" id="f-unit-kerja-info">
                                    @if ($longgar)
                                        <x-badge tone="warning" icon="bi-shield-lock">Mode Admin</x-badge> Ubah langsung (koreksi) tanpa membuat riwayat mutasi.<br>
                                    @endif
                                    Pindah bagian lewat
                                    <a href="{{ route('admin.penugasan.create', ['pegawai' => $pegawai, 'jenis' => 'mutasi']) }}">Mutasi</a>
                                    atau tambah <a href="{{ route('admin.penugasan.create', ['pegawai' => $pegawai, 'jenis' => 'rangkap']) }}">Rangkap Tugas</a>.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <x-select name="jabatan" id="f-jabatan" label="Jabatan Utama" :options="$opsi(\App\Models\Jabatan::daftar())" data-jabatan-unit="[name=unit_kerja]"
                                    :selected="$p->kepegawaian->jabatan ?? ''" placeholder="Pilih jabatan utama" help="Jabatan di bagian terpilih tampil paling atas."
                                    :required="$wajib('jabatan')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-select name="jabatan_tambahan" id="f-jabatan-tambahan" label="Jabatan Tambahan" optional :options="$opsi(\App\Models\Jabatan::daftar())" data-jabatan-unit="[name=unit_kerja]"
                                    :selected="$p->kepegawaian->jabatan_tambahan ?? ''" placeholder="Tidak ada" help="Isi bila pegawai memegang lebih dari satu jabatan." field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-select name="unit_organisasi" id="f-unit-organisasi" label="Unit" optional :options="$opsi(\App\Models\UnitOrganisasi::daftar())"
                                    :selected="$p->kepegawaian->unit_organisasi ?? ''" placeholder="Tanpa unit" aria-describedby="f-unit-organisasi-info" field-class="mb-0" />
                                <div class="form-text" id="f-unit-organisasi-info">
                                    Pengelompokan pegawai; persetujuan tetap mengikuti Bagian. Kelola daftarnya di <a href="{{ route('admin.master-unit.index') }}">Master Unit</a>.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <x-select name="lokasi_kerja" id="f-lokasi-kerja" label="Lokasi Kerja" :options="$opsi(config('kepegawaian.lokasi_kerja'))"
                                    :selected="$p->kepegawaian->lokasi_kerja ?? ''" placeholder="Pilih lokasi kerja" :required="$wajib('lokasi_kerja')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="tanggal_masuk" id="f-tanggal-masuk" type="date" label="Tanggal Masuk" :max="now()->format('Y-m-d')"
                                    :value="isset($p->kepegawaian->tanggal_masuk) ? $p->kepegawaian->tanggal_masuk->format('Y-m-d') : ''"
                                    data-pesan="Tanggal masuk tidak boleh melewati hari ini." :required="$wajib('tanggal_masuk')" field-class="mb-0" />
                            </div>
                            @if ($p->kepegawaian)
                                <div class="col-md-6">
                                    <div class="hr-field mb-0">
                                        <label for="f-masa-kerja" class="form-label">Masa Kerja</label>
                                        <input id="f-masa-kerja" type="text" class="form-control" value="{{ $p->kepegawaian->masa_kerja }}" readonly tabindex="-1" aria-describedby="f-masa-kerja-bantuan">
                                        <div class="form-text" id="f-masa-kerja-bantuan">Dihitung otomatis dari tanggal masuk.</div>
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-6">
                                <x-select name="status_kerja" id="f-status-kerja" label="Status Kerja" :options="$opsi(['Tetap', 'Kontrak', 'Magang', 'Harian Lepas', 'Probation'])"
                                    :selected="$p->kepegawaian->status_kerja ?? ''" :required="$wajib('status_kerja')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="tanggal_habis_kontrak" id="f-tanggal-habis-kontrak" type="date" label="Tanggal Habis Kontrak" optional
                                    :value="isset($p->kepegawaian->tanggal_habis_kontrak) ? $p->kepegawaian->tanggal_habis_kontrak->format('Y-m-d') : ''"
                                    help="Untuk pegawai kontrak, magang, atau masa percobaan." field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-select name="status_rekrutmen" id="f-status-rekrutmen" label="Status Rekrutmen" :options="$opsi(['Aktif', 'Tidak Aktif'])"
                                    :selected="$p->kepegawaian->status_rekrutmen ?? ''" :required="$wajib('status_rekrutmen')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6" data-tanggal-keluar>
                                <x-input name="tanggal_keluar" id="f-tanggal-keluar" type="date" label="Tanggal Keluar" optional :max="now()->format('Y-m-d')"
                                    :value="optional($p->kepegawaian->tanggal_keluar ?? null)->format('Y-m-d')"
                                    data-pesan="Tanggal keluar tidak boleh melewati hari ini." help="Kosongkan = hari ini saat status diubah ke Tidak Aktif." field-class="mb-0" />
                            </div>
                        </x-form-section>

                        <x-form-section id="bagian-kontak" class="hr-bagian" title="Kontak Darurat" subtitle="Kerabat yang dihubungi bila terjadi keadaan darurat." icon="bi-telephone">
                            <div class="col-md-6">
                                <x-input name="nama_kerabat" id="f-nama-kerabat" label="Nama Kerabat" :value="$p->kontakDarurat->nama_kerabat ?? ''" maxlength="255"
                                    placeholder="Kerabat terdekat, contoh: Ahmad Fauzi" :required="$wajib('nama_kerabat')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="no_hp_kerabat" id="f-no-hp-kerabat" type="tel" label="No. HP Kerabat" :value="$p->kontakDarurat->no_hp_kerabat ?? ''" inputmode="tel" maxlength="20"
                                    placeholder="Nomor aktif, contoh: 081234567890" :required="$wajib('no_hp_kerabat')" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="hubungan_kerabat" id="f-hubungan-kerabat" label="Hubungan dengan Kerabat" :value="$p->kontakDarurat->hubungan_kerabat ?? ''" maxlength="100"
                                    placeholder="Contoh: Istri / Suami / Ibu Kandung" :required="$wajib('hubungan_kerabat')" field-class="mb-0" />
                            </div>
                        </x-form-section>

                        <x-form-section id="bagian-bpjs" class="hr-bagian" title="BPJS" subtitle="Isi bila pegawai sudah terdaftar." icon="bi-heart-pulse">
                            <div class="col-md-6">
                                <x-input name="no_bpjs_kesehatan" id="f-no-bpjs-kesehatan" label="No. BPJS Kesehatan" optional :value="$p->bpjs->no_bpjs_kesehatan ?? ''" maxlength="30"
                                    placeholder="Nomor kartu, contoh: 0001234567890" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="no_bpjs_ketenagakerjaan" id="f-no-bpjs-ketenagakerjaan" label="No. BPJS Ketenagakerjaan" optional :value="$p->bpjs->no_bpjs_ketenagakerjaan ?? ''" maxlength="30"
                                    placeholder="Nomor kartu, contoh: 12345678901" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="faskes_tingkat_1" id="f-faskes-tingkat-1" label="Faskes Tingkat 1" optional :value="$p->bpjs->faskes_tingkat_1 ?? ''" maxlength="255"
                                    placeholder="Nama RS/klinik, contoh: Klinik Abdurrab" field-class="mb-0" />
                            </div>
                        </x-form-section>

                        <x-form-section id="bagian-dokumen" class="hr-bagian" title="Dokumen" subtitle="Unggah ulang hanya bila ingin mengganti berkas yang sudah ada." icon="bi-folder2-open">
                            @foreach ($berkas as [$nama, $id, $label, $terima, $bantuan])
                                @php $ada = $p->dokumen?->{$nama}; @endphp
                                <div class="col-md-6">
                                    <div class="hr-field mb-0">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                            <label for="{{ $id }}" class="form-label mb-0">{{ $label }} <span class="hr-field-opsional">(opsional)</span></label>
                                            @if ($ada)
                                                <a href="{{ \App\Support\Berkas::url($ada) }}" target="_blank" rel="noopener" class="hr-badge hr-badge-success text-decoration-none">
                                                    <i class="bi bi-check2" aria-hidden="true"></i>Sudah diunggah · Lihat
                                                </a>
                                            @else
                                                <x-badge tone="neutral">Belum ada</x-badge>
                                            @endif
                                        </div>
                                        <input id="{{ $id }}" type="file" name="{{ $nama }}" accept="{{ $terima }}"
                                            class="form-control @error($nama) is-invalid @enderror" aria-describedby="{{ $id }}-bantuan">
                                        @error($nama)
                                            <div class="invalid-feedback d-block" id="{{ $id }}-bantuan"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                        @else
                                            <div class="form-text" id="{{ $id }}-bantuan">{{ $bantuan }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @endforeach
                        </x-form-section>
                    </x-card>

                    <div class="hr-bilah-aksi mt-3">
                        <x-button variant="secondary" :href="route('admin.pegawai.show', $pegawai->id)">Batal</x-button>
                        <x-button type="submit" icon="bi-check-lg">Simpan Perubahan</x-button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('wizardPegawaiForm');

        // Tanggal keluar hanya relevan bila status Tidak Aktif.
        const status = form.querySelector('select[name=status_rekrutmen]');
        const keluar = form.querySelector('[data-tanggal-keluar]');
        const aturKeluar = () => { keluar.hidden = status.value !== 'Tidak Aktif'; };
        status.addEventListener('change', aturKeluar);
        aturKeluar();

        // Pesan galat isian (Bahasa Indonesia): kosong → dari label; format → data-pesan.
        function pesan(el) {
            const label = (form.querySelector('label[for="' + el.id + '"]')?.firstChild?.textContent || 'Isian ini').trim();
            if (el.validity.valueMissing) return el.tagName === 'SELECT' ? 'Pilih ' + label.toLowerCase() + '.' : label + ' wajib diisi.';
            return el.dataset.pesan || el.validationMessage;
        }

        // Tandai satu isian: merah + pesan di bawahnya bila tidak valid. Galat server yang sudah tampil tidak digandakan.
        function periksa(el) {
            const wadah = el.closest('.hr-field') || el.parentElement;
            let umpan = wadah.querySelector('[data-galat-klien]');
            if (el.checkValidity()) {
                if (umpan) {
                    umpan.remove();
                    el.classList.remove('is-invalid');
                    el.removeAttribute('aria-invalid');
                }
                return true;
            }
            el.classList.add('is-invalid');
            el.setAttribute('aria-invalid', 'true');
            if (!wadah.querySelector('.invalid-feedback:not([data-galat-klien])')) {
                if (!umpan) {
                    umpan = document.createElement('div');
                    umpan.className = 'invalid-feedback d-block';
                    umpan.id = el.id + '-galat-klien';
                    umpan.dataset.galatKlien = '';
                    wadah.append(umpan);
                    el.setAttribute('aria-describedby', ((el.getAttribute('aria-describedby') || '') + ' ' + umpan.id).trim());
                }
                umpan.innerHTML = '<i class="bi bi-exclamation-circle" aria-hidden="true"></i> ';
                umpan.append(pesan(el));
            }
            return false;
        }

        // Saat isian bertanda merah diubah: galat browser dicek ulang, pesan galat server dibuang (sedang diperbaiki;
        // .d-block mengalahkan [hidden], jadi elemennya dihapus).
        ['input', 'change'].forEach(function (jenis) {
            form.addEventListener(jenis, function (e) {
                const el = e.target;
                if (!el.classList || !el.classList.contains('is-invalid')) return;
                const wadah = el.closest('.hr-field') || el.parentElement;
                if (wadah.querySelector('[data-galat-klien]')) { periksa(el); return; }
                wadah.querySelectorAll('.invalid-feedback').forEach((g) => g.remove());
                el.classList.remove('is-invalid');
                el.removeAttribute('aria-invalid');
            });
        });

        // Sebelum dikirim: semua isian dicek. Ada yang salah → tandai & fokus ke yang pertama;
        // semua benar → dialog konfirmasi data-confirm lalu tombol Simpan berstatus memuat (layouts.partials.sweetalert).
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmed === '1') return;
            const salah = Array.from(form.elements).filter((el) => el.willValidate).filter((el) => !periksa(el));
            if (!salah.length) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            salah[0].focus({ preventScroll: true });
            salah[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
        }, true);
    });
</script>
@include('partials.jabatan-ikut-unit')
@endsection
