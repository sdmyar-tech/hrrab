@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-journal-text"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem</p>
                    <h1 class="h3 mb-1">Log Aktivitas</h1>
                    <p class="text-muted mb-0">Jejak tindakan semua pengguna selain superadmin: login, tambah, ubah, hapus, persetujuan, dan ekspor data.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.log.export', 'eksporUkuran' => ''])
            </div>
        </div>

        <div class="row g-3 mb-3">
            @foreach ([['Aktivitas hari ini', $ringkas['hari_ini'], 'bi-activity'], ['Pengguna aktif hari ini', $ringkas['pengguna_hari_ini'], 'bi-people'],
                ['Login hari ini', $ringkas['login_hari_ini'], 'bi-box-arrow-in-right'], ['Gagal hari ini', $ringkas['gagal_hari_ini'], 'bi-exclamation-triangle']] as [$judul, $angka, $ikonStat])
                <div class="col-6 col-lg-3">
                    <div class="log-stat {{ $ikonStat === 'bi-exclamation-triangle' && $angka ? 'peringatan' : '' }}">
                        <i class="bi {{ $ikonStat }}" aria-hidden="true"></i>
                        <span><strong>{{ number_format($angka, 0, ',', '.') }}</strong><small>{{ $judul }}</small></span>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="panel p-0 overflow-hidden">
            <form method="GET" action="{{ route('admin.log.index') }}" class="log-filter">
                <div class="input-group log-cari">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="Cari nama pengguna atau deskripsi…" value="{{ request('q') }}">
                </div>
                <select name="modul" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua modul</option>
                    @foreach ($modulOpsi as $m)
                        <option value="{{ $m }}" @selected(request('modul') === $m)>{{ $m }}</option>
                    @endforeach
                </select>
                <select name="aksi" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua aksi</option>
                    @foreach (\App\Models\LogAktivitas::AKSI as $kunci => [$labelAksi])
                        @continue($kunci === 'lainnya')
                        <option value="{{ $kunci }}" @selected(request('aksi') === $kunci)>{{ $labelAksi }}</option>
                    @endforeach
                </select>
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua role</option>
                    @foreach (array_diff([...\App\Models\User::INTERNAL_ROLES, \App\Models\User::ROLE_PELAMAR], [\App\Models\User::ROLE_ADMIN]) as $r)
                        <option value="{{ $r }}" @selected(request('role') === $r)>{{ \App\Models\User::roleLabelFor($r) }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="berhasil" @selected(request('status') === 'berhasil')>Berhasil</option>
                    <option value="gagal" @selected(request('status') === 'gagal')>Gagal</option>
                </select>
                <input type="date" name="dari" class="form-control" value="{{ request('dari') }}" title="Dari tanggal" onchange="this.form.submit()">
                <input type="date" name="sampai" class="form-control" value="{{ request('sampai') }}" title="Sampai tanggal" onchange="this.form.submit()">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i>Terapkan</button>
                @if (request()->hasAny(['q', 'modul', 'aksi', 'role', 'status', 'dari', 'sampai']))
                    <a href="{{ route('admin.log.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i>Reset</a>
                @endif
            </form>

            @php $tanggalSebelumnya = null; @endphp
            <ul class="list-unstyled mb-0 log-daftar">
                @forelse ($logs as $log)
                    @php
                        [$labelAksi, $badgeAksi, $ikonAksi] = $log->aksiInfo();
                        $tanggal = $log->created_at->toDateString();
                    @endphp
                    @if ($tanggal !== $tanggalSebelumnya)
                        <li class="log-tanggal">
                            {{ $log->created_at->isToday() ? 'Hari ini' : ($log->created_at->isYesterday() ? 'Kemarin' : $log->created_at->translatedFormat('l, d F Y')) }}
                        </li>
                        @php $tanggalSebelumnya = $tanggal; @endphp
                    @endif
                    <li class="log-baris {{ $log->status === 'gagal' ? 'gagal' : '' }}">
                        <span class="log-jam" title="{{ $log->created_at->translatedFormat('d M Y H:i:s') }}">{{ $log->created_at->format('H:i') }}</span>
                        <span class="log-ikon {{ $badgeAksi }}"><i class="bi {{ $ikonAksi }}" aria-hidden="true"></i></span>
                        <div class="log-isi">
                            <div class="log-judul">
                                <strong>{{ $log->nama_pengguna }}</strong>
                                <span class="text-muted small">{{ \App\Models\User::roleLabelFor($log->role) }}</span>
                                <span class="badge {{ $badgeAksi }}">{{ $labelAksi }}</span>
                                <span class="badge text-bg-light border">{{ $log->modul }}</span>
                                @if ($log->status === 'gagal')<span class="badge text-bg-danger">Gagal</span>@endif
                            </div>
                            <div class="log-deskripsi">{{ $log->deskripsi }}</div>
                            @if ($log->perubahan)
                                <details class="log-rincian">
                                    <summary>Rincian ({{ count($log->perubahan) }})</summary>
                                    @foreach ($log->perubahan as $p)
                                        <div class="log-perubahan">
                                            <div class="small fw-semibold mb-1">
                                                {{ $p['model'] }}@if ($p['id']) #{{ $p['id'] }}@endif
                                                <span class="text-muted fw-normal">· {{ ['tambah' => 'dibuat', 'ubah' => 'diubah', 'hapus' => 'dihapus'][$p['jenis']] ?? $p['jenis'] }}</span>
                                            </div>
                                            @if (! empty($p['kolom']))
                                                <table class="table table-sm mb-0 log-tabel">
                                                    <tbody>
                                                        @foreach ($p['kolom'] as $kolom => [$lama, $baru])
                                                            <tr>
                                                                <th>{{ \Illuminate\Support\Str::headline($kolom) }}</th>
                                                                @if ($p['jenis'] === 'ubah')
                                                                    <td class="lama">{{ $lama === null || $lama === '' ? '—' : (is_bool($lama) ? ($lama ? 'Ya' : 'Tidak') : $lama) }}</td>
                                                                    <td class="panah">→</td>
                                                                @endif
                                                                <td class="baru">{{ ($p['jenis'] === 'hapus' ? $lama : $baru) ?? '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @endif
                                        </div>
                                    @endforeach
                                </details>
                            @endif
                            <div class="log-meta">{{ $log->ip ?? '-' }} · {{ \Illuminate\Support\Str::limit($log->perangkat, 70) ?: '-' }}</div>
                        </div>
                    </li>
                @empty
                    <li class="text-center text-muted py-5">
                        <i class="bi bi-journal d-block fs-2 mb-2"></i>
                        {{ request()->hasAny(['q', 'modul', 'aksi', 'role', 'status', 'dari', 'sampai']) ? 'Tidak ada aktivitas yang cocok dengan filter.' : 'Belum ada aktivitas tercatat.' }}
                    </li>
                @endforelse
            </ul>

            @if ($logs->hasPages())
                <div class="px-3 py-2 border-top">{{ $logs->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>
    </div>
</main>

<style>
    .log-stat { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; border: 1px solid var(--admin-border); border-radius: .85rem; background: var(--admin-surface); height: 100%; }
    .log-stat > i { width: 38px; height: 38px; display: inline-grid; place-items: center; border-radius: .6rem; background: var(--hr-primary-soft); color: var(--hr-primary); flex: none; }
    .log-stat strong { display: block; font-size: 1.25rem; line-height: 1.1; }
    .log-stat small { color: var(--admin-muted); font-size: .78rem; }
    .log-stat.peringatan { border-color: #f1b0aa; }
    .log-stat.peringatan > i { background: #fdecea; color: #b42318; }
    .log-filter { display: flex; flex-wrap: wrap; gap: .5rem; padding: 1rem; border-bottom: 1px solid var(--admin-border); background: var(--admin-surface-soft); }
    .log-filter .log-cari { flex: 1 1 260px; }
    .log-filter .form-select, .log-filter input[type=date] { flex: 0 1 auto; width: auto; }
    .log-tanggal { position: sticky; top: 0; z-index: 1; padding: .45rem 1rem; font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        color: var(--admin-muted); background: var(--admin-surface-soft); border-bottom: 1px solid var(--admin-border); }
    .log-baris { display: flex; gap: .75rem; padding: .75rem 1rem; border-bottom: 1px solid var(--admin-border); }
    .log-baris:hover { background: rgba(136, 15, 18, .03); }
    .log-baris.gagal { background: rgba(220, 53, 69, .04); }
    .log-jam { flex: none; width: 42px; font-size: .8rem; font-weight: 600; color: var(--admin-muted); padding-top: .2rem; font-variant-numeric: tabular-nums; }
    .log-ikon { flex: none; width: 32px; height: 32px; border-radius: 50%; display: inline-grid; place-items: center; font-size: .9rem; }
    .log-isi { flex: 1 1 auto; min-width: 0; }
    .log-judul { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .5rem; }
    .log-deskripsi { margin-top: .15rem; font-size: .9rem; overflow-wrap: anywhere; }
    .log-meta { margin-top: .25rem; font-size: .72rem; color: var(--admin-muted); overflow-wrap: anywhere; }
    .log-rincian { margin-top: .4rem; }
    .log-rincian summary { cursor: pointer; font-size: .8rem; font-weight: 600; color: var(--hr-link); width: fit-content; }
    .log-perubahan { margin-top: .5rem; padding: .6rem .75rem; border: 1px solid var(--admin-border); border-radius: .6rem; background: var(--admin-surface-soft); }
    .log-tabel th, .log-tabel td { padding: .35rem .5rem !important; vertical-align: top; border-color: var(--admin-border); }
    .log-tabel tbody tr:hover { background: transparent; }
    .log-tabel th { width: 30%; font-weight: 600; font-size: .78rem; color: var(--admin-muted); text-transform: none; letter-spacing: 0; background: transparent; }
    .log-tabel td { font-size: .8rem; overflow-wrap: anywhere; }
    .log-tabel td.lama { color: #b42318; text-decoration: line-through; text-decoration-color: rgba(180, 35, 24, .4); }
    .log-tabel td.baru { color: #146c43; font-weight: 600; }
    .log-tabel td.panah { width: 1%; color: var(--admin-muted); }
    @media (max-width: 575.98px) {
        .log-filter .form-select, .log-filter input[type=date], .log-filter .btn { flex: 1 1 calc(50% - .5rem); }
        .log-jam { width: 36px; }
    }
</style>
@endsection
