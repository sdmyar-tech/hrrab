@extends('layouts.admin.app')

@section('content')
@php
    $bawaan = $mode === 'bawaan';
    $baru = ! $bawaan && ! $role->exists;
    $kunci = \App\Models\Role::DIKUNCI[$role->jenis_dasar] ?? [];
    $dipilih = old('hak_akses', $role->hak_akses ?? []);
    $aksi = $bawaan ? route('admin.role.bawaan.update', $role->kode) : ($baru ? route('admin.role.store') : route('admin.role.update', $role));
    $dasarTerkunci = ! $bawaan && $jumlahAkun > 0;
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-shield-check"></i></span>
                <div>
                    <p class="eyebrow mb-1">Master Role</p>
                    <h1 class="h3 mb-1">{{ $bawaan ? 'Ubah Role Bawaan' : ($baru ? 'Tambah Role' : 'Ubah Role') }}</h1>
                    <p class="text-muted mb-0">
                        @if ($bawaan)
                            {{ \App\Models\User::LABEL_ROLE[$role->kode] }} · dipakai {{ $jumlahAkun }} akun. Panel & alur persetujuan role ini tidak berubah.
                        @else
                            Role kustom mengikuti panel & alur persetujuan jenis dasarnya; hak akses di bawah menentukan menu yang bisa dibuka.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger small">{{ $errors->first() }}</div>
        @endif

        <form action="{{ $aksi }}" method="POST" data-confirm="Simpan role ini?{{ $jumlahAkun ? ' Perubahan hak akses langsung berlaku untuk ' . $jumlahAkun . ' akun.' : '' }}">
            @csrf
            @unless ($baru) @method('PUT') @endunless
            <div class="row g-3">
                <div class="col-12 col-lg-5">
                    {{-- Identitas role tetap terlihat saat menggulir daftar hak akses (layar lebar). --}}
                    <section class="panel p-4 rl-identitas">
                        <div class="mb-3">
                            <label class="form-label" for="rl-nama">Nama role</label>
                            <input type="text" name="nama" id="rl-nama" class="form-control @error('nama') is-invalid @enderror" maxlength="100" required
                                value="{{ old('nama', $role->nama) }}" placeholder="mis. Staf Keuangan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="rl-ket">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" name="keterangan" id="rl-ket" class="form-control" maxlength="255" value="{{ old('keterangan', $role->keterangan) }}">
                        </div>
                        @unless ($bawaan)
                            <div>
                                <label class="form-label" for="rl-dasar">Jenis dasar</label>
                                <select name="jenis_dasar" id="rl-dasar" class="form-select @error('jenis_dasar') is-invalid @enderror" required @disabled($dasarTerkunci)>
                                    @foreach (\App\Models\Role::JENIS_DASAR_KUSTOM as $d)
                                        <option value="{{ $d }}" @selected(old('jenis_dasar', $role->jenis_dasar) === $d)>{{ \App\Models\User::roleLabelFor($d) }}</option>
                                    @endforeach
                                </select>
                                @if ($dasarTerkunci)
                                    <input type="hidden" name="jenis_dasar" value="{{ $role->jenis_dasar }}">
                                    <div class="form-text"><i class="bi bi-lock-fill me-1"></i>Tidak bisa diganti: role sudah dipakai {{ $jumlahAkun }} akun.</div>
                                @else
                                    <div class="form-text">Menentukan panel (admin/pegawai) & alur persetujuan akun. Contoh: dasar Staff → cuti tetap disetujui kepala unitnya.</div>
                                @endif
                            </div>
                        @endunless
                    </section>
                </div>

                <div class="col-12 col-lg-7">
                    <section class="panel p-4 h-100">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h2 class="h6 fw-bold mb-0">Hak akses</h2>
                                <small class="text-muted"><strong data-jumlah-hak>0</strong> dari {{ count(\App\Models\Role::HAK_AKSES) }} dipilih</small>
                            </div>
                            <div class="d-flex gap-2">
                                @unless ($bawaan)
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-isi-dasar><i class="bi bi-magic me-1"></i>Samakan dengan jenis dasar</button>
                                @endunless
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-kosongkan-hak>Kosongkan</button>
                            </div>
                        </div>
                        @foreach (\App\Models\Role::GRUP_HAK as $judul => [$ikon, $hakGrup])
                            <fieldset class="rl-grup" data-grup>
                                <legend class="rl-grup-judul">
                                    <span><i class="bi {{ $ikon }} me-1"></i>{{ $judul }}</span>
                                    <button type="button" class="btn btn-link btn-sm p-0" data-semua-grup>Pilih semua</button>
                                </legend>
                                <div class="rl-hak">
                                    @foreach ($hakGrup as $kode)
                                        @php [$label, $ket] = \App\Models\Role::HAK_AKSES[$kode]; $dikunci = in_array($kode, $kunci, true); @endphp
                                        <label class="rl-hak-item {{ $dikunci ? 'terkunci' : '' }}">
                                            <input type="checkbox" class="form-check-input" name="hak_akses[]" value="{{ $kode }}"
                                                @checked($dikunci || in_array($kode, $dipilih, true)) @disabled($dikunci)>
                                            <span>
                                                <strong>{{ $label }}</strong>@if ($dikunci) <i class="bi bi-lock-fill text-warning" title="Tidak bisa dicabut"></i>@endif
                                                <small class="d-block text-muted">{{ $ket }}</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                        <p class="form-text mb-0 mt-2">Beberapa hak tetap mengikuti aturan khusus sistem, mis. staf unit SDM otomatis boleh mengelola pegawai & absensi.</p>
                    </section>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                <a href="{{ route('admin.role.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</main>

<style>
    .rl-hak { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    @media (max-width: 575.98px) { .rl-hak { grid-template-columns: 1fr; } }
    .rl-hak-item { display: flex; gap: .6rem; align-items: flex-start; padding: .55rem .7rem; border: 1px solid var(--admin-border); border-radius: 10px; cursor: pointer; margin: 0; }
    .rl-hak-item:has(input:checked) { border-color: var(--hr-primary); background: rgba(139, 18, 21, .04); }
    .rl-hak-item.terkunci { cursor: not-allowed; }
    .rl-hak-item .form-check-input { margin-top: .2rem; flex-shrink: 0; }
    @media (min-width: 992px) { .rl-identitas { position: sticky; top: 1rem; } }
    .rl-grup { border: 0; padding: 0; margin: 0 0 1rem; }
    .rl-grup:last-of-type { margin-bottom: .25rem; }
    .rl-grup-judul { display: flex; justify-content: space-between; align-items: center; width: 100%; float: none; font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--admin-muted); margin-bottom: .45rem; }
    .rl-grup-judul .btn-link { font-size: .74rem; text-transform: none; letter-spacing: 0; font-weight: 600; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const kotak = () => Array.from(document.querySelectorAll('[name="hak_akses[]"]'));
        const bisaDiubah = () => kotak().filter((c) => !c.disabled);
        const jumlah = document.querySelector('[data-jumlah-hak]');

        // Penghitung + label tombol "Pilih semua / Kosongkan" per kelompok.
        function perbarui() {
            jumlah.textContent = kotak().filter((c) => c.checked).length;
            document.querySelectorAll('[data-grup]').forEach((g) => {
                const isi = Array.from(g.querySelectorAll('input:not(:disabled)'));
                const tombol = g.querySelector('[data-semua-grup]');
                tombol.hidden = !isi.length;
                tombol.textContent = isi.length && isi.every((c) => c.checked) ? 'Kosongkan' : 'Pilih semua';
            });
        }
        document.querySelectorAll('[data-grup]').forEach((g) => {
            g.querySelector('[data-semua-grup]').addEventListener('click', () => {
                const isi = Array.from(g.querySelectorAll('input:not(:disabled)'));
                const centang = !isi.every((c) => c.checked);
                isi.forEach((c) => { c.checked = centang; });
                perbarui();
            });
        });
        document.querySelector('[data-kosongkan-hak]').addEventListener('click', () => { bisaDiubah().forEach((c) => { c.checked = false; }); perbarui(); });

        // "Samakan dengan jenis dasar": centang hak akses persis seperti role bawaan yang dipilih.
        const tombolDasar = document.querySelector('[data-isi-dasar]');
        if (tombolDasar) {
            const hakDasar = @json(collect(\App\Models\Role::JENIS_DASAR_KUSTOM)->mapWithKeys(fn ($d) => [$d => \App\Models\Role::hakAksesBawaan($d)]));
            const dasar = document.getElementById('rl-dasar');
            tombolDasar.addEventListener('click', () => {
                const hak = hakDasar[dasar.value] || [];
                bisaDiubah().forEach((c) => { c.checked = hak.includes(c.value); });
                perbarui();
            });
        }

        kotak().forEach((c) => c.addEventListener('change', perbarui));
        perbarui();
    });
</script>
@endsection
