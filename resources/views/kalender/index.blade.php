@extends(Auth::user()->panelLayout())

@section('content')
@php
    $hariKerja = config('hrrab.hari_kerja', [1, 2, 3, 4, 5]);
    $sebelum = $bulan->copy()->subMonth();
    $sesudah = $bulan->copy()->addMonth();
@endphp
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-calendar3"></i></span>
                <div>
                    <p class="eyebrow mb-1">Kepegawaian</p>
                    <h1 class="h3 mb-1">Kalender Pegawai</h1>
                    <p class="text-muted mb-0">Tanggal merah, hari kerja, serta cuti &amp; izin keluar Anda. Tanggal merah memengaruhi hitungan hari kerja, absensi, dan cuti.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'kalender.export', 'eksporParam' => ['tahun' => $tahun], 'eksporTeks' => "Ekspor Tanggal Merah {$tahun}"])
                @if ($bisaKelola)
                    <form action="{{ route('kalender.sinkron') }}" method="POST" data-confirm="Sinkronkan tanggal merah dari API sekarang? Tanggal merah manual tidak akan diubah.">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat"></i> Sinkronkan sekarang</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="row g-3 align-items-start">
            {{-- ===== Kalender bulanan ===== --}}
            <div class="col-xl-8">
                <section class="panel p-0 overflow-hidden">
                    <div class="kl-kepala">
                        <a href="{{ route('kalender.index', ['bulan' => $sebelum->format('Y-m'), 'tahun' => $sebelum->year]) }}" class="btn btn-outline-secondary btn-sm" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
                        <h2 class="h5 mb-0 kl-judul">{{ $bulan->translatedFormat('F Y') }}</h2>
                        <a href="{{ route('kalender.index', ['bulan' => $sesudah->format('Y-m'), 'tahun' => $sesudah->year]) }}" class="btn btn-outline-secondary btn-sm" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
                        @unless ($bulan->isSameMonth(now()))
                            <a href="{{ route('kalender.index') }}" class="btn btn-outline-secondary btn-sm">Hari ini</a>
                        @endunless
                        <span class="kl-ringkas ms-auto"><i class="bi bi-briefcase"></i> {{ $hariKerjaBulan }} hari kerja</span>
                    </div>

                    <div class="kl-gulir">
                        <div class="kl-grid" role="grid" aria-label="Kalender {{ $bulan->translatedFormat('F Y') }}">
                            @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $i => $nama)
                                <div class="kl-hari-nama {{ in_array($i + 1, $hariKerja, true) ? '' : 'libur' }}" role="columnheader">{{ $nama }}</div>
                            @endforeach
                            @foreach ($hari as $t)
                                @php
                                    $iso = $t->toDateString();
                                    $l = $libur->get($iso);
                                    $merah = $l && $l->aktif;
                                    $kelas = collect([
                                        $t->month !== $bulan->month ? 'luar' : null,
                                        ! in_array($t->dayOfWeekIso, $hariKerja, true) ? 'akhir-pekan' : null,
                                        $merah ? 'merah' : null,
                                        $t->isToday() ? 'kini' : null,
                                        $bisaKelola && ! $l ? 'bisa-pilih' : null,
                                    ])->filter()->implode(' ');
                                @endphp
                                <div class="kl-sel {{ $kelas }}" role="gridcell" data-tanggal="{{ $iso }}"
                                    @if ($bisaKelola && ! $l) title="Klik untuk menjadikan tanggal merah" @endif>
                                    <span class="kl-angka">{{ $t->day }}</span>
                                    @if ($l)
                                        <span class="kl-chip {{ $l->aktif ? 'libur' : 'libur-mati' }}" title="{{ $l->nama }} — {{ $l->jenisLabel() }}{{ $l->aktif ? '' : ' (dinonaktifkan: tetap masuk)' }}">{{ $l->nama }}</span>
                                    @endif
                                    @foreach ($agenda[$iso] ?? [] as $a)
                                        <span class="kl-chip {{ $a['jenis'] }} {{ $a['menunggu'] ? 'menunggu' : '' }}" title="{{ $a['teks'] }}{{ $a['menunggu'] ? ' (menunggu persetujuan)' : '' }}">{{ $a['teks'] }}</span>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="kl-legenda">
                        <span><i class="kotak merah"></i>Tanggal merah</span>
                        <span><i class="kotak cuti"></i>Cuti saya</span>
                        <span><i class="kotak izin"></i>Izin keluar saya</span>
                        <span><i class="kotak menunggu"></i>Menunggu persetujuan</span>
                        <span><i class="kotak akhir-pekan"></i>Bukan hari kerja</span>
                    </div>
                </section>
            </div>

            {{-- ===== Daftar tanggal merah setahun + pengelolaan ===== --}}
            <div class="col-xl-4">
                @if ($bisaKelola)
                    <section class="panel p-4 mb-3" id="tambah-libur">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-plus-circle me-1"></i>Tambah tanggal merah</h2>
                        <form action="{{ route('kalender.store') }}" method="POST" data-confirm="Simpan tanggal merah ini?">
                            @csrf
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1" for="kl-mulai">Tanggal</label>
                                    <input type="date" name="mulai" id="kl-mulai" class="form-control form-control-sm @error('mulai') is-invalid @enderror" value="{{ old('mulai') }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1" for="kl-selesai">Sampai <span class="text-muted fw-normal">(opsional)</span></label>
                                    <input type="date" name="selesai" id="kl-selesai" class="form-control form-control-sm @error('selesai') is-invalid @enderror" value="{{ old('selesai') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1" for="kl-nama">Nama</label>
                                    <input type="text" name="nama" id="kl-nama" class="form-control form-control-sm" maxlength="150" value="{{ old('nama') }}" placeholder="mis. Milad Yayasan Abdurrab" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1" for="kl-jenis">Jenis</label>
                                    <select name="jenis" id="kl-jenis" class="form-select form-select-sm" required>
                                        <option value="">-- Pilih jenis --</option>
                                        @foreach (\App\Models\HariLibur::JENIS as $kode => $label)
                                            <option value="{{ $kode }}" @selected(old('jenis', 'yayasan') === $kode)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <input type="text" name="keterangan" class="form-control form-control-sm" maxlength="255" value="{{ old('keterangan') }}" placeholder="Keterangan (opsional)" aria-label="Keterangan">
                                </div>
                            </div>
                            {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
                            @section('galat-di-halaman', '1')
                            @if ($errors->any())<div class="text-danger small mt-2">{{ $errors->first() }}</div>@endif
                            <button type="submit" class="btn btn-primary btn-sm w-100 mt-3"><i class="bi bi-calendar-plus"></i> Simpan tanggal merah</button>
                            <p class="form-text mb-0 mt-2">Tip: klik tanggal di kalender untuk mengisi tanggal otomatis. Tanggal merah manual tidak pernah diubah sinkron API.</p>
                        </form>
                    </section>
                @endif

                <section class="panel p-0 overflow-hidden">
                    <div class="kl-kepala">
                        <a href="{{ route('kalender.index', ['bulan' => $bulan->format('Y-m'), 'tahun' => $tahun - 1]) }}" class="btn btn-outline-secondary btn-sm" aria-label="Tahun sebelumnya"><i class="bi bi-chevron-left"></i></a>
                        <h2 class="h6 fw-bold mb-0 kl-judul">Tanggal merah {{ $tahun }}</h2>
                        <a href="{{ route('kalender.index', ['bulan' => $bulan->format('Y-m'), 'tahun' => $tahun + 1]) }}" class="btn btn-outline-secondary btn-sm" aria-label="Tahun berikutnya"><i class="bi bi-chevron-right"></i></a>
                        <span class="kl-ringkas ms-auto">{{ $daftarTahun->where('aktif', true)->count() }} hari</span>
                    </div>
                    <ul class="kl-daftar list-unstyled mb-0">
                        @forelse ($daftarTahun as $h)
                            <li class="{{ $h->aktif ? '' : 'mati' }}">
                                <div class="kl-tgl">
                                    <strong>{{ $h->tanggal->format('d') }}</strong>
                                    <small>{{ $h->tanggal->translatedFormat('M') }}</small>
                                </div>
                                <div class="kl-isi">
                                    <span class="fw-semibold d-block">{{ $h->nama }}</span>
                                    <small class="text-muted">
                                        {{ $h->tanggal->translatedFormat('l') }} · {{ $h->jenisLabel() }} ·
                                        <span title="{{ $h->dariApi() ? 'Hasil sinkron otomatis API' : 'Diatur manual' }}">{{ $h->dariApi() ? 'API' : 'Manual' }}</span>
                                        @unless ($h->aktif) · <span class="text-danger">nonaktif (tetap masuk)</span>@endunless
                                    </small>
                                    @if ($h->keterangan)<small class="text-muted d-block">{{ $h->keterangan }}</small>@endif
                                    @if ($bisaKelola)
                                        <div class="kl-aksi">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUbahLibur"
                                                data-url="{{ route('kalender.update', $h) }}" data-nama="{{ $h->nama }}" data-jenis="{{ $h->jenis }}" data-keterangan="{{ $h->keterangan }}"
                                                data-tanggal="{{ $h->tanggal->translatedFormat('l, d M Y') }}" data-api="{{ $h->dariApi() ? 1 : 0 }}">
                                                <i class="bi bi-pencil"></i> Ubah
                                            </button>
                                            <form action="{{ route('kalender.alih', $h) }}" method="POST"
                                                data-confirm="{{ $h->aktif ? 'Nonaktifkan ' . $h->nama . '? Tanggal ini dihitung hari kerja biasa.' : 'Aktifkan lagi ' . $h->nama . ' sebagai tanggal merah?' }}">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi {{ $h->aktif ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i> {{ $h->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                            @unless ($h->dariApi())
                                                <form action="{{ route('kalender.destroy', $h) }}" method="POST" data-confirm="Hapus tanggal merah {{ $h->nama }}?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Hapus</button>
                                                </form>
                                            @endunless
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="text-muted small justify-content-center py-4">Belum ada tanggal merah di {{ $tahun }}.</li>
                        @endforelse
                    </ul>
                    @if ($sinkronTerakhir)
                        <div class="kl-sinkron">
                            <i class="bi bi-cloud-check"></i> Sinkron otomatis terakhir:
                            {{ \Carbon\Carbon::parse($sinkronTerakhir['waktu'])->translatedFormat('d M Y H:i') }} dari {{ $sinkronTerakhir['sumber'] }}.
                        </div>
                    @else
                        <div class="kl-sinkron"><i class="bi bi-cloud"></i> Belum pernah sinkron otomatis — berjalan sendiri sekali sebulan.</div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>

@if ($bisaKelola)
    <div class="modal fade" id="modalUbahLibur" tabindex="-1" aria-labelledby="judulUbahLibur" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content" data-confirm="Simpan perubahan tanggal merah?">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="judulUbahLibur">Ubah tanggal merah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3" data-tanggal></p>
                    <label class="form-label">Nama</label>
                    <input type="text" name="nama" class="form-control mb-3" maxlength="150" required>
                    <label class="form-label">Jenis</label>
                    <select name="jenis" class="form-select mb-3" required>
                        @foreach (\App\Models\HariLibur::JENIS as $kode => $label)
                            <option value="{{ $kode }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="form-label">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="text" name="keterangan" class="form-control" maxlength="255">
                    <div class="alert alert-info small mt-3 mb-0" data-info-api hidden>
                        <i class="bi bi-info-circle me-1"></i>Libur ini hasil sinkron API. Setelah diubah, statusnya menjadi <strong>manual</strong> dan tidak lagi diperbarui sinkron.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endif

<style>
    .kl-kepala { display: flex; align-items: center; gap: .5rem; padding: .8rem 1rem; border-bottom: 1px solid var(--admin-border); background: var(--admin-surface-soft); }
    .kl-judul { min-width: 9.5rem; text-align: center; }
    .kl-ringkas { font-size: .8rem; font-weight: 600; color: var(--admin-muted); white-space: nowrap; }
    .kl-gulir { overflow-x: auto; }
    .kl-grid { display: grid; grid-template-columns: repeat(7, minmax(92px, 1fr)); min-width: 644px; }
    .kl-hari-nama { padding: .45rem .5rem; font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--admin-muted); border-bottom: 1px solid var(--admin-border); }
    .kl-hari-nama.libur { color: #b42318; }
    .kl-sel { min-height: 96px; padding: .35rem .4rem; border-right: 1px solid var(--admin-border); border-bottom: 1px solid var(--admin-border); display: flex; flex-direction: column; gap: .2rem; overflow: hidden; }
    .kl-sel:nth-child(7n) { border-right: 0; }
    .kl-angka { font-weight: 700; font-size: .85rem; width: 26px; height: 26px; display: inline-grid; place-items: center; border-radius: 50%; }
    .kl-sel.luar { background: var(--admin-surface-soft); }
    .kl-sel.luar .kl-angka { color: var(--admin-muted); font-weight: 500; }
    .kl-sel.akhir-pekan { background: rgba(148, 163, 184, .08); }
    .kl-sel.akhir-pekan .kl-angka, .kl-sel.merah .kl-angka { color: #b42318; }
    .kl-sel.merah { background: #fdecec; }
    .kl-sel.kini .kl-angka { background: var(--hr-primary); color: #fff; }
    .kl-sel.bisa-pilih { cursor: pointer; }
    .kl-sel.bisa-pilih:hover { box-shadow: inset 0 0 0 2px var(--hr-primary-soft); }
    .kl-chip { display: block; font-size: .7rem; font-weight: 600; line-height: 1.25; padding: .12rem .35rem; border-radius: .35rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: 1px solid transparent; }
    .kl-chip.libur { background: #b42318; color: #fff; }
    .kl-chip.libur-mati { background: transparent; color: var(--admin-muted); border-color: var(--admin-border); text-decoration: line-through; }
    .kl-chip.cuti { background: #dbeafe; color: #1e40af; }
    .kl-chip.izin { background: #ccfbf1; color: #115e59; }
    .kl-chip.menunggu { background: transparent; border-style: dashed; border-color: currentColor; }
    .kl-legenda { display: flex; flex-wrap: wrap; gap: .4rem 1rem; padding: .7rem 1rem; font-size: .75rem; color: var(--admin-muted); border-top: 1px solid var(--admin-border); }
    .kl-legenda span { display: inline-flex; align-items: center; gap: .35rem; }
    .kl-legenda .kotak { width: 12px; height: 12px; border-radius: 3px; display: inline-block; }
    .kl-legenda .kotak.merah { background: #b42318; }
    .kl-legenda .kotak.cuti { background: #dbeafe; border: 1px solid #93c5fd; }
    .kl-legenda .kotak.izin { background: #ccfbf1; border: 1px solid #5eead4; }
    .kl-legenda .kotak.menunggu { border: 1px dashed var(--admin-muted); }
    .kl-legenda .kotak.akhir-pekan { background: rgba(148, 163, 184, .25); }
    .kl-daftar { max-height: 560px; overflow-y: auto; }
    .kl-daftar li { display: flex; gap: .75rem; padding: .65rem 1rem; border-bottom: 1px solid var(--admin-border); }
    .kl-daftar li:last-child { border-bottom: 0; }
    .kl-daftar li.mati { opacity: .65; }
    .kl-tgl { flex: none; width: 44px; text-align: center; border-radius: .5rem; background: #fdecec; color: #b42318; padding: .2rem 0; align-self: flex-start; }
    .kl-daftar li.mati .kl-tgl { background: var(--admin-surface-soft); color: var(--admin-muted); }
    .kl-tgl strong { display: block; font-size: 1.05rem; line-height: 1.1; }
    .kl-tgl small { font-size: .68rem; text-transform: uppercase; font-weight: 700; }
    .kl-isi { min-width: 0; flex: 1 1 auto; font-size: .86rem; }
    .kl-aksi { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
    .kl-aksi form { margin: 0; }
    .kl-sinkron { padding: .6rem 1rem; font-size: .75rem; color: var(--admin-muted); border-top: 1px solid var(--admin-border); background: var(--admin-surface-soft); }
    html[data-theme="dark"] .kl-sel.merah { background: rgba(180, 35, 24, .16); }
    html[data-theme="dark"] .kl-tgl { background: rgba(180, 35, 24, .18); color: #fca5a5; }
    html[data-theme="dark"] .kl-chip.cuti { background: rgba(59, 130, 246, .2); color: #bfdbfe; }
    html[data-theme="dark"] .kl-chip.izin { background: rgba(20, 184, 166, .2); color: #99f6e4; }
</style>
@if ($bisaKelola)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Klik tanggal (yang belum tanggal merah) → isi form tambah.
            const mulai = document.getElementById('kl-mulai');
            document.querySelectorAll('.kl-sel.bisa-pilih').forEach((sel) => sel.addEventListener('click', () => {
                mulai.value = sel.dataset.tanggal;
                document.getElementById('kl-selesai').value = '';
                document.getElementById('tambah-libur').scrollIntoView({ behavior: 'smooth', block: 'center' });
                document.getElementById('kl-nama').focus({ preventScroll: true });
            }));

            const modal = document.getElementById('modalUbahLibur');
            modal.addEventListener('show.bs.modal', (e) => {
                const t = e.relatedTarget, f = modal.querySelector('form');
                f.action = t.dataset.url;
                f.nama.value = t.dataset.nama;
                f.jenis.value = t.dataset.jenis;
                f.keterangan.value = t.dataset.keterangan || '';
                modal.querySelector('[data-tanggal]').textContent = t.dataset.tanggal;
                modal.querySelector('[data-info-api]').hidden = t.dataset.api !== '1';
            });
        });
    </script>
@endif
@endsection
