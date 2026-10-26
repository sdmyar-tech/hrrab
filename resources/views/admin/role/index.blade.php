@extends('layouts.admin.app')

@section('content')
@php
    $hak = \App\Models\Role::HAK_AKSES;
    $chip = fn (array $daftar) => collect($hak)->keys()->filter(fn ($k) => in_array($k, $daftar, true));
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

        <section class="panel p-0 overflow-hidden mb-3">
            <div class="rl-judul">Role Bawaan <span class="text-muted fw-normal small">— tidak bisa dihapus; bisa diganti nama & hak aksesnya</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Role</th><th>Hak Akses</th><th class="text-center">Akun</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($bawaan as $r)
                            <tr>
                                <td class="rl-nama">
                                    <span class="fw-semibold d-block">{{ $r->nama }}</span>
                                    <small class="text-muted">
                                        @if ($r->disesuaikan)<span class="badge text-bg-warning">Disesuaikan</span>@if ($r->nama !== $r->namaAsli) asli: {{ $r->namaAsli }}@endif @else<span class="badge text-bg-light border">Bawaan</span>@endif
                                    </small>
                                    @if ($r->keterangan)<small class="text-muted d-block">{{ $r->keterangan }}</small>@endif
                                </td>
                                <td>@include('admin.role._chip', ['daftar' => $chip($r->hakAkses), 'kunci' => \App\Models\Role::DIKUNCI[$r->kode] ?? []])</td>
                                <td class="text-center">{{ $r->jumlah }}</td>
                                <td class="text-end rl-aksi">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.role.bawaan.edit', $r->kode) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                                        @if ($r->disesuaikan)
                                            <form action="{{ route('admin.role.bawaan.reset', $r->kode) }}" method="POST" data-confirm="Kembalikan role {{ $r->namaAsli }} ke nama & hak akses bawaan sistem?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-outline-warning btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Bawaan</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel p-0 overflow-hidden">
            <div class="rl-judul">Role Kustom</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Role</th><th>Jenis Dasar</th><th>Hak Akses</th><th class="text-center">Akun</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($kustom as $r)
                            <tr>
                                <td class="rl-nama">
                                    <span class="fw-semibold d-block">{{ $r->nama }}</span>
                                    @if ($r->keterangan)<small class="text-muted">{{ $r->keterangan }}</small>@endif
                                </td>
                                <td><span class="badge text-bg-light border">{{ \App\Models\User::roleLabelFor($r->jenis_dasar) }}</span></td>
                                <td>@include('admin.role._chip', ['daftar' => $chip($r->hakAksesEfektif()), 'kunci' => []])</td>
                                <td class="text-center">{{ $r->jumlah }}</td>
                                <td class="text-end rl-aksi">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.role.edit', $r) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                                        <form action="{{ route('admin.role.destroy', $r) }}" method="POST"
                                            data-confirm="Hapus role {{ $r->nama }}?{{ $r->jumlah ? ' ' . $r->jumlah . ' akun kembali ke role ' . \App\Models\User::roleLabelFor($r->jenis_dasar) . '.' : '' }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada role kustom. Contoh: <em>Staf Keuangan</em> berbasis Staff dengan hak lihat laporan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<style>
    .rl-judul { padding: .75rem 1rem; font-weight: 700; border-bottom: 1px solid var(--admin-border); background: var(--admin-surface-soft); }
    .rl-nama { min-width: 180px; }
    .rl-chip { display: inline-block; font-size: .72rem; font-weight: 600; padding: .12rem .5rem; margin: .1rem .15rem .1rem 0; border-radius: 999px; background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; }
    .rl-chip.kunci { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
    html[data-theme="dark"] .rl-chip:not(.kunci) { background: rgba(99, 102, 241, .15); color: #c7d2fe; border-color: rgba(99, 102, 241, .35); }
    .table tbody td.rl-aksi { white-space: nowrap !important; width: 1%; }
    .rl-aksi form { margin: 0; }
</style>
@endsection
