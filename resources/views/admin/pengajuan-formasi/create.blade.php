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
                    $unitAwal = old('unit_kerja', $unitOptions[0] ?? '');
                    $jenis = old('jenis_kebutuhan');
                @endphp

                {{-- ===== Kebutuhan karyawan ===== --}}
                <h2 class="form-bagian"><i class="bi bi-clipboard-plus"></i>Kebutuhan Karyawan</h2>

                @if (auth()->user()->isAdmin())
                    <div class="mb-3">
                        <label class="form-label" for="f-unit">Unit <span class="text-muted fw-normal">(Admin)</span></label>
                        <select name="unit_kerja" id="f-unit" class="form-select @error('unit_kerja') is-invalid @enderror" required>
                            <option value="">-- Pilih unit --</option>
                            @foreach ($unitOptions as $u)
                                <option value="{{ $u }}" @selected($unitAwal === $u)>{{ $u }}</option>
                            @endforeach
                        </select>
                        @error('unit_kerja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @else
                    {{-- Unit = unit pengaju sendiri, tidak bisa diganti. --}}
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <div class="unit-terkunci"><i class="bi bi-lock-fill"></i><strong>{{ $unitOptions[0] ?? '-' }}</strong><span class="text-muted small ms-auto">unit Anda</span></div>
                        <input type="hidden" name="unit_kerja" id="f-unit" value="{{ $unitOptions[0] ?? '' }}">
                        @error('unit_kerja')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
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
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="f-laki">Laki-laki</label>
                        <div class="input-group">
                            <input type="number" name="jumlah_laki" id="f-laki" class="form-control @error('jumlah_laki') is-invalid @enderror" min="0" max="50" value="{{ old('jumlah_laki', 0) }}" required>
                            <span class="input-group-text">orang</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="f-perempuan">Perempuan</label>
                        <div class="input-group">
                            <input type="number" name="jumlah_perempuan" id="f-perempuan" class="form-control @error('jumlah_perempuan') is-invalid @enderror" min="0" max="50" value="{{ old('jumlah_perempuan', 0) }}" required>
                            <span class="input-group-text">orang</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="f-bebas" title="Laki-laki atau perempuan sama saja">Bebas (L/P)</label>
                        <div class="input-group">
                            <input type="number" name="jumlah_bebas" id="f-bebas" class="form-control @error('jumlah_bebas') is-invalid @enderror" min="0" max="50" value="{{ old('jumlah_bebas', 0) }}">
                            <span class="input-group-text">orang</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
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
                </div>

                {{-- ===== Keterangan: bagan unit (klik) ===== --}}
                <h2 class="form-bagian"><i class="bi bi-diagram-3"></i>Keterangan <span class="text-muted fw-normal small ms-1" data-bagan-judul>— pilih kebutuhan karyawan dulu</span></h2>
                <div class="bagan-legenda">
                    <span><i class="kotak" style="background:#15803d"></i>Pegawai aktif</span>
                    <span><i class="kotak" style="background:#9ca3af"></i>Pegawai tidak aktif</span>
                    <span><i class="kotak" style="background:#b42318"></i>Posisi kosong (baru)</span>
                    <span><i class="bi bi-arrow-repeat"></i>digantikan</span>
                </div>
                <p class="small mb-2" data-bagan-petunjuk></p>
                <input type="search" class="form-control form-control-sm mb-2" placeholder="Cari nama pegawai di bagan…" data-bagan-cari>
                <div class="bagan" id="baganUnit" aria-live="polite"></div>
                <div data-bagan-input></div>
                @error('diganti')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @error('diganti.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <div class="row g-3 mb-3 mt-1">
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
@include('admin.pengajuan-formasi._bagan-gaya')
<style>
    .form-bagian { display: flex; align-items: center; gap: .5rem; font-size: .9375rem; font-weight: 700; margin: 0 0 .9rem; padding-bottom: .5rem; border-bottom: 1px solid var(--admin-border); }
    .form-bagian i { color: var(--hr-primary); }
    * + .form-bagian { margin-top: 1.25rem; }
    .unit-terkunci { display: flex; align-items: center; gap: .5rem; padding: .55rem .8rem; border: 1px solid var(--admin-border); border-radius: 9px; background: var(--admin-surface-soft); }
    .unit-terkunci .bi-lock-fill { color: var(--admin-muted); }
    .jenis-pilih { display: flex; align-items: center; gap: .5rem; min-height: 46px; white-space: normal; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formFormasi');
        const unit = document.getElementById('f-unit');
        const jabatan = document.getElementById('f-jabatan');
        const perUnit = @json($jabatanPerUnit);
        const baganPerUnit = @json($baganPerUnit);
        const lama = @json(old('jabatan'));
        const digantiLama = (@json(old('diganti', []))).map(Number);
        const slotLama = @json(old('slot_tambahan', []));
        const wadah = document.getElementById('baganUnit');
        const masukan = form.querySelector('[data-bagan-input]');
        const petunjuk = form.querySelector('[data-bagan-petunjuk]');
        const judulBagan = form.querySelector('[data-bagan-judul]');
        const cari = form.querySelector('[data-bagan-cari]');
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
            jabatan.add(new Option(unit.value ? (daftar.length ? '-- Pilih jabatan --' : 'Belum ada jabatan di unit ini') : '-- Pilih unit dulu --', ''));
            daftar.forEach((j) => jabatan.add(new Option(j, j, false, j === nilai)));
            jabatan.disabled = !daftar.length;
        }

        function node(kelas, ikon, nama, judul) {
            const b = el('button', 'bagan-node ' + kelas);
            b.type = 'button';
            b.title = judul || nama;
            b.append(el('i', 'bi ' + ikon), el('span', 'nm', nama));
            return b;
        }

        // Gambar ulang bagan sesuai unit, jenis kebutuhan, jabatan, dan jumlah.
        function gambar() {
            const data = baganPerUnit[unit.value] || { kepala: [], grup: {} };
            const mode = jenis();
            const n = total();
            const kata = (cari.value || '').trim().toLowerCase();
            wadah.innerHTML = '';
            masukan.innerHTML = '';

            const kepala = node('kepala', 'bi-person-badge', (data.kepala.length ? data.kepala.join(', ') : 'Kepala unit') + ' · ' + (unit.value || '-'));
            kepala.disabled = true;
            wadah.append(el('div', 'bagan-kepala'), el('div', 'bagan-garis'));
            wadah.firstChild.append(kepala);
            const baris = el('div', 'bagan-grup-wrap');
            wadah.append(baris);

            const grup = Object.assign({}, data.grup);
            // Penambahan: posisi baru (kotak merah) muncul di grup jabatan yang dipilih.
            if (mode === 'penambahan' && jabatan.value && !grup[jabatan.value]) grup[jabatan.value] = [];
            const jabatanAktif = jabatan.value;

            Object.keys(grup).forEach((jab) => {
                const kotak = el('div', 'bagan-grup' + (jab === jabatanAktif ? ' terpilih' : ''));
                const daftar = el('div', 'bagan-anggota');
                const anggota = grup[jab].filter((o) => !kata || o.nama.toLowerCase().includes(kata) || dipilih.has(o.id));
                kotak.append(el('div', 'bagan-jabatan', jab + ' (' + grup[jab].length + ')'), daftar);
                anggota.forEach((o) => {
                    // Tanda "digantikan" hanya berlaku di mode replacement (pilihan tetap diingat bila pengguna kembali).
                    const ganti = mode === 'replacement' && dipilih.has(o.id);
                    const b = node((o.aktif ? 'aktif' : 'nonaktif') + (ganti ? ' diganti' : ''), o.aktif ? 'bi-person-fill' : 'bi-person-dash',
                        o.nama, o.nama + (o.aktif ? ' (aktif)' : ' (tidak aktif)') + (mode === 'replacement' ? ' — klik untuk ' + (ganti ? 'batal' : 'pilih sebagai yang digantikan') : ''));
                    if (ganti) { const l = el('span', 'bagan-lencana'); l.append(el('i', 'bi bi-arrow-repeat')); b.append(l); }
                    if (mode === 'replacement') {
                        b.addEventListener('click', () => { dipilih.has(o.id) ? dipilih.delete(o.id) : dipilih.add(o.id); gambar(); });
                    } else {
                        b.disabled = true;
                    }
                    daftar.append(b);
                });
                if (mode === 'penambahan' && jab === jabatanAktif) {
                    for (let i = 0; i < n; i++) {
                        const ket = slot[i] || '';
                        const b = node('kosong', 'bi-person-plus', ket || 'Posisi baru #' + (i + 1), 'Klik untuk menulis keterangan posisi ini');
                        const l = el('span', 'bagan-lencana'); l.append(el('i', 'bi bi-plus-lg')); b.append(l);
                        b.addEventListener('click', () => {
                            Swal.fire({
                                title: 'Keterangan posisi baru #' + (i + 1), input: 'text', inputValue: slot[i] || '',
                                inputPlaceholder: 'mis. untuk shift malam / gedung B', inputAttributes: { maxlength: 150 },
                                showCancelButton: true, confirmButtonText: 'Simpan', cancelButtonText: 'Batal', confirmButtonColor: '#8b1215',
                            }).then((r) => { if (r.isConfirmed) { slot[i] = r.value.trim(); gambar(); } });
                        });
                        daftar.append(b);
                    }
                }
                if (anggota.length || (mode === 'penambahan' && jab === jabatanAktif)) baris.append(kotak);
            });
            if (!baris.children.length) baris.append(el('div', 'bagan-kosong-info', kata ? 'Tidak ada pegawai yang cocok.' : 'Belum ada pegawai tercatat di unit ini.'));

            // Nilai terkirim: id yang digantikan / keterangan posisi baru.
            if (mode === 'replacement') {
                dipilih.forEach((id) => { const i = el('input'); i.type = 'hidden'; i.name = 'diganti[]'; i.value = id; masukan.append(i); });
            } else if (mode === 'penambahan') {
                for (let i = 0; i < n; i++) { const x = el('input'); x.type = 'hidden'; x.name = 'slot_tambahan[]'; x.value = slot[i] || ''; masukan.append(x); }
            }

            judulBagan.textContent = mode === 'replacement' ? '— klik pegawai yang digantikan' : mode === 'penambahan' ? '— posisi baru (kotak merah)' : '— pilih kebutuhan karyawan dulu';
            petunjuk.className = 'small mb-2 ' + (mode === 'replacement' && dipilih.size !== n ? 'text-danger fw-semibold' : 'text-muted');
            petunjuk.textContent = mode === 'replacement'
                ? 'Dipilih ' + dipilih.size + ' dari ' + n + ' orang yang dibutuhkan. Hijau = masih aktif, abu = sudah tidak aktif.'
                : mode === 'penambahan'
                    ? (jabatan.value ? n + ' kotak merah = posisi baru di ' + jabatan.value + '. Klik kotak untuk menambah keterangan.' : 'Pilih jabatan/posisi di atas agar posisi baru tampil di bagan.')
                    : 'Bagan menampilkan susunan pegawai unit Anda.';
        }

        unit.addEventListener('change', () => { dipilih.clear(); slot = []; isiJabatan(); gambar(); });
        jabatan.addEventListener('change', gambar);
        cari.addEventListener('input', gambar);
        form.querySelectorAll('[name=jenis_kebutuhan]').forEach((r) => r.addEventListener('change', () => {
            form.querySelectorAll('[data-jenis]').forEach((s) => { s.hidden = s.dataset.jenis !== r.value; });
            gambar();
        }));
        const totalTeks = form.querySelector('[data-total-jumlah]');
        const hitung = () => { const n = total(); totalTeks.textContent = 'Total kebutuhan: ' + n + ' orang'; totalTeks.classList.toggle('text-danger', n < 1); return n; };
        ['jumlah_laki', 'jumlah_perempuan', 'jumlah_bebas'].forEach((k) => form[k].addEventListener('input', () => { hitung(); gambar(); }));
        form.addEventListener('submit', function (e) {
            const tahan = (judul, teks) => { e.preventDefault(); e.stopImmediatePropagation(); Swal.fire({ icon: 'info', title: judul, text: teks, confirmButtonColor: '#8b1215' }); };
            if (hitung() < 1) return tahan('Isi jumlah kebutuhan', 'Minimal 1 orang (laki-laki, perempuan, atau bebas).');
            if (jenis() === 'replacement' && dipilih.size !== total()) return tahan('Pilih pegawai yang digantikan', 'Klik ' + total() + ' pegawai pada bagan (sekarang ' + dipilih.size + ').');
        }, true);

        isiJabatan();
        hitung();
        gambar();
    });
</script>
@endsection
