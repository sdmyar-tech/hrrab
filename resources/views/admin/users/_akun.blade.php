{{--
    Isi form akun (Batch 6): tiga x-form-section — Info Akun · Role & Akses · Keamanan.
    Parameter: $akun (User|null), $wajibPassword (bool), $selectedDivisi, $selectedKepalaUnit; dari controller: $roles, $rolesKustom,
    $pegawaiOpsi, $divisiOptions, $gmBidang.
--}}
@php
    $pegawaiDipilih = (int) old('data_pegawai_id', $akun?->data_pegawai_id);
    $roleTerpilih = old('role', $akun ? ($akun->role_id ? 'kustom:' . $akun->role_id : $akun->role) : null);
@endphp

<x-form-section title="Info Akun" icon="bi-person" id="bagian-info"
    subtitle="Pilih dari Data Pegawai agar nama, email, dan NIP terisi otomatis dan akun langsung tertaut.">
    <div class="col-12">
        <x-alert tone="info" class="mb-0" data-info-gm hidden>
            Akun <strong>GM</strong> tidak perlu ditautkan ke Data Pegawai.
            <x-slot:action><x-button variant="link" size="sm" data-buka-tautan>Tautkan ke pegawai (opsional)</x-button></x-slot:action>
        </x-alert>
    </div>
    <div class="col-12 pilih-pegawai">
        <div class="hr-field mb-0">
            <label class="form-label" for="data_pegawai_id">Data Pegawai <span class="hr-field-opsional">(nama, email & NIP terisi otomatis)</span></label>
            <input type="search" class="form-control form-control-sm mb-2" placeholder="Cari pegawai, contoh: Siti atau SMK" data-cari-pegawai aria-label="Cari pegawai">
            <select name="data_pegawai_id" id="data_pegawai_id" class="form-select @error('data_pegawai_id') is-invalid @enderror">
                <option value="">Pilih pegawai</option>
                {{-- Opsi tersendiri (bukan placeholder): akun sengaja tidak ditautkan, mis. GM dari luar data pegawai. --}}
                <option value="" @selected($akun && ! $akun->data_pegawai_id && ! old('data_pegawai_id'))>Tidak ditautkan ke pegawai</option>
                @if ($akun?->data_pegawai_id)
                    <option value="lepas" @selected(old('data_pegawai_id') === 'lepas')>✕ Lepaskan tautan dari Data Pegawai</option>
                @endif
                @foreach ($pegawaiOpsi as $p)
                    <option value="{{ $p['id'] }}" @selected($pegawaiDipilih === $p['id'])
                        data-nama="{{ $p['nama'] }}" data-email="{{ $p['email'] }}" data-nip="{{ $p['nip'] }}" data-unit="{{ $p['unit'] }}"
                        data-teks="{{ mb_strtolower($p['nama'] . ' ' . $p['nip'] . ' ' . $p['unit']) }}">
                        {{ $p['nama'] }}{{ $p['nip'] ? ' · ' . $p['nip'] : '' }}{{ $p['unit'] ? ' · ' . $p['unit'] : '' }}{{ $p['akun'] && $pegawaiDipilih !== $p['id'] ? ' (sudah punya akun)' : '' }}
                    </option>
                @endforeach
            </select>
            @error('data_pegawai_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text" data-info-pegawai></div>
        </div>
    </div>
    <div class="col-md-6">
        <x-input name="name" label="Nama Lengkap" :value="old('name', $akun?->name)" required maxlength="150"
            placeholder="Nama sesuai identitas, contoh: Siti Aminah, S.Pd" />
    </div>
    <div class="col-md-6">
        <x-input name="email" type="email" label="Email" :value="old('email', $akun?->email)" required maxlength="255"
            placeholder="Email untuk masuk, contoh: siti.aminah@gmail.com" />
    </div>
    <div class="col-md-6">
        <x-input name="nip" label="NIP" optional :value="old('nip', $akun?->nip)" maxlength="50" inputmode="numeric"
            placeholder="Nomor induk pegawai, contoh: 115010920001" />
    </div>
</x-form-section>

<x-form-section title="Role & Akses" icon="bi-shield-check" id="bagian-role"
    subtitle="Role menentukan menu dan hak akses. Pengaturan bidang & bagian muncul sesuai role yang dipilih.">
    <div class="col-md-6">
        <div class="hr-field mb-0">
            <label class="form-label required" for="f-role">Role</label>
            {{-- Nilai: kode role bawaan, atau "kustom:<id>" untuk role kustom (jenis dasarnya ada di data-dasar). --}}
            <select name="role" id="f-role" class="form-select @error('role') is-invalid @enderror" required>
                @if (! $akun)
                    <option value="">Pilih role</option>
                @endif
                <optgroup label="Role bawaan">
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" data-dasar="{{ $role }}" @selected($roleTerpilih == $role)>{{ App\Models\User::roleLabelFor($role) }}</option>
                    @endforeach
                </optgroup>
                @if ($rolesKustom->isNotEmpty())
                    <optgroup label="Role kustom">
                        @foreach ($rolesKustom as $rk)
                            <option value="kustom:{{ $rk->id }}" data-dasar="{{ $rk->jenis_dasar }}" @selected($roleTerpilih === 'kustom:' . $rk->id)>{{ $rk->nama }} (dasar {{ App\Models\User::roleLabelFor($rk->jenis_dasar) }})</option>
                        @endforeach
                    </optgroup>
                @endif
            </select>
            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Hak akses tiap role diatur di <a href="{{ route('admin.role.index') }}">Master Role</a>.</div>
        </div>
    </div>
    <div class="col-12">
        @include('admin.users._divisi', ['selectedDivisi' => $selectedDivisi, 'selectedKepalaUnit' => $selectedKepalaUnit])
    </div>
</x-form-section>

<x-form-section title="Keamanan" icon="bi-key" id="bagian-keamanan"
    :subtitle="$wajibPassword ? 'Kata sandi awal untuk masuk. Minta pemilik akun menggantinya setelah masuk.' : 'Kosongkan kata sandi bila tidak diubah. Isi untuk mengatur ulang kata sandi akun ini.'">
    <div class="col-md-6">
        <x-input name="password" id="f-password" type="password" label="Kata Sandi" toggle :required="$wajibPassword" :optional="! $wajibPassword"
            autocomplete="new-password" placeholder="Minimal 6 karakter, contoh: Abdurrab2026" />
        <x-password-meter for="f-password" class="mt-2" :rules="[['panjang', 'Minimal 6 karakter', 'len:6'], ['sama', 'Sama dengan ulangan', 'same:f-password-ulang']]" />
    </div>
    <div class="col-md-6">
        <x-input name="password_confirmation" id="f-password-ulang" type="password" label="Ulangi Kata Sandi" toggle autocomplete="new-password"
            placeholder="Ketik ulang kata sandi yang sama" />
    </div>
    @if (! $akun || $akun->id !== auth()->id())
        <div class="col-12">
            <input type="hidden" name="wajib_ganti_password" value="0">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="wajib_ganti_password" value="1" id="wajib_ganti_password"
                    @checked(old('wajib_ganti_password', $akun?->wajib_ganti_password ?? false))>
                <label class="form-check-label" for="wajib_ganti_password">
                    Wajib ganti kata sandi saat pertama masuk
                    <span class="d-block form-text mt-0">Pemilik akun tidak bisa membuka halaman apa pun sebelum mengganti kata sandi.</span>
                </label>
            </div>
        </div>
    @endif
</x-form-section>

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
            pilih.querySelectorAll('option[data-teks]').forEach((o) => { o.hidden = kata && !o.dataset.teks.includes(kata); });
            // Satu-satunya hasil langsung dipilih.
            const terlihat = pilih.querySelectorAll('option[data-teks]:not([hidden])');
            if (kata && terlihat.length === 1) { pilih.value = terlihat[0].value; pilih.dispatchEvent(new Event('change', { bubbles: true })); }
            info.textContent = kata ? terlihat.length + ' pegawai cocok' : '';
        });

        // GM tidak wajib tertaut: blok Data Pegawai disembunyikan kecuali sudah tertaut / dibuka manual.
        const role = form.querySelector('select[name=role]');
        const infoGm = form.querySelector('[data-info-gm]');
        let dibukaManual = !!pilih.value;
        function aturGm() {
            const gm = role?.selectedOptions[0]?.dataset.dasar === 'gm';
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
            const isi = (nama, nilai, timpa) => {
                const el = form.querySelector('[name="' + nama + '"]');
                if (el && nilai && (timpa || !el.value)) { el.value = nilai; el.dispatchEvent(new Event('input', { bubbles: true })); }
            };
            isi('name', o.dataset.nama, true);
            isi('nip', o.dataset.nip, true);
            isi('email', o.dataset.email, akunBaru); // akun lama: email hanya diisi bila masih kosong
            info.textContent = 'Tertaut ke ' + o.dataset.nama + (o.textContent.includes('sudah punya akun') ? ' — pegawai ini sudah punya akun lain.' : '.');
        });
    });
</script>
