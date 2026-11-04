@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $filterAktif = request()->only(['q', 'unit', 'ikut', 'masalah', 'status']);
    $hariBawaan = config('hrrab.hari_kerja', [1, 2, 3, 4, 5]);
    $urlTab = fn ($t) => route('admin.absensi.pengaturan', ['tab' => $t]);
    $adaFilter = (bool) array_filter($filterAktif);
    $masalahLabel = ['tanpa_jadwal' => 'Jam kerja belum diatur', 'tanpa_finger' => 'Belum punya ID mesin'];
    // Kartu ringkasan masalah (klik untuk langsung menyaring).
    $kartu = [
        ['Ikut absensi', $statistik['ikut'], 'bi-person-check', 'success', route('admin.absensi.pengaturan', ['ikut' => 'ya']),
            $tab === 'pegawai' && request('ikut') === 'ya' && ! request('masalah'), 'Pegawai aktif yang dinilai'],
        ['Jam kerja belum diatur', $statistik['tanpa_jadwal'], 'bi-clock', $statistik['tanpa_jadwal'] ? 'danger' : 'neutral', route('admin.absensi.pengaturan', ['masalah' => 'tanpa_jadwal']),
            $tab === 'pegawai' && request('masalah') === 'tanpa_jadwal', $statistik['tanpa_jadwal'] ? 'Belum bisa dinilai telat' : 'Semua sudah diatur'],
        ['Belum punya ID mesin', $statistik['tanpa_finger'], $ik['mesin'], $statistik['tanpa_finger'] ? 'warning' : 'neutral', route('admin.absensi.pengaturan', ['masalah' => 'tanpa_finger']),
            $tab === 'pegawai' && request('masalah') === 'tanpa_finger', $statistik['tanpa_finger'] ? 'Data mesin belum terbaca' : 'Semua sudah punya ID'],
        ['ID mesin belum dikenali', $belumDipetakan->count(), 'bi-link-45deg', $belumDipetakan->count() ? 'warning' : 'neutral', $urlTab('pemetaan'),
            $tab === 'pemetaan', $belumDipetakan->count() ? 'Perlu dihubungkan ke pegawai' : 'Semua sudah terhubung'],
    ];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Pengaturan Absensi" eyebrow="Absensi" subtitle="Tentukan siapa yang diabsen, hubungkan ID di mesin, dan atur jam kerja tiap pegawai.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.absensi.pengaturan.export'])
                <x-button variant="secondary" icon="bi-clipboard-plus" data-bs-toggle="modal" data-bs-target="#modalDaftar"
                    title="Centang banyak pegawai sekaligus dari daftar NIP/nama (Excel)">Tempel Daftar</x-button>
            </x-slot:actions>
        </x-page-header>

        @include('admin.absensi._nav')

        @if (session('daftar_gagal'))
            <x-alert tone="warning" class="mb-4" :title="count(session('daftar_gagal')) . ' nama tidak ditemukan / ganda'">
                Centang manual di daftar pegawai:
                <ul class="mb-0 mt-1 ps-3">@foreach (session('daftar_gagal') as $g)<li>{{ $g }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <section class="row g-3 mb-4" aria-label="Ringkasan pengaturan absensi">
            @foreach ($kartu as [$label, $nilai, $ikon, $tone, $url, $aktif, $meta])
                <div class="col-6 col-xl-3">
                    <x-stat-card :label="$label" :value="$nilai" :icon="$ikon" :tone="$tone" :href="$url" :meta="$meta" @class(['hr-stat-aktif' => $aktif]) />
                </div>
            @endforeach
        </section>

        <x-card :padding="false" aria-label="Pengaturan absensi">
            <nav class="hr-tabs abs-tab-kartu" aria-label="Bagian pengaturan">
                <a class="hr-tab {{ $tab === 'pegawai' ? 'active' : '' }}" href="{{ $urlTab('pegawai') }}" @if ($tab === 'pegawai') aria-current="page" @endif>
                    <i class="bi bi-people" aria-hidden="true"></i>Pegawai & Jam Kerja
                </a>
                <a class="hr-tab {{ $tab === 'pemetaan' ? 'active' : '' }}" href="{{ $urlTab('pemetaan') }}" @if ($tab === 'pemetaan') aria-current="page" @endif>
                    <i class="bi bi-link-45deg" aria-hidden="true"></i>Hubungkan ID Mesin
                    @if ($belumDipetakan->count())<span class="hr-tab-badge">{{ $belumDipetakan->count() }}</span>@endif
                </a>
            </nav>

            @if ($tab === 'pegawai')
                <x-filter-bar :action="route('admin.absensi.pengaturan')" id="filterPengaturan"
                    search="Cari pegawai, contoh: Siti atau 1023" search-label="Cari pegawai menurut nama, NIP, ID mesin, bagian, atau jabatan"
                    :count="$jumlahFilter" unit="pegawai" :filtered="$adaFilter" :reset="$urlTab('pegawai')">
                    <x-slot:hidden>
                        <input type="hidden" name="tab" value="pegawai">
                        @if (request('masalah'))<input type="hidden" name="masalah" value="{{ request('masalah') }}">@endif
                        @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                    </x-slot:hidden>
                    <select name="unit" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Saring bagian">
                        <option value="">Semua bagian</option>
                        @foreach ($unitOpsi as $u)<option value="{{ $u }}" @selected(request('unit') === $u)>{{ $u }}</option>@endforeach
                    </select>
                    <select name="ikut" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Saring ikut absensi">
                        <option value="">Semua pegawai</option>
                        <option value="ya" @selected(request('ikut') === 'ya')>Yang ikut absensi</option>
                        <option value="tidak" @selected(request('ikut') === 'tidak')>Yang tidak ikut</option>
                    </select>
                    @include('partials.urut', ['urutForm' => 'filterPengaturan'])
                    @if (isset($masalahLabel[request('masalah')]))
                        <x-slot:chips>
                            <x-chip :href="request()->fullUrlWithoutQuery(['masalah', 'page'])" active icon="bi-x-lg" aria-label="Lepas saringan {{ $masalahLabel[request('masalah')] }}">
                                {{ $masalahLabel[request('masalah')] }}
                            </x-chip>
                        </x-slot:chips>
                    @endif
                    @if ($jumlahFilter)
                        <x-slot:summary>
                            <x-button variant="outline" size="sm" icon="bi-clock" data-massal="jadwal" data-sasaran="filter">Atur jam kerja semua ({{ $jumlahFilter }})</x-button>
                            <span class="hr-filter-bar-jumlah d-none d-md-inline">atau centang beberapa pegawai untuk mengubah sekaligus</span>
                        </x-slot:summary>
                    @endif
                </x-filter-bar>

                <x-table caption="Pegawai dan jam kerja" sticky striped scroll>
                    <x-slot:head>
                        <tr>
                            <th><input type="checkbox" class="form-check-input" data-pilih-semua-baris aria-label="Pilih semua di halaman ini"></th>
                            <th>Pegawai</th>
                            <th>Diabsen?</th>
                            <th>ID Mesin</th>
                            <th>Jam Kerja</th>
                            <th class="hr-kolom-aksi">Aksi</th>
                        </tr>
                    </x-slot:head>
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
                                <div class="hr-sel-utama">
                                    <x-avatar :name="$p->nama_lengkap" size="sm" />
                                    <div class="min-w-0">
                                        <span class="hr-sel-judul">{{ $p->nama_lengkap }}</span>
                                        <span class="hr-sel-sub">{{ $p->kepegawaian?->unit_kerja ?? '-' }}{{ $p->nip ? ' · ' . $p->nip : '' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <form action="{{ route('admin.absensi.pengaturan.ikut', $p) }}" method="POST" class="m-0">
                                    @csrf
                                    <input type="hidden" name="ikut" value="{{ $p->ikut_absensi ? 0 : 1 }}">
                                    <div class="form-check form-switch hr-toggle m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="ikut-{{ $p->id }}" @checked($p->ikut_absensi) onchange="this.form.submit()">
                                        <label class="form-check-label small" for="ikut-{{ $p->id }}">{{ $p->ikut_absensi ? 'Ya' : 'Tidak' }}</label>
                                    </div>
                                </form>
                            </td>
                            <td>
                                @if ($p->finger_id)
                                    <x-badge tone="neutral" :icon="$ik['mesin']">{{ $p->finger_id }}</x-badge>
                                @elseif ($p->ikut_absensi)
                                    <x-badge tone="warning" icon="bi-exclamation-circle">Belum ada</x-badge>
                                @else
                                    <span class="abs-nol">–</span>
                                @endif
                            </td>
                            <td>
                                @if ($j)
                                    <span class="abs-jam">{{ substr($j->jam_masuk, 0, 5) }}–{{ substr($j->jam_pulang, 0, 5) }}</span>
                                    <span class="hr-sel-sub">{{ \Illuminate\Support\Str::after($j->ringkas(), ' · ') }} · toleransi {{ $j->toleransi() }} m</span>
                                @elseif ($p->ikut_absensi)
                                    <x-badge tone="danger" icon="bi-clock">Belum diatur</x-badge>
                                @else
                                    <span class="abs-nol">–</span>
                                @endif
                            </td>
                            <td class="text-end hr-kolom-aksi">
                                <div class="hr-aksi-baris">
                                    <x-icon-button icon="bi-pencil" label="Ubah pengaturan" tone="primary" aria-label="Ubah pengaturan absensi {{ $p->nama_lengkap }}"
                                        :data-atur-pegawai="json_encode($dataPegawai)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-table-empty :colspan="6" :icon="$adaFilter ? 'bi-search' : 'bi-people'" :title="$adaFilter ? 'Tidak ada pegawai yang cocok' : 'Belum ada data pegawai'">
                            {{ $adaFilter ? 'Coba kata kunci atau saringan lain.' : 'Data pegawai aktif akan tampil di sini.' }}
                            @if ($adaFilter)
                                <x-slot:action><x-button :href="$urlTab('pegawai')" variant="secondary" icon="bi-x-circle">Reset filter</x-button></x-slot:action>
                            @endif
                        </x-table-empty>
                    @endforelse
                </x-table>

                @if ($pegawais->hasPages())
                    <x-slot:footer><x-pagination :paginator="$pegawais" /></x-slot:footer>
                @endif

            @elseif ($tab === 'pemetaan')
                <div class="abs-petakan pt-4">
                    <x-alert tone="info" class="mb-0">
                        ID dari mesin yang belum terhubung ke pegawai. Pilih pegawainya lalu klik <strong>Hubungkan</strong> — semua data absen ID itu langsung terbaca.
                        Sistem menebak pegawai dari nama di mesin bila cukup mirip.
                    </x-alert>
                    @forelse ($belumDipetakan as $b)
                        <form action="{{ route('admin.absensi.pengaturan.petakan') }}" method="POST" class="abs-petakan-item {{ $b->saran ? 'is-saran' : '' }}">
                            @csrf
                            <input type="hidden" name="finger_id" value="{{ $b->finger_id }}">
                            <div class="abs-petakan-id">
                                <x-badge tone="neutral" :icon="$ik['mesin']" size="lg">ID {{ $b->finger_id }}</x-badge>
                                <div class="min-w-0">
                                    <span class="hr-sel-judul">{{ $b->nama_mesin ?? 'Tanpa nama' }}</span>
                                    <span class="hr-sel-sub">{{ $b->hari }} hari data · terakhir {{ \Carbon\Carbon::parse($b->terakhir)->translatedFormat('d M Y') }}</span>
                                </div>
                            </div>
                            <div class="abs-petakan-pilih">
                                <label class="form-label required" for="petakan-{{ $b->finger_id }}">Pegawai untuk ID {{ $b->finger_id }}</label>
                                <select name="data_pegawai_id" id="petakan-{{ $b->finger_id }}" class="form-select" required>
                                    <option value="">Pilih pegawai</option>
                                    @foreach ($pegawaiTanpaFinger as $pt)
                                        <option value="{{ $pt->id }}" @selected($b->saran === $pt->id)>{{ $pt->nama_lengkap }} · {{ $pt->kepegawaian?->unit_kerja ?? '-' }}</option>
                                    @endforeach
                                </select>
                                @if ($b->saran)<span class="abs-saran"><i class="bi bi-magic" aria-hidden="true"></i>Disarankan dari kemiripan nama — periksa dulu.</span>@endif
                            </div>
                            <div class="abs-petakan-aksi">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="ikut_absensi" value="1" id="ikut-{{ $b->finger_id }}" checked>
                                    <label class="form-check-label" for="ikut-{{ $b->finger_id }}">Sekaligus ikutkan absensi</label>
                                </div>
                                <x-button type="submit" icon="bi-link-45deg">Hubungkan</x-button>
                            </div>
                        </form>
                    @empty
                        <x-empty-state icon="bi-check2-circle" title="Semua ID mesin sudah terhubung" compact>
                            ID baru akan muncul di sini setelah import bila belum dikenali.
                        </x-empty-state>
                    @endforelse
                </div>
            @endif
        </x-card>

        {{-- Bilah aksi massal: muncul saat ada pegawai dicentang. --}}
        <div class="hr-bilah-aksi mt-3" data-aksi-bar hidden>
            <p class="hr-filter-bar-jumlah mb-0" aria-live="polite"><strong data-jumlah-pilih>0</strong> pegawai dipilih</p>
            <div class="d-flex flex-wrap gap-2 ms-auto">
                <x-button variant="ghost" size="sm" data-batal-pilih>Batal</x-button>
                <x-button variant="danger-outline" size="sm" icon="bi-x-lg" data-massal="tidak_ikut" data-sasaran="terpilih">Keluarkan</x-button>
                <x-button variant="secondary" size="sm" icon="bi-check2" data-massal="ikut" data-sasaran="terpilih">Ikutkan absensi</x-button>
                <x-button size="sm" icon="bi-clock" data-massal="jadwal" data-sasaran="terpilih">Atur jam kerja</x-button>
            </div>
        </div>
    </div>
</main>

{{-- Modal: centang "ikut absensi" dari daftar tempel (dipakai saat persiapan awal) --}}
<x-modal id="modalDaftar" title="Tempel Daftar Pegawai" subtitle="Centang “ikut absensi” untuk banyak pegawai sekaligus." icon="bi-clipboard-plus" size="lg" scrollable>
    <form action="{{ route('admin.absensi.pengaturan.aktifkan-daftar') }}" method="POST" id="formDaftar">
        @csrf
        <p class="small text-muted">Salin kolom NIP & nama dari Excel, lalu tempel di bawah (satu pegawai per baris). Dicocokkan lewat NIP, lalu nama. Nama yang tidak ketemu akan ditampilkan.</p>
        <x-textarea name="daftar" id="daftar-tempel" label="Daftar NIP / nama pegawai" rows="10" required field-class="mb-0"
            placeholder="Satu pegawai per baris, contoh:&#10;2020220197001  Elyanora, A.md&#10;2020220419002  Neneng Sumarni&#10;Tamin" />
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formDaftar" icon="bi-check2-square">Centang yang Cocok</x-button>
    </x-slot:footer>
</x-modal>

{{-- Modal: ubah satu pegawai (status, ID mesin, jam & hari kerja) --}}
<x-modal id="modalPegawai" title="Pengaturan Absensi" :icon="$ik['pengaturan']" size="lg" scrollable>
    <form method="POST" id="formPegawai">
        @csrf
        @method('PUT')
        <x-form-section title="Status absensi" icon="bi-person-check" id="p-bagian-status">
            <div class="col-sm-6">
                <div class="form-check form-switch hr-toggle">
                    <input class="form-check-input" type="checkbox" role="switch" name="ikut_absensi" value="1" id="p-ikut">
                    <label class="form-check-label fw-semibold" for="p-ikut">Pegawai ini diabsen</label>
                    <div class="form-text mt-0">Matikan untuk pegawai yang tidak absen di mesin.</div>
                </div>
            </div>
            <div class="col-sm-6">
                <x-input name="finger_id" id="p-finger" label="ID di mesin absen (AC-No.)" inputmode="numeric" pattern="\d{1,12}"
                    placeholder="Nomor AC-No. di mesin, contoh: 13" help="Hanya angka; nol di depan diabaikan." />
            </div>
        </x-form-section>
        @include('admin.absensi._form-jadwal', ['awalan' => 'p', 'wajib' => false, 'bisaHapus' => true])
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formPegawai" icon="bi-check-lg">Simpan</x-button>
    </x-slot:footer>
</x-modal>

{{-- Modal: aksi massal (jam kerja / ikutkan / keluarkan) untuk pegawai dicentang atau semua hasil saringan --}}
<x-modal id="modalMassal" title="Atur Jam Kerja" icon="bi-clock" size="lg" scrollable>
    <form action="{{ route('admin.absensi.pengaturan.massal') }}" method="POST" id="formMassal">
        @csrf
        @foreach ($filterAktif as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
        <input type="hidden" name="aksi" value="jadwal">
        <input type="hidden" name="sasaran" value="terpilih">
        <dl class="hr-konteks mb-4"><dt>Berlaku untuk</dt><dd data-k="sasaran"></dd></dl>
        <div data-blok-jadwal>
            @include('admin.absensi._form-jadwal', ['awalan' => 'm', 'wajib' => true])
            <p class="form-text mb-0 mt-3"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Jam kerja lama pegawai tersebut akan diganti. Setelah itu tetap bisa diubah per orang.</p>
        </div>
        <p class="mb-0" data-blok-konfirmasi></p>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formMassal" icon="bi-check2-all" data-k="tombol">Terapkan</x-button>
    </x-slot:footer>
</x-modal>

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
            if (semua) {
                semua.checked = n > 0 && n === baris.length;
                semua.indeterminate = n > 0 && n < baris.length;
            }
        };
        baris.forEach((b) => b.addEventListener('change', perbarui));
        semua?.addEventListener('change', function () { baris.forEach((b) => { b.checked = semua.checked; }); perbarui(); });
        document.querySelector('[data-batal-pilih]')?.addEventListener('click', function () { baris.forEach((b) => { b.checked = false; }); perbarui(); });

        // ---- Modal massal: dibuka dari bilah aksi / tombol "Atur jam kerja semua" ----
        const formM = document.getElementById('formMassal');
        const modalM = document.getElementById('modalMassal');
        const tombolM = modalM.querySelector('[data-k="tombol"]');
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
                // Keluarkan = tindakan yang mengurangi data dinilai → tombol merah.
                tombolM.classList.toggle('btn-danger', aksi === 'tidak_ikut');
                tombolM.classList.toggle('btn-primary', aksi !== 'tidak_ikut');
                bootstrap.Modal.getOrCreateInstance(modalM).show();
            });
        });

        // Dibuka dari panduan persiapan: langsung tampilkan modal tempel daftar.
        if (new URLSearchParams(location.search).get('tempel') === '1') {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDaftar')).show();
        }

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
                formP.querySelectorAll('[data-isi-jam]').forEach((b) => { b.classList.remove('is-active'); b.setAttribute('aria-pressed', 'false'); });
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
