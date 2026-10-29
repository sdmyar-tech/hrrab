    @extends(Auth::user()->panelLayout())

    @section('content')
        <main class="dashboard-content">
            <div class="container-fluid px-3 px-lg-4 py-4">

                @include('layouts.admin.header')

                <section class="panel">
                    <div class="panel-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 mb-1 section-title">
                                <i class="bi bi-person-plus" aria-hidden="true"></i>
                                <span>Formulir Data Pegawai</span>
                            </h2>
                            <p class="text-muted mb-0">Isi formulir secara bertahap, atau klik langsung tab yang ingin diisi.</p>
                        </div>
                    </div>

                    <div class="panel-body p-0 pt-3 p-md-4">{{-- HP: .panel sudah berpadding --}}
                        <form action="{{ isset($pegawai) ? route('admin.pegawai.update', $pegawai->id) : route('admin.pegawai.store') }}"
                            method="POST"
                            enctype="multipart/form-data"
                            id="wizardPegawaiForm"
                            novalidate>
                            @csrf
                            @if(isset($pegawai))
                                @method('PUT')
                            @endif

                            <ul class="nav nav-pills nav-justified mb-4" id="pegawaiFormTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="pribadi-tab" data-bs-toggle="tab" data-bs-target="#pribadi" type="button" role="tab">
                                        <span class="badge bg-secondary me-1">1</span> Data Pribadi
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="kepegawaian-tab" data-bs-toggle="tab" data-bs-target="#kepegawaian" type="button" role="tab">
                                        <span class="badge bg-secondary me-1">2</span> Kepegawaian
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="kontak-tab" data-bs-toggle="tab" data-bs-target="#kontak" type="button" role="tab">
                                        <span class="badge bg-secondary me-1">3</span> Kontak & BPJS
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="dokumen-tab" data-bs-toggle="tab" data-bs-target="#dokumen" type="button" role="tab">
                                        <span class="badge bg-secondary me-1">4</span> Dokumen
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="pegawaiFormTabContent">

                                <div class="tab-pane fade show active" id="pribadi" role="tabpanel">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="f-nama-lengkap" class="form-label required">Nama Lengkap & Gelar</label>
                                            <input id="f-nama-lengkap" type="text" name="nama_lengkap" class="form-control @error('nama_lengkap') is-invalid @enderror"
                                                value="{{ old('nama_lengkap', $pegawai->nama_lengkap ?? '') }}" required>
                                            <div class="invalid-feedback">@error('nama_lengkap'){{ $message }}@else Nama lengkap wajib diisi. @enderror</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-email" class="form-label required">Email</label>
                                            <input id="f-email" type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                                value="{{ old('email', $pegawai->email ?? '') }}" required>
                                            <div class="invalid-feedback">@error('email'){{ $message }}@else Masukkan alamat email yang valid. @enderror</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="f-nik" class="form-label required">NIK (No. KTP)</label>
                                            <input id="f-nik" type="text" name="nik" maxlength="16" minlength="16" pattern="\d{16}" inputmode="numeric"
                                                class="form-control @error('nik') is-invalid @enderror"
                                                value="{{ old('nik', $pegawai->nik ?? '') }}" required>
                                            <div class="invalid-feedback">@error('nik'){{ $message }}@else NIK harus 16 digit angka. @enderror</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="f-no-kk" class="form-label">No. KK</label>
                                            <input id="f-no-kk" type="text" name="no_kk" maxlength="20" class="form-control @error('no_kk') is-invalid @enderror"
                                                value="{{ old('no_kk', $pegawai->no_kk ?? '') }}" required>
                                            @error('no_kk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label for="f-npwp" class="form-label">No. NPWP</label>
                                            <input id="f-npwp" type="text" name="npwp" maxlength="25" class="form-control @error('npwp') is-invalid @enderror"
                                                value="{{ old('npwp', $pegawai->npwp ?? '') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="f-tempat-lahir" class="form-label required">Tempat Lahir</label>
                                            <input id="f-tempat-lahir" type="text" name="tempat_lahir" class="form-control @error('tempat_lahir') is-invalid @enderror"
                                                value="{{ old('tempat_lahir', $pegawai->tempat_lahir ?? '') }}" required>
                                            <div class="invalid-feedback">Tempat lahir wajib diisi.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="f-tanggal-lahir" class="form-label required">Tanggal Lahir</label>
                                            <input id="f-tanggal-lahir" type="date" name="tanggal_lahir" max="{{ now()->subYears(15)->format('Y-m-d') }}"
                                                class="form-control @error('tanggal_lahir') is-invalid @enderror"
                                                value="{{ old('tanggal_lahir', isset($pegawai->tanggal_lahir) ? $pegawai->tanggal_lahir->format('Y-m-d') : '') }}" required>
                                            <div class="invalid-feedback">Tanggal lahir wajib diisi dan tidak boleh di masa depan.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="f-jenis-kelamin" class="form-label required">Jenis Kelamin</label>
                                            <select id="f-jenis-kelamin" name="jenis_kelamin" class="form-select @error('jenis_kelamin') is-invalid @enderror" required>
                                                <option value="">-- Pilih --</option>
                                                <option value="Laki-laki" {{ old('jenis_kelamin', $pegawai->jenis_kelamin ?? '') == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                                <option value="Perempuan" {{ old('jenis_kelamin', $pegawai->jenis_kelamin ?? '') == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                                            </select>
                                            <div class="invalid-feedback">Silakan pilih jenis kelamin.</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="f-agama" class="form-label required">Agama</label>
                                            <input id="f-agama" type="text" name="agama" class="form-control @error('agama') is-invalid @enderror"
                                                value="{{ old('agama', $pegawai->agama ?? 'Islam') }}" required>
                                            <div class="invalid-feedback">Agama wajib diisi.</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="f-golongan-darah" class="form-label">Golongan Darah</label>
                                            <select id="f-golongan-darah" name="golongan_darah" class="form-select">
                                                @foreach(\App\Models\DataPegawai::GOLONGAN_DARAH as $gol)
                                                    <option value="{{ $gol }}" {{ old('golongan_darah', $pegawai->golongan_darah ?? '') == $gol ? 'selected' : '' }}>{{ $gol }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="f-status-pernikahan" class="form-label">Status Pernikahan</label>
                                            <select id="f-status-pernikahan" name="status_pernikahan" class="form-select">
                                                @foreach(['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'] as $status)
                                                    <option value="{{ $status }}" {{ old('status_pernikahan', $pegawai->status_pernikahan ?? '') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="f-jumlah-tanggungan" class="form-label">Jumlah Tanggungan</label>
                                            <input id="f-jumlah-tanggungan" type="number" name="jumlah_tanggungan" min="0" max="20" class="form-control"
                                                value="{{ old('jumlah_tanggungan', $pegawai->jumlah_tanggungan ?? 0) }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-nama-ibu-kandung" class="form-label required">Nama Ibu Kandung</label>
                                            <input id="f-nama-ibu-kandung" type="text" name="nama_ibu_kandung" class="form-control @error('nama_ibu_kandung') is-invalid @enderror"
                                                value="{{ old('nama_ibu_kandung', $pegawai->nama_ibu_kandung ?? '') }}" required>
                                            <div class="invalid-feedback">Nama ibu kandung wajib diisi.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-pendidikan-terakhir" class="form-label required">Pendidikan Terakhir</label>
                                            <input id="f-pendidikan-terakhir" type="text" name="pendidikan_terakhir" class="form-control @error('pendidikan_terakhir') is-invalid @enderror"
                                                value="{{ old('pendidikan_terakhir', $pegawai->pendidikan_terakhir ?? '') }}" placeholder="contoh: S1 Pendidikan" required>
                                            <div class="invalid-feedback">Pendidikan terakhir wajib diisi.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-alamat-ktp" class="form-label required">Alamat KTP</label>
                                            <textarea id="f-alamat-ktp" name="alamat_ktp" class="form-control @error('alamat_ktp') is-invalid @enderror" rows="3" required>{{ old('alamat_ktp', $pegawai->alamat_ktp ?? '') }}</textarea>
                                            <div class="invalid-feedback">Alamat KTP wajib diisi.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-alamat-domisili" class="form-label">Alamat Domisili</label>
                                            <textarea id="f-alamat-domisili" name="alamat_domisili" class="form-control" rows="3" placeholder="Kosongkan bila sama dengan KTP. Contoh: Jl. Melati No. 5">{{ old('alamat_domisili', $pegawai->alamat_domisili ?? '') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="kepegawaian" role="tabpanel">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="f-nip" class="form-label required">NIP (No. Induk Pegawai)</label>
                                            <input id="f-nip" type="text" name="nip" class="form-control @error('nip') is-invalid @enderror"
                                                value="{{ old('nip', $pegawai->nip ?? '') }}" required>
                                            <div class="invalid-feedback">@error('nip'){{ $message }}@else NIP wajib diisi. @enderror</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-unit-kerja" class="form-label required">Bagian</label>
                                            <select id="f-unit-kerja" name="unit_kerja" class="form-select @error('unit_kerja') is-invalid @enderror" required>
                                                <option value="">-- Pilih Bagian --</option>
                                                @foreach (config('kepegawaian.unit_kerja') as $unit)
                                                    <option value="{{ $unit }}" {{ old('unit_kerja', $pegawai->kepegawaian->unit_kerja ?? '') == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">Silakan pilih bagian.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-jabatan" class="form-label required">Jabatan Utama</label>
                                            <select id="f-jabatan" name="jabatan" class="form-select @error('jabatan') is-invalid @enderror" required data-jabatan-unit="[name=unit_kerja]">
                                                <option value="">-- Pilih Jabatan --</option>
                                                @foreach (\App\Models\Jabatan::daftar() as $j)
                                                    <option value="{{ $j }}" {{ old('jabatan', $pegawai->kepegawaian->jabatan ?? '') == $j ? 'selected' : '' }}>{{ $j }}</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">Jabatan wajib diisi.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-jabatan-tambahan" class="form-label">Jabatan Tambahan <span class="text-muted">(silahkan diisi bagi yang memiliki jabatan lebih dari 1)</span></label>
                                            <select id="f-jabatan-tambahan" name="jabatan_tambahan" class="form-select" data-jabatan-unit="[name=unit_kerja]">
                                                <option value="">-- Tidak Ada --</option>
                                                @foreach (\App\Models\Jabatan::daftar() as $j)
                                                    <option value="{{ $j }}" {{ old('jabatan_tambahan', $pegawai->kepegawaian->jabatan_tambahan ?? '') == $j ? 'selected' : '' }}>{{ $j }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-unit-organisasi" class="form-label">Unit <span class="text-muted fw-normal">(opsional)</span></label>
                                            <select id="f-unit-organisasi" name="unit_organisasi" class="form-select @error('unit_organisasi') is-invalid @enderror">
                                                <option value="">-- Tanpa Unit --</option>
                                                @foreach (\App\Models\UnitOrganisasi::daftar() as $unitOrg)
                                                    <option value="{{ $unitOrg }}" @selected(old('unit_organisasi', $pegawai->kepegawaian->unit_organisasi ?? '') === $unitOrg)>{{ $unitOrg }}</option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">Pengelompokan pegawai; persetujuan tetap mengikuti Bagian. Kelola daftarnya di <a href="{{ route('admin.master-unit.index') }}">Master Unit</a>.</div>
                                            @error('unit_organisasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-lokasi-kerja" class="form-label required">Lokasi Kerja</label>
                                            <select id="f-lokasi-kerja" name="lokasi_kerja" class="form-select @error('lokasi_kerja') is-invalid @enderror" required>
                                                <option value="">-- Pilih Lokasi Kerja --</option>
                                                @foreach (config('kepegawaian.lokasi_kerja') as $lokasi)
                                                    <option value="{{ $lokasi }}" {{ old('lokasi_kerja', $pegawai->kepegawaian->lokasi_kerja ?? '') == $lokasi ? 'selected' : '' }}>{{ $lokasi }}</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">Lokasi kerja wajib diisi.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-tanggal-masuk" class="form-label required">Tanggal Masuk</label>
                                            <input id="f-tanggal-masuk" type="date" name="tanggal_masuk" max="{{ now()->format('Y-m-d') }}"
                                                class="form-control @error('tanggal_masuk') is-invalid @enderror"
                                                value="{{ old('tanggal_masuk', isset($pegawai->kepegawaian->tanggal_masuk) ? $pegawai->kepegawaian->tanggal_masuk->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                                            <div class="invalid-feedback">Tanggal masuk wajib diisi.</div>
                                        </div>

                                        @if(isset($pegawai) && $pegawai->kepegawaian)
                                            <div class="col-md-6">
                                                <label class="form-label">Masa Kerja (otomatis)</label>
                                                <input type="text" class="form-control bg-light" value="{{ $pegawai->kepegawaian->masa_kerja }}" readonly tabindex="-1">
                                                <small class="text-muted">Dihitung otomatis dari tanggal masuk, tidak perlu diisi manual.</small>
                                            </div>
                                        @endif

                                        <div class="col-md-4">
                                            <label for="f-status-rekrutmen" class="form-label required">Status Rekrutmen</label>
                                            <select id="f-status-rekrutmen" name="status_rekrutmen" class="form-select @error('status_rekrutmen') is-invalid @enderror" required>
                                                <option value="Aktif" {{ old('status_rekrutmen', $pegawai->kepegawaian->status_rekrutmen ?? '') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                                <option value="Tidak Aktif" {{ old('status_rekrutmen', $pegawai->kepegawaian->status_rekrutmen ?? '') == 'Tidak Aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                                            </select>
                                            <div class="invalid-feedback">Silakan pilih status rekrutmen.</div>
                                        </div>
                                        <div class="col-md-4" data-tanggal-keluar>
                                            <label for="f-tanggal-keluar" class="form-label">Tanggal Keluar</label>
                                            <input id="f-tanggal-keluar" type="date" name="tanggal_keluar" max="{{ now()->format('Y-m-d') }}"
                                                class="form-control @error('tanggal_keluar') is-invalid @enderror"
                                                value="{{ old('tanggal_keluar', optional($pegawai->kepegawaian->tanggal_keluar ?? null)->format('Y-m-d')) }}">
                                            @error('tanggal_keluar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            <small class="text-muted">Kosongkan = hari ini saat status diubah ke Tidak Aktif.</small>
                                        </div>
                                        <script>
                                            // Tanggal keluar hanya relevan bila status Tidak Aktif.
                                            document.addEventListener('DOMContentLoaded', function () {
                                                const s = document.querySelector('select[name=status_rekrutmen]'), k = document.querySelector('[data-tanggal-keluar]');
                                                const atur = () => { k.hidden = s.value !== 'Tidak Aktif'; };
                                                s.addEventListener('change', atur); atur();
                                            });
                                        </script>
                                        <div class="col-md-4">
                                            <label for="f-status-kerja" class="form-label required">Status Kerja</label>
                                            <select id="f-status-kerja" name="status_kerja" class="form-select @error('status_kerja') is-invalid @enderror" required>
                                                @foreach(['Tetap', 'Kontrak', 'Magang', 'Harian Lepas', 'Probation'] as $sk)
                                                    <option value="{{ $sk }}" {{ old('status_kerja', $pegawai->kepegawaian->status_kerja ?? '') == $sk ? 'selected' : '' }}>{{ $sk }}</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">Silakan pilih status kerja.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="f-tanggal-habis-kontrak" class="form-label">Tanggal Habis Kontrak</label>
                                            <input id="f-tanggal-habis-kontrak" type="date" name="tanggal_habis_kontrak" class="form-control"
                                                value="{{ old('tanggal_habis_kontrak', isset($pegawai->kepegawaian->tanggal_habis_kontrak) ? $pegawai->kepegawaian->tanggal_habis_kontrak->format('Y-m-d') : '') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="kontak" role="tabpanel">
                                    <div class="row g-3">
                                        <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-telephone me-2"></i>Kontak Darurat (Kerabat)</h6>
                                        <div class="col-md-4">
                                            <label for="f-no-hp" class="form-label required">No. HP Pegawai (Aktif/WA)</label>
                                            <input id="f-no-hp" type="text" name="no_hp" class="form-control @error('no_hp') is-invalid @enderror"
                                                value="{{ old('no_hp', $pegawai->no_hp ?? '') }}" required>
                                            <div class="invalid-feedback">No. HP wajib diisi.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="f-nama-kerabat" class="form-label required">Nama Kerabat</label>
                                            <input id="f-nama-kerabat" type="text" name="nama_kerabat" class="form-control @error('nama_kerabat') is-invalid @enderror"
                                                value="{{ old('nama_kerabat', $pegawai->kontakDarurat->nama_kerabat ?? '') }}" required>
                                            <div class="invalid-feedback">Nama kerabat wajib diisi.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="f-no-hp-kerabat" class="form-label required">No. HP Kerabat</label>
                                            <input id="f-no-hp-kerabat" type="text" name="no_hp_kerabat" class="form-control @error('no_hp_kerabat') is-invalid @enderror"
                                                value="{{ old('no_hp_kerabat', $pegawai->kontakDarurat->no_hp_kerabat ?? '') }}" required>
                                            <div class="invalid-feedback">No. HP kerabat wajib diisi.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-hubungan-kerabat" class="form-label required">Hubungan Dengan Kerabat</label>
                                            <input id="f-hubungan-kerabat" type="text" name="hubungan_kerabat" class="form-control @error('hubungan_kerabat') is-invalid @enderror"
                                                value="{{ old('hubungan_kerabat', $pegawai->kontakDarurat->hubungan_kerabat ?? '') }}" placeholder="contoh: Istri / Suami / Ibu Kandung" required>
                                            <div class="invalid-feedback">Hubungan dengan kerabat wajib diisi.</div>
                                        </div>

                                        <hr class="my-3">
                                        <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-card-checklist me-2"></i>Data BPJS</h6>
                                        <div class="col-md-4">
                                            <label for="f-no-bpjs-kesehatan" class="form-label">No. BPJS Kesehatan</label>
                                            <input id="f-no-bpjs-kesehatan" type="text" name="no_bpjs_kesehatan" class="form-control"
                                                value="{{ old('no_bpjs_kesehatan', $pegawai->bpjs->no_bpjs_kesehatan ?? '') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="f-no-bpjs-ketenagakerjaan" class="form-label">No. BPJS Ketenagakerjaan</label>
                                            <input id="f-no-bpjs-ketenagakerjaan" type="text" name="no_bpjs_ketenagakerjaan" class="form-control"
                                                value="{{ old('no_bpjs_ketenagakerjaan', $pegawai->bpjs->no_bpjs_ketenagakerjaan ?? '') }}">
                                        </div>

                                        <div class="col-md-4">
                                            <label for="f-faskes-tingkat-1" class="form-label">Faskes Tingkat 1</label>
                                            <input id="f-faskes-tingkat-1" type="text" name="faskes_tingkat_1" class="form-control @error('faskes_tingkat_1') is-invalid @enderror"
                                                placeholder="Nama RS/klinik, contoh: Klinik Abdurrab" value="{{ old('faskes_tingkat_1', $pegawai->bpjs->faskes_tingkat_1 ?? '') }}">
                                            @error('faskes_tingkat_1')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="dokumen" role="tabpanel">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="f-pas-foto" class="form-label">Pas Foto (JPG/PNG)</label>
                                            <input id="f-pas-foto" type="file" name="pas_foto" class="form-control @error('pas_foto') is-invalid @enderror" accept="image/*">
                                            @error('pas_foto') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @if(isset($pegawai) && optional($pegawai->dokumen)->pas_foto)
                                                <small class="text-muted d-block mt-1"><a href="{{ \App\Support\Berkas::url($pegawai->dokumen->pas_foto) }}" target="_blank" rel="noopener">Lihat file saat ini</a></small>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-upload-ktp" class="form-label">Unggah KTP (PDF/JPG)</label>
                                            <input id="f-upload-ktp" type="file" name="upload_ktp" class="form-control @error('upload_ktp') is-invalid @enderror">
                                            @error('upload_ktp') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @if(isset($pegawai) && optional($pegawai->dokumen)->upload_ktp)
                                                <small class="text-muted d-block mt-1"><a href="{{ \App\Support\Berkas::url($pegawai->dokumen->upload_ktp) }}" target="_blank" rel="noopener">Lihat file saat ini</a></small>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-upload-kk" class="form-label">Unggah KK (PDF/JPG)</label>
                                            <input id="f-upload-kk" type="file" name="upload_kk" class="form-control @error('upload_kk') is-invalid @enderror">
                                            @error('upload_kk') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @if(isset($pegawai) && optional($pegawai->dokumen)->upload_kk)
                                                <small class="text-muted d-block mt-1"><a href="{{ \App\Support\Berkas::url($pegawai->dokumen->upload_kk) }}" target="_blank" rel="noopener">Lihat file saat ini</a></small>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-upload-cv" class="form-label">Unggah CV (PDF)</label>
                                            <input id="f-upload-cv" type="file" name="upload_cv" class="form-control @error('upload_cv') is-invalid @enderror">
                                            @error('upload_cv') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @if(isset($pegawai) && optional($pegawai->dokumen)->upload_cv)
                                                <small class="text-muted d-block mt-1"><a href="{{ \App\Support\Berkas::url($pegawai->dokumen->upload_cv) }}" target="_blank" rel="noopener">Lihat file saat ini</a></small>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-upload-ijazah-terakhir" class="form-label">Unggah Ijazah Terakhir (PDF)</label>
                                            <input id="f-upload-ijazah-terakhir" type="file" name="upload_ijazah_terakhir" class="form-control @error('upload_ijazah_terakhir') is-invalid @enderror">
                                            @error('upload_ijazah_terakhir') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @if(isset($pegawai) && optional($pegawai->dokumen)->upload_ijazah_terakhir)
                                                <small class="text-muted d-block mt-1"><a href="{{ \App\Support\Berkas::url($pegawai->dokumen->upload_ijazah_terakhir) }}" target="_blank" rel="noopener">Lihat file saat ini</a></small>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label for="f-upload-buku-tabungan" class="form-label">Unggah Buku Tabungan (PDF/JPG)</label>
                                            <input id="f-upload-buku-tabungan" type="file" name="upload_buku_tabungan" class="form-control @error('upload_buku_tabungan') is-invalid @enderror">
                                            @error('upload_buku_tabungan') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @if(isset($pegawai) && optional($pegawai->dokumen)->upload_buku_tabungan)
                                                <small class="text-muted d-block mt-1"><a href="{{ \App\Support\Berkas::url($pegawai->dokumen->upload_buku_tabungan) }}" target="_blank" rel="noopener">Lihat file saat ini</a></small>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                                <button type="button" class="btn btn-outline-secondary" id="btnPrev" disabled>
                                    <i class="bi bi-chevron-left me-1"></i> Sebelumnya
                                </button>

                                <div>
                                    <button type="button" class="btn btn-primary" id="btnNext">
                                        Selanjutnya <i class="bi bi-chevron-right ms-1"></i>
                                    </button>

                                    <button type="submit" class="btn btn-success d-none" id="btnSubmit">
                                        <i class="bi bi-check-circle me-1"></i> {{ isset($pegawai) ? 'Simpan Perubahan' : 'Submit Data Pegawai' }}
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>
                </section>
            </div>
        </main>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const form = document.getElementById('wizardPegawaiForm');
                const tabs = Array.from(document.querySelectorAll('#pegawaiFormTab button[data-bs-toggle="tab"]'));
                // Hanya pane form ini (navbar juga punya .tab-pane) agar indeks pane = indeks tab.
                const panes = Array.from(document.querySelectorAll('#pegawaiFormTabContent > .tab-pane'));
                const btnPrev = document.getElementById('btnPrev');
                const btnNext = document.getElementById('btnNext');
                const btnSubmit = document.getElementById('btnSubmit');
                let currentStep = 0;

                function activateStep(stepIndex) {
                    const targetTab = bootstrap.Tab.getOrCreateInstance(tabs[stepIndex]);
                    targetTab.show();

                    btnPrev.disabled = stepIndex === 0;

                    if (stepIndex === tabs.length - 1) {
                        btnNext.classList.add('d-none');
                        btnSubmit.classList.remove('d-none');
                    } else {
                        btnNext.classList.remove('d-none');
                        btnSubmit.classList.add('d-none');
                    }

                    currentStep = stepIndex;
                }

                function validateTab(stepIndex) {
                    const pane = panes[stepIndex];
                    const inputs = pane.querySelectorAll('input[required], select[required], textarea[required]');
                    let isValid = true;

                    inputs.forEach(input => {
                        if (!input.checkValidity()) {
                            input.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            input.classList.remove('is-invalid');
                        }
                    });

                    return isValid;
                }

                function validateTabAndFocus(stepIndex) {
                    const isValid = validateTab(stepIndex);
                    if (!isValid) {
                        const firstInvalid = panes[stepIndex].querySelector('.is-invalid');
                        if (firstInvalid) firstInvalid.focus();
                    }
                    return isValid;
                }

                tabs.forEach((tab, index) => {
                    tab.addEventListener('click', function () {
                        activateStep(index);
                    });
                });

                btnNext.addEventListener('click', function () {
                    if (validateTabAndFocus(currentStep) && currentStep < tabs.length - 1) {
                        activateStep(currentStep + 1);
                    }
                });

                btnPrev.addEventListener('click', function () {
                    if (currentStep > 0) {
                        activateStep(currentStep - 1);
                    }
                });

                form.addEventListener('submit', function (e) {
                    if (form.dataset.confirmed === '1') return;

                    let firstInvalidStep = null;

                    panes.forEach((pane, index) => {
                        const stepValid = validateTab(index);
                        if (!stepValid && firstInvalidStep === null) {
                            firstInvalidStep = index;
                        }
                    });

                    if (firstInvalidStep !== null) {
                        e.preventDefault();
                        activateStep(firstInvalidStep);
                        const firstInvalid = panes[firstInvalidStep].querySelector('.is-invalid');
                        if (firstInvalid) firstInvalid.focus();
                        return;
                    }

                    e.preventDefault();
                    Swal.fire({
                        icon: 'question',
                        title: 'Simpan Data Pegawai?',
                        text: 'Pastikan semua data yang diisi sudah benar.',
                        showCancelButton: true,
                        confirmButtonColor: '#880f12',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.dataset.confirmed = '1';
                            form.submit();
                        }
                    });
                });

                {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
                @section('galat-di-halaman', '1')
                @if ($errors->any())
                    // Tandai juga field yang ditolak server tapi belum diberi kelas is-invalid di Blade, agar tab-nya ikut ditandai & dibuka.
                    Object.entries(@json($errors->toArray())).forEach(([nama, pesan]) => {
                        const el = form.querySelector(`[name="${nama}"], [name="${nama}[]"]`);
                        if (!el || el.classList.contains('is-invalid')) return;
                        el.classList.add('is-invalid');
                        if (!el.parentElement.querySelector('.invalid-feedback')) {
                            const umpan = document.createElement('div');
                            umpan.className = 'invalid-feedback';
                            umpan.textContent = pesan[0];
                            el.after(umpan);
                        }
                    });

                    panes.forEach((pane, index) => {
                        if (pane.querySelector('.is-invalid')) {
                            const numberBadge = tabs[index].querySelector('.badge');
                            if (numberBadge) numberBadge.classList.replace('bg-secondary', 'bg-danger');
                            if (!tabs[index].querySelector('.tab-error-count')) {
                                const errBadge = document.createElement('span');
                                errBadge.className = 'badge bg-danger rounded-pill ms-1 tab-error-count';
                                errBadge.textContent = pane.querySelectorAll('.is-invalid').length + ' error';
                                tabs[index].appendChild(errBadge);
                            }
                        }
                    });

                    const firstError = form.querySelector('.is-invalid');
                    if (firstError) {
                        const errorPane = firstError.closest('.tab-pane');
                        if (errorPane) {
                            const errorIndex = panes.indexOf(errorPane);
                            activateStep(errorIndex);
                        }
                        firstError.focus({ preventScroll: true });
                        firstError.scrollIntoView({ block: 'center' });
                    }
                @endif
            });
        </script>
    @if (auth()->user()->isAdmin())
    <script>
        // Admin boleh menyimpan data belum lengkap: hanya nama, jabatan, dan unit kerja yang tetap wajib (sama dengan aturan server).
        document.addEventListener('DOMContentLoaded', function () {
            const tetap = ['nama_lengkap', 'jabatan', 'unit_kerja'];
            document.querySelectorAll('#wizardPegawaiForm [required]').forEach(function (el) {
                if (tetap.includes(el.name)) return;
                el.removeAttribute('required');
                // Tanda * di label ikut hilang agar sesuai dengan kolom yang benar-benar wajib.
                el.closest('[class*="col-"]')?.querySelector('.form-label.required')?.classList.remove('required');
            });
        });
    </script>
@endif
@include('partials.jabatan-ikut-unit')
@endsection
