@extends('layouts.admin.app')

@section('title', 'Role')

@section('content')
@php
    use App\Models\Role;
    use App\Models\User;

    $bawaan = $mode === 'bawaan';
    $baru = ! $bawaan && ! $role->exists;
    $kunci = Role::DIKUNCI[$role->jenis_dasar] ?? [];
    $dipilih = old('hak_akses', $role->hak_akses ?? []);
    $aksi = $bawaan ? route('admin.role.bawaan.update', $role->kode) : ($baru ? route('admin.role.store') : route('admin.role.update', $role));
    $dasarTerkunci = ! $bawaan && $jumlahAkun > 0;
    $totalHak = count(Role::HAK_AKSES);
    $judul = $bawaan ? 'Ubah Role Bawaan' : ($baru ? 'Tambah Role' : 'Ubah Role');
@endphp
{{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
@section('galat-di-halaman', '1')
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="$judul" eyebrow="Sistem" :back="route('admin.role.index')" back-label="Master Role"
            :subtitle="$bawaan
                ? User::LABEL_ROLE[$role->kode] . ' · dipakai ' . $jumlahAkun . ' akun. Panel & alur persetujuan role ini tidak berubah.'
                : 'Role kustom mengikuti panel & alur persetujuan jenis dasarnya; hak akses di bawah menentukan menu yang bisa dibuka.'">
            <x-badge :tone="$bawaan ? 'neutral' : 'info'" :icon="$bawaan ? 'bi-lock' : 'bi-stars'">{{ $bawaan ? 'Bawaan' : 'Kustom' }}</x-badge>
        </x-page-header>

        @if ($bawaan)
            <x-alert tone="warning" title="Role bawaan — kode tidak bisa diubah" class="mb-4">
                Nama tampilan, keterangan, dan hak akses boleh disesuaikan. Kode, panel, dan alur persetujuan tetap mengikuti sistem;
                gunakan "Kembalikan ke bawaan" di Master Role untuk membatalkan penyesuaian.
            </x-alert>
        @endif
        @if ($errors->any())
            <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-4">{{ $errors->first() }}</x-alert>
        @endif

        <form @if ($baru) data-fokus-awal @endif action="{{ $aksi }}" method="POST" id="formRole"
            data-confirm="Simpan role ini?{{ $jumlahAkun ? ' Perubahan hak akses langsung berlaku untuk ' . $jumlahAkun . ' akun.' : '' }}">
            @csrf
            @unless ($baru) @method('PUT') @endunless
            <div class="row g-4">
                <div class="col-xl-8">
                    <x-card>
                        <x-form-section title="Info Role" icon="bi-shield-check" id="bagian-info"
                            subtitle="Nama dipakai di pilihan role Kelola Akun dan di seluruh tampilan.">
                            <div class="col-md-6">
                                <x-input name="nama" id="rl-nama" label="Nama Role" required maxlength="100" :value="old('nama', $role->nama)"
                                    placeholder="Nama role yang mudah dikenali, contoh: Staf Keuangan" />
                            </div>
                            @if ($bawaan)
                                <div class="col-md-6">
                                    <div class="hr-field mb-0">
                                        <label class="form-label" for="rl-kode">Kode</label>
                                        {{-- Hanya tampilan (tanpa name): kode role bawaan dikunci sistem. --}}
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock-fill" aria-hidden="true"></i></span>
                                            <input type="text" id="rl-kode" class="form-control" value="{{ $role->kode }}" readonly aria-describedby="rl-kode-bantu">
                                        </div>
                                        <div class="form-text" id="rl-kode-bantu">Kode role bawaan tidak bisa diubah.</div>
                                    </div>
                                </div>
                            @else
                                <div class="col-md-6">
                                    <div class="hr-field mb-0">
                                        <label class="form-label required" for="rl-dasar">Jenis Dasar</label>
                                        <select name="jenis_dasar" id="rl-dasar" class="form-select @error('jenis_dasar') is-invalid @enderror" required @disabled($dasarTerkunci)>
                                            {{-- Jenis dasar yang masih aktif (+ jenis dasar role ini sendiri bila sudah dihapus). --}}
                                            @foreach (array_values(array_unique([...array_intersect(Role::JENIS_DASAR_KUSTOM, Role::bawaanAktif()), ...array_filter([$role->jenis_dasar])])) as $d)
                                                <option value="{{ $d }}" @selected(old('jenis_dasar', $role->jenis_dasar) === $d)>{{ User::roleLabelFor($d) }}</option>
                                            @endforeach
                                        </select>
                                        @error('jenis_dasar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @if ($dasarTerkunci)
                                            <input type="hidden" name="jenis_dasar" value="{{ $role->jenis_dasar }}">
                                            <div class="form-text"><i class="bi bi-lock-fill me-1" aria-hidden="true"></i>Tidak bisa diganti: role sudah dipakai {{ $jumlahAkun }} akun.</div>
                                        @else
                                            <div class="form-text">Menentukan panel (admin/pegawai) & alur persetujuan. Contoh: dasar Staff → cuti tetap disetujui kepala bagiannya.</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="col-12">
                                <x-input name="keterangan" id="rl-ket" label="Keterangan" optional maxlength="255" :value="old('keterangan', $role->keterangan)"
                                    placeholder="Tugas singkat role ini, contoh: mengelola data pegawai bagian keuangan" />
                            </div>
                        </x-form-section>

                        <x-form-section title="Hak Akses" icon="bi-key" id="bagian-hak"
                            subtitle="Centang hak per modul. Centang judul modul untuk memilih semua hak di modul itu.">
                            <div class="col-12">
                                <div class="rl-alat">
                                    <div class="input-group input-group-sm rl-alat-cari">
                                        <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                                        <input type="search" class="form-control" placeholder="Cari hak akses, contoh: pegawai" aria-label="Cari hak akses" data-cari-hak autocomplete="off">
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="rl-semua" data-pilih-semua>
                                        <label class="form-check-label fw-semibold" for="rl-semua">Pilih semua</label>
                                    </div>
                                    @unless ($bawaan)
                                        <x-button variant="secondary" size="sm" icon="bi-magic" data-isi-dasar>Samakan dengan jenis dasar</x-button>
                                    @endunless
                                    <span class="hr-filter-bar-jumlah ms-auto" aria-live="polite"><strong data-jumlah-hak>0</strong> dari {{ $totalHak }} dipilih</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="rl-modul-daftar">
                                    @foreach (Role::GRUP_HAK as $judulGrup => [$ikon, $hakGrup])
                                        <fieldset class="rl-modul" data-grup>
                                            <legend class="rl-modul-kepala">
                                                <span class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox" id="rl-grup-{{ $loop->index }}" data-semua-grup>
                                                    <label class="form-check-label" for="rl-grup-{{ $loop->index }}"><i class="bi {{ $ikon }} me-1" aria-hidden="true"></i>{{ $judulGrup }}</label>
                                                </span>
                                                <span class="rl-modul-jumlah" data-jumlah-grup></span>
                                            </legend>
                                            <div class="rl-hak">
                                                @foreach ($hakGrup as $kode)
                                                    @php [$label, $ket] = Role::HAK_AKSES[$kode]; $dikunci = in_array($kode, $kunci, true); @endphp
                                                    <label class="rl-hak-item {{ $dikunci ? 'terkunci' : '' }}" data-teks="{{ mb_strtolower($label . ' ' . $ket . ' ' . $judulGrup) }}">
                                                        <input type="checkbox" class="form-check-input" name="hak_akses[]" value="{{ $kode }}"
                                                            @checked($dikunci || in_array($kode, $dipilih, true)) @disabled($dikunci)>
                                                        <span class="min-w-0">
                                                            <strong>{{ $label }}</strong>@if ($dikunci) <i class="bi bi-lock-fill rl-kunci" title="Tidak bisa dicabut" aria-label="Tidak bisa dicabut"></i>@endif
                                                            <small>{{ $ket }}</small>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </fieldset>
                                    @endforeach
                                    <p class="rl-hak-kosong form-text mb-0" data-hak-kosong hidden><i class="bi bi-search me-1" aria-hidden="true"></i>Tidak ada hak akses yang cocok dengan pencarian.</p>
                                </div>
                                <p class="form-text mb-0 mt-3">Beberapa hak tetap mengikuti aturan khusus sistem, mis. staf bagian SDM otomatis boleh mengelola pegawai & absensi.</p>
                            </div>
                        </x-form-section>
                    </x-card>
                </div>

                <div class="col-xl-4">
                    <div class="hr-detail-samping d-grid gap-4">
                        <x-card title="Ringkasan Role" icon="bi-card-checklist">
                            <div class="hr-sel-utama mb-3">
                                <span class="hr-stat-ikon hr-tone-{{ $bawaan ? 'neutral' : 'info' }}"><i class="bi {{ Role::IKON_DASAR[$role->jenis_dasar ?? ''] ?? 'bi-shield-check' }}" aria-hidden="true"></i></span>
                                <div class="min-w-0">
                                    <span class="hr-sel-judul" data-ringkas-nama>{{ old('nama', $role->nama) ?: 'Nama belum diisi' }}</span>
                                    <span class="hr-sel-sub">{{ $bawaan ? 'Role bawaan · ' . $role->kode : 'Role kustom' }}</span>
                                </div>
                            </div>
                            <dl class="hr-konteks hr-konteks-ringkas">
                                <dt>Jenis dasar</dt>
                                <dd data-ringkas-dasar>{{ $role->jenis_dasar ? User::roleLabelFor($role->jenis_dasar) : 'Belum dipilih' }}</dd>
                                <dt>Dipakai</dt>
                                <dd>{{ $jumlahAkun }} akun</dd>
                                <dt>Hak dipilih</dt>
                                <dd><span data-ringkas-hak>0</span> dari {{ $totalHak }}</dd>
                            </dl>
                            <div class="progress mt-3" style="height: 6px;" role="progressbar" aria-label="Persentase hak akses dipilih" data-ringkas-bar-wadah>
                                <div class="progress-bar" data-ringkas-bar style="width: 0%"></div>
                            </div>
                            <ul class="rl-ringkas-modul mt-3" data-ringkas-modul></ul>
                        </x-card>
                    </div>
                </div>
            </div>

            <div class="hr-bilah-aksi mt-4">
                <x-button variant="secondary" :href="route('admin.role.index')">Batal</x-button>
                <x-button type="submit" icon="bi-check-lg">Simpan Role</x-button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formRole');
        const kotak = () => Array.from(form.querySelectorAll('[name="hak_akses[]"]'));
        const bisaDiubah = () => kotak().filter((c) => !c.disabled);
        const semua = form.querySelector('[data-pilih-semua]');
        const grup = Array.from(form.querySelectorAll('[data-grup]'));
        const total = kotak().length;

        // Centang induk: penuh / sebagian (indeterminate) / kosong — dari kotak yang bisa diubah.
        const aturInduk = (induk, isi) => {
            const n = isi.filter((c) => c.checked).length;
            induk.checked = isi.length > 0 && n === isi.length;
            induk.indeterminate = n > 0 && n < isi.length;
            induk.disabled = isi.length === 0;
        };

        function perbarui() {
            const dipilih = kotak().filter((c) => c.checked).length;
            form.querySelector('[data-jumlah-hak]').textContent = dipilih;
            form.querySelector('[data-ringkas-hak]').textContent = dipilih;
            form.querySelector('[data-ringkas-bar]').style.width = Math.round(dipilih / Math.max(total, 1) * 100) + '%';
            aturInduk(semua, bisaDiubah());
            const ringkas = form.querySelector('[data-ringkas-modul]');
            ringkas.replaceChildren();
            grup.forEach((g) => {
                const isi = Array.from(g.querySelectorAll('[name="hak_akses[]"]'));
                aturInduk(g.querySelector('[data-semua-grup]'), isi.filter((c) => !c.disabled));
                const n = isi.filter((c) => c.checked).length;
                g.querySelector('[data-jumlah-grup]').textContent = n + '/' + isi.length;
                const li = document.createElement('li');
                li.className = n ? 'ada' : '';
                li.textContent = g.querySelector('.form-check-label').textContent.trim() + ' — ' + n + '/' + isi.length;
                ringkas.append(li);
            });
        }

        grup.forEach((g) => {
            g.querySelector('[data-semua-grup]').addEventListener('change', (e) => {
                g.querySelectorAll('[name="hak_akses[]"]:not(:disabled)').forEach((c) => { c.checked = e.target.checked; });
                perbarui();
            });
        });
        semua.addEventListener('change', () => { bisaDiubah().forEach((c) => { c.checked = semua.checked; }); perbarui(); });

        // Cari hak akses: sembunyikan yang tidak cocok; modul tanpa hasil ikut disembunyikan.
        const cari = form.querySelector('[data-cari-hak]');
        cari.addEventListener('input', () => {
            const kata = cari.value.trim().toLowerCase();
            let ada = 0;
            grup.forEach((g) => {
                let adaGrup = 0;
                g.querySelectorAll('.rl-hak-item').forEach((item) => {
                    const cocok = !kata || item.dataset.teks.includes(kata);
                    item.hidden = !cocok;
                    adaGrup += cocok ? 1 : 0;
                });
                g.hidden = adaGrup === 0;
                ada += adaGrup;
            });
            form.querySelector('[data-hak-kosong]').hidden = ada > 0;
        });

        // "Samakan dengan jenis dasar": centang hak akses persis seperti role bawaan yang dipilih.
        const tombolDasar = form.querySelector('[data-isi-dasar]');
        const dasar = document.getElementById('rl-dasar');
        if (tombolDasar && dasar) {
            const hakDasar = {{ \Illuminate\Support\Js::from(collect(Role::JENIS_DASAR_KUSTOM)->mapWithKeys(fn ($d) => [$d => Role::hakAksesBawaan($d)])) }};
            tombolDasar.addEventListener('click', () => {
                const hak = hakDasar[dasar.value] || [];
                bisaDiubah().forEach((c) => { c.checked = hak.includes(c.value); });
                perbarui();
            });
            dasar.addEventListener('change', () => { form.querySelector('[data-ringkas-dasar]').textContent = dasar.selectedOptions[0]?.textContent.trim() || '-'; });
        }
        const nama = document.getElementById('rl-nama');
        nama.addEventListener('input', () => { form.querySelector('[data-ringkas-nama]').textContent = nama.value.trim() || 'Nama belum diisi'; });

        kotak().forEach((c) => c.addEventListener('change', perbarui));
        perbarui();
    });
</script>
@endsection
