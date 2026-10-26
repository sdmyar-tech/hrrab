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
                <button type="button" class="btn btn-outline-secondary active" data-tampilan="kartu"><i class="bi bi-grid-3x2-gap me-1"></i>Kartu</button>
                <button type="button" class="btn btn-outline-secondary" data-tampilan="matriks"><i class="bi bi-table me-1"></i>Matriks hak akses</button>
            </div>
            <div class="small text-muted"><i class="bi bi-lock-fill text-warning me-1"></i>hak terkunci &nbsp;·&nbsp; klik nama role untuk mengubah</div>
        </div>

        {{-- Tampilan kartu --}}
        <div class="rl-kartu-grid" data-panel="kartu">
            @foreach ($roles as $r)
                @php $persen = round(count($r->hak) / $totalHak * 100); @endphp
                <article class="rl-kartu rl-{{ $r->jenis }}">
                    <header class="rl-kartu-kepala">
                        <span class="rl-ikon"><i class="bi {{ Role::IKON_DASAR[$r->dasar] ?? 'bi-person' }}"></i></span>
                        <div class="min-w-0">
                            <h2 class="rl-nama"><a href="{{ $r->urlUbah }}">{{ $r->nama }}</a></h2>
                            <div class="rl-meta">
                                <span class="rl-label rl-label-{{ $r->jenis }}">{{ $labelJenis[$r->jenis] }}</span>
                                @if ($r->jenis === 'kustom')
                                    <span>dasar {{ User::roleLabelFor($r->dasar) }}</span>
                                @elseif ($r->jenis === 'disesuaikan' && $r->nama !== $r->namaAsli)
                                    <span>asli: {{ $r->namaAsli }}</span>
                                @endif
                            </div>
                        </div>
                    </header>

                    <p class="rl-ket">{{ $r->keterangan ?: ($ketBawaan[$r->dasar] ?? '') }}</p>

                    <div class="rl-progres">
                        <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Hak akses</span><strong>{{ count($r->hak) }}/{{ $totalHak }}</strong></div>
                        <div class="progress" role="progressbar" aria-label="Jumlah hak akses" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $persen }}%"></div>
                        </div>
                    </div>

                    <ul class="rl-grup-ringkas">
                        @foreach (Role::GRUP_HAK as $judul => [$ikon, $hakGrup])
                            @php $punya = count(array_intersect($hakGrup, $r->hak)); @endphp
                            <li class="{{ $punya ? 'ada' : '' }}" title="{{ $judul }}: {{ collect($hakGrup)->filter(fn ($h) => in_array($h, $r->hak, true))->map(fn ($h) => Role::HAK_AKSES[$h][0])->implode(', ') ?: 'tidak ada' }}">
                                <i class="bi {{ $ikon }}"></i>{{ $judul }} <span>{{ $punya }}/{{ count($hakGrup) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <footer class="rl-kartu-kaki">
                        <a href="{{ $r->urlAkun }}" class="rl-akun" title="Lihat akun dengan role ini"><i class="bi bi-people"></i>{{ $r->jumlah }} akun</a>
                        <div class="d-flex gap-1">
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
                    </footer>
                </article>
            @endforeach

            <a href="{{ route('admin.role.create') }}" class="rl-kartu rl-kartu-tambah">
                <i class="bi bi-plus-circle"></i>
                <strong>Tambah role kustom</strong>
                <small>mis. Staf Keuangan berbasis Staff dengan hak lihat laporan</small>
            </a>
        </div>

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

    .rl-kartu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 1rem; }
    .rl-kartu { display: flex; flex-direction: column; gap: .85rem; padding: 1.1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--hr-radius, 12px); box-shadow: var(--admin-shadow-sm); border-top: 3px solid var(--rl-aksen); --rl-aksen: #94a3b8; }
    .rl-kartu.rl-disesuaikan { --rl-aksen: #d97706; }
    .rl-kartu.rl-kustom { --rl-aksen: var(--hr-primary); }
    .rl-kartu-kepala { display: flex; gap: .75rem; align-items: center; }
    .rl-ikon { width: 44px; height: 44px; flex-shrink: 0; display: grid; place-items: center; border-radius: 12px; background: var(--hr-primary-soft); color: var(--hr-primary); font-size: 1.25rem; }
    .rl-nama { font-size: 1.02rem; font-weight: 700; margin: 0 0 .15rem; }
    .rl-nama a { color: var(--admin-text); text-decoration: none; }
    .rl-nama a:hover { color: var(--hr-primary); }
    .rl-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; font-size: .75rem; color: var(--admin-muted); }
    .rl-label { display: inline-block; font-size: .68rem; font-weight: 700; letter-spacing: .02em; padding: .1rem .45rem; border-radius: 999px; border: 1px solid; }
    .rl-label-bawaan { color: #475569; border-color: #cbd5e1; background: #f8fafc; }
    .rl-label-disesuaikan { color: #92400e; border-color: #fcd34d; background: #fffbeb; }
    .rl-label-kustom { color: var(--hr-primary-soft-text); border-color: transparent; background: var(--hr-primary-soft); }
    .rl-ket { margin: 0; font-size: .84rem; color: var(--admin-muted); min-height: 2.5em; }
    .rl-progres .progress { height: 6px; background: var(--admin-surface-soft); }
    .rl-progres .progress-bar { background: var(--rl-aksen); }
    .rl-grup-ringkas { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: .35rem; }
    .rl-grup-ringkas li { display: inline-flex; align-items: center; gap: .3rem; font-size: .72rem; padding: .18rem .5rem; border-radius: 8px; border: 1px dashed var(--admin-border); color: var(--admin-muted); }
    .rl-grup-ringkas li span { font-weight: 700; }
    .rl-grup-ringkas li.ada { border-style: solid; border-color: #c7d2fe; background: #eef2ff; color: #3730a3; }
    .rl-kartu-kaki { margin-top: auto; display: flex; justify-content: space-between; align-items: center; gap: .5rem; padding-top: .75rem; border-top: 1px solid var(--admin-border); }
    .rl-kartu-kaki form { margin: 0; }
    .rl-kartu .rl-akun { display: inline-flex; align-items: center; gap: .35rem; font-size: .84rem; font-weight: 600; color: var(--admin-text); text-decoration: none; }
    .rl-kartu .rl-akun:hover { color: var(--hr-primary); }
    .rl-kartu.rl-kartu-tambah { align-items: center; justify-content: center; text-align: center; gap: .35rem; border: 2px dashed var(--admin-border); box-shadow: none; background: transparent; color: var(--admin-muted); text-decoration: none; min-height: 220px; }
    .rl-kartu-tambah i { font-size: 1.8rem; color: var(--hr-primary); }
    .rl-kartu-tambah strong { color: var(--admin-text); }
    .rl-kartu-tambah small { color: var(--admin-muted); max-width: 220px; }
    .rl-kartu.rl-kartu-tambah:hover { border-color: var(--hr-primary); background: var(--hr-primary-soft); }

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
    html[data-theme="dark"] .rl-grup-ringkas li.ada { background: rgba(99, 102, 241, .15); color: #c7d2fe; border-color: rgba(99, 102, 241, .35); }
</style>
<script>
    // Pilihan tampilan Kartu / Matriks diingat per browser (bila penyimpanan tersedia).
    document.addEventListener('DOMContentLoaded', function () {
        const tombol = document.querySelectorAll('[data-tampilan]');
        const panel = document.querySelectorAll('[data-panel]');
        function tampilkan(nama) {
            tombol.forEach((b) => b.classList.toggle('active', b.dataset.tampilan === nama));
            panel.forEach((p) => { p.hidden = p.dataset.panel !== nama; });
            try { localStorage.setItem('hrrab.master-role.tampilan', nama); } catch (e) {}
        }
        tombol.forEach((b) => b.addEventListener('click', () => tampilkan(b.dataset.tampilan)));
        let awal = 'kartu';
        try { awal = localStorage.getItem('hrrab.master-role.tampilan') || 'kartu'; } catch (e) {}
        tampilkan(awal === 'matriks' ? 'matriks' : 'kartu');
    });
</script>
@endsection
