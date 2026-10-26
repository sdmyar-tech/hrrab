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
                <a href="{{ route('admin.role.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah Role</a>
            </div>
        </div>

        <div class="rl-ringkas mb-3">
            <div class="rl-stat"><span class="rl-stat-ikon"><i class="bi bi-shield-check"></i></span><div><strong>{{ $ringkas['role'] }}</strong><small>Role</small></div></div>
            <div class="rl-stat"><span class="rl-stat-ikon"><i class="bi bi-stars"></i></span><div><strong>{{ $ringkas['kustom'] }}</strong><small>Role kustom</small></div></div>
            <div class="rl-stat"><span class="rl-stat-ikon"><i class="bi bi-sliders"></i></span><div><strong>{{ $ringkas['disesuaikan'] }}</strong><small>Bawaan disesuaikan</small></div></div>
            <div class="rl-stat"><span class="rl-stat-ikon"><i class="bi bi-people"></i></span><div><strong>{{ $ringkas['akun'] }}</strong><small>Akun internal</small></div></div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="btn-group rl-tampilan" role="group" aria-label="Pilih tampilan">
                <button type="button" class="btn btn-outline-secondary active" data-tampilan="daftar"><i class="bi bi-list-ul me-1"></i>Daftar role</button>
                <button type="button" class="btn btn-outline-secondary" data-tampilan="matriks"><i class="bi bi-table me-1"></i>Matriks hak akses</button>
            </div>
            <div class="small text-muted"><i class="bi bi-lock-fill text-warning me-1"></i>hak terkunci &nbsp;·&nbsp; klik nama role untuk mengubah</div>
        </div>

        {{-- Tampilan daftar (tabel) --}}
        <section class="panel p-0 overflow-hidden" data-panel="daftar">
            <div class="table-responsive">
                <table class="table rl-daftar align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th class="rl-kol-dasar">Jenis dasar</th>
                            <th class="rl-kol-hakakses">Hak akses</th>
                            <th class="text-center">Akun</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    @foreach (['Role bawaan' => $roles->where('jenis', '!=', 'kustom'), 'Role kustom' => $roles->where('jenis', 'kustom')] as $judulKelompok => $kelompok)
                        <tbody>
                            <tr class="rl-baris-grup">
                                <th colspan="5">
                                    {{ $judulKelompok }} <span class="rl-jumlah-grup">{{ $kelompok->count() }}</span>
                                    <small class="text-muted fw-normal text-lowercase ms-1">{{ $loop->first ? '— tidak bisa dihapus; bisa diganti nama & hak aksesnya' : '— mengikuti panel & alur jenis dasarnya' }}</small>
                                </th>
                            </tr>
                            @forelse ($kelompok as $r)
                                <tr class="rl-{{ $r->jenis }}">
                                    <td>
                                        <div class="rl-role">
                                            <span class="rl-ikon"><i class="bi {{ Role::IKON_DASAR[$r->dasar] ?? 'bi-person' }}"></i></span>
                                            <div class="min-w-0">
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <a href="{{ $r->urlUbah }}" class="rl-nama">{{ $r->nama }}</a>
                                                    <span class="rl-label rl-label-{{ $r->jenis }}">{{ $labelJenis[$r->jenis] }}</span>
                                                    @if ($r->jenis === 'disesuaikan' && $r->nama !== $r->namaAsli)<small class="text-muted">asli: {{ $r->namaAsli }}</small>@endif
                                                </div>
                                                <small class="rl-ket">{{ $r->keterangan ?: ($ketBawaan[$r->dasar] ?? '') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    {{-- Nama asli jenis dasar (bukan nama tampilan yang disesuaikan). --}}
                                    <td class="rl-kol-dasar"><span class="rl-dasar"><i class="bi {{ Role::IKON_DASAR[$r->dasar] ?? 'bi-person' }}"></i>{{ User::LABEL_ROLE[$r->dasar] ?? $r->dasar }}</span></td>
                                    <td class="rl-kol-hakakses">
                                        {{-- Bar bersegmen: satu kotak per hak, dikelompokkan; terisi = punya. --}}
                                        <div class="rl-segmen" role="img" aria-label="{{ count($r->hak) }} dari {{ $totalHak }} hak akses">
                                            @foreach (Role::GRUP_HAK as $judul => [$ikon, $hakGrup])
                                                <span class="rl-segmen-grup">
                                                    @foreach ($hakGrup as $h)
                                                        <i class="{{ in_array($h, $r->hak, true) ? 'isi' : '' }} {{ in_array($h, $r->kunci, true) ? 'kunci' : '' }}"
                                                            title="{{ $judul }} · {{ Role::HAK_AKSES[$h][0] }}: {{ in_array($h, $r->hak, true) ? 'ya' : 'tidak' }}"></i>
                                                    @endforeach
                                                </span>
                                            @endforeach
                                            <strong>{{ count($r->hak) }}/{{ $totalHak }}</strong>
                                        </div>
                                        <ul class="rl-grup-ringkas">
                                            @foreach (Role::GRUP_HAK as $judul => [$ikon, $hakGrup])
                                                @php $milik = collect($hakGrup)->filter(fn ($h) => in_array($h, $r->hak, true)); @endphp
                                                @continue($milik->isEmpty())
                                                <li title="{{ $judul }}: {{ $milik->map(fn ($h) => Role::HAK_AKSES[$h][0])->implode(', ') }}">
                                                    <i class="bi {{ $ikon }}"></i>{{ $judul }} <span>{{ $milik->count() }}/{{ count($hakGrup) }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="text-center"><a href="{{ $r->urlAkun }}" class="rl-akun" title="Lihat akun dengan role ini">{{ $r->jumlah }}</a></td>
                                    <td class="text-end rl-aksi">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ $r->urlUbah }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                                            @if ($r->urlReset)
                                                <form action="{{ $r->urlReset }}" method="POST" data-confirm="Kembalikan role {{ $r->namaAsli }} ke nama & hak akses bawaan sistem?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-warning btn-sm" title="Kembalikan ke bawaan"><i class="bi bi-arrow-counterclockwise"></i></button>
                                                </form>
                                            @endif
                                            @if ($r->urlHapus)
                                                <form action="{{ $r->urlHapus }}" method="POST"
                                                    data-confirm="Hapus role {{ $r->nama }}?{{ $r->jumlah ? ' ' . $r->jumlah . ' akun kembali ke role ' . User::roleLabelFor($r->dasar) . '.' : '' }}">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus role"><i class="bi bi-trash"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada role kustom. <a href="{{ route('admin.role.create') }}">Tambah role</a>, mis. <em>Staf Keuangan</em> berbasis Staff dengan hak lihat laporan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    @endforeach
                </table>
            </div>
        </section>

        {{-- Tampilan matriks --}}
        <section class="panel p-0 overflow-hidden" data-panel="matriks" hidden>
            <div class="table-responsive">
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

<style>
    .rl-ringkas { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
    @media (max-width: 767.98px) { .rl-ringkas { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .rl-stat { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--hr-radius, 12px); box-shadow: var(--admin-shadow-sm); }
    .rl-stat strong { display: block; font-size: 1.35rem; line-height: 1.1; }
    .rl-stat small { color: var(--admin-muted); font-size: .78rem; }
    .rl-stat-ikon { width: 38px; height: 38px; flex-shrink: 0; display: grid; place-items: center; border-radius: 10px; background: var(--hr-primary-soft); color: var(--hr-primary); font-size: 1.05rem; }

    .rl-tampilan .btn.active { background: var(--hr-primary); border-color: var(--hr-primary); color: #fff; }

    /* Tabel daftar role: aksen warna per status di sisi kiri baris. */
    .rl-daftar thead th { background: var(--admin-surface-soft); white-space: nowrap; }
    .rl-daftar tbody tr:not(.rl-baris-grup) > td:first-child { box-shadow: inset 3px 0 0 var(--rl-aksen); }
    .rl-daftar tr { --rl-aksen: #94a3b8; }
    .rl-daftar tr.rl-disesuaikan { --rl-aksen: #d97706; }
    .rl-daftar tr.rl-kustom { --rl-aksen: var(--hr-primary); }
    .rl-daftar tbody tr:not(.rl-baris-grup):hover > td { background: var(--admin-surface-soft); }
    .rl-role { display: flex; gap: .75rem; align-items: center; min-width: 260px; }
    .rl-ikon { width: 40px; height: 40px; flex-shrink: 0; display: grid; place-items: center; border-radius: 11px; background: var(--hr-primary-soft); color: var(--hr-primary); font-size: 1.15rem; }
    .rl-daftar .rl-nama { font-weight: 700; color: var(--admin-text); text-decoration: none; }
    .rl-daftar .rl-nama:hover { color: var(--hr-primary); }
    .rl-ket { display: block; margin-top: .1rem; font-size: .78rem; color: var(--admin-muted); max-width: 360px; }
    .rl-label { display: inline-block; font-size: .68rem; font-weight: 700; letter-spacing: .02em; padding: .1rem .45rem; border-radius: 999px; border: 1px solid; }
    .rl-label-bawaan { color: #475569; border-color: #cbd5e1; background: #f8fafc; }
    .rl-label-disesuaikan { color: #92400e; border-color: #fcd34d; background: #fffbeb; }
    .rl-label-kustom { color: var(--hr-primary-soft-text); border-color: transparent; background: var(--hr-primary-soft); }
    .rl-kol-dasar { white-space: nowrap; }
    .rl-dasar { display: inline-flex; align-items: center; gap: .35rem; font-size: .82rem; color: var(--admin-text); }
    .rl-dasar i { color: var(--admin-muted); }
    .rl-kol-hakakses { min-width: 320px; }
    .rl-segmen { display: flex; align-items: center; gap: .45rem; margin-bottom: .4rem; }
    .rl-segmen-grup { display: inline-flex; gap: 2px; }
    .rl-segmen-grup i { width: 14px; height: 8px; border-radius: 2px; background: var(--admin-border); }
    .rl-segmen-grup i.isi { background: #16a34a; }
    .rl-segmen-grup i.kunci { background: #f59e0b; }
    .rl-segmen strong { font-size: .8rem; margin-left: .15rem; }
    .rl-grup-ringkas { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: .3rem; }
    .rl-grup-ringkas li { display: inline-flex; align-items: center; gap: .25rem; font-size: .7rem; padding: .1rem .45rem; border-radius: 7px; border: 1px solid #c7d2fe; background: #eef2ff; color: #3730a3; }
    .rl-grup-ringkas li span { font-weight: 700; }
    .rl-daftar td:nth-child(4) { white-space: nowrap; }
    .rl-daftar .rl-akun { display: inline-block; min-width: 2.2rem; padding: .15rem .55rem; border-radius: 999px; font-weight: 700; font-size: .85rem; white-space: nowrap; text-align: center; color: var(--admin-text); background: var(--admin-surface-soft); border: 1px solid var(--admin-border); text-decoration: none; }
    .rl-daftar .rl-akun:hover { border-color: var(--hr-primary); color: var(--hr-primary); }
    .table tbody td.rl-aksi { white-space: nowrap !important; width: 1%; }
    .rl-aksi form { margin: 0; }
    .rl-jumlah-grup { display: inline-block; margin-left: .35rem; padding: 0 .45rem; border-radius: 999px; background: var(--admin-border); color: var(--admin-text); font-size: .7rem; }

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

    html[data-theme="dark"] .rl-label-bawaan { color: #cbd5e1; border-color: #475569; background: transparent; }
    html[data-theme="dark"] .rl-label-disesuaikan { color: #fcd34d; border-color: #92400e; background: transparent; }
    html[data-theme="dark"] .rl-grup-ringkas li { background: rgba(99, 102, 241, .15); color: #c7d2fe; border-color: rgba(99, 102, 241, .35); }
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
    });
</script>
@endsection
