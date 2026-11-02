@extends(Auth::user()->panelLayout())

@section('content')
@php
    $grupHak = collect(\App\Models\Role::GRUP_HAK)->map(fn ($g) => [$g[0], array_values(array_intersect($g[1], array_keys($hakOpsi)))])->filter(fn ($g) => $g[1]);
    // Kolom matriks: satu per hak, urut per kelompok; kolom pertama tiap kelompok diberi garis pemisah.
    $kolom = $grupHak->flatMap(fn ($g, $judul) => collect($g[1])->map(fn ($h, $i) => ['hak' => $h, 'grup' => $judul, 'ikon' => $g[0], 'awal' => $i === 0]))->values();
    $jumlahKolom = $kolom->count() + 1;
    $adaFilter = request()->filled('unit') || request()->filled('q') || request()->boolean('ada');
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Hak Akses Jabatan" eyebrow="Sistem" :back="route('admin.jabatan.index')" back-label="Master Jabatan"
            subtitle="Hak tambahan untuk pemegang jabatan di tiap bagian, mis. Staff bagian A boleh melihat absensi, Staff bagian B tidak.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.jabatan.hak-akses.export'])
                <x-button variant="secondary" :href="route('admin.role.index')" icon="bi-shield-check">Master Role</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-alert tone="info" class="mb-3">
            Hak di sini <strong>ditambahkan</strong> di atas hak role akun (Master Role), tidak mengurangi. Berlaku untuk pegawai yang sedang
            memegang jabatan itu di bagian tersebut, termasuk lewat rangkap tugas. Hak <em>Kelola sistem</em> hanya bisa diberikan lewat Master Role.
        </x-alert>

        <x-card :padding="false" aria-label="Matriks hak akses jabatan">
            <x-filter-bar :action="route('admin.jabatan.hak-akses')" search="Cari jabatan, contoh: Kepala" search-label="Cari hak akses menurut nama jabatan"
                :count="$baris->count()" unit="jabatan" :filtered="$adaFilter" :reset="route('admin.jabatan.hak-akses')">
                <select name="unit" class="form-select form-select-sm" aria-label="Saring bagian" onchange="this.form.submit()">
                    <option value="">Semua bagian</option>
                    @foreach ($unitOpsi as $id => $nama)
                        <option value="{{ $id }}" @selected((string) request('unit') === (string) $id)>{{ $nama }}</option>
                    @endforeach
                </select>
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="ada" value="1" id="hk-ada" @checked(request()->boolean('ada')) onchange="this.form.submit()">
                    <label class="form-check-label" for="hk-ada">Hanya yang punya hak tambahan (<span data-jumlah-berhak>{{ $jumlahBerhak }}</span>)</label>
                </div>
                <x-slot:summary>
                    <span class="hr-filter-bar-jumlah d-none d-md-inline">Centang kotak di judul kolom atau di kiri nama jabatan untuk memilih semua.</span>
                </x-slot:summary>
            </x-filter-bar>

            {{-- Matriks jabatan × hak. Tiap baris punya form PUT sendiri (rute lama); kotak centang terhubung lewat atribut form="…".
                 Bukan .table-responsive agar tidak diubah jadi kartu di HP: tetap tabel yang bisa digulir mendatar. --}}
            <div class="hr-table-wrap hk-matriks-wadah" id="matriksHak" tabindex="0" role="region" aria-label="Matriks hak akses (bisa digulir)">
                <table class="table align-middle mb-0 hk-matriks">
                    <caption class="visually-hidden">Hak akses tambahan: baris = jabatan per bagian, kolom = hak.</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="hk-kol-jabatan">
                                <span class="hk-kol-grup">Bagian</span>
                                <span class="hk-kol-nama">Jabatan</span>
                            </th>
                            @foreach ($kolom as $k)
                                <th scope="col" class="hk-kol {{ $k['awal'] ? 'hk-awal-grup' : '' }}">
                                    {{-- Nama kelompok sekali di kolom pertama kelompoknya (melebar ke kolom sebelahnya). --}}
                                    @if ($k['awal'])<span class="hk-kol-grup">{{ $k['grup'] }}</span>@endif
                                    <span class="hk-kol-nama" title="{{ $hakOpsi[$k['hak']][1] }}">{{ $hakOpsi[$k['hak']][0] }}</span>
                                    <input type="checkbox" class="form-check-input" data-pilih-kolom="{{ $k['hak'] }}" @disabled($baris->isEmpty())
                                        aria-label="Pilih {{ $hakOpsi[$k['hak']][0] }} untuk semua jabatan yang tampil">
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baris->groupBy('unit') as $unit => $daftar)
                            <tr class="hk-unit">
                                <th scope="colgroup" colspan="{{ $jumlahKolom }}">
                                    <span class="hk-unit-nama"><i class="bi bi-building" aria-hidden="true"></i>{{ $unit }}
                                        <span class="hk-unit-jumlah">{{ $daftar->count() }} jabatan</span></span>
                                </th>
                            </tr>
                            @foreach ($daftar as $b)
                                @php
                                    $idForm = 'hak-' . $b->jabatan_id . '-' . $b->unit_id;
                                    $judul = $b->jabatan . ' · ' . $b->unit;
                                @endphp
                                <tr data-baris="{{ $idForm }}" data-judul="{{ $judul }}" data-awal-ada="{{ $b->hak ? 1 : 0 }}">
                                    <th scope="row" class="hk-sel-jabatan">
                                        <div class="hk-jabatan">
                                            <input type="checkbox" class="form-check-input" data-pilih-baris aria-label="Pilih semua hak untuk {{ $judul }}">
                                            <div class="min-w-0 flex-grow-1">
                                                <span class="hr-sel-judul">{{ $b->jabatan }}</span>
                                                <span class="hr-sel-sub">{{ $b->pemegang }} pemegang aktif</span>
                                                <span data-belum hidden><x-badge tone="warning" dot>Belum disimpan</x-badge></span>
                                            </div>
                                            <form id="{{ $idForm }}" method="POST" action="{{ route('admin.jabatan.hak-akses.update', [$b->jabatan_id, $b->unit_id]) }}" data-form-hak>
                                                @csrf @method('PUT')
                                                <x-icon-button type="submit" icon="bi-check-lg" label="Simpan hak {{ $judul }}" tone="primary" data-simpan-baris hidden />
                                            </form>
                                        </div>
                                    </th>
                                    @foreach ($kolom as $k)
                                        <td class="hk-sel {{ $k['awal'] ? 'hk-awal-grup' : '' }}">
                                            <label class="hk-centang">
                                                <input type="checkbox" class="form-check-input" name="hak_akses[]" value="{{ $k['hak'] }}" form="{{ $idForm }}" data-hak="{{ $k['hak'] }}"
                                                    @checked(in_array($k['hak'], $b->hak, true))>
                                                <span class="visually-hidden">{{ $hakOpsi[$k['hak']][0] }} untuk {{ $judul }}</span>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @empty
                            <x-table-empty :colspan="$jumlahKolom" :icon="$adaFilter ? 'bi-search' : 'bi-person-badge'"
                                :title="$adaFilter ? 'Tidak ada jabatan yang cocok' : 'Belum ada jabatan yang ditautkan ke bagian'">
                                @if ($adaFilter)
                                    Coba kata kunci lain atau reset filter untuk melihat semua jabatan.
                                @else
                                    Atur bagian tiap jabatan di Master Jabatan terlebih dahulu.
                                @endif
                                <x-slot:action>
                                    @if ($adaFilter)
                                        <x-button :href="route('admin.jabatan.hak-akses')" variant="secondary" icon="bi-x-circle">Reset filter</x-button>
                                    @else
                                        <x-button :href="route('admin.jabatan.index')" icon="bi-diagram-3">Buka Master Jabatan</x-button>
                                    @endif
                                </x-slot:action>
                            </x-table-empty>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-slot:footer>
                <details>
                    <summary class="fw-semibold">Arti tiap hak</summary>
                    <x-info-list class="mt-3" :items="$kolom->mapWithKeys(fn ($k) => [$hakOpsi[$k['hak']][0] => $hakOpsi[$k['hak']][1]])->all()" />
                </details>
            </x-slot:footer>
        </x-card>

        {{-- Muncul bila ada perubahan yang belum disimpan. --}}
        <div class="hr-bilah-aksi mt-3" data-bilah-hak hidden>
            <p class="hr-filter-bar-jumlah mb-0" aria-live="polite"><strong data-jumlah-ubah>0</strong> jabatan diubah, belum disimpan.</p>
            <div class="d-flex flex-wrap gap-2 ms-auto">
                <x-button variant="secondary" data-batal-hak>Batal</x-button>
                <x-button icon="bi-check-lg" data-simpan-hak>Simpan Perubahan</x-button>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const wadah = document.getElementById('matriksHak');
        const bilah = document.querySelector('[data-bilah-hak]');
        const baris = Array.from(wadah.querySelectorAll('tr[data-baris]'));
        if (!baris.length) return;
        const kepalaKolom = Array.from(wadah.querySelectorAll('[data-pilih-kolom]'));
        const kotakBaris = (tr) => Array.from(tr.querySelectorAll('input[data-hak]'));
        const kotakKolom = (hak) => Array.from(wadah.querySelectorAll('input[data-hak="' + hak + '"]'));
        const diubah = (tr) => tr.classList.contains('is-diubah');
        const token = (nama) => getComputedStyle(document.documentElement).getPropertyValue(nama).trim();
        let sibuk = false;

        // Kotak induk: tercentang bila semua anaknya tercentang, "sebagian" (indeterminate) bila hanya sebagian.
        function aturInduk(induk, anak) {
            const n = anak.filter((c) => c.checked).length;
            induk.checked = anak.length > 0 && n === anak.length;
            induk.indeterminate = n > 0 && n < anak.length;
        }
        // Baris berubah bila ada kotak yang berbeda dari keadaan tersimpan (defaultChecked).
        function perbaruiBaris(tr) {
            const kotak = kotakBaris(tr);
            aturInduk(tr.querySelector('[data-pilih-baris]'), kotak);
            kotak.forEach((c) => c.closest('td').classList.toggle('is-diubah', c.checked !== c.defaultChecked));
            const ubah = kotak.some((c) => c.checked !== c.defaultChecked);
            tr.classList.toggle('is-diubah', ubah);
            tr.querySelector('[data-belum]').hidden = !ubah;
            tr.querySelector('[data-simpan-baris]').hidden = !ubah;
        }
        function segarkan(daftar) {
            daftar.forEach(perbaruiBaris);
            kepalaKolom.forEach((k) => aturInduk(k, kotakKolom(k.dataset.pilihKolom)));
            const n = baris.filter(diubah).length;
            bilah.hidden = n === 0;
            bilah.querySelector('[data-jumlah-ubah]').textContent = n;
        }

        wadah.addEventListener('change', function (e) {
            const t = e.target;
            if (t.matches('[data-pilih-baris]')) {
                const tr = t.closest('tr');
                kotakBaris(tr).forEach((c) => { c.checked = t.checked; });
                segarkan([tr]);
            } else if (t.matches('[data-pilih-kolom]')) {
                const kena = kotakKolom(t.dataset.pilihKolom);
                kena.forEach((c) => { c.checked = t.checked; });
                segarkan(kena.map((c) => c.closest('tr')));
            } else if (t.matches('input[data-hak]')) {
                segarkan([t.closest('tr')]);
            }
        });

        function ubahJumlahBerhak(selisih) {
            const el = document.querySelector('[data-jumlah-berhak]');
            if (el && selisih) el.textContent = Math.max(0, (parseInt(el.textContent, 10) || 0) + selisih);
        }

        // Simpan baris yang berubah satu per satu lewat rute PUT per jabatan (tanpa memuat ulang halaman).
        async function simpan(daftar) {
            if (sibuk || !daftar.length) return;
            sibuk = true;
            const tombol = bilah.querySelector('[data-simpan-hak]');
            const isiTombol = tombol.innerHTML;
            tombol.disabled = true;
            tombol.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Menyimpan…';
            wadah.setAttribute('aria-busy', 'true');
            let tersimpan = 0;
            try {
                for (const tr of daftar) {
                    const form = document.getElementById(tr.dataset.baris);
                    let res;
                    try {
                        res = await fetch(form.action, {
                            method: 'POST', body: new FormData(form), credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                    } catch (err) {
                        throw new Error('Koneksi terputus. Periksa jaringan lalu coba lagi.');
                    }
                    if (!res.ok) {
                        let pesan = 'Server menolak permintaan (' + res.status + ').';
                        if (res.status === 419) pesan = 'Sesi sudah habis. Muat ulang halaman lalu ulangi.';
                        else { try { pesan = (await res.json()).message || pesan; } catch (err) { /* bukan JSON */ } }
                        throw new Error(tr.dataset.judul + ': ' + pesan);
                    }
                    // Tersimpan: keadaan sekarang menjadi keadaan tersimpan yang baru.
                    const kotak = kotakBaris(tr);
                    kotak.forEach((c) => { c.defaultChecked = c.checked; });
                    const adaSekarang = kotak.some((c) => c.checked) ? 1 : 0;
                    ubahJumlahBerhak(adaSekarang - (+tr.dataset.awalAda || 0));
                    tr.dataset.awalAda = adaSekarang;
                    segarkan([tr]);
                    tersimpan++;
                }
                hrToast('success', tersimpan === 1 ? 'Hak akses ' + daftar[0].dataset.judul + ' disimpan.' : 'Hak akses ' + tersimpan + ' jabatan disimpan.');
            } catch (err) {
                const sisa = daftar.filter(diubah);
                Swal.fire({
                    icon: 'error', title: tersimpan ? 'Sebagian belum tersimpan' : 'Hak akses belum tersimpan',
                    text: (tersimpan ? tersimpan + ' jabatan sudah tersimpan. ' : '') + err.message,
                    showCancelButton: true, reverseButtons: true, confirmButtonText: 'Coba lagi', cancelButtonText: 'Tutup',
                    confirmButtonColor: token('--hr-primary'), cancelButtonColor: token('--hr-neutral'),
                }).then((r) => { if (r.isConfirmed) simpan(sisa); });
            } finally {
                sibuk = false;
                tombol.disabled = false;
                tombol.innerHTML = isiTombol;
                wadah.removeAttribute('aria-busy');
            }
        }

        // Simpan per baris (ikon centang di baris) atau semua baris yang berubah (bilah bawah).
        document.querySelectorAll('form[data-form-hak]').forEach((form) => form.addEventListener('submit', function (e) {
            e.preventDefault();
            simpan([wadah.querySelector('tr[data-baris="' + form.id + '"]')]);
        }));
        bilah.querySelector('[data-simpan-hak]').addEventListener('click', () => simpan(baris.filter(diubah)));

        // Batal: kembalikan semua centang ke keadaan tersimpan (dikonfirmasi dulu).
        bilah.querySelector('[data-batal-hak]').addEventListener('click', function () {
            const n = baris.filter(diubah).length;
            Swal.fire({
                icon: 'warning', title: 'Batalkan perubahan?', text: n + ' jabatan dikembalikan ke hak yang tersimpan.',
                showCancelButton: true, reverseButtons: true, confirmButtonText: 'Ya, Batalkan', cancelButtonText: 'Kembali',
                confirmButtonColor: token('--hr-danger'), cancelButtonColor: token('--hr-neutral'),
            }).then(function (r) {
                if (!r.isConfirmed) return;
                const kena = baris.filter(diubah);
                kena.forEach((tr) => kotakBaris(tr).forEach((c) => { c.checked = c.defaultChecked; }));
                segarkan(kena);
            });
        });

        // Peringatan saat meninggalkan halaman (mis. ganti filter) dengan perubahan yang belum disimpan.
        window.addEventListener('beforeunload', function (e) {
            if (baris.some(diubah)) { e.preventDefault(); e.returnValue = ''; }
        });

        segarkan(baris);
    });
</script>
@endsection
