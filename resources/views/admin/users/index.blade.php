@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-people"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem</p>
                    <h1 class="h3 mb-1">Data User</h1>
                    <p class="text-muted mb-0">Kelola akun login (nama, email, role, password).</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.users.export'])
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Tambah User
                </a>
            </div>
        </div>

        <section class="panel">
            <form action="{{ route('admin.users.index') }}" method="GET" id="filterUserForm" class="panel-filter px-3 pt-3 pb-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <input type="search" name="q" class="form-control form-control-sm"
                            placeholder="Cari nama, email, atau NIP..." value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="role" class="form-select form-select-sm" onchange="document.getElementById('filterUserForm').submit()">
                            <option value="">Semua Role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                    {{ App\Models\User::roleLabelFor($role) }}
                                </option>
                            @endforeach
                            @foreach ($rolesKustom as $rk)
                                <option value="kustom:{{ $rk->id }}" @selected(request('role') === 'kustom:' . $rk->id)>{{ $rk->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-outline-secondary btn-sm" aria-label="Cari" title="Cari"><i class="bi bi-search" aria-hidden="true"></i></button>
                    </div>
                    <div class="col-md-auto">@include('partials.urut', ['urutForm' => 'filterUserForm'])</div>
                    @if (request()->hasAny(['q', 'role']))
                        <div class="col-md-1">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset filter">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>NIP</th>
                            <th>Terhubung Dengan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item->name }}</td>
                                <td>
                                    {{ $item->email }}
                                    @if ($item->wajib_ganti_password)
                                        <span class="badge text-bg-warning d-inline-block mt-1"><i class="bi bi-shield-exclamation me-1"></i>Wajib ganti password</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge text-bg-light border">{{ $item->roleLabel() }}</span>
                                    @if ($item->isGm())
                                        <small class="text-muted d-block">Pegang: {{ $item->divisi ? implode(', ', $item->divisi) : 'Semua divisi' }}</small>
                                    @endif
                                    @if ($item->isKepalaBagian() || $item->isGm())
                                        @if ($unitsKepala = $item->unitsDipimpin())<small class="text-muted d-block">Kepala {{ implode(', ', $unitsKepala) }}</small>@endif
                                    @endif
                                </td>
                                <td class="angka-utuh">{{ $item->nip ?? '-' }}</td>
                                <td>
                                    @if ($item->dataPegawai)
                                        <small class="text-muted">Pegawai: {{ $item->dataPegawai->nama_lengkap }}</small>
                                    @elseif ($item->dataPelamar)
                                        <small class="text-muted">Pelamar: {{ $item->dataPelamar->nama_lengkap }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.edit', $item) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    @if ($item->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $item) }}" method="POST" class="d-inline"
                                            data-confirm="Hapus akun {{ $item->name }}? Tindakan ini tidak bisa dibatalkan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" aria-label="Hapus akun {{ $item->name }}" title="Hapus akun">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Belum ada data user.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="panel-footer px-3 py-2 border-top">
                    {{ $users->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>
    </div>
</main>
@endsection
