@extends('layouts.admin.app')

{{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
@section('galat-di-halaman', '1')

@section('content')
@php
    $units = config('kepegawaian.unit_kerja');
    $daftarJabatan = \App\Models\Jabatan::daftar();
    $urlKembali = route('admin.pegawai.show', ['pegawai' => $penugasan->data_pegawai_id, 'tab' => 'penugasan']);
    $pg = $penugasan->dataPegawai;
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="'Ubah ' . $penugasan->jenisLabel() . ' Langsung'" eyebrow="Kepegawaian"
            :subtitle="($pg?->nama_lengkap ?? 'Pegawai') . ' · koreksi data tanpa aturan mutasi/rangkap.'"
            :back="$urlKembali" back-label="Detail Pegawai">
            <x-status-badge :badge="$penugasan->statusBadge()" size="lg">{{ $penugasan->statusLabel() }}</x-status-badge>
        </x-page-header>

        @if ($errors->any())
            <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-3">
                Data belum tersimpan. Perbaiki isian berikut, lalu simpan lagi:
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <x-alert tone="warning" title="Mode Admin" class="mb-4">
            Perubahan disimpan langsung ke baris penugasan ini tanpa aturan mutasi/rangkap; penugasan lain tidak ikut ditutup.
            Untuk memindahkan pegawai, pakai <a href="{{ route('admin.penugasan.create', ['pegawai' => $penugasan->data_pegawai_id, 'jenis' => 'mutasi']) }}">Mutasi</a>.
        </x-alert>

        <div class="row g-4">
            <div class="col-lg-8">
                <form action="{{ route('admin.penugasan.update', $penugasan) }}" method="POST" id="formUbahPenugasan" data-confirm="Simpan perubahan penugasan ini?">
                    @csrf
                    @method('PUT')

                    <x-card>
                        <x-form-section title="Penempatan" icon="bi-building" subtitle="Bagian, jabatan, dan peran pada penugasan ini.">
                            <div class="col-md-6">
                                <x-select name="unit_kerja" id="f-unit-kerja" label="Bagian" :options="array_combine($units, $units)"
                                    :selected="$penugasan->unit_kerja" required field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-select name="jabatan" id="f-jabatan" label="Jabatan" optional :options="array_combine($daftarJabatan, $daftarJabatan)"
                                    :selected="$penugasan->jabatan" placeholder="Tanpa jabatan" data-jabatan-unit="[name=unit_kerja]" data-jabatan-bebas
                                    help="Jabatan di bagian terpilih tampil paling atas." field-class="mb-0">
                                    {{-- Jabatan lama yang sudah tidak ada di Master Jabatan tetap bisa dipertahankan. --}}
                                    @if ($penugasan->jabatan && ! in_array($penugasan->jabatan, $daftarJabatan, true))
                                        <option value="{{ $penugasan->jabatan }}" @selected(old('jabatan', $penugasan->jabatan) === $penugasan->jabatan)>{{ $penugasan->jabatan }} (nilai saat ini)</option>
                                    @endif
                                </x-select>
                            </div>
                            <div class="col-12">
                                <fieldset class="hr-field mb-0">
                                    <legend class="form-label required">Peran</legend>
                                    <div class="hr-opsi-grup">
                                        <label class="hr-opsi" for="f-peran-anggota">
                                            <input class="form-check-input @error('peran') is-invalid @enderror" type="radio" name="peran" id="f-peran-anggota" value="anggota"
                                                @checked(old('peran', $penugasan->peran) === 'anggota') required>
                                            <span><strong>Anggota</strong><small>Bekerja di bagian ini tanpa wewenang menyetujui.</small></span>
                                        </label>
                                        <label class="hr-opsi" for="f-peran-kepala">
                                            <input class="form-check-input @error('peran') is-invalid @enderror" type="radio" name="peran" id="f-peran-kepala" value="kepala"
                                                @checked(old('peran', $penugasan->peran) === 'kepala')>
                                            <span><strong>Kepala</strong><small>Menyetujui cuti/lembur bagian ini &amp; menjadi pewawancara tahap 2.</small></span>
                                        </label>
                                    </div>
                                    @error('peran')<div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>@enderror
                                </fieldset>
                            </div>
                        </x-form-section>

                        <x-form-section title="Masa Berlaku & SK" icon="bi-calendar-event" subtitle="Tanggal berlakunya penugasan dan dasar suratnya.">
                            <div class="col-md-6">
                                <x-input type="date" name="tanggal_mulai" id="f-tanggal-mulai" label="Tanggal Mulai"
                                    :value="$penugasan->tanggal_mulai->format('Y-m-d')" required field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input type="date" name="tanggal_selesai" id="f-tanggal-selesai" label="Tanggal Selesai" optional
                                    :value="$penugasan->tanggal_selesai?->format('Y-m-d')" help="Kosongkan bila masih berlaku." field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="no_sk" id="f-no-sk" label="No. SK" optional maxlength="100" :value="$penugasan->no_sk"
                                    placeholder="Nomor SK, contoh: 012/SK/YAR/2026" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input name="keterangan" id="f-keterangan" label="Keterangan" optional maxlength="1000" :value="$penugasan->keterangan"
                                    placeholder="Catatan, contoh: koreksi sesuai SK" field-class="mb-0" />
                            </div>
                        </x-form-section>
                    </x-card>

                    <div class="hr-bilah-aksi mt-4">
                        <x-button variant="secondary" :href="$urlKembali">Batal</x-button>
                        <x-button type="submit" icon="bi-check-lg">Simpan Perubahan</x-button>
                    </div>
                </form>
            </div>

            <aside class="col-lg-4"><div class="hr-samping">
                <x-card title="Data Tersimpan" icon="bi-clock-history" subtitle="Nilai sebelum diubah, untuk pembanding.">
                    <x-info-list :items="[
                        'Pegawai' => $pg?->nama_lengkap,
                        'Jenis' => $penugasan->jenisLabel() . ($penugasan->is_utama ? ' · utama' : ''),
                        'Bagian' => $penugasan->unit_kerja,
                        'Jabatan' => $penugasan->jabatan,
                        'Peran' => $penugasan->peranLabel(),
                        'Mulai' => $penugasan->tanggal_mulai->translatedFormat('d M Y'),
                        'Selesai' => $penugasan->tanggal_selesai?->translatedFormat('d M Y') ?? 'Masih berlaku',
                        'No. SK' => $penugasan->no_sk,
                    ]" />
                </x-card>
            </div></aside>
        </div>
    </div>
</main>
@include('partials.jabatan-ikut-unit')
@endsection
