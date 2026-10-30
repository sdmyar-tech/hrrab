{{--
    Riwayat pendidikan, pekerjaan, dan sosial media pelamar (baris dinamis "Tambah" / hapus). Hanya dipakai pelamar/data-pelamar.
    Tiap bagian = satu x-card ber-id "bagian-<field>" (tujuan navigasi samping). Nama isian & logika repeater tidak berubah.
--}}
@php
    $pendidikanDefault = $pelamar?->riwayatPendidikan?->map(fn ($r) => $r->only(['jenjang', 'nama_institusi', 'jurusan', 'tahun_masuk', 'tahun_lulus', 'nilai']))->values() ?? [];
    $pekerjaanDefault = $pelamar?->riwayatPekerjaan?->map(fn ($r) => [
        'nama_perusahaan' => $r->nama_perusahaan,
        'posisi' => $r->posisi,
        'tanggal_mulai' => optional($r->tanggal_mulai)->format('Y-m-d'),
        'tanggal_selesai' => optional($r->tanggal_selesai)->format('Y-m-d'),
        'deskripsi' => $r->deskripsi,
    ])->values() ?? [];
    $sosmedDefault = $pelamar?->sosial_media ?? [];

    $bagian = [
        'riwayat_pendidikan' => ['judul' => 'Riwayat Pendidikan', 'sub' => 'Mulai dari pendidikan terakhir.', 'ikon' => 'bi-mortarboard', 'data' => old('riwayat_pendidikan', $pendidikanDefault), 'kosong' => 'Belum ada riwayat pendidikan.', 'tambah' => 'Tambah Pendidikan'],
        'riwayat_pekerjaan' => ['judul' => 'Riwayat Pekerjaan', 'sub' => 'Opsional — kosongkan bila belum pernah bekerja.', 'ikon' => 'bi-briefcase', 'data' => old('riwayat_pekerjaan', $pekerjaanDefault), 'kosong' => 'Belum ada riwayat pekerjaan.', 'tambah' => 'Tambah Pekerjaan'],
        'sosial_media' => ['judul' => 'Sosial Media / Portofolio', 'sub' => 'Opsional — LinkedIn, portofolio, atau media sosial profesional.', 'ikon' => 'bi-link-45deg', 'data' => old('sosial_media', $sosmedDefault), 'kosong' => 'Belum ada tautan.', 'tambah' => 'Tambah Tautan'],
    ];
@endphp

@foreach ($bagian as $field => $b)
    <x-card :id="'bagian-' . $field" :title="$b['judul']" :subtitle="$b['sub']" :icon="$b['ikon']" class="pelamar-bagian repeater" :data-repeater="$field" :data-existing="json_encode($b['data'])">
        <x-slot:actions>
            <x-button size="sm" variant="secondary" icon="bi-plus-lg" class="repeater-add">{{ $b['tambah'] }}</x-button>
        </x-slot:actions>
        @error($field)<div class="invalid-feedback d-block mb-2"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>@enderror
        @foreach ($errors->getMessages() as $key => $pesan)
            @if (str_starts_with($key, $field . '.'))
                <div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $pesan[0] }}</div>
            @endif
        @endforeach
        <div class="repeater-rows d-grid gap-2"></div>
        <div class="repeater-empty">
            <x-empty-state :icon="$b['ikon']" compact>{{ $b['kosong'] }}</x-empty-state>
        </div>
    </x-card>
@endforeach

<template data-template="riwayat_pendidikan">
    <div class="row g-2 pelamar-baris repeater-row">
        <div class="col-6 col-md-2">
            <label class="form-label">Jenjang</label>
            <select name="riwayat_pendidikan[__i__][jenjang]" class="form-select" required>
                <option value="">Pilih</option>
                @foreach (\App\Models\RiwayatPendidikanPelamar::JENJANG as $j)
                    <option value="{{ $j }}">{{ $j }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label">Nama Sekolah / Kampus</label>
            <input type="text" name="riwayat_pendidikan[__i__][nama_institusi]" class="form-control" maxlength="150" placeholder="Contoh: Universitas Abdurrab" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Jurusan <span class="hr-field-opsional">(opsional)</span></label>
            <input type="text" name="riwayat_pendidikan[__i__][jurusan]" class="form-control" maxlength="150" placeholder="Contoh: Manajemen">
        </div>
        <div class="col-md-3">
            <label class="form-label">IPK / Nilai</label>
            <input type="text" name="riwayat_pendidikan[__i__][nilai]" class="form-control" maxlength="20" placeholder="Contoh: 3.50">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Tahun Masuk</label>
            <input type="number" name="riwayat_pendidikan[__i__][tahun_masuk]" class="form-control" min="1950" max="{{ now()->year + 10 }}" placeholder="Contoh: 2018">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Tahun Lulus</label>
            <input type="number" name="riwayat_pendidikan[__i__][tahun_lulus]" class="form-control" min="1950" max="{{ now()->year + 10 }}" placeholder="Kosongkan bila masih">
        </div>
        <div class="col-md-6 d-flex align-items-end justify-content-end">
            <button type="button" class="btn btn-ghost btn-sm text-danger repeater-remove"><i class="bi bi-trash" aria-hidden="true"></i>Hapus baris</button>
        </div>
    </div>
</template>

<template data-template="riwayat_pekerjaan">
    <div class="row g-2 pelamar-baris repeater-row">
        <div class="col-md-6">
            <label class="form-label">Nama Perusahaan / Instansi</label>
            <input type="text" name="riwayat_pekerjaan[__i__][nama_perusahaan]" class="form-control" maxlength="150" placeholder="Contoh: PT Maju Jaya" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Posisi</label>
            <input type="text" name="riwayat_pekerjaan[__i__][posisi]" class="form-control" maxlength="150" placeholder="Contoh: Staf Administrasi" required>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Mulai</label>
            <input type="date" name="riwayat_pekerjaan[__i__][tanggal_mulai]" class="form-control" required>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Selesai</label>
            <input type="date" name="riwayat_pekerjaan[__i__][tanggal_selesai]" class="form-control" aria-describedby="bantuan-selesai-__i__">
            <div class="form-text" id="bantuan-selesai-__i__">Kosongkan bila masih bekerja.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Deskripsi Tugas <span class="hr-field-opsional">(opsional)</span></label>
            <textarea name="riwayat_pekerjaan[__i__][deskripsi]" rows="2" class="form-control" maxlength="1000" placeholder="Contoh: mengelola arsip dan surat masuk"></textarea>
        </div>
        <div class="col-12 d-flex justify-content-end">
            <button type="button" class="btn btn-ghost btn-sm text-danger repeater-remove"><i class="bi bi-trash" aria-hidden="true"></i>Hapus baris</button>
        </div>
    </div>
</template>

<template data-template="sosial_media">
    <div class="row g-2 align-items-end pelamar-baris repeater-row">
        <div class="col-md-4">
            <label class="form-label">Platform</label>
            <input type="text" name="sosial_media[__i__][platform]" class="form-control" list="daftarPlatformSosmed"
                maxlength="50" placeholder="Contoh: LinkedIn" required>
        </div>
        <div class="col">
            <label class="form-label">Tautan</label>
            <input type="url" name="sosial_media[__i__][url]" class="form-control" maxlength="255" placeholder="Contoh: https://linkedin.com/in/nama" required>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-ghost btn-ikon text-danger repeater-remove" title="Hapus tautan" aria-label="Hapus tautan"><i class="bi bi-trash" aria-hidden="true"></i></button>
        </div>
    </div>
</template>

<datalist id="daftarPlatformSosmed">
    @foreach (\App\Models\DataPelamar::PLATFORM_SOSMED as $platform)
        <option value="{{ $platform }}">
    @endforeach
</datalist>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-repeater]').forEach(function (box) {
            const field = box.dataset.repeater;
            const template = document.querySelector('template[data-template="' + field + '"]');
            const rows = box.querySelector('.repeater-rows');
            const kosong = box.querySelector('.repeater-empty');
            let index = 0;

            function perbaruiKosong() {
                kosong.hidden = rows.children.length > 0;
            }

            function tambah(data, fokus) {
                const wadah = document.createElement('div');
                wadah.innerHTML = template.innerHTML.replaceAll('__i__', index++);
                const row = wadah.firstElementChild;

                Object.entries(data || {}).forEach(function ([kunci, nilai]) {
                    const input = row.querySelector('[name$="[' + kunci + ']"]');
                    if (input && nilai !== null) input.value = nilai;
                });

                row.querySelector('.repeater-remove').addEventListener('click', function () {
                    row.remove();
                    perbaruiKosong();
                });

                rows.appendChild(row);
                perbaruiKosong();
                if (fokus) row.querySelector('input, select, textarea')?.focus();
            }

            let existing = [];
            try { existing = JSON.parse(box.dataset.existing || '[]'); } catch (e) {}
            (Array.isArray(existing) ? existing : Object.values(existing)).forEach((d) => tambah(d, false));

            box.querySelector('.repeater-add').addEventListener('click', function () { tambah(null, true); });
            perbaruiKosong();
        });
    });
</script>
