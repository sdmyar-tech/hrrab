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
        <div class="col-xxl-8">
        <section class="panel p-4">
            <form action="{{ route('admin.pengajuan-formasi.store') }}" method="POST" id="formFormasi" data-confirm="Kirim pengajuan formasi ini?">
                @csrf
                @php
                    $unitAwal = old('unit_kerja', $unitOptions[0] ?? '');
                    $jenis = old('jenis_kebutuhan');
                @endphp

                {{-- ===== Kebutuhan karyawan ===== --}}
                <h2 class="form-bagian"><i class="bi bi-clipboard-plus"></i>Kebutuhan Karyawan</h2>

                @if (auth()->user()->isAdmin() || count($unitOptions) > 1)
                    {{-- Admin: semua unit. Kepala beberapa unit: hanya unit yang dipimpinnya. --}}
                    <div class="mb-3">
                        <label class="form-label" for="f-unit">Unit <span class="text-muted fw-normal">({{ auth()->user()->isAdmin() ? 'Admin' : 'unit yang Anda pimpin' }})</span></label>
                        <select name="unit_kerja" id="f-unit" class="form-select @error('unit_kerja') is-invalid @enderror" required>
                            @if (auth()->user()->isAdmin())<option value="">-- Pilih unit --</option>@endif
                            @foreach ($unitOptions as $u)
                                <option value="{{ $u }}" @selected($unitAwal === $u)>{{ $u }}</option>
                            @endforeach
                        </select>
                        @error('unit_kerja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @else
                    {{-- Hanya satu unit → terkunci. --}}
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
                    <span data-jenis="penambahan" @if ($jenis !== 'penambahan') hidden @endif><i class="bi bi-people me-1"></i>Penambahan karyawan disetujui bertahap: <strong>GM unit</strong>, lalu <strong>Owner</strong>.</span>
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
                <div class="bagan">
                    <div class="bagan-alat">
                        <input type="search" class="form-control form-control-sm" placeholder="Cari nama pegawai di bagan…" data-bagan-cari>
                        <div class="ms-auto d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-outline-secondary" data-zoom="-" title="Perkecil" aria-label="Perkecil"><i class="bi bi-zoom-out"></i></button>
                            <span class="bagan-skala" data-bagan-skala>100%</span>
                            <button type="button" class="btn btn-outline-secondary" data-zoom="+" title="Perbesar" aria-label="Perbesar"><i class="bi bi-zoom-in"></i></button>
                            <button type="button" class="btn btn-outline-secondary" data-zoom="pas" title="Muat seluruh bagan di layar"><i class="bi bi-arrows-angle-contract me-1"></i>Pas layar</button>
                        </div>
                    </div>
                    <div class="bagan-layar" data-bagan-layar>
                        <div class="bagan-pohon" id="baganUnit" aria-live="polite"></div>
                    </div>
                </div>
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

        <div class="col-xxl-4">
            <section class="panel">
                <h2 class="h6 fw-bold mb-3"><i class="bi bi-signpost-split"></i>Alur Pengajuan</h2>
                <ol class="alur-langkah">
                    <li><strong>Diajukan</strong><span>Anda mengisi kebutuhan pegawai beserta alasannya.</span></li>
                    <li><strong>Disetujui</strong><span><b>Penambahan karyawan</b>: bertahap, GM unit dulu lalu Owner (keduanya wajib). <b>Replacement</b>: {{ auth()->user()->isKepalaSdm() ? 'GM' : 'Kepala SDM atau GM' }} (unit SMK oleh Pembina).</span></li>
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
        const ADMIN = @json(auth()->user()->isAdmin());
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

        const layar = form.querySelector('[data-bagan-layar]');
        const labelSkala = form.querySelector('[data-bagan-skala]');
        // Ukuran harus sama dengan CSS (--node, padding cabang) agar pembagian baris tepat.
        const LEBAR_NODE = 148, TULANG = 28, MAKS_SEJAJAR = 4, BATAS_LIPAT = 12;
        const terbuka = new Set();          // jabatan yang raknya dibuka penuh
        let skala = 1, skalaOtomatis = true;

        function node(kelas, ikon, nama, judul) {
            const b = el('button', 'bagan-node ' + kelas);
            b.type = 'button';
            b.title = judul || nama;
            b.append(el('i', 'bi ' + ikon), el('span', 'nm', nama));
            return b;
        }

        // ---- Zoom & geser (untuk struktur besar) ----
        function setSkala(s, otomatis) {
            skala = Math.min(1.5, Math.max(0.4, s));
            skalaOtomatis = !!otomatis;
            wadah.style.zoom = skala;
            labelSkala.textContent = Math.round(skala * 100) + '%';
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

        function simpulPegawai(o, mode, kata) {
            // Tanda "digantikan" hanya di mode replacement (pilihan tetap diingat bila pengguna kembali).
            const ganti = mode === 'replacement' && dipilih.has(o.id);
            const b = node((o.aktif ? 'aktif' : 'nonaktif') + (ganti ? ' diganti' : '') + (kata && o.nama.toLowerCase().includes(kata) ? ' cocok' : ''),
                o.aktif ? 'bi-person-fill' : 'bi-person-dash', o.nama,
                o.nama + (o.aktif ? ' (aktif)' : ' (tidak aktif)') + (mode === 'replacement' ? ' — klik untuk ' + (ganti ? 'batal' : 'pilih sebagai yang digantikan') : ''));
            if (ganti) { const l = el('span', 'bagan-lencana'); l.append(el('i', 'bi bi-arrow-repeat')); b.append(l); }
            if (mode === 'replacement') {
                b.addEventListener('click', () => { dipilih.has(o.id) ? dipilih.delete(o.id) : dipilih.add(o.id); gambar(); });
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
                    inputLabel: 'Keterangan', inputPlaceholder: 'mis. untuk shift malam / gedung B', inputAttributes: { maxlength: 150 },
                    showCancelButton: true, showDenyButton: true, confirmButtonText: 'Simpan', denyButtonText: 'Hapus posisi', cancelButtonText: 'Batal', confirmButtonColor: '#8b1215',
                }).then((r) => {
                    if (r.isConfirmed) { slot[i] = r.value.trim(); gambar(); }
                    if (r.isDenied) { slot.splice(i, 1); ubahJumlah(jkSlot(i), -1); gambar(); }
                });
            });
            return { b, penting: true };
        }

        // Kotak merah "+ Tambah posisi" di tiap jabatan: pilih jabatan itu, tanya jenis kelamin & keterangan, jumlah bertambah.
        function simpulTambah(jab) {
            const b = node('kosong tambah', 'bi-plus-circle', 'Tambah posisi', 'Tambah posisi baru di ' + jab);
            b.addEventListener('click', () => tambahSlot(jab));
            return { b, penting: true };
        }

        function tambahSlot(jab) {
            const pindah = total() > 0 && jabatan.value && jabatan.value !== jab;
            const wadahSwal = el('div', 'text-start');
            wadahSwal.innerHTML = '<div class="small fw-semibold mb-1">Jenis kelamin</div><div class="d-flex flex-wrap gap-2 mb-3" data-jk></div>'
                + '<label class="small fw-semibold mb-1" for="swal-ket">Keterangan <span class="fw-normal text-muted">(opsional)</span></label>'
                + '<input id="swal-ket" class="form-control" maxlength="150" placeholder="mis. untuk shift malam / gedung B">'
                + (pindah ? '<div class="alert alert-warning small mt-3 mb-0" data-pindah></div>' : '');
            JK.forEach(([nama, label], k) => {
                const r = el('input', 'btn-check'); r.type = 'radio'; r.name = 'swal-jk'; r.id = 'swal-jk-' + k; r.value = k; r.checked = k === 2;
                const lb = el('label', 'btn btn-sm btn-outline-secondary', label); lb.htmlFor = r.id;
                wadahSwal.querySelector('[data-jk]').append(r, lb);
            });
            if (pindah) wadahSwal.querySelector('[data-pindah]').textContent = 'Satu pengajuan untuk satu jabatan: ' + total() + ' posisi baru di ' + jabatan.value + ' akan dipindah ke ' + jab + '.';
            Swal.fire({
                titleText: 'Tambah posisi baru — ' + jab, html: wadahSwal, focusConfirm: false,
                showCancelButton: true, confirmButtonText: 'Tambah', cancelButtonText: 'Batal', confirmButtonColor: '#8b1215',
                didOpen: () => document.getElementById('swal-ket').focus(),
                preConfirm: () => ({ jk: +document.querySelector('[name=swal-jk]:checked').value, ket: document.getElementById('swal-ket').value.trim() }),
            }).then((r) => {
                if (!r.isConfirmed) return;
                if (![...jabatan.options].some((o) => o.value === jab)) jabatan.add(new Option(jab, jab));
                jabatan.value = jab;
                // Sisipkan di akhir kelompok jenis kelaminnya agar urutan slot tetap L, P, bebas.
                const l = +form.jumlah_laki.value || 0, p = +form.jumlah_perempuan.value || 0;
                slot.splice([l, l + p, total()][r.value.jk], 0, r.value.ket);
                ubahJumlah(r.value.jk, 1);
                gambar();
            });
        }

        // Satu cabang jabatan: label → anggota berjajar (garis masing-masing) atau rak grid bila banyak.
        function buatCabang(c, mode, kata, uk) {
            const kotak = el('div', 'bagan-cabang' + (c.jab === jabatan.value ? ' terpilih' : ''));
            kotak.append(el('div', 'bagan-jabatan', c.jab + ' (' + c.total + ')'), el('div', 'bagan-turun'));
            const simpul = c.isi.map((o) => simpulPegawai(o, mode, kata));
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
            (data.kepala.length ? data.kepala : ['Kepala unit']).forEach((nm) => { const k = node('kepala', 'bi-person-badge', nm); k.disabled = true; deretKepala.append(k); });
            akar.append(el('div', 'bagan-akar-label', 'Kepala · ' + (unit.value || '-')), deretKepala);
            wadah.append(akar);

            // Cabang per jabatan: anggota (tersaring pencarian) + posisi baru (penambahan) di jabatan terpilih.
            const grup = Object.assign({}, data.grup);
            if (mode === 'penambahan' && jabatan.value && !grup[jabatan.value]) grup[jabatan.value] = [];
            // Penambahan: tiap jabatan yang boleh diajukan mendapat kotak merah "+ Tambah posisi"
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
                wadah.append(el('div', 'bagan-kosong-info', kata ? 'Tidak ada pegawai yang cocok.' : 'Belum ada pegawai tercatat di unit ini.'));
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

            judulBagan.textContent = mode === 'replacement' ? '— klik pegawai yang digantikan' : mode === 'penambahan' ? '— posisi baru (kotak merah)' : '— pilih kebutuhan karyawan dulu';
            petunjuk.className = 'small mb-2 ' + (mode === 'replacement' && dipilih.size !== n ? 'text-danger fw-semibold' : 'text-muted');
            petunjuk.textContent = mode === 'replacement'
                ? 'Dipilih ' + dipilih.size + ' dari ' + n + ' orang yang dibutuhkan. Hijau = masih aktif, abu = sudah tidak aktif.'
                : mode === 'penambahan'
                    ? 'Klik kotak merah "+ Tambah posisi" pada jabatan yang dibutuhkan (jabatan & jumlah terisi otomatis).'
                        + (n && jabatan.value ? ' Sekarang ' + n + ' posisi baru di ' + jabatan.value + '; klik kotaknya untuk ubah keterangan atau hapus.' : '')
                    : 'Bagan menampilkan susunan pegawai unit Anda.';
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

        unit.addEventListener('change', () => { dipilih.clear(); slot = []; terbuka.clear(); isiJabatan(); gambar(); pasLayar(); });
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
        pasLayar();
    });
</script>
@endsection
