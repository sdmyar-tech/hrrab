@extends($layout)

@section('content')
@php
    $target = old('target', 'belum_lengkap');
    $templateLengkapi = [
        'judul' => 'Lengkapi data pegawai Anda',
        'pesan' => 'Data kepegawaian Anda belum lengkap. Mohon segera lengkapi lewat menu Pengkinian Data (atau hubungi Admin SDM) agar administrasi cuti, kontrak, dan BPJS tidak terhambat.',
    ];
@endphp
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-send"></i></span>
                <div>
                    <p class="eyebrow mb-1">Notifikasi</p>
                    <h1 class="h3 mb-1">Kirim Notifikasi</h1>
                    <p class="text-muted mb-0">Kirim pengingat atau tugas ke pegawai lain. Muncul di lonceng notifikasi penerima (dan notifikasi desktop bila diaktifkan).</p>
                </div>
            </div>
        </div>

        <form action="{{ route('notifikasi.kirim.store') }}" method="POST" data-confirm="Kirim notifikasi ini?">
            @csrf
            @if ($errors->any())
                <div class="alert alert-danger small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="row g-3">
                <div class="col-12 col-lg-7">
                    <section class="panel p-4 h-100">
                        <h2 class="h6 fw-bold mb-3">1. Penerima</h2>
                        @php
                            $opsi = [
                                'belum_lengkap' => ['Pegawai yang datanya belum lengkap', 'bi-person-vcard', $belumLengkap->count() . ' akun'],
                                'unit' => ['Per unit', 'bi-diagram-3', null],
                                'pegawai' => ['Pilih pegawai tertentu', 'bi-person-check', null],
                                'semua' => ['Semua pegawai dalam wewenang saya', 'bi-people', $pilihanPegawai->count() . ' akun'],
                            ];
                            if ($bisaPilihRole) { $opsi['role'] = ['Per role', 'bi-person-badge', null]; }
                        @endphp
                        <div class="list-group mb-3">
                            @foreach ($opsi as $val => [$label, $ikon, $info])
                                <label class="list-group-item d-flex align-items-center gap-2">
                                    <input class="form-check-input mt-0" type="radio" name="target" value="{{ $val }}" @checked($target === $val)>
                                    <i class="bi {{ $ikon }} text-muted"></i> <span class="flex-grow-1">{{ $label }}</span>
                                    @if ($info)<span class="badge text-bg-light border">{{ $info }}</span>@endif
                                </label>
                            @endforeach
                        </div>

                        <div data-target="belum_lengkap">
                            @if ($belumLengkap->isEmpty())
                                <div class="alert alert-success small mb-0">Semua akun dalam wewenang Anda sudah lengkap datanya.</div>
                            @else
                                <p class="small text-muted mb-2">Akan dikirim ke {{ $belumLengkap->count() }} akun berikut. Staff diarahkan langsung ke menu Pengkinian Data.</p>
                                <div class="border rounded" style="max-height:260px; overflow-y:auto;">
                                    <table class="table table-sm small mb-0 align-middle">
                                        <tbody>
                                            @foreach ($belumLengkap as $r)
                                                <tr>
                                                    {{-- Satu kolom agar tidak terpotong di kotak sempit (HP). --}}
                                                    <td>
                                                        <span class="fw-semibold">{{ $r['user']->name }}</span>
                                                        <span class="d-block text-muted">{{ $r['user']->roleLabel() }} &middot; {{ $r['user']->dataPegawai?->kepegawaian?->unit_kerja }}</span>
                                                        <span class="d-block text-danger-emphasis"><i class="bi bi-exclamation-circle me-1"></i>Kurang: {{ implode(', ', array_slice($r['kurang'], 0, 4)) }}{{ count($r['kurang']) > 4 ? ' +' . (count($r['kurang']) - 4) : '' }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <div data-target="unit">
                            <label class="form-label small">Unit</label>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" id="semua-unit" data-pilih-semua="unit[]">
                                <label class="form-check-label small fw-semibold" for="semua-unit">Pilih semua unit <span class="text-muted fw-normal" data-jumlah-terpilih></span></label>
                            </div>
                            <div class="border rounded p-2 row row-cols-2 g-1 mx-0" style="max-height:220px; overflow-y:auto;">
                                @foreach ($pilihanUnit as $u)
                                    <div class="col"><div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="unit[]" value="{{ $u }}" id="u-{{ $loop->index }}" @checked(in_array($u, old('unit', []), true))>
                                        <label class="form-check-label small" for="u-{{ $loop->index }}">{{ $u }}</label>
                                    </div></div>
                                @endforeach
                            </div>
                        </div>

                        <div data-target="pegawai">
                            <label class="form-label small" for="cari-pegawai">Pegawai (punya akun)</label>
                            <input type="search" class="form-control form-control-sm mb-2" id="cari-pegawai" placeholder="Cari nama...">
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" id="semua-pegawai" data-pilih-semua="pegawai[]">
                                <label class="form-check-label small fw-semibold" for="semua-pegawai">Pilih semua (yang tampil) <span class="text-muted fw-normal" data-jumlah-terpilih></span></label>
                            </div>
                            <div class="border rounded p-2" style="max-height:240px; overflow-y:auto;" id="daftar-pegawai">
                                @foreach ($pilihanPegawai as $p)
                                    <div class="form-check" data-nama="{{ strtolower($p->name) }}">
                                        <input class="form-check-input" type="checkbox" name="pegawai[]" value="{{ $p->id }}" id="p-{{ $p->id }}" @checked(in_array($p->id, old('pegawai', [])))>
                                        <label class="form-check-label small" for="p-{{ $p->id }}">{{ $p->name }} <span class="text-muted">&middot; {{ $p->roleLabel() }}{{ $p->dataPegawai?->kepegawaian?->unit_kerja ? ', ' . $p->dataPegawai->kepegawaian->unit_kerja : '' }}</span></label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if ($bisaPilihRole)
                            <div data-target="role">
                                <label class="form-label small">Role</label>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" id="semua-role" data-pilih-semua="role[]">
                                <label class="form-check-label small fw-semibold" for="semua-role">Pilih semua role <span class="text-muted fw-normal" data-jumlah-terpilih></span></label>
                            </div>
                                @foreach (\App\Models\Role::bawaanAktif() as $r)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="role[]" value="{{ $r }}" id="r-{{ $r }}" @checked(in_array($r, old('role', []), true))>
                                        <label class="form-check-label small" for="r-{{ $r }}">{{ \App\Models\User::roleLabelFor($r) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div data-target="semua">
                            <p class="small text-muted mb-0">Dikirim ke {{ $pilihanPegawai->count() }} akun dalam wewenang Anda (tidak termasuk Anda sendiri). Untuk informasi umum, pertimbangkan membuat <a href="{{ route('pengumuman.create') }}" class="text-primary">pengumuman</a>.</p>
                        </div>
                    </section>
                </div>

                <div class="col-12 col-lg-5">
                    <section class="panel p-4">
                        <h2 class="h6 fw-bold mb-3">2. Pesan</h2>
                        <div class="mb-3">
                            <label class="form-label small">Jenis</label>
                            <select name="jenis" class="form-select">
                                <option value="pengingat" @selected(old('jenis', 'pengingat') === 'pengingat')>Pengingat</option>
                                <option value="tugas" @selected(old('jenis') === 'tugas')>Tugas</option>
                                <option value="info" @selected(old('jenis') === 'info')>Info</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small" for="judul">Judul</label>
                            <input type="text" name="judul" id="judul" class="form-control" maxlength="120" required value="{{ old('judul', $templateLengkapi['judul']) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small" for="pesan">Pesan</label>
                            <textarea name="pesan" id="pesan" rows="6" class="form-control" maxlength="1000" required>{{ old('pesan', $templateLengkapi['pesan']) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Kirim Notifikasi</button>
                    </section>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function sinkron() {
            const target = document.querySelector('input[name="target"]:checked').value;
            document.querySelectorAll('[data-target]').forEach(function (el) { el.hidden = el.dataset.target !== target; });
        }
        document.querySelectorAll('input[name="target"]').forEach(function (r) { r.addEventListener('change', sinkron); });
        sinkron();

        const cari = document.getElementById('cari-pegawai');
        if (cari) {
            cari.addEventListener('input', function () {
                const kata = cari.value.trim().toLowerCase();
                document.querySelectorAll('#daftar-pegawai [data-nama]').forEach(function (el) { el.hidden = kata !== '' && ! el.dataset.nama.includes(kata); });
            });
        }
    });
</script>
@include('partials.pilih-semua')
@endsection
