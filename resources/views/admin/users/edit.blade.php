@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-gear"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem</p>
                    <h1 class="h3 mb-1">Edit User</h1>
                    <p class="text-muted mb-0">{{ $user->email }}</p>
                </div>
            </div>
        </div>

        <section class="panel p-4" style="max-width:640px;">
            <form action="{{ route('admin.users.update', $user) }}" method="POST" data-confirm="Simpan perubahan akun user ini?">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Password <span class="text-muted">(kosongkan jika tidak diubah)</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" {{ old('role', $user->role) == $role ? 'selected' : '' }}>
                                    {{ App\Models\User::roleLabelFor($role) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">NIP <span class="text-muted">(opsional)</span></label>
                        <input type="text" name="nip" class="form-control @error('nip') is-invalid @enderror"
                            value="{{ old('nip', $user->nip) }}">
                        @error('nip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @include('admin.users._divisi', ['selectedDivisi' => old('divisi', $user->divisi ?? []), 'selectedKepalaUnit' => old('kepala_unit', $user->unitsDipimpin())])

                @if ($user->dataPegawai || $user->dataPelamar)
                    <div class="alert alert-secondary small">
                        Terhubung dengan data
                        @if ($user->dataPegawai)
                            Pegawai: <strong>{{ $user->dataPegawai->nama_lengkap }}</strong>
                        @else
                            Pelamar: <strong>{{ $user->dataPelamar->nama_lengkap }}</strong>
                        @endif
                        &mdash; tautan ini dikelola otomatis lewat proses rekrutmen, tidak diubah lewat form ini.
                    </div>
                @endif

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Kembali</a>
            </form>
        </section>
    </div>
</main>
@endsection
