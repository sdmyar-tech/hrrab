@extends(Auth::user()->panelLayout())

@section('title', 'Kalender')

@section('content')
@php
    $hariKerja = config('hrrab.hari_kerja', [1, 2, 3, 4, 5]);
    $sebelum = $bulan->copy()->subMonth();
    $sesudah = $bulan->copy()->addMonth();
    // Agenda bulan ini (untuk daftar di HP & kolom samping): tanggal merah + cuti/izin saya.
    $liburBulanIni = $daftarTahun->filter(fn ($h) => $h->tanggal->isSameMonth($bulan));
    $agendaBulan = collect($hari)->filter(fn ($t) => $t->isSameMonth($bulan))
        ->flatMap(fn ($t) => collect($agenda[$t->toDateString()] ?? [])->map(fn ($a) => $a + ['tanggal' => $t]))->values();
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Kalender Pegawai" eyebrow="Kepegawaian"
            subtitle="Tanggal merah, hari kerja, serta cuti & izin Anda. Tanggal merah memengaruhi hitungan hari kerja, absensi, dan cuti.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'kalender.export', 'eksporParam' => ['tahun' => $tahun], 'eksporTeks' => "Ekspor {$tahun}"])
                @if ($bisaKelola)
                    <form action="{{ route('kalender.sinkron') }}" method="POST" class="m-0" data-confirm="Sinkronkan tanggal merah dari API sekarang? Tanggal merah manual tidak akan diubah.">
                        @csrf
                        <x-button type="submit" variant="secondary" icon="bi-arrow-repeat">Sinkronkan</x-button>
                    </form>
                @endif
            </x-slot:actions>
        </x-page-header>

        <div class="row g-4 align-items-start">
            {{-- ===== Kalender bulanan ===== --}}
            <div class="col-xl-8">
                <section class="hr-card kl-kartu" aria-label="Kalender {{ $bulan->translatedFormat('F Y') }}">
                    <div class="kl-kepala">
                        <div class="d-flex align-items-center gap-1">
                            <x-button variant="ghost" size="sm" class="btn-ikon" icon="bi-chevron-left" aria-label="Bulan sebelumnya"
                                :href="route('kalender.index', ['bulan' => $sebelum->format('Y-m'), 'tahun' => $sebelum->year])" />
                            <x-button variant="ghost" size="sm" class="btn-ikon" icon="bi-chevron-right" aria-label="Bulan berikutnya"
                                :href="route('kalender.index', ['bulan' => $sesudah->format('Y-m'), 'tahun' => $sesudah->year])" />
                        </div>
                        <h2 class="kl-judul">{{ $bulan->translatedFormat('F Y') }}</h2>
                        @unless ($bulan->isSameMonth(now()))
                            <x-button variant="secondary" size="sm" :href="route('kalender.index')">Hari ini</x-button>
                        @endunless
                        <x-badge tone="neutral" icon="bi-briefcase" class="ms-auto">{{ $hariKerjaBulan }} hari kerja</x-badge>
                    </div>

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
                                @if ($t->isToday()) aria-current="date" @endif
                                @if ($bisaKelola && ! $l) title="Klik untuk menjadikan tanggal merah" tabindex="0" @endif>
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

                    <div class="kl-legenda">
                        <span><i class="kotak merah"></i>Tanggal merah</span>
                        <span><i class="kotak cuti"></i>Cuti saya</span>
                        <span><i class="kotak izin"></i>Izin saya</span>
                        <span><i class="kotak menunggu"></i>Menunggu persetujuan</span>
                        <span><i class="kotak akhir-pekan"></i>Bukan hari kerja</span>
                    </div>
                </section>

                {{-- HP: rincian agenda bulan ini sebagai daftar (sel kalender hanya bertitik). --}}
                <x-card title="Agenda {{ $bulan->translatedFormat('F') }}" icon="bi-list-ul" class="mt-3 d-md-none">
                    <ul class="hr-list">
                        @forelse ($liburBulanIni->map(fn ($h) => ['tanggal' => $h->tanggal, 'teks' => $h->nama, 'jenis' => $h->aktif ? 'libur' : 'libur-mati', 'menunggu' => false])->concat($agendaBulan)->sortBy(fn ($a) => $a['tanggal']->timestamp) as $a)
                            <li class="hr-list-item">
                                <span class="dash-tanggal {{ $a['jenis'] === 'libur' ? 'is-merah' : '' }}"><span>{{ $a['tanggal']->translatedFormat('D') }}</span><strong>{{ $a['tanggal']->format('d') }}</strong></span>
                                <span class="hr-list-utama"><span class="hr-list-judul text-wrap">{{ $a['teks'] }}</span>
                                    <span class="hr-list-sub">{{ ['libur' => 'Tanggal merah', 'libur-mati' => 'Tanggal merah (nonaktif)', 'cuti' => 'Cuti saya', 'izin' => 'Izin saya'][$a['jenis']] ?? '' }}{{ $a['menunggu'] ? ' · menunggu persetujuan' : '' }}</span></span>
                            </li>
                        @empty
                            <li><x-empty-state icon="bi-calendar-check" compact>Tidak ada tanggal merah atau agenda bulan ini.</x-empty-state></li>
                        @endforelse
                    </ul>
                </x-card>
            </div>

            {{-- ===== Samping: bulan ini · tambah · setahun ===== --}}
            <div class="col-xl-4 d-grid gap-3">
                <x-card :title="'Tanggal Merah ' . $bulan->translatedFormat('F')" icon="bi-calendar-heart" class="d-none d-md-block">
                    <ul class="hr-list">
                        @forelse ($liburBulanIni as $h)
                            <li class="hr-list-item {{ $h->aktif ? '' : 'opacity-75' }}">
                                <span class="dash-tanggal {{ $h->aktif ? 'is-merah' : '' }}"><span>{{ $h->tanggal->translatedFormat('D') }}</span><strong>{{ $h->tanggal->format('d') }}</strong></span>
                                <span class="hr-list-utama"><span class="hr-list-judul text-wrap">{{ $h->nama }}</span><span class="hr-list-sub">{{ $h->jenisLabel() }}{{ $h->aktif ? '' : ' · nonaktif' }}</span></span>
                            </li>
                        @empty
                            <li><x-empty-state icon="bi-calendar-check" compact>Tidak ada tanggal merah di bulan ini.</x-empty-state></li>
                        @endforelse
                    </ul>
                </x-card>

                @if ($bisaKelola)
                    <x-card title="Tambah Tanggal Merah" icon="bi-plus-circle" id="tambah-libur">
                        <form action="{{ route('kalender.store') }}" method="POST" data-confirm="Simpan tanggal merah ini?">
                            @csrf
                            {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
                            @section('galat-di-halaman', '1')
                            <div class="row gx-2">
                                <div class="col-6"><x-input name="mulai" id="kl-mulai" type="date" label="Tanggal" required /></div>
                                <div class="col-6"><x-input name="selesai" id="kl-selesai" type="date" label="Sampai" optional /></div>
                            </div>
                            <x-input name="nama" id="kl-nama" label="Nama" maxlength="150" placeholder="Contoh: Milad Yayasan Abdurrab" required />
                            <x-select name="jenis" id="kl-jenis" label="Jenis" :options="\App\Models\HariLibur::JENIS" selected="yayasan" placeholder="Pilih jenis" required />
                            <x-input name="keterangan" id="kl-keterangan" label="Keterangan" optional maxlength="255" placeholder="Contoh: cuti bersama" />
                            <x-button type="submit" block icon="bi-calendar-plus">Simpan Tanggal Merah</x-button>
                            <p class="form-text mb-0 mt-2">Tip: klik tanggal di kalender untuk mengisi tanggal otomatis. Tanggal merah manual tidak pernah diubah sinkron API.</p>
                        </form>
                    </x-card>
                @endif

                <x-card :padding="false" class="kl-tahun">
                    <div class="kl-kepala">
                        <x-button variant="ghost" size="sm" class="btn-ikon" icon="bi-chevron-left" aria-label="Tahun sebelumnya"
                            :href="route('kalender.index', ['bulan' => $bulan->format('Y-m'), 'tahun' => $tahun - 1])" />
                        <h2 class="kl-judul kl-judul-kecil">Tanggal merah {{ $tahun }}</h2>
                        <x-button variant="ghost" size="sm" class="btn-ikon" icon="bi-chevron-right" aria-label="Tahun berikutnya"
                            :href="route('kalender.index', ['bulan' => $bulan->format('Y-m'), 'tahun' => $tahun + 1])" />
                        <x-badge tone="danger" class="ms-auto">{{ $daftarTahun->where('aktif', true)->count() }} hari</x-badge>
                    </div>
                    <ul class="kl-daftar list-unstyled mb-0">
                        @forelse ($daftarTahun as $h)
                            <li class="{{ $h->aktif ? '' : 'mati' }}">
                                <span class="dash-tanggal {{ $h->aktif ? 'is-merah' : '' }}"><span>{{ $h->tanggal->translatedFormat('M') }}</span><strong>{{ $h->tanggal->format('d') }}</strong></span>
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
                                            <x-button variant="ghost" size="sm" icon="bi-pencil" data-bs-toggle="modal" data-bs-target="#modalUbahLibur"
                                                :data-url="route('kalender.update', $h)" :data-nama="$h->nama" :data-jenis="$h->jenis" :data-keterangan="$h->keterangan"
                                                :data-tanggal="$h->tanggal->translatedFormat('l, d M Y')" :data-api="$h->dariApi() ? 1 : 0">Ubah</x-button>
                                            <form action="{{ route('kalender.alih', $h) }}" method="POST" class="m-0"
                                                data-confirm="{{ $h->aktif ? 'Nonaktifkan ' . $h->nama . '? Tanggal ini dihitung hari kerja biasa.' : 'Aktifkan lagi ' . $h->nama . ' sebagai tanggal merah?' }}">
                                                @csrf
                                                <x-button type="submit" variant="ghost" size="sm" :icon="$h->aktif ? 'bi-toggle-on' : 'bi-toggle-off'">{{ $h->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</x-button>
                                            </form>
                                            @unless ($h->dariApi())
                                                <form action="{{ route('kalender.destroy', $h) }}" method="POST" class="m-0" data-confirm="Hapus tanggal merah {{ $h->nama }}?">
                                                    @csrf @method('DELETE')
                                                    <x-button type="submit" variant="ghost" size="sm" icon="bi-trash" class="text-danger">Hapus</x-button>
                                                </form>
                                            @endunless
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li><x-empty-state icon="bi-calendar" compact>Belum ada tanggal merah di {{ $tahun }}.</x-empty-state></li>
                        @endforelse
                    </ul>
                    <x-slot:footer>
                        <p class="small text-muted mb-0">
                            @if ($sinkronTerakhir)
                                <i class="bi bi-cloud-check me-1" aria-hidden="true"></i>Sinkron otomatis terakhir:
                                {{ \Carbon\Carbon::parse($sinkronTerakhir['waktu'])->translatedFormat('d M Y H:i') }} dari {{ $sinkronTerakhir['sumber'] }}.
                            @else
                                <i class="bi bi-cloud me-1" aria-hidden="true"></i>Belum pernah sinkron otomatis — berjalan sendiri sekali sebulan.
                            @endif
                        </p>
                    </x-slot:footer>
                </x-card>
            </div>
        </div>
    </div>
</main>

@if ($bisaKelola)
    <div class="modal fade hr-modal" id="modalUbahLibur" tabindex="-1" aria-labelledby="judulUbahLibur" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered hr-modal-md">
            <form method="POST" class="modal-content" data-confirm="Simpan perubahan tanggal merah?">
                @csrf @method('PUT')
                <div class="modal-header">
                    <div class="d-flex align-items-start gap-3">
                        <span class="hr-modal-ikon hr-tone-danger"><i class="bi bi-calendar-heart" aria-hidden="true"></i></span>
                        <div>
                            <h2 class="modal-title" id="judulUbahLibur">Ubah tanggal merah</h2>
                            <p class="small text-muted mb-0 mt-1" data-tanggal></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="hr-field">
                        <label class="form-label required" for="ubah-nama">Nama</label>
                        <input type="text" name="nama" id="ubah-nama" class="form-control" maxlength="150" required>
                    </div>
                    <div class="hr-field">
                        <label class="form-label required" for="ubah-jenis">Jenis</label>
                        <select name="jenis" id="ubah-jenis" class="form-select" required>
                            @foreach (\App\Models\HariLibur::JENIS as $kode => $label)
                                <option value="{{ $kode }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-field mb-0">
                        <label class="form-label" for="ubah-keterangan">Keterangan <span class="hr-field-opsional">(opsional)</span></label>
                        <input type="text" name="keterangan" id="ubah-keterangan" class="form-control" maxlength="255" placeholder="Contoh: cuti bersama">
                    </div>
                    <x-alert tone="info" class="mt-3 mb-0" data-info-api hidden>Libur ini hasil sinkron API. Setelah diubah, statusnya menjadi <strong>manual</strong> dan tidak lagi diperbarui sinkron.</x-alert>
                </div>
                <div class="modal-footer">
                    <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
                    <x-button type="submit" icon="bi-check-lg">Simpan</x-button>
                </div>
            </form>
        </div>
    </div>
@endif

<style>
    .kl-kartu { overflow: hidden; }
    .kl-kepala { display: flex; flex-wrap: wrap; align-items: center; gap: var(--hr-space-2); padding: var(--hr-space-3) var(--hr-space-4); border-bottom: 1px solid var(--admin-border); }
    .kl-judul { margin: 0; font-size: var(--hr-text-xl); font-weight: var(--hr-weight-bold); color: var(--admin-heading); }
    .kl-judul-kecil { font-size: var(--hr-text-base); }
    .kl-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); }
    .kl-hari-nama { padding: var(--hr-space-2); font-size: var(--hr-text-xs); font-weight: var(--hr-weight-semibold); letter-spacing: .04em; text-transform: uppercase; color: var(--admin-muted); border-bottom: 1px solid var(--admin-border); background: var(--admin-surface-soft); }
    .kl-hari-nama.libur { color: var(--hr-danger); }
    .kl-sel { min-height: 104px; min-width: 0; padding: var(--hr-space-1) var(--hr-space-2) var(--hr-space-2); border-right: 1px solid var(--admin-border); border-bottom: 1px solid var(--admin-border); display: flex; flex-direction: column; gap: 3px; overflow: hidden; transition: background-color var(--hr-durasi) var(--hr-easing); }
    .kl-sel:nth-child(7n) { border-right: 0; }
    .kl-angka { font-weight: var(--hr-weight-semibold); font-size: var(--hr-text-sm); width: 28px; height: 28px; display: inline-grid; place-items: center; border-radius: 50%; color: var(--admin-heading); flex: none; }
    .kl-sel.luar { background: var(--admin-surface-soft); }
    .kl-sel.luar .kl-angka { color: var(--hr-gray-400); font-weight: var(--hr-weight-medium); }
    .kl-sel.akhir-pekan { background: var(--admin-surface-soft); }
    .kl-sel.akhir-pekan .kl-angka, .kl-sel.merah .kl-angka { color: var(--hr-danger); }
    .kl-sel.merah { background: var(--hr-danger-soft); }
    .kl-sel.kini .kl-angka { background: var(--hr-primary); color: #fff; }
    .kl-sel.bisa-pilih { cursor: pointer; }
    .kl-sel.bisa-pilih:hover, .kl-sel.bisa-pilih:focus-visible { background: var(--hr-primary-soft); outline: 0; box-shadow: inset 0 0 0 2px var(--hr-primary-200); }
    .kl-chip { display: block; font-size: var(--hr-text-xs); font-weight: var(--hr-weight-semibold); line-height: 1.3; padding: 2px 6px; border-radius: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: 1px solid transparent; }
    .kl-chip.libur { background: var(--hr-danger); color: #fff; }
    .kl-chip.libur-mati { background: transparent; color: var(--admin-muted); border-color: var(--admin-border); text-decoration: line-through; }
    .kl-chip.cuti { background: var(--hr-info-soft); color: var(--hr-info); }
    .kl-chip.izin { background: var(--hr-success-soft); color: var(--hr-success); }
    .kl-chip.menunggu { background: transparent; border-style: dashed; border-color: currentColor; }
    .kl-legenda { display: flex; flex-wrap: wrap; gap: var(--hr-space-2) var(--hr-space-4); padding: var(--hr-space-3) var(--hr-space-4); font-size: var(--hr-text-xs); color: var(--admin-muted); }
    .kl-legenda span { display: inline-flex; align-items: center; gap: 6px; }
    .kl-legenda .kotak { width: 12px; height: 12px; border-radius: 3px; display: inline-block; }
    .kl-legenda .kotak.merah { background: var(--hr-danger); }
    .kl-legenda .kotak.cuti { background: var(--hr-info-soft); border: 1px solid var(--hr-info); }
    .kl-legenda .kotak.izin { background: var(--hr-success-soft); border: 1px solid var(--hr-success); }
    .kl-legenda .kotak.menunggu { border: 1px dashed var(--admin-muted); }
    .kl-legenda .kotak.akhir-pekan { background: var(--admin-surface-soft); border: 1px solid var(--admin-border); }
    .dash-tanggal.is-merah { border-color: var(--hr-danger-soft); background: var(--hr-danger-soft); }
    .dash-tanggal.is-merah span, .dash-tanggal.is-merah strong { color: var(--hr-danger); }
    .kl-daftar { max-height: 560px; overflow-y: auto; }
    .kl-daftar li { display: flex; gap: var(--hr-space-3); padding: var(--hr-space-3) var(--hr-space-4); border-bottom: 1px solid var(--admin-border); }
    .kl-daftar li:last-child { border-bottom: 0; }
    .kl-daftar li.mati { opacity: .7; }
    .kl-isi { min-width: 0; flex: 1 1 auto; font-size: var(--hr-text-sm); }
    .kl-aksi { display: flex; flex-wrap: wrap; gap: var(--hr-space-1); margin-top: var(--hr-space-2); }
    .kl-tahun .hr-card-body { padding: 0; }
    /* HP: sel ringkas (angka + titik warna), rincian di daftar agenda di bawah kalender. */
    @media (max-width: 767.98px) {
        .kl-sel { min-height: 52px; align-items: center; padding: var(--hr-space-1) 2px; gap: 2px; flex-direction: row; flex-wrap: wrap; justify-content: center; align-content: flex-start; }
        .kl-angka { width: 26px; height: 26px; font-size: var(--hr-text-xs); flex-basis: 100%; max-width: 26px; margin-inline: auto; }
        .kl-chip { width: 6px; height: 6px; padding: 0; border-radius: 50%; font-size: 0; border-width: 0; }
        .kl-chip.menunggu { border: 1px dashed var(--hr-info); background: transparent; }
        .kl-hari-nama { padding: var(--hr-space-2) 0; text-align: center; font-size: .625rem; }
    }
</style>
@if ($bisaKelola)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Klik / Enter pada tanggal (yang belum tanggal merah) → isi form tambah.
            const mulai = document.getElementById('kl-mulai');
            document.querySelectorAll('.kl-sel.bisa-pilih').forEach((sel) => {
                const pilih = () => {
                    mulai.value = sel.dataset.tanggal;
                    document.getElementById('kl-selesai').value = '';
                    document.getElementById('tambah-libur').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    document.getElementById('kl-nama').focus({ preventScroll: true });
                };
                sel.addEventListener('click', pilih);
                sel.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pilih(); } });
            });

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
