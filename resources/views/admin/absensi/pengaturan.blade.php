@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $filterAktif = request()->only(['q', 'unit', 'ikut', 'masalah', 'status']);
    $hariBawaan = config('hrrab.hari_kerja', [1, 2, 3, 4, 5]);
    $urlTab = fn ($t) => route('admin.absensi.pengaturan', ['tab' => $t]);
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi {{ $ik['pengaturan'] }}"></i></span>
                <div>
                    <p class="eyebrow mb-1">Absensi</p>
                    <h1 class="h3 mb-1">Pengaturan Absensi</h1>
                    <p class="text-muted mb-0">Tentukan siapa yang diabsen, hubungkan ID di mesin, dan atur jam kerja.</p>
                </div>
            </div>
        </div>

        @include('admin.absensi._nav')

        @if (session('daftar_gagal'))
            <div class="alert alert-warning small">
                <strong><i class="bi bi-exclamation-triangle me-1"></i>{{ count(session('daftar_gagal')) }} nama tidak ditemukan / ganda</strong> — centang manual di tab Pegawai:
                <ul class="mb-0 mt-1">@foreach (session('daftar_gagal') as $g)<li>{{ $g }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Ringkasan masalah (klik untuk langsung menyaring) --}}
        <div class="row g-2 mb-3">
            @foreach ([
                ['Ikut absensi', $statistik['ikut'], 'bi-person-check', 'success', route('admin.absensi.pengaturan', ['ikut' => 'ya'])],
                ['Jam kerja belum diatur', $statistik['tanpa_jadwal'], 'bi-clock', $statistik['tanpa_jadwal'] ? 'danger' : 'secondary', route('admin.absensi.pengaturan', ['masalah' => 'tanpa_jadwal'])],
                ['Belum punya ID mesin', $statistik['tanpa_finger'], $ik['mesin'], $statistik['tanpa_finger'] ? 'warning' : 'secondary', route('admin.absensi.pengaturan', ['masalah' => 'tanpa_finger'])],
                ['ID mesin belum dikenali', $belumDipetakan->count(), 'bi-link-45deg', $belumDipetakan->count() ? 'warning' : 'secondary', $urlTab('pemetaan')],
            ] as [$label, $nilai, $ikon, $warna, $url])
                <div class="col-6 col-md-3">
                    <a href="{{ $url }}" class="panel p-3 h-100 d-flex align-items-center gap-3 text-reset text-decoration-none absensi-kartu">
                        <span class="absensi-kartu-ikon {{ $warna }}"><i class="bi {{ $ikon }}"></i></span>
                        <span><span class="h4 mb-0 d-block">{{ $nilai }}</span><small class="text-muted">{{ $label }}</small></span>
                    </a>
                </div>
            @endforeach
        </div>

        <section class="panel">
            <div class="panel-header pb-0 border-0">
                <ul class="nav nav-tabs w-100 flex-nowrap overflow-auto text-nowrap abs-tab-geser" role="tablist">
                    <li class="nav-item"><a class="nav-link {{ $tab === 'pegawai' ? 'active' : '' }}" href="{{ $urlTab('pegawai') }}"><i class="bi bi-people me-1"></i>Pegawai & Jam Kerja</a></li>
                    <li class="nav-item"><a class="nav-link {{ $tab === 'pemetaan' ? 'active' : '' }}" href="{{ $urlTab('pemetaan') }}">
                        <i class="bi bi-link-45deg me-1"></i>Hubungkan ID Mesin @if ($belumDipetakan->count())<span class="badge rounded-pill text-bg-warning ms-1">{{ $belumDipetakan->count() }}</span>@endif</a></li>
                </ul>
            </div>

            @if ($tab === 'pegawai')
                <form method="GET" class="panel-filter px-3 pt-3 pb-2">
                    <input type="hidden" name="tab" value="pegawai">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                <input type="search" name="q" class="form-control border-start-0" placeholder="Cari nama, NIP, atau ID mesin..." value="{{ request('q') }}">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <select name="unit" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Unit">
                                <option value="">Semua unit</option>
                                @foreach ($unitOpsi as $u)<option value="{{ $u }}" @selected(request('unit') === $u)>{{ $u }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <select name="ikut" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Ikut absensi">
                                <option value="">Semua pegawai</option>
                                <option value="ya" @selected(request('ikut') === 'ya')>Yang ikut absensi</option>
                                <option value="tidak" @selected(request('ikut') === 'tidak')>Yang tidak ikut</option>
                            </select>
                        </div>
                        @if (array_filter($filterAktif))
                            <div class="col-auto"><a href="{{ $urlTab('pegawai') }}" class="btn btn-sm btn-link text-secondary"><i class="bi bi-x-circle me-1"></i>Hapus filter</a></div>
                        @endif
                    </div>
                    @if (request('masalah'))
                        <input type="hidden" name="masalah" value="{{ request('masalah') }}">
                        <div class="small mt-2"><span class="badge text-bg-warning">{{ request('masalah') === 'tanpa_jadwal' ? 'Jam kerja belum diatur' : 'Belum punya ID mesin' }}</span></div>
                    @endif
                </form>

                <div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-bottom">
                    <span class="small text-muted">{{ $jumlahFilter }} pegawai · centang beberapa untuk mengubah sekaligus</span>
                    <button type="button" class="btn btn-sm btn-light ms-auto" data-bs-toggle="modal" data-bs-target="#modalDaftar" title="Centang banyak pegawai sekaligus dari daftar NIP/nama (Excel)">
                        <i class="bi bi-clipboard-plus me-1"></i>Tempel daftar
                    </button>
                    @if ($jumlahFilter)
                        <button type="button" class="btn btn-sm btn-outline-primary" data-massal="jadwal" data-sasaran="filter">
                            <i class="bi bi-clock me-1"></i>Atur jam kerja semua ({{ $jumlahFilter }})
                        </button>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:36px;"><input type="checkbox" class="form-check-input" data-pilih-semua-baris aria-label="Pilih semua di halaman ini"></th>
                                <th>Pegawai</th>
                                <th>Diabsen?</th>
                                <th>ID Mesin</th>
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
                                        <small class="text-muted">{{ $p->kepegawaian?->unit_kerja ?? '-' }}{{ $p->nip ? ' · ' . $p->nip : '' }}</small>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.absensi.pengaturan.ikut', $p) }}" method="POST" class="m-0">
                                            @csrf
                                            <input type="hidden" name="ikut" value="{{ $p->ikut_absensi ? 0 : 1 }}">
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="ikut-{{ $p->id }}" @checked($p->ikut_absensi) onchange="this.form.submit()">
                                                <label class="form-check-label small" for="ikut-{{ $p->id }}">{{ $p->ikut_absensi ? 'Ya' : 'Tidak' }}</label>
                                            </div>
                                        </form>
                                    </td>
                                    <td>
                                        @if ($p->finger_id)
                                            <span class="badge text-bg-light border fw-semibold">{{ $p->finger_id }}</span>
                                        @else
                                            <span class="small {{ $p->ikut_absensi ? 'text-warning-emphasis' : 'text-muted' }}">{{ $p->ikut_absensi ? 'Belum ada' : '–' }}</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if ($j)
                                            <span class="fw-semibold">{{ substr($j->jam_masuk, 0, 5) }}–{{ substr($j->jam_pulang, 0, 5) }}</span>
                                            <span class="text-muted d-block">{{ \Illuminate\Support\Str::after($j->ringkas(), ' · ') }} · toleransi {{ $j->toleransi() }} m</span>
                                        @else
                                            <span class="{{ $p->ikut_absensi ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $p->ikut_absensi ? 'Belum diatur' : '–' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-atur-pegawai='@json($dataPegawai)'>
                                            <i class="bi bi-pencil me-1"></i>Ubah
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">@include('partials.kosong', ['teks' => 'Tidak ada pegawai yang cocok.', 'ikonKosong' => 'bi-people'])</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Bilah aksi muncul saat ada pegawai dicentang --}}
                <div class="abs-aksi-bar" data-aksi-bar hidden>
                    <span class="fw-semibold"><span data-jumlah-pilih>0</span> pegawai dipilih</span>
                    <div class="d-flex flex-wrap gap-2 ms-auto">
                        <button type="button" class="btn btn-light btn-sm" data-massal="jadwal" data-sasaran="terpilih"><i class="bi bi-clock me-1"></i>Atur jam kerja</button>
                        <button type="button" class="btn btn-success btn-sm" data-massal="ikut" data-sasaran="terpilih"><i class="bi bi-check2 me-1"></i>Ikutkan absensi</button>
                        <button type="button" class="btn btn-outline-light btn-sm" data-massal="tidak_ikut" data-sasaran="terpilih"><i class="bi bi-x me-1"></i>Keluarkan</button>
                        <button type="button" class="btn btn-link btn-sm text-white" data-batal-pilih>Batal</button>
                    </div>
                </div>

                @if ($pegawais->hasPages())
                    <div class="panel-footer px-3 py-2 border-top">{{ $pegawais->links('pagination::bootstrap-5') }}</div>
                @endif

            @elseif ($tab === 'pemetaan')
                <div class="p-3 p-md-4">
                    <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>ID dari mesin yang belum terhubung ke pegawai. Pilih pegawainya lalu klik <strong>Hubungkan</strong> — semua data absen ID itu langsung terbaca. Sistem menebak pegawai dari nama di mesin bila cukup mirip.</p>
                    @forelse ($belumDipetakan as $b)
                        <form action="{{ route('admin.absensi.pengaturan.petakan') }}" method="POST" class="border rounded-3 p-3 mb-2">
                            @csrf
                            <input type="hidden" name="finger_id" value="{{ $b->finger_id }}">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-4">
                                    <span class="badge text-bg-light border fw-semibold me-1">ID {{ $b->finger_id }}</span>
                                    <span class="fw-semibold">{{ $b->nama_mesin ?? 'Tanpa nama' }}</span>
                                    <small class="text-muted d-block">{{ $b->hari }} hari data · terakhir {{ \Carbon\Carbon::parse($b->terakhir)->translatedFormat('d M Y') }}</small>
                                </div>
                                <div class="col-md-5">
                                    <select name="data_pegawai_id" class="form-select" required aria-label="Pegawai untuk ID {{ $b->finger_id }}">
                                        <option value="">Pilih pegawai...</option>
                                        @foreach ($pegawaiTanpaFinger as $pt)
                                            <option value="{{ $pt->id }}" @selected($b->saran === $pt->id)>{{ $pt->nama_lengkap }} · {{ $pt->kepegawaian?->unit_kerja ?? '-' }}</option>
                                        @endforeach
                                    </select>
                                    @if ($b->saran)<small class="text-success"><i class="bi bi-magic me-1"></i>Disarankan berdasarkan kemiripan nama — periksa dulu.</small>@endif
                                </div>
                                <div class="col-md-3 d-flex flex-column gap-1">
                                    <button class="btn btn-primary" type="submit"><i class="bi bi-link-45deg me-1"></i>Hubungkan</button>
                                    <div class="form-check small m-0">
                                        <input class="form-check-input" type="checkbox" name="ikut_absensi" value="1" id="ikut-{{ $b->finger_id }}" checked>
                                        <label class="form-check-label" for="ikut-{{ $b->finger_id }}">Sekaligus ikutkan absensi</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @empty
                        <div class="abs-kosong-besar">
                            <i class="bi bi-check2-circle d-block mb-2 text-success"></i>
                            <p class="fw-semibold mb-1">Semua ID mesin sudah terhubung</p>
                            <p class="text-muted small mb-0">ID baru akan muncul di sini setelah import bila belum dikenali.</p>
                        </div>
                    @endforelse
                </div>

            @endif
        </section>
    </div>
</main>

{{-- Modal: centang "ikut absensi" dari daftar tempel (dipakai saat persiapan awal) --}}
<div class="modal fade" id="modalDaftar" tabindex="-1" aria-labelledby="modalDaftarJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form action="{{ route('admin.absensi.pengaturan.aktifkan-daftar') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 mb-0" id="modalDaftarJudul">Tempel Daftar Pegawai</h2>
                    <small class="text-muted">Centang "ikut absensi" untuk banyak pegawai sekaligus.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">Salin kolom NIP & nama dari Excel, tempel di bawah (satu pegawai per baris). Dicocokkan lewat NIP, lalu nama. Nama yang tidak ketemu akan ditampilkan.</p>
                <textarea name="daftar" rows="10" class="form-control" required placeholder="2020220197001  Elyanora, A.md&#10;2020220419002  Neneng Sumarni&#10;Tamin"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-square me-1"></i>Centang yang cocok</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: ubah satu pegawai --}}<div class="modal fade" id="modalPegawai" tabindex="-1" aria-labelledby="modalPegawaiJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form method="POST" class="modal-content" id="formPegawai">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="modalPegawaiJudul">Pengaturan Absensi</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3 pb-3 border-bottom">
                    <div class="col-sm-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="ikut_absensi" value="1" id="p-ikut">
                            <label class="form-check-label fw-semibold" for="p-ikut">Pegawai ini diabsen</label>
                        </div>
                        <small class="text-muted">Matikan untuk pegawai yang tidak absen di mesin.</small>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small mb-1" for="p-finger">ID di mesin absen (AC-No.)</label>
                        <input type="text" name="finger_id" id="p-finger" class="form-control" inputmode="numeric" pattern="\d{1,12}" placeholder="mis. 13">
                    </div>
                </div>
                <h3 class="h6 fw-bold mb-2">Jam kerja</h3>
                @include('admin.absensi._form-jadwal', ['awalan' => 'p', 'wajib' => false])
                <div class="form-check mt-2" data-hapus-jadwal>
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

{{-- Modal: ubah beberapa pegawai sekaligus --}}
<div class="modal fade" id="modalMassal" tabindex="-1" aria-labelledby="modalMassalJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form action="{{ route('admin.absensi.pengaturan.massal') }}" method="POST" class="modal-content" id="formMassal">
            @csrf
            @foreach ($filterAktif as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <input type="hidden" name="aksi" value="jadwal">
            <input type="hidden" name="sasaran" value="terpilih">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 mb-0" id="modalMassalJudul">Atur Jam Kerja</h2>
                    <small class="text-muted" data-k="sasaran"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div data-blok-jadwal>
                    @include('admin.absensi._form-jadwal', ['awalan' => 'm', 'wajib' => true])
                    <p class="small text-muted mb-0 mt-2"><i class="bi bi-info-circle me-1"></i>Jam kerja lama pegawai tersebut akan diganti. Setelah itu tetap bisa diubah per orang.</p>
                </div>
                <p class="mb-0" data-blok-konfirmasi></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" data-k="tombol"><i class="bi bi-check2-all me-1"></i>Terapkan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const baris = Array.from(document.querySelectorAll('[data-pilih-baris]'));
        const semua = document.querySelector('[data-pilih-semua-baris]');
        const bar = document.querySelector('[data-aksi-bar]');
        const terpilih = () => baris.filter((b) => b.checked).length;
        const perbarui = function () {
            const n = terpilih();
            document.querySelectorAll('[data-jumlah-pilih]').forEach((el) => { el.textContent = n; });
            if (bar) bar.hidden = n === 0;
            if (semua) semua.checked = n > 0 && n === baris.length;
        };
        baris.forEach((b) => b.addEventListener('change', perbarui));
        semua?.addEventListener('change', function () { baris.forEach((b) => { b.checked = semua.checked; }); perbarui(); });
        document.querySelector('[data-batal-pilih]')?.addEventListener('click', function () { baris.forEach((b) => { b.checked = false; }); perbarui(); });

        // ---- Modal massal: dibuka dari bilah aksi / tombol "Atur jam kerja semua" ----
        const formM = document.getElementById('formMassal');
        const modalM = document.getElementById('modalMassal');
        const judul = { jadwal: 'Atur Jam Kerja', ikut: 'Ikutkan Absensi', tidak_ikut: 'Keluarkan dari Absensi' };
        document.querySelectorAll('[data-massal]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const aksi = btn.dataset.massal, sasaran = btn.dataset.sasaran;
                const jumlah = sasaran === 'filter' ? {{ $jumlahFilter }} : terpilih();
                formM.aksi.value = aksi;
                formM.sasaran.value = sasaran;
                modalM.querySelector('.modal-title').textContent = judul[aksi];
                formM.querySelector('[data-k="sasaran"]').textContent = jumlah + ' pegawai' + (sasaran === 'filter' ? ' (semua yang tampil di daftar)' : ' yang dicentang');
                const pakaiJadwal = aksi === 'jadwal';
                formM.querySelector('[data-blok-jadwal]').hidden = !pakaiJadwal;
                formM.querySelectorAll('[data-j="masuk"], [data-j="pulang"]').forEach((el) => { el.required = pakaiJadwal; });
                formM.querySelector('[data-blok-konfirmasi]').textContent = pakaiJadwal ? ''
                    : (aksi === 'ikut' ? jumlah + ' pegawai akan diikutkan absensi.' : jumlah + ' pegawai tidak akan dinilai absensinya lagi (datanya tetap tersimpan).');
                bootstrap.Modal.getOrCreateInstance(modalM).show();
            });
        });

        // Dibuka dari panduan persiapan: langsung tampilkan modal tempel daftar.
        if (new URLSearchParams(location.search).get('tempel') === '1') {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDaftar')).show();
        }

        // ---- Modal satu pegawai ----
        const modalP = document.getElementById('modalPegawai');
        const formP = document.getElementById('formPegawai');
        const hariBawaan = @json($hariBawaan);
        document.querySelectorAll('[data-atur-pegawai]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const d = JSON.parse(btn.dataset.aturPegawai);
                formP.action = d.url;
                modalP.querySelector('.modal-title').textContent = d.nama;
                formP.finger_id.value = d.finger || '';
                formP.ikut_absensi.checked = !!d.ikut;
                formP.querySelector('[data-j="masuk"]').value = d.masuk || '';
                formP.querySelector('[data-j="pulang"]').value = d.pulang || '';
                formP.querySelector('[data-j="toleransi"]').value = d.toleransi ?? '';
                formP.querySelectorAll('[data-isi-jam]').forEach((b) => b.classList.remove('aktif'));
                const hari = d.hari || hariBawaan;
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
@endsection
