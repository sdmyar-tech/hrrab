@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $alasanCepat = ['Hujan deras', 'Banjir / jalan tergenang', 'Gangguan listrik', 'Acara yayasan', 'Rapat di luar'];
    $jumlahJenis = ['telat' => $izins->where('jenis', \App\Models\IzinAbsensi::JENIS_TELAT)->count(), 'pulang_cepat' => $izins->where('jenis', \App\Models\IzinAbsensi::JENIS_PULANG_CEPAT)->count()];
    $hariIni = now()->startOfDay();
    $ikonJenis = ['telat' => 'bi-alarm', 'pulang_cepat' => 'bi-door-open'];
    $toneJenis = ['telat' => 'warning', 'pulang_cepat' => 'info'];
    // Form dibuka otomatis bila ada galat isian, atau datang dari Absensi Harian (?tanggal=).
    $bukaForm = $errors->any() || request()->filled('tanggal');
    $urlBulan = fn ($b) => route('admin.absensi.izin', ['bulan' => $b->format('Y-m')]);
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Izin Telat & Pulang Cepat" eyebrow="Absensi"
            subtitle="Beri kelonggaran pada hari tertentu (mis. hujan deras). Yang datang dalam batas izin dicatat Telat Izin, bukan Telat.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.absensi.izin.export', 'eksporParam' => ['bulan' => $bulan->format('Y-m')]])
                <x-button icon="bi-plus-lg" data-bs-toggle="modal" data-bs-target="#modalIzin">Beri Izin</x-button>
            </x-slot:actions>
        </x-page-header>

        @include('admin.absensi._nav')

        <section class="abs-periode" aria-label="Pilih bulan">
            <div class="abs-periode-nav">
                <x-icon-button icon="bi-chevron-left" label="Bulan sebelumnya" :href="$urlBulan($bulan->copy()->subMonth())" />
                <div class="abs-periode-judul">
                    <p class="abs-periode-label">{{ $bulan->translatedFormat('F Y') }}</p>
                    <span class="abs-periode-sub">Izin yang berlaku pada bulan ini</span>
                </div>
                <x-icon-button icon="bi-chevron-right" label="Bulan berikutnya" :href="$urlBulan($bulan->copy()->addMonth())" />
            </div>
            @unless ($bulan->isSameMonth(now()))
                <div class="abs-periode-cepat"><x-chip :href="route('admin.absensi.izin')" icon="bi-calendar-check">Bulan ini</x-chip></div>
            @endunless
        </section>

        <section class="row g-3 mb-4" aria-label="Ringkasan izin {{ $bulan->translatedFormat('F Y') }}">
            <div class="col-12 col-md-4">
                <x-stat-card label="Total izin" :value="$izins->count()" :icon="$ik['izin']" tone="primary"
                    :meta="$izins->pluck('tanggal')->map->toDateString()->unique()->count() . ' tanggal berbeda'" />
            </div>
            <div class="col-6 col-md-4">
                <x-stat-card label="Izin telat" :value="$jumlahJenis['telat']" icon="bi-alarm" tone="warning" meta="Boleh datang terlambat" />
            </div>
            <div class="col-6 col-md-4">
                <x-stat-card label="Izin pulang cepat" :value="$jumlahJenis['pulang_cepat']" icon="bi-door-open" tone="info" meta="Boleh pulang lebih awal" />
            </div>
        </section>

        <x-card :padding="false" aria-label="Daftar izin {{ $bulan->translatedFormat('F Y') }}">
            <x-filter-bar search="Cari pegawai, bagian, atau alasan, contoh: hujan" search-label="Cari izin menurut pegawai, bagian, atau alasan" search-id="cariIzin">
                <x-slot:chips>
                    <x-chip active :count="$izins->count()" data-saring-jenis="">Semua jenis</x-chip>
                    @foreach (\App\Models\IzinAbsensi::JENIS as $k => $label)
                        <x-chip :count="$jumlahJenis[$k] ?? 0" :icon="$ikonJenis[$k]" data-saring-jenis="{{ $k }}">{{ $label }}</x-chip>
                    @endforeach
                </x-slot:chips>
                <x-slot:summary>
                    <p class="hr-filter-bar-jumlah" aria-live="polite"><strong data-jumlah-izin>{{ $izins->count() }}</strong> izin ditampilkan</p>
                </x-slot:summary>
            </x-filter-bar>

            <x-table caption="Daftar izin telat dan pulang cepat" id="tabelIzin" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Berlaku Untuk</th>
                        <th class="hr-angka">Kelonggaran</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($izins as $z)
                    @php
                        $status = match (true) {
                            $z->tanggal->gt($hariIni) => ['info', 'Akan berlaku'],
                            $z->tanggal->isSameDay($hariIni) => ['success', 'Berlaku hari ini'],
                            default => ['neutral', 'Sudah lewat'],
                        };
                        $sasaran = $z->data_pegawai_id ? ['bi-person', 'Satu pegawai'] : ($z->unit_kerja ? ['bi-diagram-3', 'Satu bagian'] : ['bi-people', 'Semua pegawai']);
                    @endphp
                    <tr data-jenis="{{ $z->jenis }}" data-cari="{{ mb_strtolower($z->sasaranLabel() . ' ' . $z->alasan . ' ' . ($z->pembuat?->name ?? '')) }}">
                        <td class="text-nowrap">
                            <span class="hr-sel-judul">{{ $z->tanggal->translatedFormat('d M Y') }}</span>
                            <span class="hr-sel-sub">{{ $z->tanggal->translatedFormat('l') }}</span>
                        </td>
                        <td><x-badge :tone="$toneJenis[$z->jenis] ?? 'neutral'" :icon="$ikonJenis[$z->jenis] ?? null">{{ \App\Models\IzinAbsensi::JENIS[$z->jenis] ?? $z->jenis }}</x-badge></td>
                        <td>
                            <span class="d-block">{{ $z->sasaranLabel() }}</span>
                            <span class="hr-sel-sub"><i class="bi {{ $sasaran[0] }} me-1" aria-hidden="true"></i>{{ $sasaran[1] }}</span>
                        </td>
                        <td class="hr-angka"><span class="abs-jam">{{ $z->menit }}</span> menit</td>
                        <td>
                            <span class="d-block">{{ $z->alasan }}</span>
                            <span class="hr-sel-sub">oleh {{ $z->pembuat?->name ?? '-' }}</span>
                        </td>
                        <td><x-badge :tone="$status[0]" dot>{{ $status[1] }}</x-badge></td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                <x-icon-button :icon="$ik['harian']" label="Lihat absensi tanggal ini" :href="route('admin.absensi.harian', ['tanggal' => $z->tanggal->toDateString()])" />
                                <form action="{{ route('admin.absensi.izin.destroy', $z) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <x-icon-button type="submit" icon="bi-trash" label="Hapus izin" tone="danger"
                                        data-confirm="true" data-icon="warning" data-confirm-text="Ya, Hapus" data-cancel-text="Batal"
                                        data-title="Hapus izin?" data-text="Status absensi {{ $z->tanggal->translatedFormat('d M Y') }} dihitung ulang tanpa izin ini." />
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="7" icon="bi-cloud-sun" title="Belum ada izin pada {{ $bulan->translatedFormat('F Y') }}">
                        Beri izin telat atau pulang cepat bila ada kejadian seperti hujan deras atau acara yayasan.
                        <x-slot:action><x-button icon="bi-plus-lg" data-bs-toggle="modal" data-bs-target="#modalIzin">Beri Izin</x-button></x-slot:action>
                    </x-table-empty>
                @endforelse
                @if ($izins->isNotEmpty())
                    <x-table-empty :colspan="7" icon="bi-search" title="Tidak ada izin yang cocok" data-kosong-izin hidden>
                        Coba kata kunci atau jenis izin lain.
                    </x-table-empty>
                @endif
            </x-table>
        </x-card>
    </div>
</main>

<x-modal id="modalIzin" title="Beri Izin" subtitle="Kelonggaran berlaku untuk tanggal yang dipilih." :icon="$ik['izin']" size="lg" scrollable>
    <form action="{{ route('admin.absensi.izin.store') }}" method="POST" id="formIzin">
        @csrf
        <x-form-section title="Jenis izin" icon="bi-ui-radios" id="izin-jenis">
            <div class="col-12">
                <div class="hr-opsi-grup" role="radiogroup" aria-label="Jenis izin">
                    @foreach (\App\Models\IzinAbsensi::JENIS as $k => $label)
                        <label class="hr-opsi" for="j-{{ $k }}">
                            <input class="form-check-input" type="radio" name="jenis" value="{{ $k }}" id="j-{{ $k }}" @checked(old('jenis', 'telat') === $k)
                                data-menit="{{ $k === 'telat' ? config('hrrab.absensi.izin_telat_menit', 60) : config('hrrab.absensi.izin_pulang_menit', 60) }}">
                            <span>
                                <strong><i class="bi {{ $ikonJenis[$k] }} me-1" aria-hidden="true"></i>{{ $label }}</strong>
                                <small>{{ $k === 'telat' ? 'Boleh datang terlambat' : 'Boleh pulang lebih awal' }}</small>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </x-form-section>

        <x-form-section title="Kapan & berapa lama" icon="bi-calendar-event" id="izin-kapan">
            <div class="col-sm-7">
                <x-input type="date" name="tanggal" id="i-tanggal" label="Tanggal" :value="$tanggalAwal" required />
            </div>
            <div class="col-sm-5">
                <div class="hr-field">
                    <label class="form-label required" for="i-menit">Kelonggaran</label>
                    <div class="input-group">
                        <input type="number" name="menit" id="i-menit" class="form-control @error('menit') is-invalid @enderror" min="1" max="480"
                            value="{{ old('menit', config('hrrab.absensi.izin_telat_menit', 60)) }}" placeholder="Contoh: 60" required aria-describedby="i-menit-bantuan">
                        <span class="input-group-text">menit</span>
                    </div>
                    @if ($errors->has('menit'))
                        <div class="invalid-feedback d-block" id="i-menit-bantuan"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $errors->first('menit') }}</div>
                    @else
                        <div class="form-text" id="i-menit-bantuan">Antara 1 dan 480 menit.</div>
                    @endif
                </div>
            </div>
        </x-form-section>

        <x-form-section title="Untuk siapa" icon="bi-people" id="izin-sasaran">
            <div class="col-12">
                <div class="hr-segmen" role="radiogroup" aria-label="Sasaran izin">
                    @foreach (['semua' => 'Semua pegawai', 'unit' => 'Satu bagian', 'pegawai' => 'Pilih pegawai'] as $k => $label)
                        <input type="radio" class="btn-check" name="sasaran" value="{{ $k }}" id="s-{{ $k }}" @checked(old('sasaran', 'semua') === $k)>
                        <label for="s-{{ $k }}">{{ $label }}</label>
                    @endforeach
                </div>
            </div>
            <div class="col-12" data-sasaran="unit">
                <x-select name="unit_kerja" id="i-unit" label="Bagian" :options="$unitOpsi->mapWithKeys(fn ($u) => [$u => $u])->all()" placeholder="Pilih bagian" />
            </div>
            <div class="col-12" data-sasaran="pegawai">
                <label class="form-label" for="i-cari-pegawai">Pegawai</label>
                <input type="search" id="i-cari-pegawai" class="form-control form-control-sm" placeholder="Saring nama pegawai, contoh: Siti" data-cari-izin autocomplete="off">
                <div class="abs-pilih-pegawai @error('pegawai_ids') border-danger @enderror" role="group" aria-label="Daftar pegawai">
                    @forelse ($pegawais as $p)
                        <div class="form-check" data-nama="{{ mb_strtolower($p->nama_lengkap) }}">
                            <input class="form-check-input" type="checkbox" name="pegawai_ids[]" value="{{ $p->id }}" id="ip-{{ $p->id }}" @checked(in_array($p->id, old('pegawai_ids', [])))>
                            <label class="form-check-label small" for="ip-{{ $p->id }}">{{ $p->nama_lengkap }}</label>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">Belum ada pegawai yang ikut absensi.</p>
                    @endforelse
                </div>
                <p class="form-text mb-0 mt-1" aria-live="polite"><span data-jumlah-pilih-izin>0</span> pegawai dipilih</p>
            </div>
        </x-form-section>

        <x-form-section title="Alasan" icon="bi-chat-left-text" id="izin-alasan">
            <div class="col-12">
                <div class="abs-chip-baris mb-2" role="group" aria-label="Alasan cepat">
                    @foreach ($alasanCepat as $a)<button type="button" class="hr-chip" data-alasan-izin="{{ $a }}" aria-pressed="false">{{ $a }}</button>@endforeach
                </div>
                <x-input name="alasan" maxlength="255" placeholder="Tulis alasan lain, contoh: banjir di jalan utama" required aria-label="Alasan izin" />
            </div>
        </x-form-section>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formIzin" icon="bi-check-lg">Simpan Izin</x-button>
    </x-slot:footer>
</x-modal>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ---- Form Beri Izin (modal) ----
        const form = document.getElementById('formIzin');
        const sinkron = function () {
            const s = form.querySelector('[name="sasaran"]:checked').value;
            form.querySelectorAll('[data-sasaran]').forEach((el) => { el.hidden = el.dataset.sasaran !== s; });
        };
        form.querySelectorAll('[name="sasaran"]').forEach((r) => r.addEventListener('change', sinkron));
        sinkron();
        form.querySelectorAll('[name="jenis"]').forEach((r) => r.addEventListener('change', function () { form.menit.value = r.dataset.menit; }));
        form.querySelectorAll('[data-alasan-izin]').forEach((b) => b.addEventListener('click', function () {
            form.alasan.value = b.dataset.alasanIzin;
            form.querySelectorAll('[data-alasan-izin]').forEach((x) => { x.classList.toggle('is-active', x === b); x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
        }));
        form.querySelector('[data-cari-izin]').addEventListener('input', function () {
            const kata = this.value.trim().toLowerCase();
            form.querySelectorAll('[data-nama]').forEach((el) => { el.hidden = kata !== '' && !el.dataset.nama.includes(kata); });
        });
        const jumlahPilih = form.querySelector('[data-jumlah-pilih-izin]');
        const hitungPilih = () => { jumlahPilih.textContent = form.querySelectorAll('[name="pegawai_ids[]"]:checked').length; };
        form.querySelectorAll('[name="pegawai_ids[]"]').forEach((c) => c.addEventListener('change', hitungPilih));
        hitungPilih();

        @if ($bukaForm)
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalIzin')).show();
            // Tanggal dari Absensi Harian sudah terisi; lepas dari URL agar muat ulang / hapus izin tidak membuka form lagi.
            const url = new URL(location.href);
            if (url.searchParams.has('tanggal')) { url.searchParams.delete('tanggal'); history.replaceState(null, '', url); }
        @endif

        // ---- Saring daftar di sisi klien: kata kunci + jenis ----
        const tabel = document.getElementById('tabelIzin');
        const cari = document.getElementById('cariIzin');
        const chip = document.querySelectorAll('[data-saring-jenis]');
        const jumlah = document.querySelector('[data-jumlah-izin]');
        const kosong = document.querySelector('[data-kosong-izin]');
        let jenis = '';
        const saring = function () {
            const kata = cari.value.trim().toLowerCase();
            let tampil = 0;
            tabel.querySelectorAll('tbody tr[data-jenis]').forEach(function (tr) {
                const cocok = (!jenis || tr.dataset.jenis === jenis) && tr.dataset.cari.includes(kata);
                tr.hidden = !cocok;
                tampil += cocok ? 1 : 0;
            });
            jumlah.textContent = tampil;
            if (kosong) kosong.hidden = tampil > 0;
        };
        chip.forEach((b) => b.addEventListener('click', function () {
            jenis = b.dataset.saringJenis;
            chip.forEach((x) => { x.classList.toggle('is-active', x === b); x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
            saring();
        }));
        cari.addEventListener('input', saring);
    });
</script>
@endsection
