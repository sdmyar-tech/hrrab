@extends('layouts.admin.app')

{{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
@section('galat-di-halaman', '1')

@section('content')
@php
    $mutasi = $jenis === 'mutasi';
    $hariIni = now()->format('Y-m-d');
    // Dari halaman Mutasi & Rangkap: pegawai dipilih lewat dropdown di form ini; tujuan setelah simpan/batal tetap di modul ini.
    $urlJenis = fn (string $j) => $dariDaftar
        ? route('admin.penugasan.tambah', array_filter(['pegawai' => $pegawai?->id, 'jenis' => $j]))
        : route('admin.penugasan.create', ['pegawai' => $pegawai, 'jenis' => $j]);
    $urlKembali = $dariDaftar ? route('admin.penugasan.index') : route('admin.pegawai.show', ['pegawai' => $pegawai, 'tab' => 'penugasan']);
    $units = config('kepegawaian.unit_kerja');
    $judul = $mutasi ? 'Mutasi Pegawai' : 'Tambah Rangkap Tugas';
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="$judul" eyebrow="Kepegawaian"
            :subtitle="$pegawai ? $pegawai->nama_lengkap . ' · ' . ($pegawai->nip ? 'NIP ' . $pegawai->nip : 'NIP belum diisi') : 'Pilih pegawai, lalu isi data penugasannya.'"
            :back="$urlKembali" :back-label="$dariDaftar ? 'Mutasi & Rangkap' : 'Detail Pegawai'" />

        @if ($errors->any())
            <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-3">
                Data belum tersimpan. Perbaiki isian berikut, lalu simpan lagi:
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <div class="row g-4">
            <div class="col-lg-8 d-grid gap-4 align-content-start">
                {{-- Langkah 1: jenis & pegawai. Di luar form simpan: memilih = memuat ulang form untuk pilihan itu. --}}
                <x-card>
                    <x-form-section title="Jenis Penugasan" icon="bi-signpost-split"
                        subtitle="Mutasi memindahkan bagian utama; rangkap tugas menambah tugas di bagian lain tanpa melepas bagian utama.">
                        <div class="col-12">
                            {{-- Dua pilihan sama lebar (tautan: berganti jenis memuat ulang form, pegawai terpilih tetap). --}}
                            <div class="pn-jenis hr-opsi-grup" role="group" aria-label="Jenis penugasan">
                                <a href="{{ $urlJenis('mutasi') }}" class="hr-opsi" @if ($mutasi) aria-current="page" @endif>
                                    <i class="bi bi-arrow-left-right hr-opsi-ikon" aria-hidden="true"></i>
                                    <span><strong>Mutasi</strong><small>Pindah bagian utama</small></span>
                                </a>
                                <a href="{{ $urlJenis('rangkap') }}" class="hr-opsi" @unless ($mutasi) aria-current="page" @endunless>
                                    <i class="bi bi-diagram-3 hr-opsi-ikon" aria-hidden="true"></i>
                                    <span><strong>Rangkap Tugas</strong><small>Tugas tambahan di bagian lain</small></span>
                                </a>
                            </div>
                        </div>
                    </x-form-section>

                    @if ($dariDaftar)
                        <x-form-section title="Pegawai" icon="bi-person" subtitle="Ketik nama, NIP, atau bagian untuk menyaring daftar.">
                            <div class="col-12">
                                <x-form-group label="Pegawai" for="pn-pegawai" required class="mb-0">
                                    @include('partials.pilih-pegawai', ['name' => 'pegawai', 'id' => 'pn-pegawai', 'terpilih' => $pegawai?->id, 'wajib' => true])
                                </x-form-group>
                            </div>
                        </x-form-section>
                    @endif
                </x-card>

                @if (! $pegawai)
                    <x-card>
                        <x-empty-state icon="bi-person-check" title="Pilih pegawai dulu" compact>
                            Isian {{ $mutasi ? 'mutasi' : 'rangkap tugas' }} tampil setelah pegawai dipilih di atas.
                        </x-empty-state>
                    </x-card>
                @else
                    {{-- Langkah 2: data penugasan (form simpan). --}}
                    <form data-fokus-awal action="{{ route('admin.penugasan.store', $pegawai) }}" method="POST" id="formPenugasan"
                        data-confirm="{{ $mutasi ? 'Simpan mutasi pegawai ini?' : 'Tambahkan rangkap tugas ini?' }}">
                        @csrf
                        <input type="hidden" name="jenis" value="{{ $jenis }}">
                        @if ($dariDaftar)<input type="hidden" name="asal" value="daftar">@endif

                        <x-card>
                            <x-form-section title="Penempatan" icon="bi-building"
                                :subtitle="$mutasi ? 'Bagian & jabatan baru pegawai.' : 'Bagian tempat tugas tambahan & jabatannya di sana.'">
                                <div class="col-md-6">
                                    <x-select name="unit_kerja" id="f-unit-kerja" :label="$mutasi ? 'Bagian Tujuan' : 'Bagian Rangkap'"
                                        :options="array_combine($units, $units)" placeholder="Pilih bagian" required field-class="mb-0" />
                                </div>
                                <div class="col-md-6">
                                    <x-input name="jabatan" id="f-jabatan" :label="$mutasi ? 'Jabatan Baru' : 'Jabatan di Bagian Rangkap'"
                                        :value="$mutasi ? $utama?->jabatan : ''" :required="$mutasi" :optional="! $mutasi"
                                        list="daftarJabatan" data-jabatan-unit="[name=unit_kerja]" autocomplete="off" maxlength="255"
                                        placeholder="Pilih jabatan, contoh: STAFF KEUANGAN"
                                        help="Jabatan di bagian terpilih tampil paling atas." field-class="mb-0" />
                                    <datalist id="daftarJabatan">
                                        @foreach (\App\Models\Jabatan::daftar() as $j)
                                            <option value="{{ $j }}">
                                        @endforeach
                                    </datalist>
                                </div>
                                <div class="col-12">
                                    <fieldset class="hr-field mb-0">
                                        <legend class="form-label required">Peran di Bagian</legend>
                                        <div class="hr-opsi-grup">
                                            <label class="hr-opsi" for="f-peran-anggota">
                                                <input class="form-check-input @error('peran') is-invalid @enderror" type="radio" name="peran" id="f-peran-anggota" value="anggota"
                                                    @checked(old('peran', 'anggota') === 'anggota') required>
                                                <span><strong>Anggota / Staff</strong><small>Bekerja di bagian ini tanpa wewenang menyetujui.</small></span>
                                            </label>
                                            <label class="hr-opsi" for="f-peran-kepala">
                                                <input class="form-check-input @error('peran') is-invalid @enderror" type="radio" name="peran" id="f-peran-kepala" value="kepala"
                                                    @checked(old('peran') === 'kepala')>
                                                <span><strong>Kepala Bagian</strong><small>Menyetujui cuti/lembur bagian ini &amp; menjadi pewawancara tahap 2.</small></span>
                                            </label>
                                        </div>
                                        @error('peran')<div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>@enderror
                                    </fieldset>
                                </div>
                            </x-form-section>

                            <x-form-section title="Masa Berlaku & SK" icon="bi-calendar-event"
                                :subtitle="$mutasi ? 'Tanggal ke depan = mutasi terjadwal, berlaku otomatis pada tanggal itu.' : 'Rangkap tugas bisa diakhiri kapan saja dari detail pegawai.'">
                                @if ($mutasi)
                                    <div class="col-md-6">
                                        <x-input type="date" name="tanggal_efektif" id="f-tanggal-efektif" label="Tanggal Efektif" :value="$hariIni" required
                                            help="Penugasan utama lama ditutup sehari sebelumnya." field-class="mb-0" />
                                    </div>
                                @else
                                    <div class="col-md-6">
                                        <x-input type="date" name="tanggal_mulai" id="f-tanggal-mulai" label="Tanggal Mulai" :value="$hariIni" required field-class="mb-0" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input type="date" name="tanggal_selesai" id="f-tanggal-selesai" label="Tanggal Selesai" optional
                                            help="Kosongkan bila belum ditentukan." field-class="mb-0" />
                                    </div>
                                @endif
                                <div class="col-md-6">
                                    <x-input name="no_sk" id="f-no-sk" label="No. SK" optional maxlength="100"
                                        placeholder="Nomor SK, contoh: 012/SK/YAR/2026" field-class="mb-0" />
                                </div>
                                <div class="col-12">
                                    <x-textarea name="keterangan" id="f-keterangan" label="Keterangan" optional rows="2" maxlength="1000"
                                        placeholder="Catatan singkat, contoh: menggantikan kepala bagian yang cuti panjang" field-class="mb-0" />
                                </div>
                            </x-form-section>

                            @if ($mutasi)
                                <x-form-section title="Pengajuan yang Masih Menunggu" icon="bi-hourglass-split"
                                    :subtitle="$jumlahPending ? 'Ada ' . $jumlahPending . ' pengajuan cuti/lembur pegawai ini yang belum diputuskan.' : 'Saat ini tidak ada pengajuan cuti/lembur yang menunggu; pilihan ini berlaku bila ada.'">
                                    <div class="col-12">
                                        <fieldset class="hr-field mb-0">
                                            <legend class="form-label required">Penanganan pengajuan cuti/lembur yang menunggu</legend>
                                            <div class="hr-opsi-grup">
                                                <label class="hr-opsi" for="pendingLanjut">
                                                    <input class="form-check-input @error('pengajuan_pending') is-invalid @enderror" type="radio" name="pengajuan_pending" id="pendingLanjut" value="lanjut"
                                                        @checked(old('pengajuan_pending', 'lanjut') === 'lanjut')>
                                                    <span><strong>Tetap diproses kepala bagian lama</strong><small>Disarankan — pengajuan dibuat saat masih di bagian lama.</small></span>
                                                </label>
                                                <label class="hr-opsi" for="pendingTolak">
                                                    <input class="form-check-input @error('pengajuan_pending') is-invalid @enderror" type="radio" name="pengajuan_pending" id="pendingTolak" value="tolak"
                                                        @checked(old('pengajuan_pending') === 'tolak')>
                                                    <span><strong>Tolak otomatis</strong><small>Pegawai mengajukan ulang di bagian baru.</small></span>
                                                </label>
                                            </div>
                                            @error('pengajuan_pending')<div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>@enderror
                                        </fieldset>
                                    </div>
                                </x-form-section>
                            @endif
                        </x-card>

                        <div class="hr-bilah-aksi mt-4">
                            <x-button variant="secondary" :href="$urlKembali">Batal</x-button>
                            <x-button type="submit" icon="bi-check-lg">{{ $mutasi ? 'Simpan Mutasi' : 'Simpan Rangkap' }}</x-button>
                        </div>
                    </form>
                @endif
            </div>

            <aside class="col-lg-4"><div class="hr-samping d-grid gap-4">
                <x-card title="Penugasan Saat Ini" icon="bi-diagram-3" :padding="false"
                    :subtitle="$pegawai ? null : 'Pilih pegawai untuk melihat penugasannya.'">
                    @if ($pegawai)
                        <ul class="list-group list-group-flush">
                            @forelse ($pegawai->penugasanAktif as $p)
                                <li class="list-group-item">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                        <span class="hr-sel-judul">{{ $p->unit_kerja }}</span>
                                        <span class="d-flex flex-wrap gap-1">
                                            <x-badge :tone="$p->is_utama ? 'primary' : 'info'">{{ $p->is_utama ? 'Utama' : 'Rangkap' }}</x-badge>
                                            @if ($p->isKepala())<x-badge tone="neutral" icon="bi-star">Kepala</x-badge>@endif
                                        </span>
                                    </div>
                                    <span class="hr-sel-sub">{{ $p->jabatan ?? '-' }} &middot; sejak {{ $p->tanggal_mulai->translatedFormat('d M Y') }}</span>
                                </li>
                            @empty
                                <li class="list-group-item hr-sel-sub">Belum ada penugasan tercatat.</li>
                            @endforelse
                        </ul>
                    @endif
                </x-card>

                <x-card :title="$mutasi ? 'Yang Terjadi Saat Mutasi' : 'Ketentuan Rangkap Tugas'" icon="bi-info-circle">
                    <ul class="hr-daftar-info">
                        @if ($mutasi)
                            <li><i class="bi bi-calendar-x" aria-hidden="true"></i>Penugasan utama lama ditutup sehari sebelum tanggal efektif; rangkap tugas tetap berjalan.</li>
                            <li><i class="bi bi-journal-text" aria-hidden="true"></i>Pengajuan yang sudah dibuat tetap tercatat di bagian lama (laporan tidak berubah).</li>
                            <li><i class="bi bi-person-exclamation" aria-hidden="true"></i>Bila pegawai ini kepala di bagian lama, sistem mengingatkan bagian yang jadi tanpa kepala.</li>
                        @else
                            <li><i class="bi bi-calendar-check" aria-hidden="true"></i>Cuti tetap disetujui kepala bagian utama; kepala bagian rangkap ikut melihat.</li>
                            <li><i class="bi bi-clock-history" aria-hidden="true"></i>Lembur: pegawai memilih bagian tempat lembur, disetujui kepala bagian itu.</li>
                            <li><i class="bi bi-person-up" aria-hidden="true"></i>Peran Kepala: akun Staff otomatis dinaikkan menjadi Kepala Bagian.</li>
                        @endif
                    </ul>
                </x-card>
            </div></aside>
        </div>
    </div>
</main>

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
