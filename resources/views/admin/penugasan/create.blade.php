@extends('layouts.admin.app')

@section('content')
@php
    $mutasi = $jenis === 'mutasi';
    $hariIni = now()->format('Y-m-d');
    // Dari halaman Mutasi & Rangkap: pegawai dipilih lewat dropdown di form ini; tujuan setelah simpan/batal tetap di modul ini.
    $urlJenis = fn (string $j) => $dariDaftar
        ? route('admin.penugasan.tambah', array_filter(['pegawai' => $pegawai?->id, 'jenis' => $j]))
        : route('admin.penugasan.create', ['pegawai' => $pegawai, 'jenis' => $j]);
    $urlKembali = $dariDaftar ? route('admin.penugasan.index') : route('admin.pegawai.show', ['pegawai' => $pegawai, 'tab' => 'penugasan']);
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi {{ $mutasi ? 'bi-arrow-left-right' : 'bi-diagram-3' }}"></i></span>
                <div>
                    <p class="eyebrow mb-1">Mutasi &amp; Rangkap Tugas</p>
                    <h1 class="h3 mb-1">{{ $mutasi ? 'Mutasi Pegawai' : 'Tambah Rangkap Tugas' }}</h1>
                    <p class="text-muted mb-0">{{ $pegawai ? $pegawai->nama_lengkap . ' · NIP ' . ($pegawai->nip ?? '-') : 'Pilih pegawai, lalu isi data penugasannya.' }}</p>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <section class="panel p-4">
                    {{-- Dua pilihan jenis, sama lebar. --}}
                    <div class="pn-jenis mb-3" role="group" aria-label="Jenis penugasan">
                        <a href="{{ $urlJenis('mutasi') }}" class="btn {{ $mutasi ? 'btn-primary' : 'btn-outline-primary' }}" @if ($mutasi) aria-current="page" @endif>
                            <span><i class="bi bi-arrow-left-right me-1"></i>Mutasi</span><small>Pindah bagian utama</small>
                        </a>
                        <a href="{{ $urlJenis('rangkap') }}" class="btn {{ $mutasi ? 'btn-outline-primary' : 'btn-primary' }}" @unless ($mutasi) aria-current="page" @endunless>
                            <span><i class="bi bi-diagram-3 me-1"></i>Rangkap Tugas</span><small>Tugas tambahan di bagian lain</small>
                        </a>
                    </div>

                    @if ($dariDaftar)
                        <div class="mb-3">
                            <label class="form-label" for="pn-pegawai">Pegawai</label>
                            @include('partials.pilih-pegawai', ['name' => 'pegawai', 'id' => 'pn-pegawai', 'terpilih' => $pegawai?->id, 'wajib' => true])
                        </div>
                    @endif

                    @if (! $pegawai)
                        <div class="alert alert-light border small mb-0"><i class="bi bi-person-check me-1"></i>Pilih pegawai di atas untuk mengisi data {{ $mutasi ? 'mutasi' : 'rangkap tugas' }}.</div>
                    @else
                    <form action="{{ route('admin.penugasan.store', $pegawai) }}" method="POST"
                        data-confirm="{{ $mutasi ? 'Simpan mutasi pegawai ini?' : 'Tambahkan rangkap tugas ini?' }}">
                        @csrf
                        <input type="hidden" name="jenis" value="{{ $jenis }}">
                        @if ($dariDaftar)<input type="hidden" name="asal" value="daftar">@endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="f-unit-kerja" class="form-label">{{ $mutasi ? 'Bagian Tujuan' : 'Bagian Rangkap' }}</label>
                                <select id="f-unit-kerja" name="unit_kerja" class="form-select @error('unit_kerja') is-invalid @enderror" required>
                                    <option value="">-- Pilih Bagian --</option>
                                    @foreach (config('kepegawaian.unit_kerja') as $unit)
                                        <option value="{{ $unit }}" @selected(old('unit_kerja') === $unit)>{{ $unit }}</option>
                                    @endforeach
                                </select>
                                @error('unit_kerja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="f-peran" class="form-label">Peran di Bagian</label>
                                <select id="f-peran" name="peran" class="form-select @error('peran') is-invalid @enderror" required>
                                    <option value="anggota" @selected(old('peran', 'anggota') === 'anggota')>Anggota / Staff</option>
                                    <option value="kepala" @selected(old('peran') === 'kepala')>Kepala Bagian</option>
                                </select>
                                <div class="form-text">Kepala bagian meng-approve cuti/lembur bagian itu & jadi interviewer tahap 2.</div>
                                @error('peran')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label for="f-jabatan" class="form-label">Jabatan {{ $mutasi ? 'Baru' : 'di Bagian Rangkap' }} @unless ($mutasi)<span class="text-muted">(opsional)</span>@endunless</label>
                                <input id="f-jabatan" type="text" name="jabatan" list="daftarJabatan" data-jabatan-unit="[name=unit_kerja]" class="form-control @error('jabatan') is-invalid @enderror"
                                    value="{{ old('jabatan', $mutasi ? $utama?->jabatan : '') }}" {{ $mutasi ? 'required' : '' }} autocomplete="off">
                                <datalist id="daftarJabatan">
                                    @foreach (\App\Models\Jabatan::daftar() as $j)
                                        <option value="{{ $j }}">
                                    @endforeach
                                </datalist>
                                @error('jabatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if ($mutasi)
                                <div class="col-md-6">
                                    <label for="f-tanggal-efektif" class="form-label">Tanggal Efektif</label>
                                    <input id="f-tanggal-efektif" type="date" name="tanggal_efektif" class="form-control @error('tanggal_efektif') is-invalid @enderror"
                                        value="{{ old('tanggal_efektif', $hariIni) }}" required>
                                    <div class="form-text">Tanggal ke depan = mutasi terjadwal, berlaku otomatis pada tanggal itu.</div>
                                    @error('tanggal_efektif')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @else
                                <div class="col-md-6">
                                    <label for="f-tanggal-mulai" class="form-label">Tanggal Mulai</label>
                                    <input id="f-tanggal-mulai" type="date" name="tanggal_mulai" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                        value="{{ old('tanggal_mulai', $hariIni) }}" required>
                                    @error('tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="f-tanggal-selesai" class="form-label">Tanggal Selesai <span class="text-muted">(opsional)</span></label>
                                    <input id="f-tanggal-selesai" type="date" name="tanggal_selesai" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                                        value="{{ old('tanggal_selesai') }}">
                                    <div class="form-text">Kosongkan bila belum ditentukan; bisa diakhiri kapan saja.</div>
                                    @error('tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif

                            <div class="col-md-6">
                                <label for="f-no-sk" class="form-label">No. SK <span class="text-muted">(opsional)</span></label>
                                <input id="f-no-sk" type="text" name="no_sk" class="form-control @error('no_sk') is-invalid @enderror" value="{{ old('no_sk') }}" maxlength="100">
                                @error('no_sk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label for="f-keterangan" class="form-label">Keterangan <span class="text-muted">(opsional)</span></label>
                                <textarea id="f-keterangan" name="keterangan" rows="2" class="form-control @error('keterangan') is-invalid @enderror">{{ old('keterangan') }}</textarea>
                                @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if ($mutasi)
                                <div class="col-12">
                                    <label class="form-label">Pengajuan cuti/lembur yang masih menunggu
                                        <span class="badge text-bg-{{ $jumlahPending ? 'warning' : 'secondary' }}">{{ $jumlahPending }}</span></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pengajuan_pending" id="pendingLanjut" value="lanjut" @checked(old('pengajuan_pending', 'lanjut') === 'lanjut')>
                                        <label class="form-check-label" for="pendingLanjut">Tetap diproses kepala bagian lama <span class="text-muted">(disarankan — terjadi saat masih di bagian lama)</span></label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pengajuan_pending" id="pendingTolak" value="tolak" @checked(old('pengajuan_pending') === 'tolak')>
                                        <label class="form-check-label" for="pendingTolak">Tolak otomatis, pegawai mengajukan ulang di bagian baru</label>
                                    </div>
                                    @error('pengajuan_pending')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> {{ $mutasi ? 'Simpan Mutasi' : 'Simpan Rangkap' }}
                            </button>
                            <a href="{{ $urlKembali }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                    @endif
                </section>
            </div>

            <div class="col-12 col-lg-5">
                <section class="panel p-4">
                    <h2 class="h6 fw-bold mb-3">Penugasan Saat Ini</h2>
                    @forelse ($pegawai?->penugasanAktif ?? [] as $p)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">{{ $p->unit_kerja }}</span>
                                <span>
                                    <span class="badge {{ $p->is_utama ? 'text-bg-primary' : 'text-bg-info' }}">{{ $p->is_utama ? 'Utama' : 'Rangkap' }}</span>
                                    @if ($p->isKepala())<span class="badge text-bg-dark">Kepala</span>@endif
                                </span>
                            </div>
                            <small class="text-muted">{{ $p->jabatan ?? '-' }} &middot; sejak {{ $p->tanggal_mulai->format('d-m-Y') }}</small>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">{{ $pegawai ? 'Belum ada penugasan tercatat.' : 'Pilih pegawai untuk melihat penugasannya.' }}</p>
                    @endforelse

                    <hr>
                    <ul class="small text-muted mb-0 ps-3">
                        @if ($mutasi)
                            <li>Penugasan utama lama ditutup sehari sebelum tanggal efektif; rangkap tugas tetap berjalan.</li>
                            <li>Pengajuan yang sudah dibuat tetap tercatat di bagian lama (laporan tidak berubah).</li>
                            <li>Bila pegawai ini kepala di bagian lama, sistem mengingatkan bagian yang jadi tanpa kepala.</li>
                        @else
                            <li>Cuti tetap di-approve kepala bagian utama; kepala bagian rangkap ikut melihat.</li>
                            <li>Lembur: pegawai memilih bagian tempat lembur, di-approve kepala bagian itu.</li>
                            <li>Peran Kepala: akun Staff otomatis dinaikkan menjadi Kepala Bagian.</li>
                        @endif
                    </ul>
                </section>
            </div>
        </div>
    </div>
</main>

<style>
    .pn-jenis { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    .pn-jenis .btn { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .1rem; padding: .6rem .5rem; font-weight: 600; }
    .pn-jenis .btn small { font-weight: 400; font-size: .75rem; opacity: .85; }
</style>
@if ($dariDaftar)
    <script>
        // Ganti pegawai = muat ulang form untuk pegawai itu (penugasan saat ini & jabatan bawaan ikut berubah).
        document.getElementById('pn-pegawai').addEventListener('change', function () {
            const url = new URL(@json(route('admin.penugasan.tambah')), location.origin);
            if (this.value) url.searchParams.set('pegawai', this.value);
            url.searchParams.set('jenis', @json($jenis));
            location.assign(url);
        });
    </script>
@endif
@include('partials.jabatan-ikut-unit')
@endsection
