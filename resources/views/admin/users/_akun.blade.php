<div class="mb-3">
    <label class="form-label">Nama Lengkap</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $akun?->name) }}" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $akun?->email) }}" required>
    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <label class="form-label">Password @unless ($wajibPassword)<span class="text-muted fw-normal">(opsional)</span>@endunless</label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" @required($wajibPassword)>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-6">
        <label class="form-label">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
    </div>
    @unless ($wajibPassword)
        <div class="col-12 form-text mt-1">Kosongkan bila password tidak diubah.</div>
    @endunless
</div>

<div class="row g-3">
    <div class="col-sm-6">
        <label class="form-label">Role</label>
        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
            @if (! $akun)
                <option value="">-- Pilih Role --</option>
            @endif
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $akun?->role) == $role)>{{ App\Models\User::roleLabelFor($role) }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-6">
        <label class="form-label">NIP <span class="text-muted fw-normal">(opsional)</span></label>
        <input type="text" name="nip" class="form-control @error('nip') is-invalid @enderror" value="{{ old('nip', $akun?->nip) }}">
        @error('nip')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
