@extends('layouts.admin.app')

@section('content')
@php
    use App\Models\Role;
    use App\Models\User;

    $totalHak = count(Role::HAK_AKSES);
    // Keterangan singkat role bawaan bila belum diisi di Master Role.
    $ketBawaan = [
        User::ROLE_PEMBINA => 'Pemilik yayasan: memantau (hanya-baca) & ikut menyetujui formasi.',
        User::ROLE_GM => 'Menyetujui pengajuan pegawai di bidangnya.',
        User::ROLE_KEPALA_BAGIAN => 'Kepala unit: menyetujui pengajuan anggota unitnya.',
        User::ROLE_KEPALA_SDM => 'Mengelola kepegawaian & absensi, persetujuan tahap akhir.',
        User::ROLE_STAFF => 'Pegawai: mengajukan cuti, lembur & izin keluar.',
        User::ROLE_ADMIN => 'Pengelola sistem dengan akses penuh.',
    ];
    $labelJenis = ['bawaan' => 'Bawaan', 'disesuaikan' => 'Disesuaikan', 'kustom' => 'Kustom'];
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-shield-check"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem</p>
                    <h1 class="h3 mb-1">Master Role</h1>
                    <p class="text-muted mb-0">Atur nama & hak akses tiap role. Role kustom mengikuti <strong>jenis dasar</strong>-nya untuk panel & alur persetujuan, tetapi hak aksesnya sendiri.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.role.export'])
                <a href="{{ route('admin.role.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah Role</a>
            </div>
        </div>

        <section class="panel p-0 overflow-hidden">
            {{-- Bilah atas (seperti tabel lain): cari + pilihan tampilan Daftar / Matriks. --}}
            <div class="rl-bilah">
                <div class="input-group rl-cari" data-panel="daftar">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input type="search" class="form-control" id="rl-cari" placeholder="Cari role, contoh: Staff" autocomplete="off" aria-label="Cari role menurut nama">
                </div>
                <div class="btn-group rl-tampilan ms-auto" role="group" aria-label="Pilih tampilan">
                    <button type="button" class="btn btn-outline-secondary active" data-tampilan="daftar"><i class="bi bi-list-ul"></i> Daftar</button>
                    <button type="button" class="btn btn-outline-secondary" data-tampilan="matriks"><i class="bi bi-table"></i> Matriks hak akses</button>
                </div>
            </div>

            {{-- Tampilan daftar (tabel) --}}
            <div class="table-responsive" data-panel="daftar">
                <table class="table align-middle mb-0 table-mobile-cards">
                    <thead>
                        <tr>
                            <th class="rl-no" style="width:56px">No</th>
                            <th>Role</th>
                            <th>Jenis</th>
                            <th>Jenis Dasar</th>
                            <th>Hak Akses</th>
                            <th class="text-center">Akun</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="rl-daftar">
                        @foreach ($roles as $r)
                            @php
                                // Ringkas per kelompok, mis. "Umum, Pengajuan, Kepegawaian" (rinciannya di tooltip & tampilan Matriks).
                                $grupMilik = collect(Role::GRUP_HAK)->filter(fn ($g) => array_intersect($g[1], $r->hak))->keys();
                                $rincianHak = collect($r->hak)->map(fn ($h) => Role::HAK_AKSES[$h][0] ?? $h)->implode(', ');
                            @endphp
                            <tr data-cari="{{ mb_strtolower($r->nama . ' ' . ($r->namaAsli ?? '') . ' ' . $labelJenis[$r->jenis] . ' ' . (User::LABEL_ROLE[$r->dasar] ?? '')) }}">
                                <td class="text-muted rl-no" data-label="No">{{ $loop->iteration }}</td>
                                <td data-label="Role" class="kolom-penuh">
                                    <span class="fw-semibold d-block">{{ $r->nama }}</span>
                                    <small class="text-muted">
                                        {{ $r->keterangan ?: ($ketBawaan[$r->dasar] ?? '') }}
                                        @if ($r->jenis === 'disesuaikan' && $r->nama !== $r->namaAsli) · nama asli: {{ $r->namaAsli }} @endif
                                    </small>
                                </td>
                                <td data-label="Jenis"><span class="badge {{ ['bawaan' => 'text-bg-light border', 'disesuaikan' => 'text-bg-warning', 'kustom' => 'text-bg-info'][$r->jenis] }}">{{ $labelJenis[$r->jenis] }}</span></td>
                                <td data-label="Jenis Dasar">{{ User::LABEL_ROLE[$r->dasar] ?? $r->dasar }}</td>
                                <td data-label="Hak Akses" title="{{ $rincianHak }}">
                                    <span class="d-block">{{ count($r->hak) }} dari {{ $totalHak }} hak</span>
                                    <small class="text-muted">{{ $grupMilik->implode(', ') ?: '-' }}</small>
                                </td>
                                <td class="text-md-center" data-label="Akun"><a href="{{ $r->urlAkun }}" title="Lihat akun dengan role ini">{{ $r->jumlah }}</a></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1 justify-content-end">
                                        <a href="{{ $r->urlUbah }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                                        @if ($r->urlReset)
                                            <form action="{{ $r->urlReset }}" method="POST" data-confirm="Kembalikan role {{ $r->namaAsli }} ke nama & hak akses bawaan sistem?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-outline-warning btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                                            </form>
                                        @endif
                                        @if ($r->urlHapus)
                                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalHapusRole"
                                                data-url="{{ $r->urlHapus }}" data-metode="{{ $r->jenis === 'kustom' ? 'DELETE' : 'POST' }}" data-nama="{{ $r->nama }}"
                                                data-nilai="{{ $r->nilai }}" data-jumlah="{{ $r->jumlah }}" data-otomatis="{{ $r->otomatis ? '1' : '' }}"
                                                data-kustom="{{ $r->jenis === 'kustom' ? '1' : '' }}" data-dasar="{{ User::roleLabelFor($r->dasar) }}">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        {{-- Role bawaan yang dihapus: tetap di tabel yang sama, bisa dipulihkan. --}}
                        @foreach ($dihapus as $d)
                            <tr class="rl-dihapus" data-cari="{{ mb_strtolower($d->nama . ' ' . $d->namaAsli . ' dihapus') }}">
                                <td class="text-muted rl-no" data-label="No">{{ $roles->count() + $loop->iteration }}</td>
                                <td data-label="Role" class="kolom-penuh">
                                    <span class="fw-semibold d-block">{{ $d->nama }}</span>
                                    <small class="text-muted">{{ $d->pengganti ? 'Akun dipindahkan ke ' . $d->pengganti . '.' : 'Tanpa role pengganti.' }}</small>
                                </td>
                                <td data-label="Jenis"><span class="badge text-bg-secondary">Dihapus</span></td>
                                <td data-label="Jenis Dasar">{{ $d->namaAsli }}</td>
                                <td data-label="Hak Akses" class="text-muted">-</td>
                                <td class="text-md-center" data-label="Akun">{{ $d->jumlah }}</td>
                                <td class="text-end">
                                    <form action="{{ $d->urlPulihkan }}" method="POST" class="d-inline" data-confirm="Pulihkan role {{ $d->nama }}? Role ini bisa dipilih lagi di Kelola Akun.">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Pulihkan</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        <tr data-kosong-cari hidden>
                            <td colspan="7" class="text-center py-4 text-muted"><i class="bi bi-search fs-3 d-block mb-2"></i>Tidak ada role yang cocok.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Tampilan matriks --}}
            <div class="table-responsive" data-panel="matriks" hidden>
                <table class="table rl-matriks mb-0">
                    <thead>
                        <tr>
                            <th class="rl-kol-hak">Hak akses</th>
                            @foreach ($roles as $r)
                                <th class="text-center">
                                    <a href="{{ $r->urlUbah }}" class="rl-kol-nama"><i class="bi {{ Role::IKON_DASAR[$r->dasar] ?? 'bi-person' }}"></i>{{ $r->nama }}</a>
                                    <span class="rl-label rl-label-{{ $r->jenis }}">{{ $labelJenis[$r->jenis] }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (Role::GRUP_HAK as $judul => [$ikon, $hakGrup])
                            <tr class="rl-baris-grup"><th colspan="{{ $roles->count() + 1 }}"><i class="bi {{ $ikon }} me-1"></i>{{ $judul }}</th></tr>
                            {{-- Hak terkunci = tidak bisa dicabut dari role itu. --}}
                            @foreach ($hakGrup as $h)
                                <tr>
                                    <th scope="row" class="rl-kol-hak">{{ Role::HAK_AKSES[$h][0] }}<small>{{ Role::HAK_AKSES[$h][1] }}</small></th>
                                    @foreach ($roles as $r)
                                        <td class="text-center">
                                            @if (in_array($h, $r->kunci, true))
                                                <i class="bi bi-lock-fill text-warning" title="Terkunci"></i>
                                            @elseif (in_array($h, $r->hak, true))
                                                <i class="bi bi-check-circle-fill rl-ya" title="Ya"></i>
                                            @else
                                                <span class="rl-tidak" title="Tidak">–</span>
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
        </section>
    </div>
</main>

<div class="modal fade" id="modalHapusRole" tabindex="-1" aria-labelledby="judulHapusRole" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="formHapusRole">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="judulHapusRole">Hapus role <span data-nama></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small mb-3" data-info></p>
                <label class="form-label" for="rl-pengganti">Pindahkan akun ke role</label>
                <select name="pengganti" id="rl-pengganti" class="form-select @error('pengganti') is-invalid @enderror">
                    <option value="" data-kosong>-- Pilih role pengganti --</option>
                    @foreach ($pilihanPengganti as $nilai => $label)
                        <option value="{{ $nilai }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('pengganti')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text" data-catatan></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Hapus role</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Bilah atas sama dengan Master Lokasi Kerja / Unit. */
    .rl-bilah { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; padding: 1rem; border-bottom: 1px solid var(--admin-border); background: var(--admin-surface-soft); }
    .rl-cari { flex: 0 1 360px; }
    .rl-tampilan .btn.active { background: var(--hr-primary); border-color: var(--hr-primary); color: #fff; }
    .rl-dihapus td { color: var(--admin-muted); }
    #rl-daftar td form { margin: 0; }
    @media (max-width: 767.98px) { .rl-no { display: none !important; } }

    .rl-matriks { font-size: .85rem; }
    .rl-matriks thead th { vertical-align: bottom; text-transform: none; letter-spacing: normal; background: var(--admin-surface-soft); min-width: 96px; max-width: 130px; white-space: normal; }
    .rl-matriks .rl-kol-nama { display: block; font-weight: 700; font-size: .8rem; line-height: 1.25; color: var(--admin-text); text-decoration: none; margin-bottom: .25rem; }
    .rl-kol-nama i { display: block; margin: 0 auto .2rem; font-size: 1rem; color: var(--hr-primary); }
    .rl-matriks .rl-kol-nama:hover { color: var(--hr-primary); }
    .rl-matriks .rl-baris-total a { color: var(--admin-text); }
    .rl-kol-hak { position: sticky; left: 0; z-index: 1; background: var(--admin-surface); min-width: 210px; font-weight: 600; }
    .rl-kol-hak small { display: block; font-weight: 400; color: var(--admin-muted); font-size: .74rem; }
    thead .rl-kol-hak { background: var(--admin-surface-soft); }
    .rl-baris-grup th { background: var(--admin-surface-soft) !important; font-size: .74rem; text-transform: uppercase; letter-spacing: .05em; color: var(--admin-muted); padding: .45rem .75rem; }
    .rl-matriks td { vertical-align: middle; }
    .rl-ya { color: #16a34a; font-size: 1.05rem; }
    .rl-tidak { color: var(--admin-muted); opacity: .6; }
    .rl-baris-total th, .rl-baris-total td { border-top: 2px solid var(--admin-border); font-weight: 700; }

</style>
<script>
    // Pilihan tampilan Daftar / Matriks diingat per browser (bila penyimpanan tersedia).
    document.addEventListener('DOMContentLoaded', function () {
        const tombol = document.querySelectorAll('[data-tampilan]');
        const panel = document.querySelectorAll('[data-panel]');
        function tampilkan(nama) {
            tombol.forEach((b) => b.classList.toggle('active', b.dataset.tampilan === nama));
            panel.forEach((p) => { p.hidden = p.dataset.panel !== nama; });
            try { localStorage.setItem('hrrab.master-role.tampilan', nama); } catch (e) {}
        }
        tombol.forEach((b) => b.addEventListener('click', () => tampilkan(b.dataset.tampilan)));
        let awal = 'daftar';
        try { awal = localStorage.getItem('hrrab.master-role.tampilan') || 'daftar'; } catch (e) {}
        tampilkan(awal === 'matriks' ? 'matriks' : 'daftar');

        // Cari role (nama, nama asli, jenis, jenis dasar) — langsung di tabel, seperti tabel master lain.
        const cari = document.getElementById('rl-cari');
        const kosong = document.querySelector('#rl-daftar [data-kosong-cari]');
        cari.addEventListener('input', function () {
            const kata = cari.value.trim().toLowerCase();
            let ada = 0;
            document.querySelectorAll('#rl-daftar tr[data-cari]').forEach((tr) => {
                tr.hidden = kata !== '' && !tr.dataset.cari.includes(kata);
                ada += tr.hidden ? 0 : 1;
            });
            kosong.hidden = ada > 0;
        });

        // Dialog hapus role: pengganti wajib bila role masih dipakai akun atau dipasang otomatis oleh sistem.
        const modal = document.getElementById('modalHapusRole');
        const form = document.getElementById('formHapusRole');
        const pengganti = form.pengganti;
        modal.addEventListener('show.bs.modal', (e) => {
            const t = e.relatedTarget, d = t.dataset;
            const jumlah = Number(d.jumlah || 0);
            const wajib = jumlah > 0 || !!d.otomatis;
            form.action = d.url;
            form.querySelector('[name=_method]').value = d.metode;
            modal.querySelector('[data-nama]').textContent = d.nama;
            Array.from(pengganti.options).forEach((o) => { o.hidden = o.value === d.nilai; });
            pengganti.value = '';
            pengganti.required = wajib;
            pengganti.querySelector('[data-kosong]').textContent = wajib
                ? '-- Pilih role pengganti --'
                : (d.kustom ? 'Kembali ke role ' + d.dasar : 'Tidak perlu pengganti');
            const info = modal.querySelector('[data-info]');
            info.textContent = (jumlah ? jumlah + ' akun memakai role ini dan akan dipindahkan. ' : 'Tidak ada akun yang memakai role ini. ')
                + (d.kustom ? 'Role kustom dihapus permanen.' : 'Role bawaan disembunyikan dari pilihan dan bisa dipulihkan kapan saja.');
            modal.querySelector('[data-catatan]').textContent = d.otomatis
                ? 'Role ini juga dipasang otomatis oleh sistem (pegawai baru / kepala unit). Setelah dihapus, penetapan otomatis memakai role pengganti — disarankan role dengan jenis dasar yang sama.'
                : 'Panel & alur persetujuan akun mengikuti jenis dasar role pengganti.';
        });
    });
</script>
@endsection
