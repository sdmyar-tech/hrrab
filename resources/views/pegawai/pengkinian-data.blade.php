@extends(Auth::user()->panelLayout())

@section('title', 'Pengkinian Data')

@section('content')
@use('App\Http\Controllers\Pegawai\ProfilController', 'Profil')
@php
    $employee = $user->dataPegawai;
    $kepegawaian = $employee?->kepegawaian;
    $kontakDarurat = $employee?->kontakDarurat;
    $bpjs = $employee?->bpjs;
    $dokumen = $employee?->dokumen;
    $kosong = fn ($value) => is_null($value) || $value === '' || $value === [];

    $jabatanOptions = [...\App\Models\Jabatan::daftar(), 'Lainnya'];
    $infoFile = 'PDF/JPG/PNG, maks. 2 MB.';

    $bagian = [
        'pribadi' => ['judul' => 'Data Pribadi', 'langkah' => 'Identitas', 'ikon' => 'bi-person', 'sub' => 'Sesuai KTP dan Kartu Keluarga.', 'fields' => [
            ['name' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'text', 'value' => $employee?->nama_lengkap, 'required' => true, 'help' => 'Tulis nama lengkap beserta gelar.', 'placeholder' => 'Contoh: Siti Aminah, S.Pd'],
            ['name' => 'nik', 'label' => 'NIK (No. KTP)', 'type' => 'text', 'value' => $employee?->nik, 'required' => true, 'attr' => 'inputmode=numeric maxlength=16 pattern=[0-9]{16}', 'help' => '16 digit angka.', 'placeholder' => 'Contoh: 1471010101010001'],
            ['name' => 'no_kk', 'label' => 'No. KK', 'type' => 'text', 'value' => $employee?->no_kk, 'required' => true, 'attr' => 'inputmode=numeric maxlength=20', 'placeholder' => 'Contoh: 1471010101010002'],
            ['name' => 'npwp', 'label' => 'No. NPWP', 'type' => 'text', 'value' => $employee?->npwp, 'required' => false, 'help' => 'Hanya bagi yang sudah memiliki NPWP.', 'placeholder' => 'Contoh: 12.345.678.9-012.000'],
            ['name' => 'no_hp', 'label' => 'No. HP (aktif)', 'type' => 'tel', 'value' => $employee?->no_hp, 'required' => true, 'attr' => 'inputmode=tel maxlength=20', 'placeholder' => 'Contoh: 081234567890'],
            ['name' => 'tempat_lahir', 'label' => 'Tempat Lahir', 'type' => 'text', 'value' => $employee?->tempat_lahir, 'required' => true, 'placeholder' => 'Contoh: Pekanbaru'],
            ['name' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'type' => 'date', 'value' => optional($employee?->tanggal_lahir)->format('Y-m-d'), 'required' => true],
            ['name' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'select', 'value' => $employee?->jenis_kelamin, 'options' => ['Laki-laki', 'Perempuan'], 'required' => true],
            ['name' => 'agama', 'label' => 'Agama', 'type' => 'select', 'value' => $employee?->agama, 'options' => Profil::AGAMA, 'required' => true],
            ['name' => 'golongan_darah', 'label' => 'Golongan Darah', 'type' => 'select', 'value' => $employee?->golongan_darah, 'options' => Profil::GOLONGAN_DARAH, 'required' => false, 'help' => 'Pilih "Belum Diketahui" bila belum pernah cek.'],
            ['name' => 'status_pernikahan', 'label' => 'Status Pernikahan', 'type' => 'select', 'value' => $employee?->status_pernikahan, 'options' => Profil::STATUS_PERNIKAHAN, 'required' => true],
            ['name' => 'jumlah_tanggungan', 'label' => 'Jumlah Tanggungan', 'type' => 'number', 'value' => $employee?->jumlah_tanggungan, 'required' => true, 'attr' => 'min=0 max=20', 'help' => 'Jumlah orang yang Anda nafkahi.'],
            ['name' => 'nama_ibu_kandung', 'label' => 'Nama Ibu Kandung', 'type' => 'text', 'value' => $employee?->nama_ibu_kandung, 'required' => true, 'placeholder' => 'Sesuai Kartu Keluarga'],
            ['name' => 'pendidikan_terakhir', 'label' => 'Pendidikan Terakhir', 'type' => 'text', 'value' => $employee?->pendidikan_terakhir, 'required' => true, 'placeholder' => 'Contoh: S1 Manajemen'],
            ['name' => 'alamat_ktp', 'label' => 'Alamat KTP', 'type' => 'textarea', 'value' => $employee?->alamat_ktp, 'required' => true, 'lebar' => 12, 'placeholder' => 'Jalan, RT/RW, kelurahan, kecamatan, kota'],
            ['name' => 'alamat_domisili', 'label' => 'Alamat Domisili', 'type' => 'textarea', 'value' => $employee?->alamat_domisili, 'required' => false, 'lebar' => 12, 'help' => 'Kosongkan bila sama dengan alamat KTP.'],
        ]],
        'kepegawaian' => ['judul' => 'Kepegawaian', 'langkah' => 'Kepegawaian', 'ikon' => 'bi-briefcase', 'sub' => 'Jabatan dan penempatan Anda saat ini.', 'fields' => [
            ['name' => 'jabatan', 'label' => 'Jabatan', 'type' => 'select', 'value' => $kepegawaian?->jabatan, 'options' => $jabatanOptions, 'required' => true, 'lainnya' => true, 'help' => 'Jabatan utama saat ini. Pilih "Lainnya" bila tidak ada di daftar.'],
            ['name' => 'unit_kerja', 'label' => 'Bagian', 'type' => 'select', 'value' => $kepegawaian?->unit_kerja, 'options' => config('kepegawaian.unit_kerja'), 'required' => true],
            ['name' => 'lokasi_kerja', 'label' => 'Lokasi Kerja', 'type' => 'select', 'value' => $kepegawaian?->lokasi_kerja, 'options' => config('kepegawaian.lokasi_kerja'), 'required' => true,
                // Lokasi ikut bagian & jabatan (Master Jabatan → Atur lokasi): satu lokasi → terisi otomatis.
                'attrs' => ['data-lokasi-jabatan' => '[name=jabatan]', 'data-lokasi-unit' => '[name=unit_kerja]']],
            ['name' => 'tanggal_masuk', 'label' => 'Tanggal Masuk', 'type' => 'date', 'value' => optional($kepegawaian?->tanggal_masuk)->format('Y-m-d'), 'required' => true, 'help' => 'Tanggal mulai aktif bekerja. Tanyakan pimpinan bila tidak tahu.'],
        ]],
        'kontak' => ['judul' => 'Kontak Darurat', 'langkah' => 'Kontak', 'ikon' => 'bi-telephone', 'sub' => 'Kerabat yang dihubungi bila terjadi keadaan darurat.', 'fields' => [
            ['name' => 'nama_kerabat', 'label' => 'Nama Kerabat', 'type' => 'text', 'value' => $kontakDarurat?->nama_kerabat, 'required' => true, 'help' => 'Kerabat terdekat sesuai domisili.', 'placeholder' => 'Contoh: Ahmad Fauzi'],
            ['name' => 'no_hp_kerabat', 'label' => 'No. HP Kerabat', 'type' => 'tel', 'value' => $kontakDarurat?->no_hp_kerabat, 'required' => true, 'attr' => 'inputmode=tel maxlength=20', 'placeholder' => 'Contoh: 081234567890'],
            ['name' => 'hubungan_kerabat', 'label' => 'Hubungan dengan Kerabat', 'type' => 'select', 'value' => $kontakDarurat?->hubungan_kerabat, 'options' => Profil::HUBUNGAN_KERABAT, 'required' => true, 'lainnya' => true],
        ]],
        'bpjs' => ['judul' => 'BPJS', 'langkah' => 'BPJS', 'ikon' => 'bi-heart-pulse', 'sub' => 'Isi bila sudah terdaftar.', 'fields' => [
            ['name' => 'no_bpjs_kesehatan', 'label' => 'No. BPJS Kesehatan', 'type' => 'text', 'value' => $bpjs?->no_bpjs_kesehatan, 'required' => false, 'placeholder' => 'Contoh: 0001234567890'],
            ['name' => 'no_bpjs_ketenagakerjaan', 'label' => 'No. BPJS Ketenagakerjaan', 'type' => 'text', 'value' => $bpjs?->no_bpjs_ketenagakerjaan, 'required' => false, 'placeholder' => 'Contoh: 12345678901'],
            ['name' => 'faskes_tingkat_1', 'label' => 'Faskes Tingkat 1', 'type' => 'text', 'value' => $bpjs?->faskes_tingkat_1, 'required' => false, 'lebar' => 12, 'placeholder' => 'Contoh: Puskesmas Sail'],
        ]],
        'dokumen' => ['judul' => 'Dokumen', 'langkah' => 'Dokumen', 'ikon' => 'bi-folder2-open', 'sub' => 'Unggah berkas yang masih kosong.', 'fields' => [
            ['name' => 'upload_ktp', 'label' => 'KTP', 'type' => 'file', 'value' => $dokumen?->upload_ktp, 'required' => true, 'accept' => '.pdf,.jpg,.jpeg,.png', 'help' => $infoFile],
            ['name' => 'upload_kk', 'label' => 'Kartu Keluarga', 'type' => 'file', 'value' => $dokumen?->upload_kk, 'required' => true, 'accept' => '.pdf,.jpg,.jpeg,.png', 'help' => $infoFile],
            ['name' => 'pas_foto', 'label' => 'Pas Foto', 'type' => 'file', 'value' => $dokumen?->pas_foto, 'required' => true, 'accept' => '.jpg,.jpeg,.png', 'help' => 'JPG/PNG, maks. 2 MB.'],
            ['name' => 'upload_ijazah_terakhir', 'label' => 'Ijazah Terakhir', 'type' => 'file', 'value' => $dokumen?->upload_ijazah_terakhir, 'required' => false, 'accept' => '.pdf,.jpg,.jpeg,.png', 'help' => $infoFile],
            ['name' => 'upload_cv', 'label' => 'CV', 'type' => 'file', 'value' => $dokumen?->upload_cv, 'required' => false, 'accept' => '.pdf,.jpg,.jpeg,.png', 'help' => $infoFile],
            ['name' => 'upload_buku_tabungan', 'label' => 'Buku Tabungan', 'type' => 'file', 'value' => $dokumen?->upload_buku_tabungan, 'required' => false, 'accept' => '.pdf,.jpg,.jpeg,.png', 'help' => $infoFile],
        ]],
    ];

    $totalWajib = collect($bagian)->flatMap(fn ($b) => $b['fields'])->where('required', true)->count();
    foreach ($bagian as $key => $b) {
        $bagian[$key]['fields'] = array_values(array_filter($b['fields'], fn ($f) => $kosong($f['value'])));
    }
    $bagian = array_filter($bagian, fn ($b) => $b['fields'] !== []);
    $jumlahKosong = collect($bagian)->sum(fn ($b) => count($b['fields']));
    $wajibKosong = collect($bagian)->flatMap(fn ($b) => $b['fields'])->where('required', true)->count();
    $persen = $totalWajib ? (int) round(($totalWajib - $wajibKosong) / $totalWajib * 100) : 100;
    $langkah = collect($bagian)->map(fn ($b, $k) => ['key' => $k, 'label' => $b['langkah'], 'icon' => $b['ikon']])->values()
        ->push(['key' => 'tinjau', 'label' => 'Tinjau', 'icon' => 'bi-check2-square'])->all();
@endphp

<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Pengkinian Data" eyebrow="Data Saya" subtitle="Lengkapi data yang masih kosong. Data yang sudah terisi diubah lewat Admin SDM."
            :back="route('pegawai.data-pegawai')" back-label="Data Pegawai" />

        @if ($bagian === [])
            <x-card>
                <x-empty-state icon="bi-patch-check" title="Data Anda sudah lengkap">
                    Tidak ada isian yang perlu dilengkapi lagi. Terima kasih!
                    <x-slot:action><x-button :href="route('pegawai.data-pegawai')" variant="secondary" icon="bi-person-vcard">Lihat Data Pegawai</x-button></x-slot:action>
                </x-empty-state>
            </x-card>
        @else
            {{-- Kelengkapan data wajib --}}
            <x-card class="mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <span class="hr-stat-label d-block">Kelengkapan data wajib</span>
                        <strong class="pelamar-persen">{{ $persen }}%</strong>
                    </div>
                    <div class="small text-muted text-md-end">
                        {{ $wajibKosong }} isian wajib & {{ $jumlahKosong - $wajibKosong }} opsional masih kosong<br>
                        NIP: <strong class="text-body">{{ $employee?->nip ?: ($user->nip ?: 'belum ada — hubungi Admin SDM') }}</strong>
                    </div>
                </div>
                <div class="progress" style="height: 6px;" role="progressbar" aria-label="Kelengkapan data wajib" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar {{ $persen >= 100 ? 'bg-success' : '' }}" style="width: {{ $persen }}%"></div>
                </div>
            </x-card>

            {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
            @section('galat-di-halaman', '1')
            @if ($errors->any())
                <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-3">
                    <ul class="mb-0 ps-3">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
                </x-alert>
            @endif

            <x-stepper id="langkahPengkinian" :steps="$langkah" class="mb-3" />

            <form action="{{ route('pegawai.pengkinian-data.store') }}" method="POST" enctype="multipart/form-data"
                data-confirm="Simpan pengkinian data ini?" id="formPengkinian" class="d-grid gap-3">
                @csrf

                @foreach ($bagian as $kunci => $b)
                    <x-card :title="$b['judul']" :subtitle="$b['sub']" :icon="$b['ikon']" data-langkah="{{ $kunci }}">
                        <div class="row gx-3">
                            @foreach ($b['fields'] as $field)
                                @php
                                    $nama = $field['name'];
                                    $nilai = old($nama, $field['value']);
                                    $galat = $errors->first($nama);
                                    $invalid = $galat ? 'is-invalid' : '';
                                    $pakaiLainnya = ($field['lainnya'] ?? false) && filled($nilai) && ! in_array($nilai, $field['options'], true);
                                @endphp
                                <div class="col-md-{{ $field['lebar'] ?? 6 }}">
                                    <div class="hr-field">
                                        <label class="form-label {{ $field['required'] ? 'required' : '' }}" for="f-{{ $nama }}">
                                            {{ $field['label'] }}@unless ($field['required']) <span class="hr-field-opsional">(opsional)</span>@endunless
                                        </label>

                                        @switch($field['type'])
                                            @case('textarea')
                                                <textarea name="{{ $nama }}" id="f-{{ $nama }}" rows="2" class="form-control {{ $invalid }}" placeholder="{{ $field['placeholder'] ?? '' }}" @required($field['required'])>{{ $nilai }}</textarea>
                                                @break

                                            @case('select')
                                                <select name="{{ $nama }}" id="f-{{ $nama }}" class="form-select {{ $invalid }}" @required($field['required'])
                                                    @if ($field['lainnya'] ?? false) data-lainnya="#f-{{ $nama }}-lainnya" @endif
                                                    @foreach ($field['attrs'] ?? [] as $atribut => $isi) {{ $atribut }}="{{ $isi }}" @endforeach>
                                                    <option value="">Pilih {{ mb_strtolower($field['label']) }}</option>
                                                    @foreach ($field['options'] as $option)
                                                        <option value="{{ $option }}" @selected($pakaiLainnya ? $option === 'Lainnya' : $nilai === $option)>{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($field['lainnya'] ?? false)
                                                    <input type="text" name="{{ $nama }}_lainnya" id="f-{{ $nama }}-lainnya" class="form-control mt-2"
                                                        value="{{ old($nama . '_lainnya', $pakaiLainnya ? $nilai : '') }}" maxlength="100"
                                                        placeholder="{{ $nama === 'jabatan' ? 'Tulis jabatan, contoh: Guru Seni Budaya' : 'Tulis hubungan, contoh: Paman' }}"
                                                        aria-label="{{ $field['label'] }} lainnya" hidden>
                                                @endif
                                                @break

                                            @case('file')
                                                <input type="file" name="{{ $nama }}" id="f-{{ $nama }}" class="form-control {{ $invalid }}" accept="{{ $field['accept'] }}" @required($field['required'])>
                                                @break

                                            @default
                                                <input type="{{ $field['type'] }}" name="{{ $nama }}" id="f-{{ $nama }}" class="form-control {{ $invalid }}"
                                                    value="{{ $nilai }}" placeholder="{{ $field['placeholder'] ?? '' }}" {!! $field['attr'] ?? '' !!} @required($field['required'])>
                                        @endswitch

                                        @if ($galat)
                                            <div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $galat }}</div>
                                        @elseif (! empty($field['help']))
                                            <div class="form-text">{{ $field['help'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endforeach

                {{-- Langkah terakhir: ringkasan isian sebelum disimpan (diisi skrip). --}}
                <x-card title="Tinjau Data" subtitle="Periksa sekali lagi sebelum disimpan. Kembali ke langkah sebelumnya untuk memperbaiki." icon="bi-check2-square"
                    data-langkah="tinjau" hidden>
                    <div data-ringkasan></div>
                </x-card>

                <div class="hr-bilah-aksi">
                    <x-button variant="secondary" icon="bi-arrow-left" data-langkah-kembali hidden>Kembali</x-button>
                    <x-button :href="route(Auth::user()->dashboardRoute())" variant="ghost" data-langkah-batal>Batal</x-button>
                    <x-button icon-right="bi-arrow-right" data-langkah-lanjut hidden>Lanjut</x-button>
                    <x-button type="submit" icon="bi-check-lg" data-langkah-simpan>Simpan Data</x-button>
                </div>
            </form>
        @endif
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // "Lainnya" → isian teks muncul & wajib.
        document.querySelectorAll('select[data-lainnya]').forEach(function (select) {
            const input = document.querySelector(select.dataset.lainnya);
            function sinkron() {
                const lainnya = select.value === 'Lainnya';
                input.hidden = !lainnya;
                input.required = lainnya;
            }
            select.addEventListener('change', sinkron);
            sinkron();
        });

        // Form bertahap: satu bagian per langkah, validasi sebelum lanjut, tinjau sebelum simpan.
        // Tanpa skrip semua bagian tetap tampil sebagai satu form biasa.
        const form = document.getElementById('formPengkinian');
        if (!form) return;
        const langkah = Array.from(form.querySelectorAll('[data-langkah]'));
        const tKembali = form.querySelector('[data-langkah-kembali]');
        const tLanjut = form.querySelector('[data-langkah-lanjut]');
        const tSimpan = form.querySelector('[data-langkah-simpan]');
        const tBatal = form.querySelector('[data-langkah-batal]');
        const ringkasan = form.querySelector('[data-ringkasan]');
        // Mulai dari langkah yang memuat galat server (bila ada).
        let kini = Math.max(0, langkah.findIndex((s) => s.querySelector('.is-invalid')));

        function nilaiTampil(el) {
            if (el.type === 'file') return el.files.length ? el.files[0].name : '';
            if (el.tagName === 'SELECT') return el.value ? el.options[el.selectedIndex].text : '';
            if (el.type === 'date' && el.value) return new Date(el.value + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
            return el.value.trim();
        }

        function bangunRingkasan() {
            ringkasan.innerHTML = '';
            langkah.slice(0, -1).forEach(function (s) {
                const judul = s.querySelector('.hr-card-title')?.textContent.trim();
                const dl = document.createElement('dl');
                dl.className = 'dash-dl mb-4';
                s.querySelectorAll('input:not([type=hidden]), select, textarea').forEach(function (el) {
                    if (el.hidden || el.name.endsWith('_lainnya')) return;
                    const label = s.querySelector('label[for="' + el.id + '"]')?.childNodes[0]?.textContent.trim() || el.name;
                    let isi = nilaiTampil(el);
                    const lain = el.dataset.lainnya && el.value === 'Lainnya' ? document.querySelector(el.dataset.lainnya).value.trim() : '';
                    if (lain) isi = lain;
                    const baris = document.createElement('div');
                    baris.innerHTML = '<dt></dt><dd></dd>';
                    baris.querySelector('dt').textContent = label;
                    baris.querySelector('dd').textContent = isi || 'Belum diisi';
                    if (!isi) baris.querySelector('dd').className = 'text-muted fw-normal';
                    dl.appendChild(baris);
                });
                const h = document.createElement('p');
                h.className = 'hr-section-sub fw-semibold text-body mb-2';
                h.textContent = judul;
                ringkasan.append(h, dl);
            });
        }

        function tampil(i) {
            kini = i;
            langkah.forEach((s, j) => { s.hidden = j !== i; });
            const akhir = i === langkah.length - 1;
            tKembali.hidden = i === 0;
            tBatal.hidden = i !== 0;
            tLanjut.hidden = akhir;
            tSimpan.hidden = !akhir;
            if (akhir) bangunRingkasan();
            window.hrStepper('langkahPengkinian', i);
            document.getElementById('langkahPengkinian').scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        tLanjut.addEventListener('click', function () {
            const salah = Array.from(langkah[kini].querySelectorAll('input, select, textarea')).find((el) => !el.hidden && !el.checkValidity());
            if (salah) { salah.reportValidity(); salah.focus(); return; }
            tampil(kini + 1);
            langkah[kini].querySelector('input:not([type=hidden]), select, textarea')?.focus({ preventScroll: true });
        });
        tKembali.addEventListener('click', () => tampil(kini - 1));
        // Kiriman dengan galat browser di langkah tersembunyi: pindah ke langkah tersebut.
        form.addEventListener('invalid', function (e) {
            const s = e.target.closest('[data-langkah]');
            const j = langkah.indexOf(s);
            if (j >= 0 && j !== kini) tampil(j);
        }, true);
        tampil(kini);
    });
</script>
@include('partials.lokasi-ikut-jabatan')
@endsection
