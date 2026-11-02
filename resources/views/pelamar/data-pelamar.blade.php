@extends('layouts.pelamar.app')

@section('title', 'Data Pelamar')

@section('content')
@php
    $p = $pelamar;
    // Kelengkapan profil (panduan untuk pelamar; tidak mengubah aturan validasi server).
    $cek = [
        'biodata' => ['Nama lengkap' => $p?->nama_lengkap, 'NIK' => $p?->nik, 'No. HP' => $p?->no_hp, 'No. KK' => $p?->no_kk,
            'Tempat lahir' => $p?->tempat_lahir, 'Tanggal lahir' => $p?->tanggal_lahir, 'Jenis kelamin' => $p?->jenis_kelamin,
            'Agama' => $p?->agama, 'Status pernikahan' => $p?->status_pernikahan, 'Nama ibu kandung' => $p?->nama_ibu_kandung,
            'Pendidikan terakhir' => $p?->pendidikan_terakhir],
        'alamat' => ['Alamat KTP' => $p?->alamat_ktp],
        'dokumen' => ['Pas foto' => $p?->foto_path, 'CV' => $p?->cv_path, 'Ijazah' => $p?->ijazah_path],
        'riwayat_pendidikan' => ['Riwayat pendidikan' => $p?->riwayatPendidikan?->isNotEmpty() ? 'ada' : null],
    ];
    $semua = collect($cek)->flatMap(fn ($x) => $x);
    $terisi = $semua->filter(fn ($v) => filled($v))->count();
    $persen = (int) round($terisi / max(1, $semua->count()) * 100);
    $kurang = $semua->filter(fn ($v) => blank($v))->keys();
    $bagianLengkap = fn (string $k) => isset($cek[$k]) ? collect($cek[$k])->every(fn ($v) => filled($v)) : null;

    $navBagian = [
        ['biodata', 'Biodata', 'bi-person'],
        ['alamat', 'Alamat', 'bi-house'],
        ['dokumen', 'Dokumen', 'bi-folder2'],
        ['riwayat_pendidikan', 'Pendidikan', 'bi-mortarboard'],
        ['riwayat_pekerjaan', 'Pekerjaan', 'bi-briefcase'],
        ['sosial_media', 'Sosial Media', 'bi-link-45deg'],
    ];
    $opsiNikah = array_combine($x = ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'], $x);
    $opsiDarah = array_combine($x = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-', 'Rh-Null', 'Belum Diketahui'], $x);
    $dokumen = [
        ['foto', 'f-foto', 'Pas Foto', 'foto_path', 'image/*', 'JPG/PNG, maks. 2 MB.'],
        ['cv', 'f-cv', 'CV / Resume', 'cv_path', '.pdf,.doc,.docx', 'PDF/DOC, maks. 4 MB.'],
        ['ijazah', 'f-ijazah', 'Ijazah', 'ijazah_path', '.pdf,.jpg,.jpeg,.png', 'PDF/JPG/PNG, maks. 4 MB.'],
        ['sertifikat', 'f-sertifikat', 'Sertifikat', 'sertifikat_path', '.pdf,.jpg,.jpeg,.png', 'PDF/JPG/PNG, maks. 4 MB.'],
    ];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Data Pelamar" eyebrow="Pelamar"
            subtitle="Data ini dipakai untuk verifikasi lamaran dan, bila Anda diterima, menjadi dasar Data Pegawai.">
            @if ($pelamar)
                <x-slot:actions>
                    @include('partials.tombol-ekspor', ['eksporRoute' => 'pelamar.data-pelamar.export', 'eksporTeks' => 'Ekspor Data Saya'])
                </x-slot:actions>
            @endif
        </x-page-header>

        <div class="row g-4 align-items-start">
            {{-- ============ Kiri: kelengkapan + navigasi bagian ============ --}}
            <aside class="col-lg-3 pelamar-samping">
                <x-card class="mb-3">
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <span class="hr-stat-label">Kelengkapan profil</span>
                        <strong class="pelamar-persen">{{ $persen }}%</strong>
                    </div>
                    <div class="progress" style="height: 6px;" role="progressbar" aria-label="Kelengkapan profil" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar {{ $persen >= 100 ? 'bg-success' : '' }}" style="width: {{ $persen }}%"></div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        @if ($kurang->isEmpty())
                            <i class="bi bi-check-circle-fill text-success me-1" aria-hidden="true"></i>Profil lengkap. Siap melamar.
                        @else
                            Belum diisi: {{ $kurang->take(3)->implode(', ') }}{{ $kurang->count() > 3 ? ', +' . ($kurang->count() - 3) . ' lainnya' : '' }}.
                        @endif
                    </p>
                </x-card>
                <x-section-nav label="Bagian data pelamar" :items="collect($navBagian)->map(fn ($b) => ['id' => 'bagian-' . $b[0], 'label' => $b[1], 'icon' => $b[2], 'status' => $bagianLengkap($b[0])])->all()" />
            </aside>

            {{-- ============ Kanan: form per bagian ============ --}}
            <div class="col-lg-9">
                <form action="{{ route('pelamar.data-pelamar.update') }}" method="POST" enctype="multipart/form-data" data-confirm="Simpan data pelamar ini?" class="d-grid gap-3">
                    @csrf

                    <x-card id="bagian-biodata" title="Biodata" subtitle="Sesuai KTP & Kartu Keluarga." icon="bi-person" class="pelamar-bagian">
                        <div class="row gx-3">
                            <div class="col-md-6"><x-input name="nama_lengkap" id="f-nama-lengkap" label="Nama Lengkap" :value="$p?->nama_lengkap" placeholder="Sesuai KTP, contoh: Siti Aminah" maxlength="150" required /></div>
                            <div class="col-md-6"><x-input name="nik" id="f-nik" label="NIK (No. KTP)" :value="$p?->nik" placeholder="16 digit, contoh: 3201010101010001" inputmode="numeric" maxlength="20" required /></div>
                            <div class="col-md-6"><x-input name="no_hp" id="f-no-hp" type="tel" label="No. HP" :value="$p?->no_hp" icon="bi-phone" placeholder="Contoh: 081234567890" maxlength="20" required /></div>
                            <div class="col-md-6"><x-input name="no_kk" id="f-no-kk" label="No. KK" :value="$p?->no_kk" placeholder="16 digit, contoh: 3201010101010002" inputmode="numeric" maxlength="20" required /></div>
                            <div class="col-md-6"><x-input name="tempat_lahir" id="f-tempat-lahir" label="Tempat Lahir" :value="$p?->tempat_lahir" placeholder="Contoh: Pekanbaru" maxlength="150" required /></div>
                            <div class="col-md-6"><x-input name="tanggal_lahir" id="f-tanggal-lahir" type="date" label="Tanggal Lahir" :value="optional($p?->tanggal_lahir)->format('Y-m-d')" required /></div>
                            <div class="col-md-6"><x-select name="jenis_kelamin" id="f-jenis-kelamin" label="Jenis Kelamin" :options="['Laki-laki' => 'Laki-laki', 'Perempuan' => 'Perempuan']" :selected="$p?->jenis_kelamin" placeholder="Pilih jenis kelamin" required /></div>
                            <div class="col-md-6"><x-input name="agama" id="f-agama" label="Agama" :value="$p?->agama" placeholder="Contoh: Islam" maxlength="50" required /></div>
                            <div class="col-md-6"><x-select name="status_pernikahan" id="f-status-pernikahan" label="Status Pernikahan" :options="$opsiNikah" :selected="$p?->status_pernikahan ?? 'Belum Menikah'" required /></div>
                            <div class="col-md-6"><x-input name="jumlah_tanggungan" id="f-jumlah-tanggungan" type="number" label="Jumlah Tanggungan" :value="$p?->jumlah_tanggungan ?? 0" min="0" max="20" help="Anak/anggota keluarga yang ditanggung." /></div>
                            <div class="col-md-6"><x-input name="nama_ibu_kandung" id="f-nama-ibu-kandung" label="Nama Ibu Kandung" :value="$p?->nama_ibu_kandung" placeholder="Sesuai Kartu Keluarga" maxlength="150" required /></div>
                            <div class="col-md-6"><x-input name="pendidikan_terakhir" id="f-pendidikan-terakhir" label="Pendidikan Terakhir" :value="$p?->pendidikan_terakhir" placeholder="Contoh: S1 Manajemen" maxlength="100" required /></div>
                            <div class="col-md-6"><x-select name="golongan_darah" id="f-golongan-darah" label="Golongan Darah" optional :options="$opsiDarah" :selected="$p?->golongan_darah" placeholder="Pilih golongan darah" /></div>
                            <div class="col-md-6"><x-input name="npwp" id="f-npwp" label="NPWP" optional :value="$p?->npwp" placeholder="Contoh: 12.345.678.9-012.000" maxlength="25" /></div>
                        </div>
                    </x-card>

                    <x-card id="bagian-alamat" title="Alamat" subtitle="Alamat domisili diisi hanya bila berbeda dari KTP." icon="bi-house" class="pelamar-bagian">
                        <x-textarea name="alamat_ktp" id="f-alamat-ktp" label="Alamat KTP" :value="$p?->alamat_ktp" rows="2" placeholder="Jalan, RT/RW, kelurahan, kecamatan, kota" maxlength="1000" required />
                        <x-textarea name="alamat_domisili" id="f-alamat-domisili" label="Alamat Domisili" optional :value="$p?->alamat_domisili" rows="2" placeholder="Kosongkan bila sama dengan alamat KTP" maxlength="1000" field-class="mb-0" />
                    </x-card>

                    <x-card id="bagian-dokumen" title="Dokumen" subtitle="Unggah ulang hanya bila ingin mengganti berkas yang sudah ada." icon="bi-folder2" class="pelamar-bagian">
                        <div class="row g-3">
                            @foreach ($dokumen as [$nama, $id, $label, $kolom, $terima, $bantuan])
                                @php $ada = $p?->{$kolom}; @endphp
                                <div class="col-md-6">
                                    <div class="pelamar-berkas {{ $ada ? 'is-ada' : '' }}">
                                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
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
                                            <div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                        @else
                                            <div class="form-text" id="{{ $id }}-bantuan">{{ $bantuan }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-card>

                    @include('partials.pelamar-riwayat', ['pelamar' => $pelamar])

                    <div class="hr-bilah-aksi">
                        <p class="small text-muted mb-0 d-none d-sm-block"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Kolom bertanda <span class="text-danger">*</span> wajib diisi.</p>
                        <x-button type="submit" icon="bi-check-lg">Simpan Data Pelamar</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection
