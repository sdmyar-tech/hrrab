@extends('layouts.admin.app')

@section('title', 'Kelola Akun')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'role', 'bagian', 'unit', 'tanggal_dari', 'tanggal_sampai']);
    $param = request()->except(['role', 'page']);
    $kartu = [
        ['Total akun', $ringkas['semua'], 'bi-people', 'primary', route('admin.users.index', $param), ! request('role')],
        ['Akun internal', $ringkas['internal'], 'bi-person-badge', 'info', null, false],
        ['Akun pelamar', $ringkas['pelamar'], 'bi-person', 'neutral', route('admin.users.index', $param + ['role' => \App\Models\User::ROLE_PELAMAR]), request('role') === \App\Models\User::ROLE_PELAMAR],
        ['Wajib ganti kata sandi', $ringkas['wajibGanti'], 'bi-shield-exclamation', 'warning', null, false],
    ];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Kelola Akun" eyebrow="Sistem" subtitle="Akun untuk masuk ke HRRAB: nama, email, role, dan kata sandi.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.users.export'])
                <x-button :href="route('admin.users.create')" icon="bi-plus-lg">Tambah Akun</x-button>
            </x-slot:actions>
        </x-page-header>

        <section class="row g-3 mb-4" aria-label="Ringkasan akun">
            @foreach ($kartu as [$label, $angka, $ikon, $tone, $href, $aktif])
                <div class="col-6 col-xl-3">
                    <x-stat-card :label="$label" :value="$angka" :icon="$ikon" :tone="$tone" :href="$href" @class(['hr-stat-aktif' => $aktif]) />
                </div>
            @endforeach
        </section>

        <x-card :padding="false" aria-label="Daftar akun">
            <x-filter-bar :action="route('admin.users.index')" id="filterUserForm"
                search="Cari akun, contoh: Siti atau siti@gmail.com" search-label="Cari akun menurut nama, email, atau NIP"
                :count="$users->total()" unit="akun" :filtered="$adaFilter" :reset="route('admin.users.index')">
                <select name="role" class="form-select form-select-sm" aria-label="Saring role" onchange="this.form.submit()">
                    <option value="">Semua role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(request('role') == $role)>{{ App\Models\User::roleLabelFor($role) }}</option>
                    @endforeach
                    @foreach ($rolesKustom as $rk)
                        <option value="kustom:{{ $rk->id }}" @selected(request('role') === 'kustom:' . $rk->id)>{{ $rk->nama }}</option>
                    @endforeach
                </select>
                <select name="bagian" class="form-select form-select-sm" aria-label="Saring bagian" onchange="this.form.submit()">
                    <option value="">Semua bagian</option>
                    @foreach ($bagianOpsi as $b)
                        <option value="{{ $b }}" @selected(request('bagian') === $b)>{{ $b }}</option>
                    @endforeach
                </select>
                @if ($unitOpsi)
                    <select name="unit" class="form-select form-select-sm" aria-label="Saring unit" onchange="this.form.submit()">
                        <option value="">Semua unit</option>
                        @foreach ($unitOpsi as $u)
                            <option value="{{ $u }}" @selected(request('unit') === $u)>{{ $u }}</option>
                        @endforeach
                    </select>
                @endif
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Dibuat'])
                @include('partials.urut', ['urutForm' => 'filterUserForm'])
            </x-filter-bar>

            <x-table caption="Daftar akun" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Akun</th>
                        <th>Role</th>
                        <th>Terhubung Dengan</th>
                        <th>Keamanan</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($users as $item)
                    <tr>
                        <td>
                            <div class="hr-sel-utama">
                                <x-avatar :name="$item->name" size="sm" />
                                <div class="min-w-0">
                                    {{-- Nama membuka halaman ubah (aksi paling sering untuk akun). --}}
                                    <a href="{{ route('admin.users.edit', $item) }}" class="hr-sel-judul">{{ $item->name }}</a>
                                    <span class="hr-sel-sub">{{ $item->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <x-badge :tone="$item->role_id ? 'info' : 'neutral'">{{ $item->roleLabel() }}</x-badge>
                            @if ($item->isGm())
                                <span class="hr-sel-sub mt-1">Pegang: {{ $item->divisi ? implode(', ', $item->divisi) : 'Semua divisi' }}</span>
                            @endif
                            @if (($item->isKepalaBagian() || $item->isGm()) && ($unitsKepala = $item->unitsDipimpin()))
                                <span class="hr-sel-sub">Kepala {{ implode(', ', $unitsKepala) }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($item->dataPegawai)
                                <span class="d-block">Pegawai</span>
                                @if ($item->dataPegawai->nama_lengkap !== $item->name)<span class="hr-sel-sub">{{ $item->dataPegawai->nama_lengkap }}</span>@endif
                                @if ($bagianPegawai = $item->dataPegawai->kepegawaian?->unit_kerja)
                                    <span class="hr-sel-sub">{{ $bagianPegawai }}{{ ($u = $item->dataPegawai->kepegawaian->unit_organisasi) ? ' · ' . $u : '' }}</span>
                                @endif
                            @elseif ($item->dataPelamar)
                                <span class="d-block">Pelamar</span>
                                @if ($item->dataPelamar->nama_lengkap !== $item->name)<span class="hr-sel-sub">{{ $item->dataPelamar->nama_lengkap }}</span>@endif
                            @else
                                <span class="text-muted d-block">Belum terhubung</span>
                            @endif
                            @if ($item->nip)<span class="hr-sel-sub angka-utuh">NIP {{ $item->nip }}</span>@endif
                        </td>
                        <td>
                            @if ($item->wajib_ganti_password)
                                <x-badge tone="warning" icon="bi-shield-exclamation">Wajib ganti kata sandi</x-badge>
                            @else
                                <x-badge tone="success" dot>Normal</x-badge>
                            @endif
                            @if ($item->google_id)<span class="hr-sel-sub mt-1"><i class="bi bi-google me-1" aria-hidden="true"></i>Masuk dengan Google</span>@endif
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                <x-icon-button icon="bi-pencil" label="Ubah akun" :href="route('admin.users.edit', $item)" />
                                <x-icon-button icon="bi-key" label="Atur ulang kata sandi" :href="route('admin.users.edit', $item) . '#bagian-keamanan'" />
                                @if ($item->id !== auth()->id())
                                    <form action="{{ route('admin.users.destroy', $item) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <x-icon-button type="submit" icon="bi-trash" tone="danger" label="Hapus akun" data-confirm="true" data-icon="warning"
                                            data-title="Hapus akun?" data-text="Akun {{ $item->name }} ({{ $item->email }}) akan dihapus dan tidak bisa dipakai masuk lagi. Tindakan ini tidak bisa dibatalkan."
                                            data-confirm-text="Ya, Hapus" data-cancel-text="Batal" />
                                    </form>
                                @else
                                    {{-- Akun sendiri tidak bisa dihapus: pengganti selebar tombol agar ikon tetap sejajar. --}}
                                    <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="5" :icon="$adaFilter ? 'bi-search' : 'bi-person-lock'"
                        :title="$adaFilter ? 'Tidak ada akun yang cocok' : 'Belum ada akun'">
                        {{ $adaFilter ? 'Coba kata kunci lain atau reset filter.' : 'Buat akun agar pegawai bisa masuk ke HRRAB.' }}
                        <x-slot:action>
                            @if ($adaFilter)
                                <x-button :href="route('admin.users.index')" variant="secondary" icon="bi-x-circle">Reset filter</x-button>
                            @else
                                <x-button :href="route('admin.users.create')" icon="bi-plus-lg">Tambah Akun</x-button>
                            @endif
                        </x-slot:action>
                    </x-table-empty>
                @endforelse
            </x-table>

            @if ($users->hasPages())
                <x-slot:footer><x-pagination :paginator="$users" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>
@endsection
