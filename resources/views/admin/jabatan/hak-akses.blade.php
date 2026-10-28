@extends(Auth::user()->panelLayout())

@section('content')
@php
    $grupHak = collect(\App\Models\Role::GRUP_HAK)->map(fn ($g) => [$g[0], array_values(array_intersect($g[1], array_keys($hakOpsi)))])->filter(fn ($g) => $g[1]);
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-badge"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem</p>
                    <h1 class="h3 mb-1">Hak Akses Jabatan</h1>
                    <p class="text-muted mb-0">Tambahan hak untuk pemegang jabatan tertentu di tiap unit — mis. Staff unit A boleh melihat absensi, Staff unit B tidak.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.jabatan.hak-akses.export'])
                <a href="{{ route('admin.role.index') }}" class="btn btn-outline-secondary"><i class="bi bi-shield-check"></i> Master Role</a>
            </div>
        </div>

        <div class="alert alert-info small d-flex gap-2">
            <i class="bi bi-info-circle-fill"></i>
            <div>
                Hak di sini <strong>ditambahkan</strong> di atas hak role akun (Master Role), tidak mengurangi.
                Berlaku untuk pegawai yang sedang memegang jabatan itu di unit tersebut, termasuk lewat rangkap tugas.
                Hak <em>Kelola sistem</em> hanya bisa diberikan lewat Master Role.
            </div>
        </div>

        <section class="panel">
            <form method="GET" class="panel-filter row g-2 align-items-center mb-3">
                <div class="col-md-4">
                    <select name="unit" class="form-select form-select-sm" aria-label="Unit" onchange="this.form.submit()">
                        <option value="">Semua unit</option>
                        @foreach ($unitOpsi as $id => $nama)
                            <option value="{{ $id }}" @selected((string) request('unit') === (string) $id)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                        <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="Cari jabatan…" aria-label="Cari jabatan">
                    </div>
                </div>
                <div class="col-md-auto">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="ada" value="1" id="hk-ada" @checked(request()->boolean('ada')) onchange="this.form.submit()">
                        <label class="form-check-label small" for="hk-ada">Hanya yang punya hak tambahan ({{ $jumlahBerhak }})</label>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0 table-mobile-cards">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Jabatan</th>
                            <th class="text-center">Pemegang</th>
                            <th>Hak akses tambahan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baris as $b)
                            <tr>
                                <td data-label="Unit">{{ $b->unit }}</td>
                                <td data-label="Jabatan" class="fw-semibold">{{ $b->jabatan }}</td>
                                <td data-label="Pemegang" class="text-md-center">{{ $b->pemegang }}</td>
                                <td data-label="Hak akses tambahan">
                                    @forelse ($b->hak as $h)
                                        <span class="badge text-bg-info me-1 mb-1">{{ $hakOpsi[$h][0] ?? $h }}</span>
                                    @empty
                                        <span class="text-muted small">Tidak ada (mengikuti role)</span>
                                    @endforelse
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalHakJabatan"
                                        data-url="{{ route('admin.jabatan.hak-akses.update', [$b->jabatan_id, $b->unit_id]) }}"
                                        data-judul="{{ $b->jabatan }} · {{ $b->unit }}" data-hak='@json($b->hak)'>
                                        <i class="bi bi-sliders"></i> Atur
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-person-badge fs-3 d-block mb-2"></i>
                                    @if (request()->hasAny(['unit', 'q', 'ada']))
                                        Tidak ada jabatan yang cocok dengan filter ini.
                                    @else
                                        Belum ada jabatan yang ditautkan ke unit. Atur unit jabatan di <a href="{{ route('admin.jabatan.index') }}">Master Jabatan</a>.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<div class="modal fade" id="modalHakJabatan" tabindex="-1" aria-labelledby="judulHakJabatan" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="POST" id="formHakJabatan">
            @csrf @method('PUT')
            <div class="modal-header">
                <h2 class="modal-title" id="judulHakJabatan"><i class="bi bi-sliders me-1"></i>Hak akses · <span data-judul></span></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Centang hak yang <strong>ditambahkan</strong> untuk pemegang jabatan ini di unit ini. Kosongkan semua untuk mengikuti role saja.</p>
                @foreach ($grupHak as $judul => [$ikon, $daftar])
                    <fieldset class="mb-3">
                        <legend class="fw-semibold small mb-2"><i class="bi {{ $ikon }} me-1"></i>{{ $judul }}</legend>
                        @foreach ($daftar as $h)
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="hak_akses[]" value="{{ $h }}" id="hk-{{ $h }}">
                                <label class="form-check-label" for="hk-{{ $h }}">
                                    {{ $hakOpsi[$h][0] }}
                                    <span class="d-block small text-muted">{{ $hakOpsi[$h][1] }}</span>
                                </label>
                            </div>
                        @endforeach
                    </fieldset>
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('modalHakJabatan');
        const form = document.getElementById('formHakJabatan');
        modal.addEventListener('show.bs.modal', function (e) {
            const t = e.relatedTarget;
            const hak = JSON.parse(t.dataset.hak || '[]');
            form.action = t.dataset.url;
            modal.querySelector('[data-judul]').textContent = t.dataset.judul;
            form.querySelectorAll('input[name="hak_akses[]"]').forEach(function (c) { c.checked = hak.includes(c.value); });
        });
    });
</script>
@endsection
