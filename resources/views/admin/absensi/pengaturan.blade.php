@extends($layout)

@section('content')
@php
    $filterAktif = request()->only(['q', 'unit', 'ikut', 'masalah', 'status']);
    $hariBawaan = config('hrrab.hari_kerja', [1, 2, 3, 4, 5]);
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-sliders"></i></span>
                <div>
                    <p class="eyebrow mb-1">Absensi</p>
                    <h1 class="h3 mb-1">Pengaturan Absensi</h1>
                    <p class="text-muted mb-0">Pilih pegawai yang ikut absensi, hubungkan ID mesin (AC-No.), dan atur jam kerja tiap orang.</p>
                </div>
            </div>
        </div>

        @include('admin.absensi._nav')

        @if (session('daftar_gagal'))
            <div class="alert alert-warning small">
                <strong><i class="bi bi-exclamation-triangle me-1"></i>{{ count(session('daftar_gagal')) }} baris tidak cocok / ragu</strong> — centang manual dari daftar:
                <ul class="mb-0 mt-1">@foreach (session('daftar_gagal') as $g)<li>{{ $g }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="row g-2 g-md-3 mb-3">
            @foreach ([
                ['Ikut absensi', $statistik['ikut'], 'bi-person-check', 'success', ['ikut' => 'ya']],
                ['Belum punya ID mesin', $statistik['tanpa_finger'], 'bi-fingerprint', 'warning', ['masalah' => 'tanpa_finger']],
                ['Jam kerja belum diatur', $statistik['tanpa_jadwal'], 'bi-clock', 'danger', ['masalah' => 'tanpa_jadwal']],
                ['ID mesin belum dipetakan', $belumDipetakan->count(), 'bi-question-circle', 'secondary', null],
            ] as [$label, $nilai, $ikon, $warna, $tautan])
                <div class="col-6 col-md-3">
                    <a href="{{ $tautan ? route('admin.absensi.pengaturan', $tautan) : '#id-belum-dipetakan' }}" class="panel p-3 h-100 d-flex align-items-center gap-3 text-reset text-decoration-none absensi-kartu">
                        <span class="absensi-kartu-ikon text-{{ $warna }}"><i class="bi {{ $ikon }}"></i></span>
                        <span><span class="h4 mb-0 d-block">{{ $nilai }}</span><small class="text-muted">{{ $label }}</small></span>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xxl-8">
                <section class="panel">
                    <form method="GET" class="panel-filter px-3 pt-3 pb-2" id="filterPengaturan">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari nama, NIP, atau ID mesin..." value="{{ request('q') }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <select name="unit" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Unit">
                                    <option value="">Semua unit</option>
                                    @foreach ($unitOpsi as $u)<option value="{{ $u }}" @selected(request('unit') === $u)>{{ $u }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <select name="ikut" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Ikut absensi">
                                    <option value="">Semua</option>
                                    <option value="ya" @selected(request('ikut') === 'ya')>Ikut absensi</option>
                                    <option value="tidak" @selected(request('ikut') === 'tidak')>Tidak ikut</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <select name="masalah" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Perlu dilengkapi">
                                    <option value="">Semua kondisi</option>
                                    <option value="tanpa_finger" @selected(request('masalah') === 'tanpa_finger')>Belum punya ID mesin</option>
                                    <option value="tanpa_jadwal" @selected(request('masalah') === 'tanpa_jadwal')>Jam kerja belum diatur</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-bottom bg-body-tertiary">
                        <span class="small text-muted"><span data-jumlah-pilih>0</span> dipilih &middot; {{ $jumlahFilter }} pegawai sesuai filter</span>
                        <button type="button" class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#modalMassal">
                            <i class="bi bi-ui-checks me-1"></i> Atur Massal
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:36px;"><input type="checkbox" class="form-check-input" data-pilih-semua-baris aria-label="Pilih semua di halaman ini"></th>
                                    <th>Pegawai</th>
                                    <th>ID Mesin</th>
                                    <th>Absensi</th>
                                    <th>Jam Kerja</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pegawais as $p)
                                    @php
                                        $j = $p->jadwalKerja;
                                        $dataPegawai = [
                                            'url' => route('admin.absensi.pengaturan.pegawai', $p),
                                            'nama' => $p->nama_lengkap,
                                            'finger' => $p->finger_id,
                                            'ikut' => $p->ikut_absensi,
                                            'masuk' => $j ? substr($j->jam_masuk, 0, 5) : null,
                                            'pulang' => $j ? substr($j->jam_pulang, 0, 5) : null,
                                            'toleransi' => $j?->toleransi_menit,
                                            'hari' => $j?->hariKerja(),
                                            'khusus' => $j?->jam_per_hari ?? (object) [],
                                        ];
                                    @endphp
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input" name="pegawai_ids[]" value="{{ $p->id }}" form="formMassal" data-pilih-baris aria-label="Pilih {{ $p->nama_lengkap }}"></td>
                                        <td>
                                            <span class="fw-semibold d-block">{{ $p->nama_lengkap }}</span>
                                            <small class="text-muted">{{ $p->nip ?: 'NIP –' }} &middot; {{ $p->kepegawaian?->unit_kerja ?? '-' }}</small>
                                        </td>
                                        <td>
                                            @if ($p->finger_id)
                                                <span class="badge text-bg-light border fs-6 fw-semibold">{{ $p->finger_id }}</span>
                                            @else
                                                <span class="small {{ $p->ikut_absensi ? 'text-warning-emphasis' : 'text-muted' }}">belum</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($p->ikut_absensi)
                                                <span class="badge text-bg-success"><i class="bi bi-check2 me-1"></i>Ikut</span>
                                            @else
                                                <span class="badge text-bg-light border text-muted">Tidak ikut</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if ($j)
                                                {{ $j->ringkas() }}
                                                <span class="text-muted d-block">toleransi {{ $j->toleransi() }} menit</span>
                                            @else
                                                <span class="{{ $p->ikut_absensi ? 'text-danger' : 'text-muted' }}">Belum diatur</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-atur-pegawai='@json($dataPegawai)'>
                                                <i class="bi bi-gear me-1"></i>Atur
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6">@include('partials.kosong', ['teks' => 'Tidak ada pegawai yang cocok.', 'ikonKosong' => 'bi-people'])</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($pegawais->hasPages())
                        <div class="panel-footer px-3 py-2 border-top">{{ $pegawais->links('pagination::bootstrap-5') }}</div>
                    @endif
                </section>
            </div>

            <div class="col-12 col-xxl-4 d-grid gap-3">
                <section class="panel p-4" id="id-belum-dipetakan">
                    <h2 class="h6 fw-bold mb-1"><i class="bi bi-question-circle me-1"></i>ID Mesin Belum Dipetakan</h2>
                    <p class="small text-muted">ID dari file import yang belum terhubung ke pegawai. Setelah dihubungkan, semua absensinya langsung terbaca.</p>
                    @forelse ($belumDipetakan as $b)
                        <form action="{{ route('admin.absensi.pengaturan.petakan') }}" method="POST" class="border rounded p-2 mb-2">
                            @csrf
                            <input type="hidden" name="finger_id" value="{{ $b->finger_id }}">
                            <div class="d-flex justify-content-between small mb-2">
                                <span><span class="badge text-bg-light border fw-semibold">ID {{ $b->finger_id }}</span> {{ $b->nama_mesin ?? 'tanpa nama' }}</span>
                                <span class="text-muted">{{ $b->hari }} hari &middot; s/d {{ \Carbon\Carbon::parse($b->terakhir)->format('d/m/Y') }}</span>
                            </div>
                            <div class="input-group input-group-sm">
                                <select name="data_pegawai_id" class="form-select" required aria-label="Pegawai untuk ID {{ $b->finger_id }}">
                                    <option value="">Pilih pegawai...</option>
                                    @foreach ($pegawaiTanpaFinger as $pt)
                                        <option value="{{ $pt->id }}">{{ $pt->nama_lengkap }}{{ $pt->nip ? ' · ' . $pt->nip : '' }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-primary" type="submit">Hubungkan</button>
                            </div>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="ikut_absensi" value="1" id="ikut-{{ $b->finger_id }}" checked>
                                <label class="form-check-label small" for="ikut-{{ $b->finger_id }}">Sekaligus centang ikut absensi</label>
                            </div>
                        </form>
                    @empty
                        <p class="small text-success mb-0"><i class="bi bi-check-circle me-1"></i>Semua ID mesin sudah terhubung ke pegawai.</p>
                    @endforelse
                </section>

                <section class="panel p-4">
                    <h2 class="h6 fw-bold mb-1"><i class="bi bi-list-check me-1"></i>Aktifkan dari Daftar</h2>
                    <p class="small text-muted">Tempel daftar pegawai yang diabsen (satu per baris, NIP dan/atau nama — bisa salin dari Excel). Yang cocok otomatis dicentang <em>ikut absensi</em>.</p>
                    <form action="{{ route('admin.absensi.pengaturan.aktifkan-daftar') }}" method="POST">
                        @csrf
                        <textarea name="daftar" rows="6" class="form-control form-control-sm mb-2" placeholder="2020220197001  Elyanora, A.md&#10;2020220419002  Neneng Sumarni&#10;Tamin" required></textarea>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-check2-square me-1"></i> Centang yang cocok</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</main>

{{-- Modal: atur satu pegawai --}}
<div class="modal fade" id="modalPegawai" tabindex="-1" aria-labelledby="modalPegawaiJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" class="modal-content" id="formPegawai">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="modalPegawaiJudul">Atur Absensi</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label small" for="p-finger">ID mesin (AC-No.)</label>
                        <input type="text" name="finger_id" id="p-finger" class="form-control" inputmode="numeric" pattern="\d{1,12}" placeholder="mis. 13">
                        <div class="form-text">Sesuai nomor pegawai di mesin absen.</div>
                    </div>
                    <div class="col-sm-6 d-flex align-items-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="ikut_absensi" value="1" id="p-ikut">
                            <label class="form-check-label fw-semibold" for="p-ikut">Ikut absensi</label>
                            <div class="form-text mt-0">Matikan untuk pegawai yang tidak diabsen.</div>
                        </div>
                    </div>
                </div>
                <h3 class="h6 fw-bold">Jam kerja</h3>
                @include('admin.absensi._form-jadwal', ['awalan' => 'p', 'wajib' => false])
                <div class="form-check" data-hapus-jadwal>
                    <input class="form-check-input" type="checkbox" name="hapus_jadwal" value="1" id="p-hapus">
                    <label class="form-check-label small text-danger" for="p-hapus">Hapus jam kerja (kosongkan jam masuk)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: atur massal --}}
<div class="modal fade" id="modalMassal" tabindex="-1" aria-labelledby="modalMassalJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('admin.absensi.pengaturan.massal') }}" method="POST" class="modal-content" id="formMassal">
            @csrf
            @foreach ($filterAktif as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="modalMassalJudul">Atur Massal</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small fw-semibold">Berlaku untuk</label>
                <div class="d-grid gap-1 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sasaran" value="terpilih" id="s-terpilih" checked>
                        <label class="form-check-label" for="s-terpilih"><span data-jumlah-pilih>0</span> pegawai yang dicentang</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sasaran" value="filter" id="s-filter">
                        <label class="form-check-label" for="s-filter">Semua <strong>{{ $jumlahFilter }}</strong> pegawai sesuai filter saat ini
                            @if (request('unit'))<span class="text-muted">(unit {{ request('unit') }})</span>@endif</label>
                    </div>
                </div>

                <label class="form-label small fw-semibold">Aksi</label>
                <div class="btn-group w-100 mb-3" role="group" aria-label="Aksi massal">
                    <input type="radio" class="btn-check" name="aksi" value="jadwal" id="a-jadwal" checked>
                    <label class="btn btn-outline-primary" for="a-jadwal"><i class="bi bi-clock me-1"></i>Atur jam kerja</label>
                    <input type="radio" class="btn-check" name="aksi" value="ikut" id="a-ikut">
                    <label class="btn btn-outline-success" for="a-ikut"><i class="bi bi-check2 me-1"></i>Ikut absensi</label>
                    <input type="radio" class="btn-check" name="aksi" value="tidak_ikut" id="a-tidak">
                    <label class="btn btn-outline-secondary" for="a-tidak"><i class="bi bi-x me-1"></i>Tidak ikut</label>
                </div>

                <div data-blok-jadwal>
                    @include('admin.absensi._form-jadwal', ['awalan' => 'm', 'wajib' => true])
                    <p class="small text-muted mb-0">Jam kerja yang sudah ada pada pegawai terpilih akan diganti. Setelah itu tetap bisa diubah per orang.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-all me-1"></i>Terapkan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ---- Pilih baris ----
        const baris = Array.from(document.querySelectorAll('[data-pilih-baris]'));
        const semua = document.querySelector('[data-pilih-semua-baris]');
        const perbaruiJumlah = function () {
            const n = baris.filter((b) => b.checked).length;
            document.querySelectorAll('[data-jumlah-pilih]').forEach((el) => { el.textContent = n; });
            if (semua) semua.checked = n > 0 && n === baris.length;
        };
        baris.forEach((b) => b.addEventListener('change', perbaruiJumlah));
        semua?.addEventListener('change', function () { baris.forEach((b) => { b.checked = semua.checked; }); perbaruiJumlah(); });

        // ---- Modal massal: jam kerja hanya untuk aksi "Atur jam kerja" ----
        const formMassal = document.getElementById('formMassal');
        const blokJadwal = formMassal.querySelector('[data-blok-jadwal]');
        const sinkronAksi = function () {
            const jadwal = formMassal.aksi.value === 'jadwal';
            blokJadwal.hidden = !jadwal;
            blokJadwal.querySelectorAll('[data-j="masuk"], [data-j="pulang"]').forEach((el) => { el.required = jadwal; });
        };
        formMassal.querySelectorAll('[name="aksi"]').forEach((r) => r.addEventListener('change', sinkronAksi));
        sinkronAksi();
        formMassal.addEventListener('submit', function (e) {
            if (formMassal.sasaran.value === 'terpilih' && !baris.some((b) => b.checked)) {
                e.preventDefault();
                Swal.fire({ icon: 'info', title: 'Belum ada yang dicentang', text: 'Centang pegawai di tabel, atau pilih "Semua pegawai sesuai filter".', confirmButtonColor: '#880f12' });
            }
        });

        // ---- Modal satu pegawai ----
        const modalP = document.getElementById('modalPegawai');
        const formP = document.getElementById('formPegawai');
        document.querySelectorAll('[data-atur-pegawai]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const d = JSON.parse(btn.dataset.aturPegawai);
                formP.action = d.url;
                modalP.querySelector('.modal-title').textContent = 'Atur Absensi — ' + d.nama;
                formP.finger_id.value = d.finger || '';
                formP.ikut_absensi.checked = !!d.ikut;
                formP.querySelector('[data-j="masuk"]').value = d.masuk || '';
                formP.querySelector('[data-j="pulang"]').value = d.pulang || '';
                formP.querySelector('[data-j="toleransi"]').value = d.toleransi ?? '';
                const hari = d.hari || @json($hariBawaan);
                formP.querySelectorAll('[name="hari_kerja[]"]').forEach((c) => { c.checked = hari.includes(parseInt(c.value, 10)); });
                let adaKhusus = false;
                formP.querySelectorAll('[data-khusus]').forEach(function (row) {
                    const k = d.khusus[row.dataset.khusus] || {};
                    const [m, p] = row.querySelectorAll('input');
                    m.value = k.masuk || '';
                    p.value = k.pulang || '';
                    adaKhusus = adaKhusus || !!(k.masuk || k.pulang);
                });
                formP.querySelector('[data-j="khusus-wrap"]').open = adaKhusus;
                formP.hapus_jadwal.checked = false;
                formP.querySelector('[data-hapus-jadwal]').hidden = !d.masuk;
                bootstrap.Modal.getOrCreateInstance(modalP).show();
            });
        });
    });
</script>
@include('admin.absensi._gaya')
@endsection
