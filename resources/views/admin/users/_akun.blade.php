@php $pegawaiDipilih = (int) old('data_pegawai_id', $akun?->data_pegawai_id); @endphp
<div class="alert alert-info small py-2 mb-3" data-info-gm hidden>
    <i class="bi bi-info-circle me-1"></i>Akun <strong>GM</strong> tidak perlu ditautkan ke Data Pegawai.
    <button type="button" class="btn btn-link btn-sm p-0 align-baseline" data-buka-tautan>Tautkan ke pegawai (opsional)</button>
</div>
<div class="mb-3 pilih-pegawai">
    <label class="form-label" for="data_pegawai_id">Data Pegawai <span class="text-muted fw-normal">(nama, email & NIP terisi otomatis)</span></label>
    <input type="search" class="form-control form-control-sm mb-1" placeholder="Cari nama, NIP, atau unit…" data-cari-pegawai aria-label="Cari pegawai">
    <select name="data_pegawai_id" id="data_pegawai_id" class="form-select @error('data_pegawai_id') is-invalid @enderror">
        <option value="">-- Pilih pegawai --</option>
        {{-- Opsi tersendiri (bukan placeholder): akun sengaja tidak ditautkan, mis. GM dari luar data pegawai. --}}
        <option value="" @selected($akun && ! $akun->data_pegawai_id && ! old('data_pegawai_id'))>Tidak ditautkan ke pegawai</option>
        @if ($akun?->data_pegawai_id)
            <option value="lepas" @selected(old('data_pegawai_id') === 'lepas')>✕ Lepaskan tautan dari Data Pegawai</option>
        @endif
        @foreach ($pegawaiOpsi as $p)
            <option value="{{ $p['id'] }}" @selected($pegawaiDipilih === $p['id'])
                data-nama="{{ $p['nama'] }}" data-email="{{ $p['email'] }}" data-nip="{{ $p['nip'] }}"
                data-teks="{{ mb_strtolower($p['nama'] . ' ' . $p['nip'] . ' ' . $p['unit']) }}">
                {{ $p['nama'] }}{{ $p['nip'] ? ' · ' . $p['nip'] : '' }}{{ $p['unit'] ? ' · ' . $p['unit'] : '' }}{{ $p['akun'] && $pegawaiDipilih !== $p['id'] ? ' (sudah punya akun)' : '' }}
            </option>
        @endforeach
    </select>
    @error('data_pegawai_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text" data-info-pegawai></div>
</div>

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
    @if (! $akun || $akun->id !== auth()->id())
        <div class="col-12">
            <input type="hidden" name="wajib_ganti_password" value="0">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="wajib_ganti_password" value="1" id="wajib_ganti_password"
                    @checked(old('wajib_ganti_password', $akun?->wajib_ganti_password ?? false))>
                <label class="form-check-label" for="wajib_ganti_password">
                    Wajib ganti password saat membuka sistem
                    <span class="d-block small text-muted">Pemilik akun tidak bisa memakai halaman apa pun sebelum mengganti password.</span>
                </label>
            </div>
        </div>
    @endif
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

<script>
    // Dropdown Data Pegawai: cari + isi otomatis nama, email, NIP.
    document.addEventListener('DOMContentLoaded', function () {
        const wadah = document.querySelector('.pilih-pegawai');
        if (!wadah) return;
        const pilih = wadah.querySelector('select');
        const cari = wadah.querySelector('[data-cari-pegawai]');
        const info = wadah.querySelector('[data-info-pegawai]');
        const form = pilih.closest('form');
        const akunBaru = @json(! $akun);

        cari.addEventListener('input', function () {
            const kata = cari.value.trim().toLowerCase();
            let pertama = null;
            pilih.querySelectorAll('option[data-teks]').forEach((o) => {
                const cocok = !kata || o.dataset.teks.includes(kata);
                o.hidden = !cocok;
                if (cocok && !pertama) pertama = o;
            });
            // Satu-satunya hasil langsung dipilih.
            const terlihat = pilih.querySelectorAll('option[data-teks]:not([hidden])');
            if (kata && terlihat.length === 1) { pilih.value = terlihat[0].value; pilih.dispatchEvent(new Event('change')); }
            info.textContent = kata ? terlihat.length + ' pegawai cocok' : '';
        });

        // GM tidak wajib tertaut: blok Data Pegawai disembunyikan kecuali sudah tertaut / dibuka manual.
        const role = form.querySelector('select[name=role]');
        const infoGm = form.querySelector('[data-info-gm]');
        let dibukaManual = !!pilih.value;
        function aturGm() {
            const gm = role && role.value === 'gm';
            const tampil = !gm || dibukaManual;
            wadah.hidden = !tampil;
            pilih.disabled = !tampil;
            cari.disabled = !tampil;
            infoGm.hidden = !gm || dibukaManual;
        }
        infoGm.querySelector('[data-buka-tautan]').addEventListener('click', () => { dibukaManual = true; aturGm(); cari.focus(); });
        role?.addEventListener('change', aturGm);
        aturGm();

        pilih.addEventListener('change', function () {
            const o = pilih.selectedOptions[0];
            if (!o || !o.value) { info.textContent = 'Akun tidak ditautkan ke Data Pegawai.'; return; }
            if (o.value === 'lepas') { info.textContent = 'Tautan ke Data Pegawai akan dilepas saat disimpan (NIP ikut dikosongkan).'; return; }
            const isi = (nama, nilai, timpa) => { const el = form.querySelector('[name="' + nama + '"]'); if (el && nilai && (timpa || !el.value)) el.value = nilai; };
            isi('name', o.dataset.nama, true);
            isi('nip', o.dataset.nip, true);
            isi('email', o.dataset.email, akunBaru); // akun lama: email hanya diisi bila masih kosong
            info.textContent = 'Tertaut ke ' + o.dataset.nama + (o.textContent.includes('sudah punya akun') ? ' — pegawai ini sudah punya akun lain.' : '.');
        });
    });
</script>
