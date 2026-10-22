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
        'riwayat_pendidikan' => ['judul' => 'Riwayat Pendidikan', 'ikon' => 'bi-mortarboard', 'data' => old('riwayat_pendidikan', $pendidikanDefault), 'kosong' => 'Belum ada riwayat pendidikan.'],
        'riwayat_pekerjaan' => ['judul' => 'Riwayat Pekerjaan', 'ikon' => 'bi-briefcase', 'data' => old('riwayat_pekerjaan', $pekerjaanDefault), 'kosong' => 'Belum ada riwayat pekerjaan.'],
        'sosial_media' => ['judul' => 'Sosial Media / Portofolio', 'ikon' => 'bi-link-45deg', 'data' => old('sosial_media', $sosmedDefault), 'kosong' => 'Belum ada link.'],
    ];
@endphp

@foreach ($bagian as $field => $b)
    <div class="repeater mt-4" data-repeater="{{ $field }}" data-existing='@json($b['data'])'>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 fw-bold mb-0"><i class="bi {{ $b['ikon'] }} me-1"></i>{{ $b['judul'] }} <span class="text-muted small fw-normal">(opsional)</span></h2>
            <button type="button" class="btn btn-outline-primary btn-sm repeater-add">
                <i class="bi bi-plus-lg"></i> Tambah
            </button>
        </div>
        @error($field)<div class="text-danger small mb-2">{{ $message }}</div>@enderror
        @foreach ($errors->getMessages() as $key => $pesan)
            @if (str_starts_with($key, $field . '.'))
                <div class="text-danger small">{{ $pesan[0] }}</div>
            @endif
        @endforeach
        <div class="repeater-rows"></div>
        <p class="text-muted small mb-0 repeater-empty">{{ $b['kosong'] }}</p>
    </div>
@endforeach

<template data-template="riwayat_pendidikan">
    <div class="row g-2 border rounded p-2 mb-2 repeater-row">
        <div class="col-md-2">
            <label class="form-label small mb-1">Jenjang</label>
            <select name="riwayat_pendidikan[__i__][jenjang]" class="form-select form-select-sm" required>
                <option value="">Pilih</option>
                @foreach (\App\Models\RiwayatPendidikanPelamar::JENJANG as $j)
                    <option value="{{ $j }}">{{ $j }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Nama Sekolah / Kampus</label>
            <input type="text" name="riwayat_pendidikan[__i__][nama_institusi]" class="form-control form-control-sm" maxlength="150" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Jurusan <span class="text-muted">(opsional)</span></label>
            <input type="text" name="riwayat_pendidikan[__i__][jurusan]" class="form-control form-control-sm" maxlength="150">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">IPK / Nilai</label>
            <input type="text" name="riwayat_pendidikan[__i__][nilai]" class="form-control form-control-sm" maxlength="20" placeholder="mis. 3.50">
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="button" class="btn btn-outline-danger btn-sm repeater-remove" title="Hapus"><i class="bi bi-trash"></i></button>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Tahun Masuk</label>
            <input type="number" name="riwayat_pendidikan[__i__][tahun_masuk]" class="form-control form-control-sm" min="1950" max="{{ now()->year + 10 }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Tahun Lulus <span class="text-muted">(kosongkan jika masih)</span></label>
            <input type="number" name="riwayat_pendidikan[__i__][tahun_lulus]" class="form-control form-control-sm" min="1950" max="{{ now()->year + 10 }}">
        </div>
    </div>
</template>

<template data-template="riwayat_pekerjaan">
    <div class="row g-2 border rounded p-2 mb-2 repeater-row">
        <div class="col-md-4">
            <label class="form-label small mb-1">Nama Perusahaan</label>
            <input type="text" name="riwayat_pekerjaan[__i__][nama_perusahaan]" class="form-control form-control-sm" maxlength="150" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Posisi</label>
            <input type="text" name="riwayat_pekerjaan[__i__][posisi]" class="form-control form-control-sm" maxlength="150" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Mulai</label>
            <input type="date" name="riwayat_pekerjaan[__i__][tanggal_mulai]" class="form-control form-control-sm" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Selesai <span class="text-muted">(kosongkan jika masih)</span></label>
            <input type="date" name="riwayat_pekerjaan[__i__][tanggal_selesai]" class="form-control form-control-sm">
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="button" class="btn btn-outline-danger btn-sm repeater-remove" title="Hapus"><i class="bi bi-trash"></i></button>
        </div>
        <div class="col-12">
            <label class="form-label small mb-1">Deskripsi Tugas <span class="text-muted">(opsional)</span></label>
            <textarea name="riwayat_pekerjaan[__i__][deskripsi]" rows="2" class="form-control form-control-sm" maxlength="1000"></textarea>
        </div>
    </div>
</template>

<template data-template="sosial_media">
    <div class="row g-2 align-items-end mb-2 repeater-row">
        <div class="col-md-4">
            <label class="form-label small mb-1">Platform</label>
            <input type="text" name="sosial_media[__i__][platform]" class="form-control form-control-sm" list="daftarPlatformSosmed"
                maxlength="50" placeholder="mis. LinkedIn" required>
        </div>
        <div class="col">
            <label class="form-label small mb-1">Link</label>
            <input type="url" name="sosial_media[__i__][url]" class="form-control form-control-sm" maxlength="255" placeholder="https://..." required>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-outline-danger btn-sm repeater-remove" title="Hapus"><i class="bi bi-trash"></i></button>
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

            function tambah(data) {
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
            }

            let existing = [];
            try { existing = JSON.parse(box.dataset.existing || '[]'); } catch (e) {}
            (Array.isArray(existing) ? existing : Object.values(existing)).forEach(tambah);

            box.querySelector('.repeater-add').addEventListener('click', function () { tambah(); });
            perbaruiKosong();
        });
    });
</script>
