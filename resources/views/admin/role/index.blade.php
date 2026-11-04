@extends('layouts.admin.app')

@section('title', 'Master Role')

@section('content')
@php
    use App\Models\Role;
    use App\Models\User;

    $totalHak = count(Role::HAK_AKSES);
    // Keterangan singkat role bawaan bila belum diisi di Master Role.
    $ketBawaan = [
        User::ROLE_PEMBINA => 'Pemilik yayasan: memantau (hanya-baca) & ikut menyetujui formasi.',
        User::ROLE_GM => 'Menyetujui pengajuan pegawai di bidangnya.',
        User::ROLE_KEPALA_BAGIAN => 'Kepala bagian: menyetujui pengajuan anggota bagiannya.',
        User::ROLE_KEPALA_SDM => 'Mengelola kepegawaian & absensi, persetujuan tahap akhir.',
        User::ROLE_STAFF => 'Pegawai: mengajukan cuti, lembur & izin keluar.',
        User::ROLE_ADMIN => 'Pengelola sistem dengan akses penuh.',
    ];
    $labelJenis = ['bawaan' => 'Bawaan', 'disesuaikan' => 'Disesuaikan', 'kustom' => 'Kustom'];
    $toneJenis = ['bawaan' => 'neutral', 'disesuaikan' => 'warning', 'kustom' => 'info'];
    $jumlahBawaan = $roles->where('jenis', '!=', 'kustom')->count();
    $jumlahKustom = $roles->where('jenis', 'kustom')->count();
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Master Role" eyebrow="Sistem"
            subtitle="Atur nama & hak akses tiap role. Role kustom mengikuti jenis dasarnya untuk panel & alur persetujuan, tetapi hak aksesnya sendiri.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.role.export'])
                <x-button :href="route('admin.role.create')" icon="bi-plus-lg">Tambah Role</x-button>
            </x-slot:actions>
        </x-page-header>

        <section class="row g-3 mb-4" aria-label="Ringkasan role">
            @foreach ([['Total role', $roles->count(), 'bi-shield-check', 'primary'], ['Role bawaan', $jumlahBawaan, 'bi-lock', 'neutral'], ['Role kustom', $jumlahKustom, 'bi-stars', 'info']] as [$label, $angka, $ikon, $tone])
                <div class="col-6 col-xl-4">
                    <x-stat-card :label="$label" :value="$angka" :icon="$ikon" :tone="$tone"
                        :meta="$label === 'Role bawaan' && $dihapus->isNotEmpty() ? $dihapus->count() . ' dihapus' : null" />
                </div>
            @endforeach
        </section>

        <x-card :padding="false" aria-label="Daftar role">
            <x-filter-bar search-id="rl-cari" search="Cari role, contoh: Staff" search-label="Cari role menurut nama, jenis, atau jenis dasar">
                <div class="btn-group btn-group-sm rl-tampilan" role="group" aria-label="Pilih tampilan">
                    <button type="button" class="btn btn-outline-secondary active" data-tampilan="daftar" aria-pressed="true"><i class="bi bi-list-ul" aria-hidden="true"></i> Daftar</button>
                    <button type="button" class="btn btn-outline-secondary" data-tampilan="matriks" aria-pressed="false"><i class="bi bi-grid-3x3" aria-hidden="true"></i> Matriks hak akses</button>
                </div>
                <x-slot:chips>
                    @foreach (['semua' => ['Semua', $roles->count() + $dihapus->count()], 'bawaan' => ['Bawaan', $jumlahBawaan], 'kustom' => ['Kustom', $jumlahKustom], 'dihapus' => ['Dihapus', $dihapus->count()]] as $kode => [$label, $angka])
                        @continue($kode === 'dihapus' && $angka === 0)
                        <x-chip :active="$kode === 'semua'" :count="$angka" data-saring-jenis="{{ $kode }}">{{ $label }}</x-chip>
                    @endforeach
                </x-slot:chips>
                <x-slot:summary>
                    <p class="hr-filter-bar-jumlah" aria-live="polite">Menampilkan <strong data-jumlah-role>{{ $roles->count() + $dihapus->count() }}</strong> role · {{ $totalHak }} hak akses tersedia</p>
                </x-slot:summary>
            </x-filter-bar>

            {{-- Tampilan daftar --}}
            <div data-panel="daftar">
                <x-table caption="Daftar role" id="rl-daftar" sticky striped scroll>
                    <x-slot:head>
                        <tr>
                            <th>Role</th>
                            <th>Jenis</th>
                            <th>Jenis Dasar</th>
                            <th>Hak Akses</th>
                            <th class="text-end">Akun</th>
                            <th class="hr-kolom-aksi">Aksi</th>
                        </tr>
                    </x-slot:head>
                    @foreach ($roles as $r)
                        @php
                            // Ringkas per kelompok, mis. "Umum, Pengajuan, Kepegawaian" (rinciannya di tooltip & tampilan Matriks).
                            $grupMilik = collect(Role::GRUP_HAK)->filter(fn ($g) => array_intersect($g[1], $r->hak))->keys();
                            $rincianHak = collect($r->hak)->map(fn ($h) => Role::HAK_AKSES[$h][0] ?? $h)->implode(', ');
                        @endphp
                        <tr data-cari="{{ mb_strtolower($r->nama . ' ' . ($r->namaAsli ?? '') . ' ' . $labelJenis[$r->jenis] . ' ' . (User::LABEL_ROLE[$r->dasar] ?? '')) }}"
                            data-jenis="{{ $r->jenis === 'kustom' ? 'kustom' : 'bawaan' }}">
                            <td class="kolom-penuh">
                                <div class="hr-sel-utama">
                                    <span class="hr-stat-ikon hr-tone-{{ $toneJenis[$r->jenis] }}"><i class="bi {{ Role::IKON_DASAR[$r->dasar] ?? 'bi-person' }}" aria-hidden="true"></i></span>
                                    <div class="min-w-0">
                                        <a href="{{ $r->urlUbah }}" class="hr-sel-judul">{{ $r->nama }}</a>
                                        <span class="hr-sel-sub">
                                            {{ $r->keterangan ?: ($ketBawaan[$r->dasar] ?? '') }}
                                            @if ($r->jenis === 'disesuaikan' && $r->nama !== $r->namaAsli) · nama asli: {{ $r->namaAsli }} @endif
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <x-badge :tone="$toneJenis[$r->jenis]" :icon="$r->jenis === 'kustom' ? 'bi-stars' : 'bi-lock'">{{ $labelJenis[$r->jenis] }}</x-badge>
                            </td>
                            <td>{{ User::LABEL_ROLE[$r->dasar] ?? $r->dasar }}</td>
                            <td title="{{ $rincianHak }}">
                                <span class="d-block">{{ count($r->hak) }} dari {{ $totalHak }} hak</span>
                                <span class="hr-sel-sub">{{ $grupMilik->implode(', ') ?: '-' }}</span>
                            </td>
                            <td class="hr-angka"><a href="{{ $r->urlAkun }}" title="Lihat akun dengan role ini">{{ $r->jumlah }}</a></td>
                            <td class="text-end hr-kolom-aksi">
                                <div class="hr-aksi-baris">
                                    <x-icon-button icon="bi-pencil" :label="$r->jenis === 'kustom' ? 'Ubah role' : 'Ubah role bawaan (terbatas)'" :href="$r->urlUbah" />
                                    @if ($r->urlReset)
                                        <form action="{{ $r->urlReset }}" method="POST" data-confirm="Kembalikan role {{ $r->namaAsli }} ke nama & hak akses bawaan sistem?">
                                            @csrf @method('DELETE')
                                            <x-icon-button type="submit" icon="bi-arrow-counterclockwise" tone="warning" label="Kembalikan ke bawaan" />
                                        </form>
                                    @else
                                        <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                    @endif
                                    @if ($r->urlHapus)
                                        <x-icon-button icon="bi-trash" tone="danger" :label="$r->jenis === 'kustom' ? 'Hapus role' : 'Hapus role bawaan'"
                                            data-bs-toggle="modal" data-bs-target="#modalHapusRole"
                                            data-url="{{ $r->urlHapus }}" data-metode="{{ $r->jenis === 'kustom' ? 'DELETE' : 'POST' }}" data-nama="{{ $r->nama }}"
                                            data-nilai="{{ $r->nilai }}" data-jumlah="{{ $r->jumlah }}" data-otomatis="{{ $r->otomatis ? '1' : '' }}"
                                            data-kustom="{{ $r->jenis === 'kustom' ? '1' : '' }}" data-dasar="{{ User::roleLabelFor($r->dasar) }}" />
                                    @else
                                        {{-- Role inti (mis. Admin) tidak bisa dihapus. --}}
                                        <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    {{-- Role bawaan yang dihapus: tetap di tabel yang sama, bisa dipulihkan. --}}
                    @foreach ($dihapus as $d)
                        <tr class="rl-dihapus" data-cari="{{ mb_strtolower($d->nama . ' ' . $d->namaAsli . ' dihapus disembunyikan') }}" data-jenis="dihapus">
                            <td class="kolom-penuh">
                                <div class="hr-sel-utama">
                                    <span class="hr-stat-ikon hr-tone-neutral"><i class="bi bi-eye-slash" aria-hidden="true"></i></span>
                                    <div class="min-w-0">
                                        <span class="hr-sel-judul">{{ $d->nama }}</span>
                                        <span class="hr-sel-sub">{{ $d->pengganti ? 'Akun dipindahkan ke ' . $d->pengganti . '.' : 'Tanpa role pengganti.' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><x-badge tone="neutral" icon="bi-eye-slash">Dihapus</x-badge></td>
                            <td>{{ $d->namaAsli }}</td>
                            <td class="text-muted">-</td>
                            <td class="hr-angka">{{ $d->jumlah }}</td>
                            <td class="text-end hr-kolom-aksi">
                                <div class="hr-aksi-baris">
                                    <form action="{{ $d->urlPulihkan }}" method="POST" data-confirm="Pulihkan role {{ $d->nama }}? Role ini bisa dipilih lagi di Kelola Akun.">
                                        @csrf
                                        <x-icon-button type="submit" icon="bi-arrow-counterclockwise" tone="primary" label="Pulihkan role" />
                                    </form>
                                    <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                    <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <x-table-empty :colspan="6" icon="bi-search" title="Tidak ada role yang cocok" data-kosong-cari hidden>
                        Coba kata kunci lain atau pilih chip "Semua".
                    </x-table-empty>
                </x-table>
            </div>

            {{-- Tampilan matriks (hanya-baca; ubah lewat ikon Ubah tiap role) --}}
            {{-- Bukan x-table: wadah .table-responsive diubah main.js jadi kartu di HP; matriks tetap tabel yang bisa digulir. --}}
            <div data-panel="matriks" hidden>
                <div class="rl-matriks-wadah">
                <table class="table hr-table rl-matriks mb-0">
                    <caption class="visually-hidden">Matriks hak akses per role</caption>
                    <thead>
                        <tr>
                            <th class="rl-kol-hak">Hak akses</th>
                            @foreach ($roles as $r)
                                <th class="text-center rl-kol-role">
                                    <a href="{{ $r->urlUbah }}" class="rl-kol-nama"><i class="bi {{ Role::IKON_DASAR[$r->dasar] ?? 'bi-person' }}" aria-hidden="true"></i>{{ $r->nama }}</a>
                                    <x-badge :tone="$toneJenis[$r->jenis]">{{ $labelJenis[$r->jenis] }}</x-badge>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                    @foreach (Role::GRUP_HAK as $judul => [$ikon, $hakGrup])
                        <tr class="rl-baris-grup"><th colspan="{{ $roles->count() + 1 }}"><i class="bi {{ $ikon }} me-1" aria-hidden="true"></i>{{ $judul }}</th></tr>
                        {{-- Hak terkunci = tidak bisa dicabut dari role itu. --}}
                        @foreach ($hakGrup as $h)
                            <tr>
                                <th scope="row" class="rl-kol-hak">{{ Role::HAK_AKSES[$h][0] }}<small>{{ Role::HAK_AKSES[$h][1] }}</small></th>
                                @foreach ($roles as $r)
                                    <td class="text-center">
                                        @if (in_array($h, $r->kunci, true))
                                            <i class="bi bi-lock-fill rl-kunci" title="Terkunci" aria-label="Terkunci"></i>
                                        @elseif (in_array($h, $r->hak, true))
                                            <i class="bi bi-check-circle-fill rl-ya" title="Ya" aria-label="Ya"></i>
                                        @else
                                            <span class="rl-tidak" title="Tidak" aria-label="Tidak">–</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="rl-baris-total">
                        <th class="rl-kol-hak">Jumlah akun</th>
                        @foreach ($roles as $r)
                            <td class="text-center"><a href="{{ $r->urlAkun }}">{{ $r->jumlah }}</a></td>
                        @endforeach
                    </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </x-card>
    </div>
</main>

<x-modal id="modalHapusRole" title="Hapus role" icon="bi-trash" tone="danger">
    <form method="POST" id="formHapusRole">
        @csrf
        <input type="hidden" name="_method" value="POST">
        <p class="mb-3" data-info></p>
        <label class="form-label" for="rl-pengganti">Pindahkan akun ke role</label>
        <select name="pengganti" id="rl-pengganti" class="form-select @error('pengganti') is-invalid @enderror">
            <option value="" data-kosong>Pilih role pengganti</option>
            @foreach ($pilihanPengganti as $nilai => $label)
                <option value="{{ $nilai }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('pengganti')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text" data-catatan></div>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" variant="danger" form="formHapusRole" icon="bi-trash" data-tombol-hapus>Hapus Role</x-button>
    </x-slot:footer>
</x-modal>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Pilihan tampilan Daftar / Matriks diingat per browser (bila penyimpanan tersedia).
        const tombol = document.querySelectorAll('[data-tampilan]');
        const panel = document.querySelectorAll('[data-panel]');
        function tampilkan(nama) {
            tombol.forEach((b) => { const aktif = b.dataset.tampilan === nama; b.classList.toggle('active', aktif); b.setAttribute('aria-pressed', aktif ? 'true' : 'false'); });
            panel.forEach((p) => { p.hidden = p.dataset.panel !== nama; });
            try { localStorage.setItem('hrrab.master-role.tampilan', nama); } catch (e) {}
        }
        tombol.forEach((b) => b.addEventListener('click', () => tampilkan(b.dataset.tampilan)));
        let awal = 'daftar';
        try { awal = localStorage.getItem('hrrab.master-role.tampilan') || 'daftar'; } catch (e) {}
        tampilkan(awal === 'matriks' ? 'matriks' : 'daftar');

        // Cari + chip jenis — langsung di tabel tanpa muat ulang.
        const cari = document.getElementById('rl-cari');
        const kosong = document.querySelector('#rl-daftar [data-kosong-cari]');
        const jumlah = document.querySelector('[data-jumlah-role]');
        const chip = Array.from(document.querySelectorAll('[data-saring-jenis]'));
        let jenis = 'semua';
        function saring() {
            const kata = cari.value.trim().toLowerCase();
            let ada = 0;
            document.querySelectorAll('#rl-daftar tr[data-cari]').forEach((tr) => {
                tr.hidden = (kata !== '' && !tr.dataset.cari.includes(kata)) || (jenis !== 'semua' && tr.dataset.jenis !== jenis);
                ada += tr.hidden ? 0 : 1;
            });
            kosong.hidden = ada > 0;
            jumlah.textContent = ada;
        }
        cari.addEventListener('input', saring);
        chip.forEach((c) => c.addEventListener('click', () => {
            chip.forEach((x) => { x.classList.toggle('is-active', x === c); x.setAttribute('aria-pressed', x === c ? 'true' : 'false'); });
            jenis = c.dataset.saringJenis;
            saring();
        }));

        // Dialog hapus role: pengganti wajib bila role masih dipakai akun atau dipasang otomatis oleh sistem.
        const modal = document.getElementById('modalHapusRole');
        const form = document.getElementById('formHapusRole');
        const pengganti = form.pengganti;
        modal.addEventListener('show.bs.modal', (e) => {
            const t = e.relatedTarget, d = t.dataset;
            const n = Number(d.jumlah || 0);
            const wajib = n > 0 || !!d.otomatis;
            form.action = d.url;
            form.querySelector('[name=_method]').value = d.metode;
            document.getElementById('modalHapusRole-judul').textContent = 'Hapus role ' + d.nama;
                        Array.from(pengganti.options).forEach((o) => { o.hidden = o.value === d.nilai; });
            pengganti.value = '';
            pengganti.required = wajib;
            pengganti.querySelector('[data-kosong]').textContent = wajib
                ? 'Pilih role pengganti'
                : (d.kustom ? 'Kembali ke role ' + d.dasar : 'Tidak perlu pengganti');
            modal.querySelector('[data-info]').textContent = (n ? n + ' akun memakai role ini dan akan dipindahkan. ' : 'Tidak ada akun yang memakai role ini. ')
                + (d.kustom ? 'Role kustom dihapus permanen.' : 'Role bawaan disembunyikan dari pilihan dan bisa dipulihkan kapan saja.');
            modal.querySelector('[data-catatan]').textContent = d.otomatis
                ? 'Role ini juga dipasang otomatis oleh sistem (pegawai baru / kepala bagian). Setelah dihapus, penetapan otomatis memakai role pengganti — disarankan role dengan jenis dasar yang sama.'
                : 'Panel & alur persetujuan akun mengikuti jenis dasar role pengganti.';
        });
    });
</script>
@endsection
