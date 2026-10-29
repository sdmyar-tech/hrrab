@extends('layouts.admin.app')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'role']);
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-lock"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem</p>
                    <h1 class="h3 mb-1">Kelola Akun</h1>
                    <p class="text-muted mb-0">Akun untuk masuk ke HRRAB: nama, email, role, dan kata sandi.</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.users.export'])
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Akun
                </a>
            </div>
        </div>

        <section class="panel" aria-label="Daftar akun">
            <form action="{{ route('admin.users.index') }}" method="GET" id="filterUserForm" class="panel-filter px-3 pt-3 pb-2" role="search">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6 col-xl-4">
                        @include('partials.daftar-cari', [
                            'cariTeks' => 'Cari akun, contoh: Siti atau siti@gmail.com',
                            'cariLabel' => 'Cari akun menurut nama, email, atau NIP',
                        ])
                    </div>
                    <div class="col-12 col-md-3">
                        <select name="role" class="form-select form-select-sm" aria-label="Role" onchange="document.getElementById('filterUserForm').submit()">
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
                    <div class="col-12 col-md-auto">@include('partials.urut', ['urutForm' => 'filterUserForm'])</div>
                </div>
                @include('partials.daftar-ringkas', [
                    'ringkasJumlah' => $users->total(), 'ringkasSatuan' => 'akun',
                    'ringkasFilter' => $adaFilter, 'ringkasReset' => route('admin.users.index'),
                ])
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Akun</th>
                            <th>Role</th>
                            <th>Terhubung dengan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $item)
                            <tr>
                                <td>
                                    {{-- Nama membuka halaman ubah (aksi paling sering untuk akun). --}}
                                    <a href="{{ route('admin.users.edit', $item) }}" class="nama-tautan d-block">{{ $item->name }}</a>
                                    <small class="text-muted">{{ $item->email }}</small>
                                    @if ($item->wajib_ganti_password)
                                        <span class="badge text-bg-warning d-table mt-1"><i class="bi bi-shield-exclamation me-1"></i>Wajib ganti kata sandi</span>
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
                                <td>
                                    @if ($item->dataPegawai)
                                        <span class="d-block">Pegawai</span>
                                        @if ($item->dataPegawai->nama_lengkap !== $item->name)
                                            <small class="text-muted d-block">{{ $item->dataPegawai->nama_lengkap }}</small>
                                        @endif
                                    @elseif ($item->dataPelamar)
                                        <span class="d-block">Pelamar</span>
                                        @if ($item->dataPelamar->nama_lengkap !== $item->name)
                                            <small class="text-muted d-block">{{ $item->dataPelamar->nama_lengkap }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted d-block">Belum terhubung</span>
                                    @endif
                                    @if ($item->nip)
                                        <small class="text-muted angka-utuh">NIP {{ $item->nip }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <a href="{{ route('admin.users.edit', $item) }}" class="btn btn-light btn-sm">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>Ubah
                                        </a>
                                        @if ($item->id !== auth()->id())
                                            <div class="dropdown">
                                                @include('partials.tombol-aksi-lain', ['aksiUntuk' => $item->name])
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <form action="{{ route('admin.users.destroy', $item) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger"
                                                                data-confirm="true" data-icon="warning"
                                                                data-title="Hapus akun?"
                                                                data-text="Akun {{ $item->name }} akan dihapus dan tidak bisa dipakai masuk lagi. Tindakan ini tidak bisa dibatalkan."
                                                                data-confirm-text="Ya, Hapus" data-confirm-color="#dc3545" data-cancel-text="Batal">
                                                                <i class="bi bi-trash me-2"></i>Hapus Akun
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        @else
                                            {{-- Akun sendiri tidak bisa dihapus: pengganti kosong agar tombol Ubah tetap sejajar. --}}
                                            <span class="btn-ikon" aria-hidden="true"></span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @include('partials.kosong-tabel', [
                                'kosongKolom' => 4,
                                'kosongTeks' => $adaFilter ? 'Tidak ada akun yang cocok dengan pencarian/filter ini.' : 'Belum ada akun.',
                                'kosongIkon' => $adaFilter ? 'bi-search' : 'bi-person-lock',
                                'kosongReset' => $adaFilter ? route('admin.users.index') : null,
                            ])
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
