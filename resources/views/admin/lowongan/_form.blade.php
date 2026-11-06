{{--
    Isi form lowongan (Buat & Ubah) — Batch 6, dipasang di dalam <form id="formLowongan">:
    kiri satu kartu berisi bagian form (2 kolom di desktop), kanan pratinjau tampilan pelamar (lengket, berubah saat mengetik),
    lalu bilah aksi lengket. Param: $lowongan (opsional; tersimpan → mode Ubah). Nama isian sama dengan versi lama.
    Form terpisah close-form / reopen-form (halaman Ubah) berada DI LUAR form utama; tombolnya di sini memakai atribut form.
--}}
@php
    $ubah = isset($lowongan) && $lowongan->exists;
    $isAdmin = auth()->user()->can('sistem.override');
    $status = $ubah ? $lowongan->status : \App\Models\Lowongan::STATUS_DRAFT;
    $statusInfo = [
        \App\Models\Lowongan::STATUS_DRAFT => ['Draf', 'neutral', 'bi-pencil-square'],
        \App\Models\Lowongan::STATUS_DIPUBLIKASIKAN => ['Dipublikasikan', 'success', 'bi-megaphone'],
        \App\Models\Lowongan::STATUS_DITUTUP => ['Ditutup', 'danger', 'bi-lock'],
    ];
    [$labelStatus, $toneStatus, $ikonStatus] = $statusInfo[$status] ?? $statusInfo[\App\Models\Lowongan::STATUS_DRAFT];
    $opsi = fn (array $daftar) => array_combine($daftar, $daftar);
    $tipeOpsi = ['Full Time', 'Part Time', 'Kontrak', 'Magang'];
    $tipe = (string) old('tipe_kerja', $lowongan->tipe_kerja ?? '');
    $lokasi = (string) old('lokasi_kerja', $lowongan->lokasi_kerja ?? '');
    $tutup = old('tanggal_tutup', $ubah ? optional($lowongan->tanggal_tutup)->format('Y-m-d') : now()->format('Y-m-d'));

    // Nilai awal pratinjau (sama dengan isian; selanjutnya diperbarui skrip saat mengetik).
    $pv = [
        'judul' => (string) old('judul', $lowongan->judul ?? ''),
        'unit' => (string) old('unit_kerja', $lowongan->unit_kerja ?? ''),
        'jabatan' => (string) old('jabatan', $lowongan->jabatan ?? ''),
        'jumlah' => (int) old('jumlah_dibutuhkan', $lowongan->jumlah_dibutuhkan ?? 1),
        'deskripsi' => (string) old('deskripsi', $lowongan->deskripsi ?? ''),
        'kualifikasi' => (string) old('kualifikasi', $lowongan->kualifikasi ?? ''),
    ];
    $tglTutup = $tutup ? rescue(fn () => \Illuminate\Support\Carbon::parse($tutup), null, false) : null;
    $sisaHari = $tglTutup ? (int) now()->startOfDay()->diffInDays($tglTutup->copy()->startOfDay(), false) : null;
@endphp
<div class="row g-4">
    <div class="col-xl-8">
        <x-card>
            {{-- ================= 1. Info dasar ================= --}}
            <x-form-section id="bagian-info" class="hr-bagian" title="Info Dasar" icon="bi-briefcase"
                subtitle="Posisi yang dibuka dan tampil ke pelamar. Kolom bertanda * wajib diisi.">
                <div class="col-12">
                    <x-input name="judul" id="f-judul" label="Judul lowongan" :value="$lowongan->judul ?? ''" maxlength="150" required
                        placeholder="Nama posisi yang tampil ke pelamar, contoh: Guru Matematika SMP" />
                </div>
                <div class="col-md-6">
                    <x-select name="unit_kerja" id="f-unit" label="Bagian" :options="$opsi(config('kepegawaian.unit_kerja'))"
                        :selected="$lowongan->unit_kerja ?? ''" placeholder="Pilih bagian" required />
                </div>
                <div class="col-md-6">
                    {{-- Opsi disusun skrip partials.jabatan-ikut-unit: hanya jabatan di bagian terpilih (Admin: + jabatan lainnya). --}}
                    <x-select name="jabatan" id="f-jabatan" label="Jabatan" :options="$opsi(\App\Models\Jabatan::daftar())"
                        :selected="$lowongan->jabatan ?? ''" placeholder="Pilih jabatan" required data-jabatan-unit="[name=unit_kerja]" :data-jabatan-bebas="$isAdmin ? '' : null"
                        help="Hanya jabatan di bagian terpilih." />
                </div>
                <div class="col-md-6">
                    <x-select name="tipe_kerja" id="f-tipe" label="Tipe kerja" placeholder="Pilih tipe kerja" optional>
                        <option value="" @selected($ubah && $tipe === '')>Tidak ditentukan</option>
                        @if ($tipe !== '' && ! in_array($tipe, $tipeOpsi, true))
                            <option value="{{ $tipe }}" selected>{{ $tipe }} (nilai saat ini)</option>
                        @endif
                        @foreach ($tipeOpsi as $t)
                            <option value="{{ $t }}" @selected($tipe === $t)>{{ $t }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="col-md-6">
                    {{-- Lokasi mengikuti bagian & jabatan (Master Jabatan → Atur lokasi); satu lokasi terisi otomatis. --}}
                    <x-select name="lokasi_kerja" id="f-lokasi" label="Lokasi kerja" placeholder="Pilih lokasi kerja" optional
                        data-lokasi-jabatan="[name=jabatan]" data-lokasi-unit="[name=unit_kerja]" :data-lokasi-bebas="$isAdmin ? '' : null">
                        <option value="" @selected($ubah && $lokasi === '')>Tidak ditentukan</option>
                        @foreach (config('kepegawaian.lokasi_kerja') as $l)
                            <option value="{{ $l }}" @selected($lokasi === $l)>{{ $l }}</option>
                        @endforeach
                    </x-select>
                </div>
            </x-form-section>

            {{-- ================= 2. Deskripsi & persyaratan ================= --}}
            <x-form-section id="bagian-deskripsi" class="hr-bagian" title="Deskripsi & Persyaratan" icon="bi-file-text"
                subtitle="Wajib diisi sebelum lowongan bisa dipublikasikan. Tulis satu poin per baris; baris baru tetap tampil di halaman karier.">
                <div class="col-12">
                    <x-textarea name="deskripsi" id="f-deskripsi" label="Deskripsi pekerjaan" rows="5" maxlength="5000" :value="$lowongan->deskripsi ?? ''"
                        placeholder="Tugas utama posisi ini, contoh: mengajar matematika kelas VII–IX dan menyusun bahan ajar" />
                </div>
                <div class="col-12">
                    <x-textarea name="kualifikasi" id="f-kualifikasi" label="Kualifikasi" rows="5" maxlength="5000" :value="$lowongan->kualifikasi ?? ''"
                        placeholder="Syarat pelamar, contoh: S1 Pendidikan Matematika, pengalaman mengajar minimal 1 tahun" />
                </div>
            </x-form-section>

            {{-- ================= 3. Kebutuhan & batas waktu (belum ada kolom gaji di backend) ================= --}}
            <x-form-section id="bagian-kebutuhan" class="hr-bagian" title="Kebutuhan & Batas Waktu" icon="bi-calendar-event"
                subtitle="Jumlah orang yang direkrut dan hari terakhir lowongan menerima lamaran.">
                <div class="col-md-6">
                    <x-form-group label="Jumlah dibutuhkan" for="f-jumlah" name="jumlah_dibutuhkan" required class="mb-0"
                        :help="$ubah ? 'Sudah terisi ' . $lowongan->jumlah_terisi . ' orang. Isi 1–50 orang.' : 'Isi 1–50 orang.'">
                        <div class="input-group">
                            <input type="number" name="jumlah_dibutuhkan" id="f-jumlah" class="form-control @error('jumlah_dibutuhkan') is-invalid @enderror"
                                value="{{ old('jumlah_dibutuhkan', $lowongan->jumlah_dibutuhkan ?? 1) }}" min="1" max="50" step="1" inputmode="numeric" required
                                aria-describedby="{{ $errors->has('jumlah_dibutuhkan') ? 'f-jumlah-galat' : 'f-jumlah-bantuan' }}"
                                @error('jumlah_dibutuhkan') aria-invalid="true" @enderror>
                            <span class="input-group-text">orang</span>
                        </div>
                    </x-form-group>
                </div>
                <div class="col-md-6">
                    {{-- Bukan Admin: tanggal baru minimal hari ini (aturan server); saat mengubah, tanggal lama yang sudah lewat tetap boleh dibiarkan. --}}
                    <x-input type="date" name="tanggal_tutup" id="f-tutup" label="Tanggal tutup" optional :value="$tutup"
                        :min="(! $ubah && ! $isAdmin) ? now()->toDateString() : null"
                        help="Hari terakhir menerima lamaran; setelahnya lowongan otomatis ditutup. Kosongkan bila tidak dibatasi." />
                </div>
            </x-form-section>

            {{-- ================= 4. Status & publikasi ================= --}}
            <x-form-section id="bagian-status" class="hr-bagian" title="Status & Publikasi" icon="bi-megaphone"
                subtitle="Hanya lowongan berstatus Dipublikasikan yang tampil di halaman karier dan menerima lamaran.">
                <div class="col-12 d-flex flex-wrap align-items-center gap-2">
                    <x-badge :tone="$toneStatus" :icon="$ikonStatus" size="lg">{{ $labelStatus }}</x-badge>
                    <span class="text-muted small">
                        @if (! $ubah)
                            Lowongan baru disimpan sebagai draf. Publikasikan dari daftar lowongan atau halaman Ubah setelah deskripsi &amp; kualifikasi lengkap.
                        @elseif ($status === \App\Models\Lowongan::STATUS_DRAFT)
                            Belum tampil ke pelamar. Tekan <strong>Simpan &amp; Publikasikan</strong> di bawah untuk menayangkannya.
                        @elseif ($status === \App\Models\Lowongan::STATUS_DIPUBLIKASIKAN)
                            Sedang tampil di halaman karier dan menerima lamaran.
                        @else
                            Tidak menerima lamaran baru. Buka kembali dengan tanggal tutup baru bila kuota belum terpenuhi.
                        @endif
                    </span>
                </div>
                @if ($ubah)
                    <div class="col-12">
                        <x-info-list :items="[
                            'Dipublikasikan' => $lowongan->dipublikasikan_pada ? $lowongan->dipublikasikan_pada->translatedFormat('d M Y H:i') . ($lowongan->dipublikasikanOleh ? ' · ' . $lowongan->dipublikasikanOleh->name : '') : 'Belum pernah',
                            'Pelamar' => $lowongan->lamarans()->count() . ' orang · terisi ' . $lowongan->jumlah_terisi . '/' . $lowongan->jumlah_dibutuhkan,
                            'Sumber' => $lowongan->pengajuan_formasi_id ? 'Pengajuan formasi #' . $lowongan->pengajuan_formasi_id : 'Dibuat manual',
                        ]" />
                    </div>
                    @if ($status === \App\Models\Lowongan::STATUS_DIPUBLIKASIKAN)
                        <div class="col-12">
                            <x-button variant="danger-outline" size="sm" icon="bi-lock" type="submit" form="close-form">Tutup Lowongan</x-button>
                        </div>
                    @elseif ($status === \App\Models\Lowongan::STATUS_DITUTUP)
                        <div class="col-12">@include('admin.lowongan._reopen-button', ['lowongan' => $lowongan, 'form' => 'reopen-form'])</div>
                    @endif
                @endif
            </x-form-section>
        </x-card>
    </div>

    {{-- ================= Kanan: pratinjau seperti halaman karier (public/lowongan/show) ================= --}}
    <div class="col-xl-4">
        <div class="hr-detail-samping d-grid gap-4">
            <x-card title="Pratinjau" icon="bi-eye" subtitle="Ringkasan yang dilihat pelamar di halaman karier. Berubah saat Anda mengetik."
                class="rk-pratinjau" data-pratinjau-lowongan aria-live="polite">
                <div class="d-flex flex-wrap gap-1 mb-3">
                    <x-badge tone="primary" icon="bi-diagram-3" :hidden="$pv['unit'] === ''"><span data-pv="unit">{{ $pv['unit'] }}</span></x-badge>
                    <x-badge tone="neutral" icon="bi-clock" :hidden="$tipe === ''"><span data-pv="tipe">{{ $tipe }}</span></x-badge>
                    <x-badge tone="neutral" icon="bi-geo-alt" :hidden="$lokasi === ''"><span data-pv="lokasi">{{ $lokasi }}</span></x-badge>
                    <x-badge tone="warning" icon="bi-hourglass-split" :hidden="$sisaHari === null || $sisaHari < 0 || $sisaHari > 7">
                        <span data-pv="sisa">{{ $sisaHari === 0 ? 'Tutup hari ini' : $sisaHari . ' hari lagi' }}</span>
                    </x-badge>
                </div>
                <p @class(['rk-pratinjau-judul', 'is-kosong' => $pv['judul'] === '']) data-pv="judul">{{ $pv['judul'] !== '' ? $pv['judul'] : 'Judul lowongan' }}</p>
                <p class="rk-pratinjau-sub">Yayasan Abdurrab · <span data-pv="jabatan" @class(['is-kosong' => $pv['jabatan'] === ''])>{{ $pv['jabatan'] !== '' ? $pv['jabatan'] : 'jabatan belum dipilih' }}</span></p>
                <dl class="dash-dl mb-3">
                    <div><dt><i class="bi bi-calendar-event me-1" aria-hidden="true"></i>Batas lamaran</dt>
                        <dd data-pv="tutup">{{ $tglTutup ? $tglTutup->translatedFormat('d M Y') . ($sisaHari < 0 ? ' (sudah lewat)' : '') : 'Tanpa batas' }}</dd></div>
                    <div><dt><i class="bi bi-people me-1" aria-hidden="true"></i>Kebutuhan</dt><dd><span data-pv="jumlah">{{ $pv['jumlah'] }}</span> orang</dd></div>
                </dl>
                <p class="rk-pratinjau-label"><i class="bi bi-file-text" aria-hidden="true"></i>Deskripsi Pekerjaan</p>
                <p @class(['rk-pratinjau-teks', 'is-kosong' => $pv['deskripsi'] === '']) data-pv="deskripsi">{{ $pv['deskripsi'] !== '' ? $pv['deskripsi'] : 'Belum diisi — wajib sebelum dipublikasikan.' }}</p>
                <p class="rk-pratinjau-label"><i class="bi bi-patch-check" aria-hidden="true"></i>Kualifikasi</p>
                <p @class(['rk-pratinjau-teks mb-0', 'is-kosong' => $pv['kualifikasi'] === '']) data-pv="kualifikasi">{{ $pv['kualifikasi'] !== '' ? $pv['kualifikasi'] : 'Belum diisi — wajib sebelum dipublikasikan.' }}</p>
                <x-slot:footer>
                    @if ($ubah && $status !== \App\Models\Lowongan::STATUS_DRAFT)
                        <a href="{{ route('lowongan.show', $lowongan) }}" target="_blank" rel="noopener" class="small">
                            <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Buka halaman karier (tab baru)
                        </a>
                    @else
                        <span class="small text-muted"><i class="bi bi-eye-slash me-1" aria-hidden="true"></i>Draf belum tampil di halaman karier.</span>
                    @endif
                </x-slot:footer>
            </x-card>
        </div>
    </div>
</div>

{{-- Bilah aksi lengket: Batal di kiri, simpan di kanan (utama paling kanan). --}}
<div class="hr-bilah-aksi mt-4">
    <x-button variant="secondary" :href="route('admin.lowongan.index')">Batal</x-button>
    <div class="d-flex flex-wrap justify-content-end gap-2 ms-auto">
        @if (! $ubah)
            <x-button type="submit" icon="bi-save">Simpan Draf</x-button>
        @elseif ($status === \App\Models\Lowongan::STATUS_DRAFT)
            <x-button type="submit" variant="outline" icon="bi-save" name="submit_action" value="save">Simpan Draf</x-button>
            <x-button type="submit" icon="bi-megaphone" name="submit_action" value="publish"
                data-pesan-konfirmasi="Simpan & publikasikan lowongan ini? Lowongan langsung tampil di halaman karier dan menerima lamaran.">Simpan &amp; Publikasikan</x-button>
        @else
            <x-button type="submit" icon="bi-check-lg" name="submit_action" value="save">Simpan Perubahan</x-button>
        @endif
    </div>
</div>

@include('partials.jabatan-ikut-unit')
@include('partials.lokasi-ikut-jabatan')

@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formLowongan');
        const kartu = document.querySelector('[data-pratinjau-lowongan]');
        if (!form || !kartu) return;

        // Pesan konfirmasi mengikuti tombol yang menekan (Simpan vs Simpan & Publikasikan).
        // Fase capture: berjalan sebelum dialog konfirmasi global (form[data-confirm]) membaca pesannya.
        const pesanAsal = form.dataset.confirm;
        form.addEventListener('submit', function (e) {
            form.dataset.confirm = (e.submitter && e.submitter.dataset.pesanKonfirmasi) || pesanAsal;
        }, true);

        // ---------- Pratinjau langsung ----------
        const pv = (k) => kartu.querySelector('[data-pv="' + k + '"]');
        const nilai = (nama) => ((form.elements[nama] || {}).value || '').trim();
        const formatTanggal = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        function teks(k, isi, kosong) {
            const el = pv(k);
            el.textContent = isi || kosong;
            el.classList.toggle('is-kosong', !isi);
        }
        function badge(k, isi) {
            pv(k).textContent = isi;
            pv(k).closest('.hr-badge').hidden = !isi;
        }
        function perbarui() {
            teks('judul', nilai('judul'), 'Judul lowongan');
            teks('jabatan', nilai('jabatan'), 'jabatan belum dipilih');
            badge('unit', nilai('unit_kerja'));
            badge('tipe', nilai('tipe_kerja'));
            badge('lokasi', nilai('lokasi_kerja'));
            pv('jumlah').textContent = nilai('jumlah_dibutuhkan') || '0';
            teks('deskripsi', nilai('deskripsi'), 'Belum diisi — wajib sebelum dipublikasikan.');
            teks('kualifikasi', nilai('kualifikasi'), 'Belum diisi — wajib sebelum dipublikasikan.');

            // Batas lamaran + sisa hari (≤ 7 hari → badge kuning seperti di halaman karier).
            const t = nilai('tanggal_tutup');
            const bagian = t.split('-').map(Number);
            const tgl = bagian.length === 3 && bagian.every((n) => n > 0) ? new Date(bagian[0], bagian[1] - 1, bagian[2]) : null;
            const kini = new Date(); kini.setHours(0, 0, 0, 0);
            const sisa = tgl ? Math.round((tgl - kini) / 86400000) : null;
            pv('tutup').textContent = tgl ? formatTanggal.format(tgl) + (sisa < 0 ? ' (sudah lewat)' : '') : 'Tanpa batas';
            badge('sisa', sisa !== null && sisa >= 0 && sisa <= 7 ? (sisa === 0 ? 'Tutup hari ini' : sisa + ' hari lagi') : '');
        }
        form.addEventListener('input', perbarui);
        form.addEventListener('change', perbarui);
        perbarui();
    });
</script>
@endonce
