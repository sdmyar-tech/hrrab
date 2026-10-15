@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-plus"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">Ajukan Formasi</h1>
                    <p class="text-muted mb-0">Permintaan kekurangan pegawai untuk divisi Anda.</p>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start">
        <div class="col-lg-7 col-xl-8">
        <section class="panel p-4">
            <form action="{{ route('admin.pengajuan-formasi.store') }}" method="POST" id="formFormasi" data-confirm="Kirim pengajuan formasi ini?">
                @csrf
                @php
                    $unitAwal = old('unit_kerja', count($unitOptions) === 1 ? $unitOptions[0] : auth()->user()->unitDipimpin());
                    $jenis = old('jenis_kebutuhan');
                @endphp

                {{-- ===== Kebutuhan karyawan ===== --}}
                <h2 class="form-bagian"><i class="bi bi-clipboard-plus"></i>Kebutuhan Karyawan</h2>

                @if (count($unitOptions) > 1)
                    <div class="mb-3">
                        <label class="form-label" for="f-unit">Unit</label>
                        <select name="unit_kerja" id="f-unit" class="form-select @error('unit_kerja') is-invalid @enderror" required>
                            <option value="">-- Pilih unit --</option>
                            @foreach ($unitOptions as $u)
                                <option value="{{ $u }}" @selected($unitAwal === $u)>{{ $u }}</option>
                            @endforeach
                        </select>
                        @error('unit_kerja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @else
                    <p class="small text-muted mb-3"><i class="bi bi-diagram-3 me-1"></i>Unit: <strong>{{ $unitOptions[0] ?? '-' }}</strong></p>
                    <input type="hidden" id="f-unit" value="{{ $unitOptions[0] ?? '' }}">
                @endif

                <label class="form-label">Kebutuhan karyawan karena</label>
                <div class="row g-2 mb-1">
                    @foreach (\App\Models\PengajuanFormasi::JENIS_LABEL as $kode => $label)
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="jenis_kebutuhan" id="jenis-{{ $kode }}" value="{{ $kode }}" @checked($jenis === $kode) required>
                            <label class="btn btn-outline-secondary w-100 text-start jenis-pilih" for="jenis-{{ $kode }}">
                                <i class="bi {{ $kode === 'penambahan' ? 'bi-person-plus' : 'bi-arrow-repeat' }}"></i>{{ $label }}
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('jenis_kebutuhan')<div class="text-danger small">{{ $message }}</div>@enderror
                <p class="form-text mb-3" data-info-jenis>
                    <span data-jenis="penambahan" @if ($jenis !== 'penambahan') hidden @endif><i class="bi bi-people me-1"></i>Penambahan karyawan perlu disetujui <strong>GM unit dan Owner</strong> (bersamaan, tidak bertahap).</span>
                    <span data-jenis="replacement" @if ($jenis !== 'replacement') hidden @endif><i class="bi bi-person-check me-1"></i>Replacement direview Kepala SDM/GM seperti biasa.</span>
                </p>

                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <label class="form-label" for="f-laki">Jumlah laki-laki</label>
                        <div class="input-group">
                            <input type="number" name="jumlah_laki" id="f-laki" class="form-control @error('jumlah_laki') is-invalid @enderror" min="0" max="50" value="{{ old('jumlah_laki', 0) }}" required>
                            <span class="input-group-text">orang</span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="f-perempuan">Jumlah perempuan</label>
                        <div class="input-group">
                            <input type="number" name="jumlah_perempuan" id="f-perempuan" class="form-control @error('jumlah_perempuan') is-invalid @enderror" min="0" max="50" value="{{ old('jumlah_perempuan', 0) }}" required>
                            <span class="input-group-text">orang</span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="f-tanggal">Tanggal dibutuhkan</label>
                        <input type="date" name="target_waktu" id="f-tanggal" class="form-control @error('target_waktu') is-invalid @enderror"
                            value="{{ old('target_waktu') }}" @unless (auth()->user()->isAdmin()) min="{{ now()->toDateString() }}" @endunless required>
                    </div>
                    <div class="col-12">
                        @error('jumlah_laki')<div class="text-danger small">{{ $message }}</div>@enderror
                        @error('target_waktu')<div class="text-danger small">{{ $message }}</div>@enderror
                        <span class="small text-muted" data-total-jumlah></span>
                    </div>
                </div>

                {{-- ===== Persyaratan ===== --}}
                <h2 class="form-bagian"><i class="bi bi-list-check"></i>Persyaratan</h2>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="f-pendidikan">Minimal pendidikan</label>
                        <select name="min_pendidikan" id="f-pendidikan" class="form-select @error('min_pendidikan') is-invalid @enderror" required>
                            <option value="">-- Pilih --</option>
                            @foreach (\App\Models\PengajuanFormasi::PENDIDIKAN as $p)
                                <option value="{{ $p }}" @selected(old('min_pendidikan') === $p)>{{ $p }}</option>
                            @endforeach
                        </select>
                        @error('min_pendidikan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="f-usia">Minimal usia <span class="text-muted fw-normal">(opsional)</span></label>
                        <div class="input-group">
                            <input type="number" name="min_usia" id="f-usia" class="form-control @error('min_usia') is-invalid @enderror" min="15" max="70" value="{{ old('min_usia') }}">
                            <span class="input-group-text">tahun</span>
                        </div>
                        @error('min_usia')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="f-keahlian">Minimal kemampuan / keahlian yang dibutuhkan</label>
                        <textarea name="kualifikasi" id="f-keahlian" rows="2" class="form-control @error('kualifikasi') is-invalid @enderror" required
                            placeholder="mis. menguasai Excel, pengalaman administrasi keuangan 1 tahun">{{ old('kualifikasi') }}</textarea>
                        @error('kualifikasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="f-jabatan">Jabatan / posisi</label>
                        <select name="jabatan" id="f-jabatan" class="form-select @error('jabatan') is-invalid @enderror" required></select>
                        <div class="form-text" data-info-jabatan>Hanya jabatan yang ada di unit ini.</div>
                        @error('jabatan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="f-atasan">Atasan langsung</label>
                        <input type="text" name="atasan_langsung" id="f-atasan" class="form-control @error('atasan_langsung') is-invalid @enderror" maxlength="150"
                            value="{{ old('atasan_langsung', $atasanBawaan) }}" required>
                        @error('atasan_langsung')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="f-tugas">Tugas dan tanggung jawab (secara ringkas)</label>
                        <textarea name="tugas" id="f-tugas" rows="4" class="form-control @error('tugas') is-invalid @enderror" required
                            placeholder="Tulis satu tugas per baris">{{ old('tugas') }}</textarea>
                        @error('tugas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="f-alasan">Keterangan <span class="text-muted fw-normal">(opsional — mis. nama karyawan yang digantikan)</span></label>
                        <textarea name="alasan" id="f-alasan" rows="2" class="form-control @error('alasan') is-invalid @enderror">{{ old('alasan') }}</textarea>
                        @error('alasan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Kirim Pengajuan
                    </button>
                    <a href="{{ route('admin.pengajuan-formasi.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </section>
        </div>

        <div class="col-lg-5 col-xl-4">
            <section class="panel">
                <h2 class="h6 fw-bold mb-3"><i class="bi bi-signpost-split"></i>Alur Pengajuan</h2>
                <ol class="alur-langkah">
                    <li><strong>Diajukan</strong><span>Anda mengisi kebutuhan pegawai beserta alasannya.</span></li>
                    <li><strong>Disetujui</strong><span><b>Penambahan karyawan</b>: GM unit dan Owner menyetujui bersamaan (keduanya wajib). <b>Replacement</b>: {{ auth()->user()->isKepalaSdm() ? 'GM' : 'Kepala SDM atau GM' }} (unit SMK oleh Pembina).</span></li>
                    <li><strong>Lowongan terbit</strong><span>Bila disetujui, lowongan otomatis tampil di halaman publik dan pelamar bisa mulai melamar.</span></li>
                </ol>
                <div class="alert alert-light border small mb-0">
                    <i class="bi bi-lightbulb me-1"></i>Persyaratan (pendidikan, usia, keahlian) otomatis menjadi kualifikasi lowongan, dan tugas menjadi deskripsinya.
                </div>
            </section>
        </div>
        </div>
    </div>
</main>
<style>
    .form-bagian { display: flex; align-items: center; gap: .5rem; font-size: .9375rem; font-weight: 700; margin: 0 0 .9rem; padding-bottom: .5rem; border-bottom: 1px solid var(--admin-border); }
    .form-bagian i { color: var(--hr-primary); }
    * + .form-bagian { margin-top: 1.25rem; }
    .jenis-pilih { display: flex; align-items: center; gap: .5rem; min-height: 46px; white-space: normal; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formFormasi');
        const unit = document.getElementById('f-unit');
        const jabatan = document.getElementById('f-jabatan');
        const perUnit = @json($jabatanPerUnit);
        const lama = @json(old('jabatan'));
        // Dropdown jabatan mengikuti unit: hanya jabatan yang ada di unit tersebut.
        function isiJabatan() {
            const daftar = perUnit[unit.value] || [];
            jabatan.innerHTML = '';
            jabatan.add(new Option(unit.value ? (daftar.length ? '-- Pilih jabatan --' : 'Belum ada jabatan di unit ini') : '-- Pilih unit dulu --', ''));
            daftar.forEach((j) => jabatan.add(new Option(j, j, false, j === lama)));
            jabatan.disabled = !daftar.length;
        }
        unit.addEventListener('change', isiJabatan);
        isiJabatan();
        // Info alur sesuai jenis kebutuhan + total jumlah.
        form.querySelectorAll('[name=jenis_kebutuhan]').forEach((r) => r.addEventListener('change', () => {
            form.querySelectorAll('[data-jenis]').forEach((s) => { s.hidden = s.dataset.jenis !== r.value; });
        }));
        const total = form.querySelector('[data-total-jumlah]');
        const hitung = () => { const n = (+form.jumlah_laki.value || 0) + (+form.jumlah_perempuan.value || 0); total.textContent = 'Total kebutuhan: ' + n + ' orang'; total.classList.toggle('text-danger', n < 1); return n; };
        form.addEventListener('input', hitung); hitung();
        form.addEventListener('submit', function (e) {
            if (hitung() < 1) { e.preventDefault(); e.stopImmediatePropagation(); Swal.fire({ icon: 'info', title: 'Isi jumlah kebutuhan', text: 'Minimal 1 orang (laki-laki atau perempuan).', confirmButtonColor: '#8b1215' }); }
        }, true);
    });
</script>
@endsection
