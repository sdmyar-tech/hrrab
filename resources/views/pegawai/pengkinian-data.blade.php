@extends('layouts.pegawai.app')

@section('content')
@php
    use App\Http\Controllers\Pegawai\ProfilController as Profil;

    $employee = $user->dataPegawai;
    $kepegawaian = $employee?->kepegawaian;
    $kontakDarurat = $employee?->kontakDarurat;
    $bpjs = $employee?->bpjs;
    $dokumen = $employee?->dokumen;
    $kosong = fn ($value) => is_null($value) || $value === '' || $value === [];

    $jabatanOptions = [...\App\Models\Jabatan::daftar(), 'Lainnya'];
    $infoFile = 'PDF/JPG/PNG, maks. 2 MB.';

    $bagian = [
        'pribadi' => ['judul' => 'Data Pribadi', 'ikon' => 'bi-person', 'fields' => [
            ['name' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'text', 'value' => $employee?->nama_lengkap, 'required' => true, 'help' => 'Tulis nama lengkap beserta gelar.'],
            ['name' => 'nik', 'label' => 'NIK (No. KTP)', 'type' => 'text', 'value' => $employee?->nik, 'required' => true, 'attr' => 'inputmode=numeric maxlength=16 pattern=[0-9]{16}', 'help' => '16 digit angka.'],
            ['name' => 'no_kk', 'label' => 'No. KK', 'type' => 'text', 'value' => $employee?->no_kk, 'required' => true, 'attr' => 'inputmode=numeric maxlength=20'],
            ['name' => 'npwp', 'label' => 'No. NPWP', 'type' => 'text', 'value' => $employee?->npwp, 'required' => false, 'help' => 'Hanya bagi yang sudah memiliki NPWP.'],
            ['name' => 'no_hp', 'label' => 'No. HP (aktif)', 'type' => 'tel', 'value' => $employee?->no_hp, 'required' => true, 'attr' => 'inputmode=tel maxlength=20'],
            ['name' => 'tempat_lahir', 'label' => 'Tempat Lahir', 'type' => 'text', 'value' => $employee?->tempat_lahir, 'required' => true],
            ['name' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'type' => 'date', 'value' => optional($employee?->tanggal_lahir)->format('Y-m-d'), 'required' => true],
            ['name' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'select', 'value' => $employee?->jenis_kelamin, 'options' => ['Laki-laki', 'Perempuan'], 'required' => true],
            ['name' => 'agama', 'label' => 'Agama', 'type' => 'select', 'value' => $employee?->agama, 'options' => Profil::AGAMA, 'required' => true],
            ['name' => 'golongan_darah', 'label' => 'Golongan Darah', 'type' => 'select', 'value' => $employee?->golongan_darah, 'options' => Profil::GOLONGAN_DARAH, 'required' => false, 'help' => 'Pilih "Belum Diketahui" bila belum pernah cek.'],
            ['name' => 'status_pernikahan', 'label' => 'Status Pernikahan', 'type' => 'select', 'value' => $employee?->status_pernikahan, 'options' => Profil::STATUS_PERNIKAHAN, 'required' => true],
            ['name' => 'jumlah_tanggungan', 'label' => 'Jumlah Tanggungan', 'type' => 'number', 'value' => $employee?->jumlah_tanggungan, 'required' => true, 'attr' => 'min=0 max=20', 'help' => 'Jumlah orang yang Anda nafkahi.'],
            ['name' => 'nama_ibu_kandung', 'label' => 'Nama Ibu Kandung', 'type' => 'text', 'value' => $employee?->nama_ibu_kandung, 'required' => true],
            ['name' => 'pendidikan_terakhir', 'label' => 'Pendidikan Terakhir', 'type' => 'text', 'value' => $employee?->pendidikan_terakhir, 'required' => true, 'placeholder' => 'mis. S1 Manajemen'],
            ['name' => 'alamat_ktp', 'label' => 'Alamat KTP', 'type' => 'textarea', 'value' => $employee?->alamat_ktp, 'required' => true, 'lebar' => 12],
            ['name' => 'alamat_domisili', 'label' => 'Alamat Domisili', 'type' => 'textarea', 'value' => $employee?->alamat_domisili, 'required' => false, 'lebar' => 12, 'help' => 'Kosongkan bila sama dengan alamat KTP.'],
        ]],
        'kepegawaian' => ['judul' => 'Kepegawaian', 'ikon' => 'bi-briefcase', 'fields' => [
            ['name' => 'jabatan', 'label' => 'Jabatan', 'type' => 'select', 'value' => $kepegawaian?->jabatan, 'options' => $jabatanOptions, 'required' => true, 'lainnya' => true, 'help' => 'Jabatan utama saat ini. Pilih "Lainnya" bila tidak ada di daftar.'],
            ['name' => 'unit_kerja', 'label' => 'Bagian', 'type' => 'select', 'value' => $kepegawaian?->unit_kerja, 'options' => config('kepegawaian.unit_kerja'), 'required' => true],
            ['name' => 'lokasi_kerja', 'label' => 'Lokasi Kerja', 'type' => 'select', 'value' => $kepegawaian?->lokasi_kerja, 'options' => config('kepegawaian.lokasi_kerja'), 'required' => true],
            ['name' => 'tanggal_masuk', 'label' => 'Tanggal Masuk', 'type' => 'date', 'value' => optional($kepegawaian?->tanggal_masuk)->format('Y-m-d'), 'required' => true, 'help' => 'Tanggal mulai aktif bekerja. Tanyakan pimpinan bila tidak tahu.'],
        ]],
        'kontak' => ['judul' => 'Kontak Darurat', 'ikon' => 'bi-telephone', 'fields' => [
            ['name' => 'nama_kerabat', 'label' => 'Nama Kerabat', 'type' => 'text', 'value' => $kontakDarurat?->nama_kerabat, 'required' => true, 'help' => 'Kerabat terdekat sesuai domisili.'],
            ['name' => 'no_hp_kerabat', 'label' => 'No. HP Kerabat', 'type' => 'tel', 'value' => $kontakDarurat?->no_hp_kerabat, 'required' => true, 'attr' => 'inputmode=tel maxlength=20'],
            ['name' => 'hubungan_kerabat', 'label' => 'Hubungan dengan Kerabat', 'type' => 'select', 'value' => $kontakDarurat?->hubungan_kerabat, 'options' => Profil::HUBUNGAN_KERABAT, 'required' => true, 'lainnya' => true],
        ]],
        'bpjs' => ['judul' => 'BPJS', 'ikon' => 'bi-heart-pulse', 'fields' => [
            ['name' => 'no_bpjs_kesehatan', 'label' => 'No. BPJS Kesehatan', 'type' => 'text', 'value' => $bpjs?->no_bpjs_kesehatan, 'required' => false],
            ['name' => 'no_bpjs_ketenagakerjaan', 'label' => 'No. BPJS Ketenagakerjaan', 'type' => 'text', 'value' => $bpjs?->no_bpjs_ketenagakerjaan, 'required' => false],
            ['name' => 'faskes_tingkat_1', 'label' => 'Faskes Tingkat 1', 'type' => 'text', 'value' => $bpjs?->faskes_tingkat_1, 'required' => false, 'lebar' => 12],
        ]],
        'dokumen' => ['judul' => 'Dokumen', 'ikon' => 'bi-folder2-open', 'fields' => [
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
@endphp

<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <div class="eyebrow">Pegawai</div>
                    <h1 class="mb-0">Pengkinian Data</h1>
                </div>
            </div>
        </div>

        @if ($bagian === [])
            <div class="alert alert-success mb-0">Data pegawai Anda sudah lengkap. Tidak ada form yang perlu diisi lagi.</div>
        @else
            <div class="panel p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <span class="fw-semibold">Kelengkapan data wajib {{ $persen }}%</span>
                        <span class="text-muted small ms-1">&middot; {{ $wajibKosong }} isian wajib (<span class="text-danger">*</span>) & {{ $jumlahKosong - $wajibKosong }} opsional masih kosong</span>
                    </div>
                    <span class="small text-muted">NIP: <strong>{{ $employee?->nip ?: ($user->nip ?: 'belum ada — hubungi Admin SDM') }}</strong></span>
                </div>
                <div class="progress" style="height:8px;" role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar {{ $persen >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $persen }}%"></div>
                </div>
                <p class="small text-muted mb-0 mt-2">Hanya isian yang masih kosong yang ditampilkan. Data yang sudah terisi diubah lewat Admin SDM.</p>
            </div>

            {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
            @section('galat-di-halaman', '1')
            @if ($errors->any())
                <div class="alert alert-danger small">
                    <strong>Periksa kembali isian berikut:</strong>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('pegawai.pengkinian-data.store') }}" method="POST" enctype="multipart/form-data" data-confirm="Simpan pengkinian data ini?">
                @csrf

                @foreach ($bagian as $b)
                    <section class="panel p-4 mb-3">
                        <h2 class="h6 fw-bold mb-3"><i class="bi {{ $b['ikon'] }} me-1"></i>{{ $b['judul'] }}</h2>
                        <div class="row g-3">
                            @foreach ($b['fields'] as $field)
                                @php
                                    $nama = $field['name'];
                                    $nilai = old($nama, $field['value']);
                                    $invalid = $errors->has($nama) ? 'is-invalid' : '';
                                    $pakaiLainnya = ($field['lainnya'] ?? false) && filled($nilai) && ! in_array($nilai, $field['options'], true);
                                @endphp
                                <div class="col-md-{{ $field['lebar'] ?? 6 }}">
                                    <label class="form-label" for="f-{{ $nama }}">
                                        {{ $field['label'] }}@if ($field['required']) <span class="text-danger">*</span>@endif
                                    </label>

                                    @switch($field['type'])
                                        @case('textarea')
                                            <textarea name="{{ $nama }}" id="f-{{ $nama }}" rows="2" class="form-control {{ $invalid }}" @required($field['required'])>{{ $nilai }}</textarea>
                                            @break

                                        @case('select')
                                            <select name="{{ $nama }}" id="f-{{ $nama }}" class="form-select {{ $invalid }}" @required($field['required'])
                                                @if ($field['lainnya'] ?? false) data-lainnya="#f-{{ $nama }}-lainnya" @endif>
                                                <option value="">-- Pilih --</option>
                                                @foreach ($field['options'] as $option)
                                                    <option value="{{ $option }}" @selected($pakaiLainnya ? $option === 'Lainnya' : $nilai === $option)>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                            @if ($field['lainnya'] ?? false)
                                                <input type="text" name="{{ $nama }}_lainnya" id="f-{{ $nama }}-lainnya" class="form-control mt-2"
                                                    value="{{ old($nama . '_lainnya', $pakaiLainnya ? $nilai : '') }}" maxlength="100"
                                                    placeholder="{{ $nama === 'jabatan' ? 'Jabatan lainnya' : 'Hubungan lainnya' }}" hidden>
                                            @endif
                                            @break

                                        @case('file')
                                            <input type="file" name="{{ $nama }}" id="f-{{ $nama }}" class="form-control {{ $invalid }}" accept="{{ $field['accept'] }}" @required($field['required'])>
                                            @break

                                        @default
                                            <input type="{{ $field['type'] }}" name="{{ $nama }}" id="f-{{ $nama }}" class="form-control {{ $invalid }}"
                                                value="{{ $nilai }}" placeholder="{{ $field['placeholder'] ?? '' }}" {!! $field['attr'] ?? '' !!} @required($field['required'])>
                                    @endswitch

                                    @error($nama)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @else
                                        @if (! empty($field['help']))
                                            <div class="form-text">{{ $field['help'] }}</div>
                                        @endif
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="{{ route('pegawai.dashboard') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Data</button>
                </div>
            </form>
        @endif
    </div>
</div>

<script>
    document.querySelectorAll('select[data-lainnya]').forEach(function (select) {
        const input = document.querySelector(select.dataset.lainnya);
        function sinkron() {
            const lainnya = select.value === 'Lainnya';
            input.hidden = ! lainnya;
            input.required = lainnya;
        }
        select.addEventListener('change', sinkron);
        sinkron();
    });
</script>
@endsection
