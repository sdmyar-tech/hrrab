{{-- Kartu ringkasan akun di kolom kanan form (Batch 6): ikut berubah saat nama, email, role, atau tautan pegawai diisi. Parameter: $akun (User|null). --}}
<x-card title="Ringkasan Akun" icon="bi-person-vcard" data-ringkasan-akun>
    <div class="hr-sel-utama mb-3">
        <x-avatar :name="old('name', $akun?->name) ?: '?'" size="lg" data-ringkas-avatar />
        <div class="min-w-0">
            <span class="hr-sel-judul" data-ringkas="name">{{ old('name', $akun?->name) ?: 'Nama belum diisi' }}</span>
            <span class="hr-sel-sub" data-ringkas="email">{{ old('email', $akun?->email) ?: 'Email belum diisi' }}</span>
        </div>
    </div>
    <dl class="hr-konteks hr-konteks-ringkas">
        <dt>Role</dt>
        <dd data-ringkas="role">{{ $akun?->roleLabel() ?? 'Belum dipilih' }}</dd>
        <dt>Data pegawai</dt>
        <dd data-ringkas="pegawai">{{ $akun?->dataPegawai?->nama_lengkap ?? ($akun?->dataPelamar ? 'Pelamar: ' . $akun->dataPelamar->nama_lengkap : 'Tidak ditautkan') }}</dd>
        @if ($akun)
            <dt>Dibuat</dt>
            <dd>{{ optional($akun->created_at)->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
            <dt>Kata sandi diganti</dt>
            <dd>{{ $akun->password_diganti_pada ? \Illuminate\Support\Carbon::parse($akun->password_diganti_pada)->translatedFormat('d M Y, H:i') : 'Belum pernah' }}</dd>
            <dt>Masuk dengan Google</dt>
            <dd>{{ $akun->google_id ? 'Ya' : 'Tidak' }}</dd>
        @endif
    </dl>
    @if ($akun?->wajib_ganti_password)
        <x-badge tone="warning" icon="bi-shield-exclamation" class="mt-3">Wajib ganti kata sandi</x-badge>
    @endif
    <x-slot:footer>
        <p class="form-text mb-0">Kolom bertanda <span class="text-danger">*</span> wajib diisi. Perubahan berlaku setelah Simpan.</p>
    </x-slot:footer>
</x-card>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const kartu = document.querySelector('[data-ringkasan-akun]');
        const form = kartu?.closest('form');
        if (!form) return;
        const tulis = (kunci, teks) => { const el = kartu.querySelector('[data-ringkas="' + kunci + '"]'); if (el) el.textContent = teks; };
        const avatar = kartu.querySelector('[data-ringkas-avatar] .hr-avatar-inisial');
        const inisial = (nama) => nama.replace(/,.*$/, '').replace(/[^\p{L}\s]/gu, '').trim().split(/\s+/).slice(0, 2).map((k) => k.charAt(0)).join('').toUpperCase() || '?';
        const perbarui = function () {
            const nama = form.querySelector('[name=name]')?.value.trim() || '';
            tulis('name', nama || 'Nama belum diisi');
            tulis('email', form.querySelector('[name=email]')?.value.trim() || 'Email belum diisi');
            if (avatar) avatar.textContent = inisial(nama);
            const role = form.querySelector('select[name=role]');
            tulis('role', role?.value ? role.selectedOptions[0].textContent.trim() : 'Belum dipilih');
            const pg = form.querySelector('select[name=data_pegawai_id]');
            const o = pg && !pg.disabled ? pg.selectedOptions[0] : null;
            tulis('pegawai', o && o.dataset.nama ? o.dataset.nama + (o.dataset.unit ? ' · ' + o.dataset.unit : '') : (o && o.value === 'lepas' ? 'Tautan akan dilepas' : 'Tidak ditautkan'));
        };
        form.addEventListener('input', perbarui);
        form.addEventListener('change', perbarui);
    });
</script>
