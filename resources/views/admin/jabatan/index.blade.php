@extends('layouts.admin.app')

@section('title', 'Master Jabatan')

@section('content')
@php
    $total = $jabatans->count();
    $terpakai = $jabatans->filter(fn ($j) => ($dipakai[$j->nama] ?? 0) > 0)->count();
    // Galat isian "tambah" (bag default) → buka lagi modal tambah; galat "ubah" punya bag sendiri.
    $galatTambah = $errors->getBag('default')->any();
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Master Jabatan" eyebrow="Sistem"
            subtitle="Satu daftar untuk semua pilihan jabatan: data pegawai, mutasi, lowongan, formasi, dan kandidat. Ganti nama otomatis memperbarui semua data.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.jabatan.export'])
                <x-button icon="bi-plus-lg" data-bs-toggle="modal" data-bs-target="#modalTambahJabatan">Tambah Jabatan</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card :padding="false" aria-label="Daftar jabatan">
            <x-filter-bar search-id="jb-cari" search="Cari jabatan, contoh: Guru" search-label="Cari jabatan menurut nama">
                <select id="jb-saring-unit" class="form-select form-select-sm" aria-label="Saring per bagian">
                    <option value="">Semua bagian</option>
                    <option value="-">Belum ada bagian</option>
                    @foreach ($units as $un)
                        <option value="{{ $un->nama }}" @selected(request('unit') === $un->nama)>{{ $un->nama }}</option>
                    @endforeach
                </select>
                <div class="input-group input-group-sm urut-pilih">
                    <span class="input-group-text"><i class="bi bi-sort-down" aria-hidden="true"></i></span>
                    <select id="jb-urut" class="form-select" aria-label="Urutkan">
                        <option value="nama">A – Z</option>
                        <option value="dipakai">Paling banyak dipakai</option>
                    </select>
                </div>
                <x-slot:chips>
                    @foreach ([['Semua', $total, 'bi-diagram-3', 'semua'], ['Sedang dipakai', $terpakai, 'bi-person-check', 'dipakai'], ['Tidak dipakai', $total - $terpakai, 'bi-archive', 'kosong']] as [$judul, $angka, $ikonChip, $saring])
                        <x-chip :active="$saring === 'semua'" :icon="$ikonChip" :count="$angka" data-saring-pakai="{{ $saring }}">{{ $judul }}</x-chip>
                    @endforeach
                </x-slot:chips>
                <x-slot:summary>
                    <p class="hr-filter-bar-jumlah" aria-live="polite">Menampilkan <strong id="jb-tampil">{{ $total }}</strong> dari {{ $total }} jabatan</p>
                </x-slot:summary>
            </x-filter-bar>

            <x-table caption="Daftar jabatan" id="jb-daftar" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Jabatan</th>
                        <th>Bagian</th>
                        <th class="text-end">Dipakai</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($jabatans as $j)
                    @php $n = $dipakai[$j->nama] ?? 0; @endphp
                    <tr class="jb-baris {{ session('jabatan_disorot') === $j->id ? 'disorot' : '' }}" id="jabatan-{{ $j->id }}" data-nama="{{ $j->nama }}" data-dipakai="{{ $n }}"
                        data-id="{{ $j->id }}" data-url="{{ route('admin.jabatan.update', $j) }}" data-url-hapus="{{ route('admin.jabatan.destroy', $j) }}"
                        data-rincian='@json($rincian[$j->nama] ?? (object) [])'
                        data-units='@json($j->units->pluck('nama'))' data-unit-ids='@json($j->units->pluck('id'))'>
                        <td class="kolom-penuh">
                            <button type="button" class="jb-nama" data-ubah-jabatan title="Klik untuk mengubah nama / bagian">{{ $j->nama }}</button>
                        </td>
                        <td>
                            <span class="jb-unit">
                                @forelse ($j->units as $un)
                                    <span class="jb-unit-chip">{{ $un->nama }}</span>
                                @empty
                                    <span class="jb-unit-chip kosong" title="Belum ditautkan ke bagian — tidak muncul di daftar jabatan bagian mana pun">tanpa bagian</span>
                                @endforelse
                            </span>
                        </td>
                        <td class="hr-angka">
                            @if ($n)
                                <a href="{{ route('admin.pegawai.index', ['q' => $j->nama]) }}" class="jb-pakai" title="Lihat pegawai dengan jabatan ini">
                                    <i class="bi bi-people" aria-hidden="true"></i> {{ $n }} data
                                </a>
                            @else
                                <span class="jb-pakai kosong">tidak dipakai</span>
                            @endif
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                <x-icon-button icon="bi-pencil" label="Ubah nama / bagian" data-ubah-jabatan />
                                @if ($n)
                                    <x-icon-button icon="bi-trash" tone="danger" label="Hapus (pilih pengganti)"
                                        data-bs-toggle="modal" data-bs-target="#modalHapusJabatan"
                                        data-url="{{ route('admin.jabatan.destroy', $j) }}" data-id="{{ $j->id }}" data-nama="{{ $j->nama }}" data-dipakai="{{ $n }}" />
                                @else
                                    <form action="{{ route('admin.jabatan.destroy', $j) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <x-icon-button type="submit" icon="bi-trash" tone="danger" label="Hapus jabatan"
                                            data-confirm="true" data-icon="warning" data-title="Hapus jabatan?" data-text="Jabatan {{ $j->nama }} akan dihapus dari daftar."
                                            data-confirm-text="Ya, Hapus" data-cancel-text="Batal" />
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="4" icon="bi-diagram-3" title="Belum ada jabatan">
                        Jabatan menjadi pilihan di data pegawai, mutasi, lowongan, formasi, dan kandidat.
                        <x-slot:action><x-button icon="bi-plus-lg" data-bs-toggle="modal" data-bs-target="#modalTambahJabatan">Tambah Jabatan</x-button></x-slot:action>
                    </x-table-empty>
                @endforelse
                <x-table-empty :colspan="4" icon="bi-search" title="Tidak ada jabatan yang cocok" class="jb-kosong" hidden>
                    Coba kata kunci lain, atau pilih "Semua bagian" dan chip "Semua".
                </x-table-empty>
            </x-table>
        </x-card>
    </div>
</main>

{{-- Tambah jabatan: langsung di bagiannya (bagian lain bisa ditambah lewat Ubah). --}}
<x-modal id="modalTambahJabatan" title="Tambah Jabatan" icon="bi-diagram-3" subtitle="Jabatan hanya muncul di pilihan bagian yang ditautkan.">
    <form action="{{ route('admin.jabatan.store') }}" method="POST" id="formTambahJabatan">
        @csrf
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label required" for="jb-tambah-nama">Nama jabatan</label>
                <input type="text" name="nama" id="jb-tambah-nama" class="form-control text-uppercase @error('nama') is-invalid @enderror" maxlength="150" required autocomplete="off"
                    placeholder="Tulis nama jabatan, contoh: GURU" value="{{ $errors->ubah->any() ? '' : old('nama') }}">
                @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="jb-tambah-unit">Bagian</label>
                <select name="unit_id" id="jb-tambah-unit" class="form-select">
                    <option value="">Tanpa bagian</option>
                    @foreach ($units as $un)
                        <option value="{{ $un->id }}" @selected(old('unit_id') == $un->id || (! old('unit_id') && request('unit') === $un->nama))>{{ $un->nama }}</option>
                    @endforeach
                </select>
                <div class="form-text">Tanpa bagian: jabatan tidak muncul di pilihan bagian mana pun sampai ditautkan lewat Ubah.</div>
            </div>
        </div>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formTambahJabatan" icon="bi-plus-lg">Tambah Jabatan</x-button>
    </x-slot:footer>
</x-modal>

{{-- Hapus jabatan yang masih dipakai: pindahkan datanya ke jabatan pengganti. --}}
<x-modal id="modalHapusJabatan" title="Hapus Jabatan" icon="bi-trash" tone="danger">
    <form method="POST" id="formHapusJabatan">
        @csrf
        @method('DELETE')
        <x-alert tone="warning" class="mb-3">
            <strong data-nama></strong> masih dipakai di <strong data-dipakai></strong> data (pegawai, mutasi, lowongan, formasi, atau lamaran).
        </x-alert>
        <label class="form-label required" for="jb-pengganti">Pindahkan data tersebut ke jabatan</label>
        <input type="search" class="form-control form-control-sm mb-2" placeholder="Saring daftar, contoh: Staff" aria-label="Saring daftar jabatan pengganti" data-cari-pengganti autocomplete="off">
        <select name="pengganti_id" id="jb-pengganti" class="form-select jb-pilih-daftar" size="7" required>
            @foreach ($semuaJabatan as $opsi)
                <option value="{{ $opsi->id }}">{{ $opsi->nama }}</option>
            @endforeach
        </select>
        <p class="form-text mt-2 mb-0">Setelah dipindahkan, jabatan lama dihapus dari daftar.</p>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" variant="danger" form="formHapusJabatan" icon="bi-trash">Pindahkan &amp; Hapus</x-button>
    </x-slot:footer>
</x-modal>

<x-modal id="modalUbahJabatan" title="Ubah Jabatan" icon="bi-pencil-square">
    <form method="POST" id="formUbahJabatan" autocomplete="off">
        @csrf
        @method('PUT')
        <input type="hidden" name="jabatan_id">
        <input type="hidden" name="kembali_q">
        <div class="jb-lama mb-3">
            <span class="small text-muted d-block">Nama sekarang</span>
            <strong data-nama-lama></strong>
        </div>

        <label class="form-label required" for="jb-nama-baru">Nama baru</label>
        <input type="text" name="nama" id="jb-nama-baru" class="form-control form-control-lg @error('nama', 'ubah') is-invalid @enderror" maxlength="150" required placeholder="Contoh: GURU KELAS">
        <div class="d-flex justify-content-between gap-2 mt-1">
            <span class="form-text m-0" data-pesan-nama>Otomatis huruf kapital. Spasi berlebih dirapikan.</span>
            <span class="form-text m-0 text-nowrap" data-hitung-huruf></span>
        </div>
        @error('nama', 'ubah')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

        {{-- Bagian tempat jabatan ini berada: menentukan jabatan yang muncul saat bagian dipilih (pegawai, mutasi, formasi, lowongan). --}}
        <input type="hidden" name="unit_dikirim" value="1">
        <div class="mt-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="form-label mb-0" id="jb-label-unit">Bagian</span>
                <span class="form-text m-0" data-jumlah-unit></span>
            </div>
            <input type="search" class="form-control form-control-sm mb-2" placeholder="Saring bagian, contoh: SMK" aria-label="Saring daftar bagian" data-cari-unit autocomplete="off">
            <div class="jb-pilih-unit" data-pilih-unit role="group" aria-labelledby="jb-label-unit">
                @foreach ($units as $un)
                    <label class="form-check" data-nama-unit="{{ $un->nama }}">
                        <input class="form-check-input" type="checkbox" name="unit_ids[]" value="{{ $un->id }}">
                        <span class="form-check-label">{{ $un->nama }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Nama sama dengan jabatan lain → tawarkan penggabungan. --}}
        <x-alert tone="warning" class="mt-3 mb-0" data-kembar hidden>
            Jabatan <strong data-nama-kembar></strong> sudah ada. Bila keduanya sama, <strong>gabungkan</strong>: semua data dipindahkan ke jabatan itu lalu jabatan ini dihapus.
            <x-slot:action><x-button variant="secondary" size="sm" icon="bi-intersect" data-gabungkan>Gabungkan ke <span data-nama-kembar></span></x-button></x-slot:action>
        </x-alert>

        <div class="jb-dampak mt-3" data-dampak></div>
    </form>
    <form method="POST" id="formGabungJabatan" hidden>
        @csrf
        @method('DELETE')
        <input type="hidden" name="pengganti_id">
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formUbahJabatan" icon="bi-check-lg" data-simpan>Simpan Perubahan</x-button>
    </x-slot:footer>
</x-modal>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const daftar = document.querySelector('#jb-daftar tbody');
        const baris = Array.from(daftar.querySelectorAll('.jb-baris'));
        const cari = document.getElementById('jb-cari');
        const urut = document.getElementById('jb-urut');
        const tampil = document.getElementById('jb-tampil');
        const kosong = daftar.querySelector('.jb-kosong');
        const saringUnit = document.getElementById('jb-saring-unit');
        const chipPakai = Array.from(document.querySelectorAll('[data-saring-pakai]'));
        let saring = 'semua';
        cari.value = @json((string) request('q'));

        // Saring, cari, dan urutkan langsung tanpa memuat ulang halaman.
        function perbarui() {
            const kata = cari.value.trim().toUpperCase();
            let n = 0;
            baris.forEach(function (tr) {
                const dipakai = parseInt(tr.dataset.dipakai, 10);
                const units = JSON.parse(tr.dataset.units || '[]');
                const cocokUnit = !saringUnit.value || (saringUnit.value === '-' ? units.length === 0 : units.includes(saringUnit.value));
                const cocok = tr.dataset.nama.includes(kata) && cocokUnit
                    && (saring === 'semua' || (saring === 'dipakai' ? dipakai > 0 : dipakai === 0));
                tr.hidden = ! cocok;
                n += cocok ? 1 : 0;
            });
            tampil.textContent = n;
            kosong.hidden = n > 0 || baris.length === 0;
        }

        function urutkan() {
            baris.sort(function (a, b) {
                return urut.value === 'dipakai'
                    ? (b.dataset.dipakai - a.dataset.dipakai) || a.dataset.nama.localeCompare(b.dataset.nama)
                    : a.dataset.nama.localeCompare(b.dataset.nama);
            }).forEach(function (tr) { daftar.insertBefore(tr, kosong); });
        }

        cari.addEventListener('input', perbarui);
        saringUnit.addEventListener('change', perbarui);
        urut.addEventListener('change', urutkan);
        chipPakai.forEach(function (chip) {
            chip.addEventListener('click', function () {
                chipPakai.forEach(function (c) { c.classList.toggle('is-active', c === chip); c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
                saring = chip.dataset.saringPakai;
                perbarui();
            });
        });

        const modalUbah = document.getElementById('modalUbahJabatan');
        const formUbah = document.getElementById('formUbahJabatan');
        const formGabung = document.getElementById('formGabungJabatan');
        const inputNama = formUbah.querySelector('[name=nama]');
        const tombolSimpan = modalUbah.querySelector('[data-simpan]');
        const pesanNama = formUbah.querySelector('[data-pesan-nama]');
        const kotakKembar = formUbah.querySelector('[data-kembar]');
        const kotakDampak = formUbah.querySelector('[data-dampak]');
        const centangUnit = Array.from(formUbah.querySelectorAll('[name="unit_ids[]"]'));
        const cariUnit = formUbah.querySelector('[data-cari-unit]');
        let unitAwal = '';
        const unitDipilih = () => centangUnit.filter((c) => c.checked).map((c) => c.value).sort().join(',');
        // Peta nama → id semua jabatan untuk cek nama kembar tanpa memuat ulang.
        const semua = new Map(baris.map((tr) => [tr.dataset.nama, tr.dataset.id]));
        const rapikan = (v) => v.replace(/\s+/g, ' ').trim().toUpperCase();
        let aktif = null;

        function bukaUbah(tr, namaAwal) {
            aktif = tr;
            formUbah.action = tr.dataset.url;
            formUbah.jabatan_id.value = tr.dataset.id;
            formUbah.kembali_q.value = cari.value;
            modalUbah.querySelector('[data-nama-lama]').textContent = tr.dataset.nama;
            inputNama.value = namaAwal ?? tr.dataset.nama;
            // Bagian jabatan: centang sesuai data, ingat keadaan awal untuk mendeteksi perubahan.
            const idUnit = JSON.parse(tr.dataset.unitIds || '[]').map(String);
            centangUnit.forEach((c) => { c.checked = idUnit.includes(c.value); });
            unitAwal = unitDipilih();
            cariUnit.value = '';
            centangUnit.forEach((c) => { c.closest('label').hidden = false; });
            // Rincian dampak: data apa saja yang ikut berganti nama.
            const rincian = JSON.parse(tr.dataset.rincian || '{}');
            const total = Object.values(rincian).reduce((a, b) => a + b, 0);
            kotakDampak.classList.toggle('ada', total > 0);
            kotakDampak.innerHTML = total
                ? '<i class="bi bi-arrow-repeat me-1"></i>Nama baru otomatis dipakai di <strong>' + total + ' data</strong>:<ul></ul>'
                : '<i class="bi bi-info-circle me-1"></i>Jabatan ini belum dipakai di data mana pun.';
            Object.entries(rincian).forEach(([label, n]) => {
                const item = document.createElement('li');
                item.textContent = label + ' ' + n;
                kotakDampak.querySelector('ul').appendChild(item);
            });
            periksa();
            bootstrap.Modal.getOrCreateInstance(modalUbah).show();
        }

        function periksa() {
            const nama = rapikan(inputNama.value);
            const lama = aktif.dataset.nama;
            const idKembar = semua.get(nama);
            const kembar = Boolean(nama && nama !== lama && idKembar && idKembar !== aktif.dataset.id);
            kotakKembar.hidden = !kembar;
            kotakKembar.querySelectorAll('[data-nama-kembar]').forEach((e) => { e.textContent = nama; });
            formGabung.pengganti_id.value = kembar ? idKembar : '';
            formGabung.action = aktif.dataset.urlHapus;
            const unitBerubah = unitDipilih() !== unitAwal;
            formUbah.querySelector('[data-jumlah-unit]').textContent = centangUnit.filter((c) => c.checked).length + ' bagian dipilih';
            tombolSimpan.disabled = !nama || kembar || (nama === lama && !unitBerubah);
            pesanNama.textContent = !nama ? 'Nama jabatan wajib diisi.'
                : nama === lama ? (unitBerubah ? 'Nama tetap; bagian akan diperbarui.' : 'Belum ada perubahan.')
                : kembar ? 'Nama ini sudah dipakai jabatan lain.'
                : 'Akan disimpan sebagai: ' + nama;
            pesanNama.classList.toggle('text-danger', !nama || kembar);
            formUbah.querySelector('[data-hitung-huruf]').textContent = nama.length + '/150';
        }

        document.querySelectorAll('[data-ubah-jabatan]').forEach((b) => b.addEventListener('click', () => bukaUbah(b.closest('.jb-baris'))));
        inputNama.addEventListener('input', periksa);
        centangUnit.forEach((c) => c.addEventListener('change', periksa));
        cariUnit.addEventListener('input', () => {
            const kata = cariUnit.value.trim().toUpperCase();
            centangUnit.forEach((c) => { c.closest('label').hidden = !c.closest('label').dataset.namaUnit.includes(kata); });
        });
        modalUbah.addEventListener('shown.bs.modal', () => { inputNama.focus(); inputNama.select(); });
        formUbah.addEventListener('submit', () => { inputNama.value = rapikan(inputNama.value); });
        kotakKembar.querySelector('[data-gabungkan]').addEventListener('click', function () {
            const tujuan = rapikan(inputNama.value);
            Swal.fire({
                icon: 'warning', title: 'Gabungkan jabatan?',
                html: 'Semua data <strong></strong> dipindahkan ke <strong></strong>, lalu jabatan lama dihapus.',
                didOpen: (el) => { const s = el.querySelectorAll('strong'); s[0].textContent = aktif.dataset.nama; s[1].textContent = tujuan; },
                showCancelButton: true, confirmButtonText: 'Ya, gabungkan', cancelButtonText: 'Batal', reverseButtons: true, confirmButtonColor: '#8b1215',
            }).then((r) => { if (r.isConfirmed) formGabung.submit(); });
        });

        // Galat validasi dari server → buka lagi modal yang sama dengan isian terakhir.
        @if ($errors->ubah->any() && old('jabatan_id'))
            (function () {
                const tr = document.getElementById('jabatan-{{ (int) old('jabatan_id') }}');
                if (tr) bukaUbah(tr, @json(old('nama')));
            })();
        @elseif ($galatTambah)
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTambahJabatan')).show();
        @endif

        // Kembali dari simpan: gulir ke baris yang disorot.
        const disorot = daftar.querySelector('.jb-baris.disorot');
        if (disorot) disorot.scrollIntoView({ block: 'center' });

        // Modal hapus: cari pengganti & sembunyikan jabatan yang sedang dihapus.
        const modal = document.getElementById('modalHapusJabatan');
        const pilihan = modal.querySelector('[name=pengganti_id]');
        const cariPengganti = modal.querySelector('[data-cari-pengganti]');
        let idDihapus = null;
        function saringPengganti() {
            const kata = cariPengganti.value.trim().toUpperCase();
            Array.prototype.forEach.call(pilihan.options, function (o) {
                o.hidden = o.value === idDihapus || ! o.text.includes(kata);
            });
        }
        modal.addEventListener('show.bs.modal', function (e) {
            const tombol = e.relatedTarget;
            idDihapus = tombol.dataset.id;
            modal.querySelector('form').action = tombol.dataset.url;
            modal.querySelector('[data-nama]').textContent = tombol.dataset.nama;
            modal.querySelector('[data-dipakai]').textContent = tombol.dataset.dipakai;
            pilihan.value = '';
            cariPengganti.value = '';
            saringPengganti();
        });
        modal.addEventListener('shown.bs.modal', function () { cariPengganti.focus(); });
        cariPengganti.addEventListener('input', saringPengganti);

        if (cari.value || saringUnit.value) perbarui();
    });
</script>
@endsection
