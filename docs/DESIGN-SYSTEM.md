# Design System HRRAB

Panduan singkat untuk menambah atau mengubah halaman agar tampilannya tetap seragam.
Stack: **Laravel 12 + Blade + Bootstrap 5 (aset lokal) + Bootstrap Icons + SweetAlert2**. Tidak memakai Tailwind/Vite
(`package.json`/`vite.config.js` hanya sisa kerangka Laravel dan tidak dimuat oleh layout mana pun).

## 1. Urutan CSS & sumber token

| Urutan | File | Isi |
|---|---|---|
| 1 | `public/assets-admin/css/bootstrap.min.css` | Bootstrap 5 |
| 2 | `public/assets-admin/css/style.css` | Kerangka panel (sidebar, navbar, kartu) |
| 3 | `public/assets-admin/css/hrrab.css` | Warna merek di variabel Bootstrap, tombol |
| 4 | `public/assets-admin/css/ui.css` | **Lapisan desain global + satu-satunya sumber token** (dimuat paling akhir) |

Token didefinisikan di `:root` pada `ui.css` (mode gelap: `html[data-theme="dark"]`). Pakai `var(--…)` dan jangan menulis hex baru di Blade.

| Kelompok | Token |
|---|---|
| Merek | `--hr-primary` `#880f12`, `--hr-primary-dark`, `--hr-primary-soft`, `--hr-primary-soft-text` |
| Netral | `--admin-bg`, `--admin-surface`, `--admin-surface-soft`, `--admin-border`, `--admin-text`, `--admin-muted` |
| Status | `--hr-success` hijau (sukses/hadir/aktif), `--hr-warning` kuning (menunggu/terlambat), `--hr-danger` merah (gagal/absen/ditolak), `--hr-neutral` abu (nonaktif/dibatalkan), `--hr-info` biru (info) + varian `-soft` untuk latar |
| Jarak | `--hr-space-1` … `--hr-space-8` = 4 / 8 / 12 / 16 / 24 / 32 / 48 / 64 px |
| Huruf | `--hr-text-xs` 12px, `-sm` 13px, `-base` 14px, `-lg` 16px, `-xl` 20px, `-2xl` 24px (font Inter) |
| Sudut | `--hr-radius-sm` 8px, `--hr-radius` 12px, `--hr-radius-pill` |
| Bayangan | `--admin-shadow-sm`, `--admin-shadow`, `--admin-shadow-lg`, cincin fokus `--admin-ring` |
| Gerak | `--hr-durasi` 160ms, `--hr-easing`; `prefers-reduced-motion` mematikan animasi |
| Lapisan | `--hr-z-dropdown` < `--hr-z-sticky` < `--hr-z-modal` < `--hr-z-toast` |

Badge status memakai kelas Bootstrap `text-bg-success / warning / danger / secondary / info`; warnanya sudah disesuaikan di `ui.css`.

## 2. Kerangka halaman panel

```blade
@extends('layouts.admin.app')   {{-- atau layouts.pegawai.app / layouts.pelamar.app --}}
@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        @include('partials.kembali', ['ke' => route('modul.index'), 'label' => 'Nama Modul'])  {{-- hanya halaman dalam --}}
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-…"></i></span>
                <div>
                    <p class="eyebrow mb-1">Grup menu</p>
                    <h1 class="h3 mb-1">Judul halaman</h1>   {{-- otomatis jadi <title> --}}
                    <p class="text-muted mb-0">Satu kalimat penjelasan.</p>
                </div>
            </div>
            <div class="heading-actions">{{-- Ekspor, Tambah --}}</div>
        </div>
        <section class="panel">…</section>
    </div>
</main>
@endsection
```

Navigasi halaman dalam memakai **eyebrow + tautan "Kembali ke …"** (`partials.kembali`), bukan breadcrumb bertingkat, karena struktur menu hanya dua tingkat.

## 3. Komponen (partial Blade)

| Partial | Kegunaan |
|---|---|
| `partials.kembali` | Tautan kembali di atas judul halaman dalam |
| `partials.daftar-cari` | Kotak cari di bilah filter (cari otomatis setelah berhenti mengetik) |
| `partials.filter-tanggal` | Filter rentang tanggal (`tanggal_dari` / `tanggal_sampai`, backend: `App\Support\FilterTanggal`) |
| `partials.urut` | Pilihan urutan daftar |
| `partials.daftar-ringkas` | "N dari M data" + tautan reset filter |
| `partials.kosong-tabel` / `partials.kosong` | Keadaan kosong: ikon + penjelasan + tautan aksi |
| `partials.tombol-ekspor` | Tombol ekspor (ikon & posisi seragam) |
| `partials.tombol-aksi-lain` | Menu "⋯" untuk aksi sekunder di baris tabel |
| `partials.pilih-pegawai` | Pilih pegawai dengan pencarian |
| `partials.preview-file` / `preview-file-modal` | Pratinjau dokumen/gambar |
| `partials.viz.*` | Grafik ringan (batang, kolom, komposisi) + tabel alternatif untuk pembaca layar |
| `layouts.partials.umpan-balik` | Banner galat/peringatan + tombol "Coba lagi" |
| `layouts.partials.sweetalert` | Toast sukses/info, dialog konfirmasi, spinner tombol kirim |

## 4. Pola interaksi (otomatis, cukup pasang atribut)

| Kebutuhan | Pasang | Ditangani di |
|---|---|---|
| Konfirmasi saat form dikirim | `<form data-confirm="Hapus data ini?">` | `sweetalert.blade.php` |
| Konfirmasi per tombol | `<button data-confirm="true" data-title="…" data-text="…" data-confirm-text="Ya, Hapus">` | `sweetalert.blade.php` |
| Fokus ke isian pertama (form tambah) | `<form data-fokus-awal>` | `main.js` (isian galat difokus otomatis) |
| Cari otomatis | `data-cari-otomatis` | `main.js` |
| Saring baris tanpa muat ulang | `data-saring`, `data-saring-jumlah`, `data-saring-kosong` | `main.js` |
| Indikator memuat saat filter | `form.panel-filter` / `form[data-filter-daftar]` | `main.js` |

- Kata kerja destruktif (hapus, tolak, batalkan, nonaktifkan, kosongkan, keluarkan, cabut) otomatis memakai ikon peringatan, tombol merah "Ya, <Kata>", dan tombol "Batal" di kiri.
- Setelah dikonfirmasi, form dikirim dengan `requestSubmit`, sehingga validasi HTML, spinner "Memproses…", dan `name/value` tombol tetap berlaku.
- **Jangan** memakai `confirm()` / `alert()` bawaan browser.

## 5. Umpan balik & bahasa

- Sukses/info → toast pojok kanan bawah. Galat/peringatan/validasi → banner di area konten + "Coba lagi". Galat isian juga tampil di bawah isiannya (`is-invalid` + `invalid-feedback`).
- Bahasa Indonesia formal tapi ramah: **Simpan, Hapus, Ubah, Batal, Kembali, Keluar, Profil** (bukan Submit/Delete/Edit/Logout/Profile).
- Placeholder: deskripsi umum + contoh, mis. `Cari nama, contoh: Siti`. Label tetap wajib (`<label for>`).

## 6. Aksesibilitas & responsif

- Fokus terlihat lewat `:focus-visible` (cincin merek) dan skip link "Lewati ke konten utama".
- Ikon dekoratif `aria-hidden="true"`; tombol ikon saja wajib `aria-label`.
- Tabel di dalam `.table-responsive`; di layar kecil berubah menjadi kartu (`ui.css`).
- Gambar di bawah lipatan: `loading="lazy" decoding="async"`.
- Halaman error (`resources/views/errors/*`) berdiri sendiri (CSS di dalam file) agar tetap tampil walau aset gagal dimuat.
