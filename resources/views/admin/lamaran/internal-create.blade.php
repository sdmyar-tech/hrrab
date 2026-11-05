@extends('layouts.admin.app')

@section('title', 'Tambah Kandidat Internal')

{{-- Galat isian ditampilkan di halaman ini (ringkasan + di bawah isian); banner global tidak perlu. --}}
@section('galat-di-halaman', '1')

@section('content')
@php
    $jenis = old('jenis', 'luar');
    $posisi = old('posisi', 'lowongan');
    $opsi = fn (array $daftar) => array_combine($daftar, $daftar);
    $pegawaiLama = (string) old('data_pegawai_id');
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Tambah Kandidat Internal" eyebrow="Rekrutmen" :back="route('admin.lamaran.index')" back-label="Lamaran Masuk"
            subtitle="Daftarkan kandidat tanpa lamaran online: pegawai yang akan dipindah atau naik jabatan, atau calon yang diusulkan pimpinan. Kandidat langsung masuk tahap interview." />

        @if ($errors->any())
            <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-4">
                Kandidat belum tersimpan. Perbaiki isian berikut, lalu simpan lagi:
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form data-fokus-awal action="{{ route('admin.lamaran.internal.store') }}" method="POST" id="formKandidat"
            data-confirm="Tambahkan kandidat ini dan lanjut ke tahap interview?">
            @csrf

            <div class="row g-4">
                <div class="col-xl-8">
                    <x-card>
                        {{-- ================= 1. Kandidat ================= --}}
                        <x-form-section id="bagian-kandidat" class="hr-bagian" title="Kandidat" icon="bi-person"
                            subtitle="Pilih pegawai yang sudah ada, atau isi biodata calon pegawai baru. Kolom bertanda * wajib diisi.">
                            <div class="col-12">
                                <fieldset class="hr-field mb-0">
                                    <legend class="form-label required">Jenis kandidat</legend>
                                    <div class="hr-opsi-grup">
                                        <label class="hr-opsi" for="jenis-luar">
                                            <input class="form-check-input" type="radio" name="jenis" id="jenis-luar" value="luar" @checked($jenis === 'luar')>
                                            <i class="bi bi-person-plus hr-opsi-ikon" aria-hidden="true"></i>
                                            <span><strong>Calon pegawai baru</strong><small>Belum terdaftar; isi biodata lengkap di bawah.</small></span>
                                        </label>
                                        <label class="hr-opsi" for="jenis-pegawai">
                                            <input class="form-check-input" type="radio" name="jenis" id="jenis-pegawai" value="pegawai" @checked($jenis === 'pegawai')>
                                            <i class="bi bi-person-badge hr-opsi-ikon" aria-hidden="true"></i>
                                            <span><strong>Pegawai yayasan</strong><small>Mutasi atau promosi; data pegawai sudah ada di sistem.</small></span>
                                        </label>
                                    </div>
                                </fieldset>
                            </div>

                            {{-- Blok tidak aktif disembunyikan & isiannya dinonaktifkan skrip (tidak ikut terkirim). --}}
                            <div class="col-12" data-jenis="pegawai">
                                <div class="hr-subbagian">
                                    <x-form-group label="Pegawai" for="f-pegawai" name="data_pegawai_id" required class="mb-0"
                                        help="Data pegawai sudah ada di sistem, tidak perlu diisi ulang. Ketik nama, NIP, jabatan, atau bagian untuk menyaring daftar.">
                                        <input type="search" class="form-control form-control-sm mb-2" placeholder="Cari pegawai, contoh: Siti atau SDM"
                                            data-saring-pilihan="#f-pegawai" aria-label="Cari pegawai" autocomplete="off">
                                        <select name="data_pegawai_id" id="f-pegawai" class="form-select @error('data_pegawai_id') is-invalid @enderror" required
                                            aria-describedby="{{ $errors->has('data_pegawai_id') ? 'f-pegawai-galat' : 'f-pegawai-bantuan' }}"
                                            @error('data_pegawai_id') aria-invalid="true" @enderror>
                                            <option value="">Pilih pegawai</option>
                                            @foreach ($pegawais as $pg)
                                                @php $kp = $pg->kepegawaian; @endphp
                                                <option value="{{ $pg->id }}" @selected($pegawaiLama === (string) $pg->id)
                                                    data-nama="{{ $pg->nama_lengkap }}" data-nip="{{ $pg->nip }}" data-jabatan="{{ $kp?->jabatan }}" data-unit="{{ $kp?->unit_kerja }}"
                                                    data-cari="{{ mb_strtolower($pg->nama_lengkap . ' ' . $pg->nip . ' ' . $kp?->jabatan . ' ' . $kp?->unit_kerja) }}">
                                                    {{ $pg->nama_lengkap }} — {{ $kp?->jabatan ?? '-' }} ({{ $kp?->unit_kerja ?? '-' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </x-form-group>
                                </div>
                            </div>

                            <div class="col-12" data-jenis="luar">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <x-input name="nama_lengkap" id="f-nama-lengkap" label="Nama lengkap" required maxlength="150"
                                            placeholder="Nama sesuai KTP, contoh: Budi Santoso" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input name="nik" id="f-nik" label="NIK" required maxlength="20" inputmode="numeric"
                                            placeholder="16 digit, contoh: 1471012345678901" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input type="email" name="email" id="f-email" label="Email" required maxlength="150"
                                            placeholder="Email aktif, contoh: budi@gmail.com" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input name="no_hp" id="f-no-hp" label="No. HP / WhatsApp" required maxlength="20" inputmode="tel"
                                            placeholder="Nomor aktif, contoh: 081234567890" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input name="tempat_lahir" id="f-tempat-lahir" label="Tempat lahir" required maxlength="150" placeholder="Kota kelahiran, contoh: Pekanbaru" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input type="date" name="tanggal_lahir" id="f-tanggal-lahir" label="Tanggal lahir" required :max="now()->subDay()->toDateString()" />
                                    </div>
                                    <div class="col-md-4">
                                        <x-select name="jenis_kelamin" id="f-jenis-kelamin" label="Jenis kelamin" :options="$opsi(['Laki-laki', 'Perempuan'])"
                                            placeholder="Pilih jenis kelamin" required />
                                    </div>
                                    <div class="col-md-4">
                                        <x-select name="agama" id="f-agama" label="Agama" :options="$opsi(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'])"
                                            placeholder="Pilih agama" required />
                                    </div>
                                    <div class="col-md-4">
                                        <x-select name="status_pernikahan" id="f-status-pernikahan" label="Status pernikahan" required
                                            :options="$opsi(['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'])" selected="Belum Menikah" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input name="nama_ibu_kandung" id="f-nama-ibu-kandung" label="Nama ibu kandung" required maxlength="150" placeholder="Sesuai KK, contoh: Siti Aminah" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input name="pendidikan_terakhir" id="f-pendidikan-terakhir" label="Pendidikan terakhir" required maxlength="100"
                                            placeholder="Jenjang & jurusan, contoh: S1 Akuntansi" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input name="no_kk" id="f-no-kk" label="No. KK" optional maxlength="20" inputmode="numeric" placeholder="16 digit, contoh: 1471010101010001" />
                                    </div>
                                    <div class="col-12">
                                        <x-textarea name="alamat_ktp" id="f-alamat-ktp" label="Alamat KTP" rows="2" required maxlength="1000"
                                            placeholder="Alamat lengkap sesuai KTP, contoh: Jl. Sudirman No. 10, Pekanbaru" />
                                    </div>
                                </div>
                                <p class="form-text mt-2 mb-3">Biodata dipakai langsung saat kandidat direkrut menjadi pegawai. Bila email sudah pernah terdaftar sebagai pelamar, datanya dipakai ulang.</p>
                                {{-- Riwayat pendidikan, pekerjaan, sosial media (kartu repeater bersama dengan halaman Data Pelamar). --}}
                                <div class="rk-riwayat-pelamar d-grid gap-3">
                                    @include('partials.pelamar-riwayat', ['pelamar' => null])
                                </div>
                            </div>
                        </x-form-section>

                        {{-- ================= 2. Posisi tujuan ================= --}}
                        <x-form-section id="bagian-posisi" class="hr-bagian" title="Posisi Tujuan" icon="bi-briefcase"
                            subtitle="Lowongan yang diikuti kandidat, atau bagian & jabatan tujuan bila tanpa lowongan.">
                            <div class="col-12">
                                <fieldset class="hr-field mb-0">
                                    <legend class="form-label required">Posisi dari</legend>
                                    <div class="hr-opsi-grup">
                                        <label class="hr-opsi" for="posisi-lowongan">
                                            <input class="form-check-input" type="radio" name="posisi" id="posisi-lowongan" value="lowongan" @checked($posisi === 'lowongan')>
                                            <i class="bi bi-megaphone hr-opsi-ikon" aria-hidden="true"></i>
                                            <span><strong>Dari lowongan</strong><small>Lowongan yang sudah terbit; kuotanya belum penuh.</small></span>
                                        </label>
                                        <label class="hr-opsi" for="posisi-manual">
                                            <input class="form-check-input" type="radio" name="posisi" id="posisi-manual" value="manual" @checked($posisi === 'manual')>
                                            <i class="bi bi-diagram-3 hr-opsi-ikon" aria-hidden="true"></i>
                                            <span><strong>Tanpa lowongan</strong><small>Tentukan sendiri bagian & jabatan tujuan.</small></span>
                                        </label>
                                    </div>
                                </fieldset>
                            </div>

                            <div class="col-12" data-posisi="lowongan">
                                <x-select name="lowongan_id" id="f-lowongan" label="Lowongan" placeholder="Pilih lowongan" required
                                    help="Termasuk lowongan yang sudah ditutup, selama kuotanya belum terpenuhi.">
                                    @foreach ($lowongans as $lw)
                                        <option value="{{ $lw->id }}" @selected((string) old('lowongan_id') === (string) $lw->id) data-judul="{{ $lw->judul }}" data-unit="{{ $lw->unit_kerja }}">
                                            {{ $lw->judul }} — {{ $lw->unit_kerja }} ({{ $lw->statusLabel() }}, {{ $lw->jumlah_terisi }}/{{ $lw->jumlah_dibutuhkan }})
                                        </option>
                                    @endforeach
                                </x-select>
                            </div>

                            <div class="col-12" data-posisi="manual">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <x-select name="unit_kerja_tujuan" id="unit-tujuan" label="Bagian tujuan" :options="$opsi(config('kepegawaian.unit_kerja'))"
                                            placeholder="Pilih bagian" required help="Interview 2 otomatis dengan Kepala Bagian di bagian ini." />
                                    </div>
                                    <div class="col-md-6">
                                        {{-- Diisi skrip sesuai bagian: jabatan yang ada di bagian itu dulu, lalu jabatan lainnya. --}}
                                        <x-select name="jabatan_tujuan" id="jabatan-tujuan" label="Jabatan tujuan" required help="Jabatan di bagian terpilih tampil paling atas." />
                                    </div>
                                    <div class="col-md-6">
                                        {{-- Lokasi mengikuti bagian & jabatan tujuan (Master Jabatan → Atur lokasi). --}}
                                        <x-select name="lokasi_kerja_tujuan" id="f-lokasi-kerja-tujuan" label="Lokasi kerja" placeholder="Pilih lokasi kerja" optional
                                            data-lokasi-jabatan="[name=jabatan_tujuan]" data-lokasi-unit="[name=unit_kerja_tujuan]"
                                            :data-lokasi-bebas="auth()->user()->can('sistem.override') ? '' : null">
                                            <option value="">Tidak ditentukan</option>
                                            @foreach (config('kepegawaian.lokasi_kerja') as $lk)
                                                <option value="{{ $lk }}" @selected(old('lokasi_kerja_tujuan') === $lk)>{{ $lk }}</option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                </div>
                            </div>
                        </x-form-section>

                        {{-- ================= 3. Gambaran posisi di bagan ================= --}}
                        <x-form-section id="bagian-bagan" class="hr-bagian" title="Gambaran Posisi di Bagan" icon="bi-diagram-3"
                            subtitle="Posisi yang akan diisi kandidat ditandai kotak merah pada bagan bagian tujuan.">
                            <div class="col-12">
                                <div data-bagan-kandidat>
                                    <x-empty-state icon="bi-diagram-3" compact>Pilih lowongan, atau bagian &amp; jabatan tujuan, untuk melihat posisi yang akan diisi kandidat.</x-empty-state>
                                </div>
                            </div>
                        </x-form-section>

                        {{-- ================= 4. Pengusul ================= --}}
                        <x-form-section id="bagian-pengusul" class="hr-bagian" title="Pengusul" icon="bi-person-check"
                            subtitle="Opsional — pegawai yang mengusulkan kandidat ini; tercatat di data lamaran.">
                            <div class="col-md-8">
                                <x-form-group label="Diusulkan oleh" for="pengusul" optional class="mb-0">
                                    @include('partials.pilih-pegawai', [
                                        'name' => 'direkomendasikan_oleh', 'id' => 'pengusul', 'nilai' => 'nama',
                                        'terpilih' => old('direkomendasikan_oleh'), 'kosong' => 'Tidak ada pengusul',
                                    ])
                                </x-form-group>
                            </div>
                        </x-form-section>
                    </x-card>
                </div>

                {{-- ================= Kanan: pratinjau kandidat (lengket) ================= --}}
                <div class="col-xl-4">
                    <div class="hr-detail-samping d-grid gap-4">
                        <x-card title="Pratinjau Kandidat" icon="bi-person-vcard" subtitle="Berubah sesuai pilihan & isian Anda." class="rk-pratinjau" data-pratinjau-kandidat aria-live="polite">
                            <div class="hr-sel-utama mb-3">
                                <x-avatar name="?" size="lg" data-pv-avatar />
                                <div class="min-w-0">
                                    <p class="rk-pratinjau-nama is-kosong" data-pv="nama">Kandidat belum dipilih</p>
                                    <span class="hr-sel-sub" data-pv="sub"></span>
                                </div>
                            </div>
                            <dl class="dash-dl mb-0">
                                <div data-pv-untuk="pegawai"><dt>NIP</dt><dd data-pv="nip">-</dd></div>
                                <div data-pv-untuk="pegawai"><dt>Jabatan sekarang</dt><dd data-pv="jabatan">-</dd></div>
                                <div data-pv-untuk="pegawai"><dt>Bagian sekarang</dt><dd data-pv="unit">-</dd></div>
                                <div data-pv-untuk="luar"><dt>No. HP</dt><dd data-pv="hp">-</dd></div>
                                <div><dt>Posisi tujuan</dt><dd data-pv="posisi">Belum dipilih</dd></div>
                                <div><dt>Bagian tujuan</dt><dd data-pv="tujuan">-</dd></div>
                                <div><dt>Pengusul</dt><dd data-pv="pengusul">Tidak ada</dd></div>
                            </dl>
                        </x-card>
                        <x-alert tone="info" title="Setelah disimpan" class="mb-0">
                            Kandidat langsung masuk tahap <strong>Interview 1 (Kepala SDM)</strong>. Anda mendapat <strong>link jadwal</strong>
                            untuk dibagikan ke kandidat (bisa dibuka tanpa login).
                        </x-alert>
                    </div>
                </div>
            </div>

            {{-- Bilah aksi lengket: Batal di kiri, simpan (utama) di kanan. --}}
            <div class="hr-bilah-aksi mt-4">
                <x-button variant="secondary" :href="route('admin.lamaran.index')">Batal</x-button>
                <x-button type="submit" icon="bi-person-plus">Tambah Kandidat</x-button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function sinkron(nama, atribut) {
            const nilai = document.querySelector('input[name="' + nama + '"]:checked').value;
            document.querySelectorAll('[' + atribut + ']').forEach(function (blok) {
                const aktif = blok.getAttribute(atribut) === nilai;
                blok.hidden = ! aktif;
                blok.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = ! aktif; });
            });
        }

        [['jenis', 'data-jenis'], ['posisi', 'data-posisi']].forEach(function (pasangan) {
            document.querySelectorAll('input[name="' + pasangan[0] + '"]').forEach(function (radio) {
                radio.addEventListener('change', function () { sinkron(pasangan[0], pasangan[1]); });
            });
            sinkron(pasangan[0], pasangan[1]);
        });

        // Posisi tanpa lowongan: pilih unit dulu, lalu jabatan (yang ada di unit itu tampil paling atas).
        const unit = document.getElementById('unit-tujuan');
        const jabatan = document.getElementById('jabatan-tujuan');
        const semuaJabatan = @json(\App\Models\Jabatan::daftar());
        const jabatanPerUnit = @json($jabatanPerUnit);
        const jabatanLama = @json(old('jabatan_tujuan'));

        function isiJabatan() {
            const nilai = jabatan.value || jabatanLama;
            jabatan.innerHTML = '';
            if (!unit.value) {
                jabatan.add(new Option('Pilih bagian dulu', ''));
                return;
            }
            jabatan.add(new Option('Pilih jabatan', ''));
            const diUnit = jabatanPerUnit[unit.value] || [];
            const grup = (label, daftar) => {
                if (!daftar.length) return;
                const g = document.createElement('optgroup');
                g.label = label;
                daftar.forEach((j) => g.append(new Option(j, j, false, j === nilai)));
                jabatan.append(g);
            };
            grup('Jabatan di bagian ' + unit.value, diUnit);
            grup(diUnit.length ? 'Jabatan lainnya' : 'Semua jabatan', semuaJabatan.filter((j) => !diUnit.includes(j)));
        }

        unit.addEventListener('change', isiJabatan);
        isiJabatan();

        // Gambaran posisi di bagan: dimuat ulang tiap lowongan / unit / jabatan berubah; kotak merah diberi nama kandidat.
        const wadahBagan = document.querySelector('[data-bagan-kandidat]');
        const lowongan = document.querySelector('[name="lowongan_id"]');
        const pegawai = document.querySelector('[name="data_pegawai_id"]');
        const namaLuar = document.querySelector('[name="nama_lengkap"]');
        const petunjukAwal = wadahBagan.innerHTML;
        let permintaan = 0;

        const opsiPegawai = () => (pegawai.value ? pegawai.selectedOptions[0] : null);
        function namaKandidat() {
            const jenis = document.querySelector('input[name="jenis"]:checked').value;
            const opsi = opsiPegawai();
            const nama = jenis === 'pegawai'
                ? (opsi ? (opsi.dataset.nama || opsi.text.split(' — ')[0]).trim() : '')
                : namaLuar.value.trim();
            return 'Kandidat' + (nama ? ': ' + nama : '');
        }
        function tulisNama() {
            wadahBagan.querySelectorAll('.bagan-node.kandidat .nm').forEach((nm) => { nm.textContent = namaKandidat(); });
        }

        function muatBagan() {
            const posisi = document.querySelector('input[name="posisi"]:checked').value;
            const param = posisi === 'lowongan'
                ? (lowongan.value ? { lowongan_id: lowongan.value } : null)
                : (unit.value && jabatan.value ? { unit: unit.value, jabatan: jabatan.value } : null);
            const ke = ++permintaan;   // abaikan jawaban lama bila pilihan sudah berubah lagi
            if (!param) { wadahBagan.innerHTML = petunjukAwal; return; }
            wadahBagan.setAttribute('aria-busy', 'true');
            wadahBagan.innerHTML = '<p class="small text-muted mb-0"><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Memuat bagan…</p>';
            fetch(@json(route('admin.lamaran.internal.bagan')) + '?' + new URLSearchParams(param), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((r) => { if (!r.ok) throw new Error(r.status); return r.text(); })
                .then((html) => { if (ke !== permintaan) return; wadahBagan.innerHTML = html; tulisNama(); })
                .catch(() => {
                    if (ke !== permintaan) return;
                    wadahBagan.innerHTML = '<p class="small text-danger mb-0"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>Bagan gagal dimuat. '
                        + '<button type="button" class="btn btn-link btn-sm p-0 align-baseline" data-ulang-bagan>Coba lagi</button></p>';
                    wadahBagan.querySelector('[data-ulang-bagan]').addEventListener('click', muatBagan);
                })
                .finally(() => { if (ke === permintaan) wadahBagan.removeAttribute('aria-busy'); });
        }

        [lowongan, unit, jabatan].forEach((el) => el.addEventListener('change', muatBagan));
        document.querySelectorAll('input[name="posisi"]').forEach((r) => r.addEventListener('change', muatBagan));
        document.querySelectorAll('input[name="jenis"]').forEach((r) => r.addEventListener('change', tulisNama));
        pegawai.addEventListener('change', tulisNama);
        namaLuar.addEventListener('input', tulisNama);
        muatBagan();

        // ================= Pratinjau kandidat (kolom kanan) =================
        const form = document.getElementById('formKandidat');
        const kartu = document.querySelector('[data-pratinjau-kandidat]');
        const pv = (k) => kartu.querySelector('[data-pv="' + k + '"]');
        const avatar = kartu.querySelector('[data-pv-avatar]');
        const lokasi = document.getElementById('f-lokasi-kerja-tujuan');
        const pengusul = document.getElementById('pengusul');
        const NADA = ['primary', 'info', 'success', 'warning', 'neutral', 'danger'];
        // Inisial & warna avatar sama dengan komponen x-avatar (crc32 nama sebelum koma).
        const TABEL_CRC = Array.from({ length: 256 }, (_, n) => {
            let c = n;
            for (let k = 0; k < 8; k++) c = c & 1 ? 0xEDB88320 ^ (c >>> 1) : c >>> 1;
            return c >>> 0;
        });
        const crc32 = (s) => {
            let c = 0xFFFFFFFF;
            new TextEncoder().encode(s).forEach((b) => { c = TABEL_CRC[(c ^ b) & 0xFF] ^ (c >>> 8); });
            return (c ^ 0xFFFFFFFF) >>> 0;
        };
        function aturAvatar(nama) {
            const bersih = (nama || '').split(',')[0].trim();
            const kata = bersih.replace(/[^\p{L}\s]/gu, '').split(/\s+/).filter(Boolean);
            const inisial = ((kata[0] || '?').charAt(0) + (kata[1] || '').charAt(0)).toUpperCase();
            avatar.querySelector('.hr-avatar-inisial').textContent = inisial;
            NADA.forEach((n) => avatar.classList.remove('hr-tone-' + n));
            avatar.classList.add('hr-tone-' + (bersih ? NADA[crc32(bersih) % 6] : 'neutral'));
            avatar.setAttribute('aria-label', bersih || 'Kandidat belum dipilih');
        }
        function isi(k, nilai, kosong) {
            pv(k).textContent = nilai || kosong;
            pv(k).classList.toggle('is-kosong', !nilai);
        }
        function perbaruiPratinjau() {
            const jenis = document.querySelector('input[name="jenis"]:checked').value;
            const opsi = opsiPegawai();
            const nama = jenis === 'pegawai' ? (opsi ? opsi.dataset.nama : '') : namaLuar.value.trim();
            isi('nama', nama, jenis === 'pegawai' ? 'Pegawai belum dipilih' : 'Nama calon belum diisi');
            pv('sub').textContent = jenis === 'pegawai' ? 'Pegawai yayasan (mutasi/promosi)' : (form.elements.email.value.trim() || 'Calon pegawai baru');
            aturAvatar(nama);
            kartu.querySelectorAll('[data-pv-untuk]').forEach((b) => { b.hidden = b.dataset.pvUntuk !== jenis; });
            isi('nip', opsi && opsi.dataset.nip, '-');
            isi('jabatan', opsi && opsi.dataset.jabatan, '-');
            isi('unit', opsi && opsi.dataset.unit, '-');
            isi('hp', form.elements.no_hp.value.trim(), '-');

            const posisi = document.querySelector('input[name="posisi"]:checked').value;
            const lw = posisi === 'lowongan' && lowongan.value ? lowongan.selectedOptions[0] : null;
            if (posisi === 'lowongan') {
                isi('posisi', lw && lw.dataset.judul, 'Lowongan belum dipilih');
                isi('tujuan', lw && lw.dataset.unit, '-');
            } else {
                isi('posisi', jabatan.value, 'Jabatan belum dipilih');
                isi('tujuan', unit.value ? unit.value + (lokasi.value ? ' · ' + lokasi.value : '') : '', '-');
            }
            isi('pengusul', pengusul.value ? pengusul.selectedOptions[0].text.trim() : '', 'Tidak ada');
        }
        form.addEventListener('input', perbaruiPratinjau);
        form.addEventListener('change', perbaruiPratinjau);
        perbaruiPratinjau();
    });
</script>
@include('partials.lokasi-ikut-jabatan')
@include('admin.pengajuan-formasi._bagan-statis-aset')
@endsection
