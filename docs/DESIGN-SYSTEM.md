# Design System HRRAB

Panduan untuk menambah atau mengubah halaman agar tampilannya seragam. Berlaku untuk Batch 2–7 perombakan UI.
Stack: **Laravel 12 + Blade + Bootstrap 5 (aset lokal) + Bootstrap Icons + SweetAlert2**. Tidak memakai Tailwind/Vite.
`package.json`/`vite.config.js` hanya sisa kerangka Laravel dan tidak dimuat layout mana pun. Menambah Tailwind akan
bentrok dengan Bootstrap (reset/preflight) dan butuh `npm run build` di server.

Gaya: *modern-clean professional* (Linear/Vercel/Stripe), warna merek marun Abdurrab sebagai satu-satunya aksen.

## 1. Urutan CSS & sumber token

| Urutan | File | Isi |
|---|---|---|
| 1 | `public/assets-admin/css/bootstrap.min.css` | Bootstrap 5 |
| 2 | `public/assets-admin/css/style.css` | Sisa gaya dasar (kartu lama, tabel→kartu di HP) |
| 3 | `public/assets-admin/css/hrrab.css` | Gaya modul lama (tombol, panel, filter, tabel) |
| 4 | `public/assets-admin/css/ui.css` | **Satu-satunya sumber token** + kerangka aplikasi (`app-*`) + komponen (`hr-*`) + halaman tamu (`auth-*`) |

Token ada di `:root` pada `ui.css`; mode gelap ada di `html[data-theme="dark"]`. Di Blade/CSS selalu pakai `var(--…)`.
Jangan menulis hex atau ukuran lepas (13px, 17px, dst.).

### Palet

| Token | Terang | Pakai untuk |
|---|---|---|
| `--hr-primary` (`-50` … `-800`) | `#880f12` | Tombol utama, menu aktif, tautan, fokus |
| `--hr-primary-soft` / `--hr-primary-soft-text` | `#fdf3f3` / `#7a1013` | Latar pill aktif, ikon kartu |
| `--hr-secondary` / `--hr-secondary-soft` | `#374151` / `#f3f4f6` | Aksi sekunder |
| `--hr-gray-50` … `--hr-gray-900` | `#f9fafb` … `#111827` | Netral (dibalik otomatis di mode gelap) |
| `--hr-success` (+`-soft`) | `#157a3a` | Hijau: sukses, hadir, aktif |
| `--hr-warning` (+`-soft`) | `#b45309` | Kuning: menunggu, terlambat |
| `--hr-danger` (+`-soft`) | `#b42318` | Merah: gagal, absen, ditolak |
| `--hr-neutral` (+`-soft`) | `#5b6475` | Abu: nonaktif, dibatalkan |
| `--hr-info` (+`-soft`) | `#1d4ed8` | Biru: info |
| `--admin-bg` / `--admin-surface` / `--admin-border` | `#f9fafb` / `#fff` / `#e5e7eb` | Latar halaman, kartu, garis |
| `--admin-heading` / `--admin-text` / `--admin-muted` | `#111827` / `#1f2937` / `#646b78` | Judul, isi, teks redup |

Semua pasangan teks/latar di atas lolos WCAG AA (≥ 4,5:1), termasuk teks status di atas varian `-soft`-nya.

### Skala

| Kelompok | Nilai |
|---|---|
| Jarak `--hr-space-1…8` | 4 · 8 · 12 · 16 · 24 · 32 · 48 · 64 px |
| Huruf `--hr-text-*` | xs 12 · sm 13 · base 14 (isi) · lg 16 · xl 20 (h3) · 2xl 24 (h2) · 3xl 28 · 4xl 32 (h1) · 5xl 36 |
| Baris | `--hr-leading` 1.6 (isi), `--hr-leading-tight` 1.25 (judul). Font **Inter** |
| Sudut | `--hr-radius-sm` 6 (tombol/isian) · `--hr-radius-md` 8 · `--hr-radius` 12 (kartu/modal) · `--hr-radius-pill` 9999 |
| Kontrol | `--hr-control-sm` 32 · `--hr-control` 40 · `--hr-control-lg` 48 px (tombol & isian) |
| Ikon | 16 · 20 · 24 px |
| Bayangan | `--hr-shadow-xs` (kartu) · `-sm` (tombol disorot) · `-md` (kartu disorot) · `-lg` (dropdown) · `-xl` (modal/drawer) |
| Kerangka | sidebar 264 (ringkas 72) · topbar 64 · isi maks 1280 · padding isi 32 / 24 (< 992) / 16 (< 576) |
| Gerak | `--hr-durasi` 160 ms, `--hr-durasi-lambat` 200 ms, `--hr-easing`; `prefers-reduced-motion` mematikan animasi |
| Lapisan | dropdown 1000 < sticky 1020 < topbar 1025 < latar drawer 1035 < drawer 1040 < modal 1055 < toast 1090 |

## 2. Layout induk (3)

| Layout | Untuk | Catatan |
|---|---|---|
| `layouts/app.blade.php` | Semua panel yang login: Admin/SDM, Pegawai, Pelamar | `layouts.{admin,pegawai,pelamar}.app` hanya pembungkus 1 baris yang memilih menu sidebar |
| `layouts/guest/app.blade.php` | Masuk staf/pelamar, daftar, wajib ganti kata sandi, layar absen acara | Section `body-class`, `pakai-banner`, `styles`, `scripts` |
| `layouts/public/app.blade.php` | Situs karier (lowongan, jadwal kandidat) | Bilah atas lengket + kaki ringan, isi `.pub-wadah` (maks 1280). Gaya di `ui.css` bagian "HALAMAN PUBLIK" (Batch 2) |

Isi kerangka panel:

- **Sidebar** (`layouts.partials.sidebar`): kiri tetap 264px. Menu induk dropdown akordeon, badge tugas, menu aktif = pill + garis aksen kiri. Mode ringkas di desktop, drawer di < 992px. Isi menu tetap di `layouts.{peran}.sidebar` (array `$menu`).
- **Topbar** (`layouts.partials.navbar`): breadcrumb otomatis dari menu aktif + judul `<h1>` halaman, lalu **Cari menu** (Ctrl+K atau `/`), tema terang/gelap, lonceng, dan menu akun (Profil, Dashboard, Keluar).

## 3. Kerangka halaman panel (pola baru)

```blade
@extends('layouts.admin.app')   {{-- atau layouts.pegawai.app / layouts.pelamar.app --}}
@section('title', 'Data Pegawai')
@section('content')
<main class="dashboard-content">
    <div class="container-fluid">                      {{-- padding & lebar maks dari ui.css --}}
        <x-page-header title="Data Pegawai" subtitle="Kelola data seluruh pegawai." eyebrow="Kepegawaian">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pegawai.export'])
                <x-button :href="route('admin.pegawai.create')" icon="bi-person-plus">Tambah Pegawai</x-button>
            </x-slot:actions>
        </x-page-header>

        <section class="row g-3">…x-stat-card…</section>             {{-- 1. angka --}}
        <section class="hr-section">…grafik…</section>               {{-- 2. grafik --}}
        <section class="hr-section"><x-card :padding="false">…x-table…</x-card></section>   {{-- 3. tabel --}}
    </div>
</main>
@endsection
```

Aturan susunan:
- Teks rata kiri, angka rata kanan (`text-end hr-angka`), aksi utama paling kanan.
- Form 2 kolom di desktop (`row` + `col-md-6`), 1 kolom di HP.
- Tombol: primer di kanan, sekunder di kirinya, destruktif dipisah atau memakai varian `danger-outline`.
- Jarak antarbagian memakai `.hr-section` (32px). Grid memakai 12 kolom Bootstrap (`row g-3` + `col-*`).

## 4. Komponen Blade (`resources/views/components`)

| Komponen | Contoh singkat |
|---|---|
| `x-button` | `<x-button variant="primary\|secondary\|outline\|ghost\|danger\|danger-outline\|link" size="sm\|md\|lg" icon="bi-check-lg" :href="…" type="submit" block loading>Simpan</x-button>` |
| `x-input` | `<x-input name="email" type="email" label="Email" icon="bi-envelope" placeholder="Contoh: nama@gmail.com" required />`; kata sandi: tambahkan `toggle` |
| `x-select` | `<x-select name="unit" label="Bagian" :options="$opsi" placeholder="Pilih bagian" />` |
| `x-textarea` | `<x-textarea name="alasan" label="Alasan" maxlength="500" />` (penghitung karakter otomatis) |
| `x-checkbox` / `x-radio` / `x-toggle` | Centang, grup pilihan (`fieldset`+`legend`), sakelar |
| `x-form-group` | Label + kontrol kustom + galat/bantuan |
| `x-badge` | `<x-badge tone="success\|warning\|danger\|neutral\|info\|primary" icon="…" dot size="lg">Disetujui</x-badge>` (`size="lg"` untuk status utama) |
| `x-card` | `<x-card title subtitle icon :padding="false" hover href title-tag="h3">` + slot `actions`, `footer` |
| `x-chip` | `<x-chip :href="…" :active="…" icon :count="12">SDM</x-chip>`; tanpa `href` jadi tombol untuk saring di sisi klien |
| `x-timeline` + `x-timeline.item` | `<x-timeline.item state="done\|active\|stopped\|pending" title meta icon>rincian</x-timeline.item>` |
| `x-status-badge` | `<x-status-badge :badge="$item->badgeClass()">{{ $item->statusLabel() }}</x-status-badge>` — kelas `text-bg-*` dari model → tone semantik |
| `x-section-nav` | Navigasi bagian halaman panjang (kolom kiri lengket, penanda bagian terlihat): `:items="[['id', 'label', 'icon', 'status' => true\|false\|null]]"` |
| `x-stepper` | Penunjuk langkah form bertahap: `id :steps :current`; perbarui dengan `hrStepper(id, indeks)` |
| `x-filter-bar` | Bilah filter daftar (Batch 4): `:action` (form GET, cari otomatis) **atau** `target="#idTabel"` (saring di sisi klien) + `search search-label search-id :count unit :filtered :reset`; slot bawaan = pilihan/tanggal/urutan, slot `chips`, `hidden`, `summary` |
| `x-icon-button` | Tombol ikon + tooltip untuk kolom aksi: `<x-icon-button icon="bi-pencil" label="Ubah" :href tone="primary\|danger\|warning" type>`; `label` wajib (aria-label + tooltip `data-hr-tooltip`) |
| `x-table-empty` | Baris kosong tabel: `<x-table-empty :colspan icon title>` teks + slot `action`; saring klien: `data-saring-kosong="#id" hidden` |
| `x-form-section` | Bagian form: `<x-form-section title subtitle icon id>` berisi kolom grid (`col-md-6`/`col-12`); antarbagian dalam satu kartu otomatis bergaris |
| `x-info-list` | Label–nilai halaman detail: `<x-info-list :items="['NIK' => $p->nik]" />`, kosong → "Belum diisi", nilai panjang selebar penuh |
| `x-tangga-alur` | Tangga alur persetujuan: `:tahap="[['label','oleh','status','final','semua','kosong','catatan']]" awal="Anda mengajukan" akhir="Disetujui" arah="horizontal\|vertikal"`; status `selesai\|berjalan\|menunggu\|ditolak\|lewati` (kosong = pratinjau rute). Data rute → `PengajuanWorkflow::tahapTampil($rute)` |

Pola kelas (CSS di `ui.css`, tanpa komponen):

| Kelas | Kegunaan |
|---|---|
| `.hr-samping` + `.hr-bagian` | Kolom samping lengket di desktop + jarak gulir bagian tujuan di bawah topbar |
| `.hr-opsi-grup` > `label.hr-opsi` | Pilihan radio berbentuk kartu (tersorot saat terpilih) |
| `.hr-segmen` | Pilihan tunggal mendatar (radio `.btn-check` + `label`) |
| `.btn-check` + `label.hr-chip` | Chip sebagai radio |
| `.hr-hitungan` | Nilai hasil hitungan otomatis (lama cuti, jumlah jam) |
| `.hr-daftar-info` | Daftar ketentuan/keterangan berikon |
| `.hr-bilah-aksi` | Bilah tombol simpan lengket di bawah form panjang (tombol terakhir rata kanan) |
| `.lamaran-filter` + `.pub-chips` | Baris chip filter + urutan (chip bisa digeser di HP) |
| `partials.saring-status` | Saring kartu `[data-status]` lewat chip `[data-saring-status]` tanpa muat ulang |
| `.tangga-sel` / `.tangga-tahap` / `.tangga-sumber` | Halaman Tangga Alur Pengajuan: kartu tangga per bagian (otomatis = garis putus) & penyusun tahap (kartu tahap, kartu penyetuju) |
| `.tangga-jenis` / `.tangga-sumber-bantu` / `.tangga-sumber-detail` / `.tangga-sumber-hasil` | Kartu penyetuju: jenis sebagai chip radio (`.btn-check` + `.hr-chip`), keterangan jenis, isian lanjutan berlabel pertanyaan, hasil "Yang menyetujui: …" (hijau) / alasan kosong (`.is-kosong`, kuning) |
| `x-stat-card` | `<x-stat-card label value icon tone unit meta :trend="12.5" trend-label href>` |
| `x-page-header` | `title subtitle eyebrow back back-label` + slot `actions`, `media` |
| `x-table` | `<x-table caption compact sticky striped scroll>` + slot `head`, `foot`; di HP otomatis jadi kartu. `striped` = zebra halus, `scroll` = tinggi maks setinggi layar (header lengket di dalam kartu) — varian Batch 4. Kolom aksi: `<th class="hr-kolom-aksi">` + `<td class="text-end hr-kolom-aksi"><div class="hr-aksi-baris">…` |
| `x-pagination` | `<x-pagination :paginator="$data" />`, menampilkan "Menampilkan 1–20 dari 135" |
| `x-modal` | `<x-modal id title subtitle icon tone size="sm\|md\|lg\|xl" static>` + slot `footer` |
| `x-dropdown` | `<x-dropdown label icon variant icon-only>` berisi `.dropdown-item` |
| `x-alert` | `<x-alert tone title dismissible>` + slot `action` (mis. tombol Coba lagi) |
| `x-toast` | `<x-toast type="success" message="…" />`; dari JS: `hrToast('success', '…')` |
| `x-breadcrumb` | `:items="[['label'=>…, 'url'=>…], …]"`; topbar sudah mengisinya otomatis |
| `x-empty-state` | `<x-empty-state icon title compact>` teks + slot `action` (CTA) |
| `x-skeleton` | `variant="text\|card\|table\|avatar\|block"` untuk konten async |
| `x-avatar` | `:src :name size="sm\|md\|lg\|xl"`; tanpa foto atau foto gagal dimuat → inisial berwarna |
| `x-tabs` + `x-tabs.pane` | Tab mendatar/`vertical`; tab terbuka diingat lewat `#hash` |
| `x-auth-card` | Kartu halaman tamu (logo, eyebrow, judul, pesan sesi, kaki) |
| `x-password-meter` | Kekuatan kata sandi + syarat (`len:N`, `letter`, `digit`, `mix`, `same:id`, `differs:id`) |

### Pola seragam CRUD admin (Batch 4) — wajib untuk halaman daftar/detail/form admin berikutnya

**Daftar** (contoh: `admin/pegawai/index`, `admin/unit/index`):
```blade
<x-page-header title eyebrow subtitle>  <x-slot:actions> Ekspor · tombol sekunder · <x-button icon="bi-plus-lg">Tambah X</x-button> </x-slot:actions> </x-page-header>
<x-card :padding="false">
    <x-filter-bar …>pilihan · @include('partials.filter-tanggal') · @include('partials.urut')</x-filter-bar>
    <x-table caption sticky striped scroll> … <x-table-empty …> </x-table>
    <x-slot:footer><x-pagination :paginator /></x-slot:footer>   {{-- hanya bila hasPages() --}}
</x-card>
```
- Sel utama: `.hr-sel-judul` (tautan ke detail) + `.hr-sel-sub`; foto `x-avatar size="sm"` dalam `.hr-sel-utama`; badge tambahan di `.hr-sel-badge`.
- Aksi baris: `x-icon-button` (lihat · ubah · lainnya · hapus paling kanan). Baris yang tak punya satu aksi diberi pengganti `<span class="hr-ikon-btn invisible" aria-hidden="true"></span>` agar ikon sejajar.
- Tambah/ubah data master sederhana (bagian, unit, lokasi, jabatan) memakai `x-modal` dari tombol utama; form di badan modal, tombol simpan di kaki memakai `form="idForm"`.
  Galat validasi membuka lagi modal yang sama: input tersembunyi `_form=tambah|ubah` (+ `_url` untuk ubah), lalu `old()`.
- Saring cepat berbentuk tab/status → chip di slot `chips` (`x-chip` + jumlah), bukan tab Bootstrap.
- Aksi massal (centang + bilah aksi) belum dipakai: belum ada endpoint massal di backend.

**Detail**: `x-page-header` (back, `media` avatar, status `x-badge size="lg"`, aksi) → `row` kiri `col-xl-8` kartu berisi `.hr-detail-bagian` + `.hr-detail-judul` + `x-info-list` (tab `x-tabs` bila data banyak), kanan `col-xl-4` `.hr-detail-samping` (lengket) berisi ringkasan + `x-timeline`.

**Form**: `x-page-header` (back) → `x-card` berisi beberapa `x-form-section` (2 kolom desktop) → `.hr-bilah-aksi` lengket (Batal | Simpan). Form panjang boleh `x-section-nav` di kolom kiri.

Partial lama yang tetap dipakai: `partials.tombol-ekspor`, `partials.daftar-cari`, `partials.filter-tanggal`, `partials.urut`,
`partials.daftar-ringkas`, `partials.kosong(-tabel)`, `partials.tombol-aksi-lain`, `partials.pilih-pegawai`,
`partials.preview-file(-modal)`, `partials.viz.*`, `partials.kembali`.

## 5. Pola interaksi (otomatis, cukup pasang atribut)

| Kebutuhan | Pasang | Ditangani di |
|---|---|---|
| Konfirmasi saat form dikirim | `<form data-confirm="Hapus data ini?">` | `layouts/partials/sweetalert.blade.php` |
| Konfirmasi per tombol | `data-confirm="true" data-title data-text data-confirm-text` | sama |
| Galat validasi inline | Otomatis dari `$errors` (komponen isian juga menandai sendiri) | sama + komponen |
| Fokus ke isian pertama | `<form data-fokus-awal>` | `main.js` |
| Cari otomatis / saring tanpa muat ulang | `data-cari-otomatis` / `data-saring` | `main.js` |
| Indikator memuat saat filter | `form.panel-filter` | `main.js` |

Kata kerja destruktif (hapus, tolak, batalkan, nonaktifkan, kosongkan, keluarkan, cabut) otomatis memakai tombol merah "Ya, <Kata>".
Jangan memakai `confirm()` / `alert()` bawaan browser.

## 6. Umpan balik, bahasa, aksesibilitas

- Sukses/info tampil sebagai toast. Galat/peringatan tampil sebagai banner + "Coba lagi", dan galat isian di bawah isiannya.
- Bahasa: **Simpan, Hapus, Ubah, Batal, Kembali, Keluar, Profil, Akun, Kata Sandi**. Placeholder berisi deskripsi + contoh; label tetap wajib.
- Fokus `:focus-visible` (cincin merek), skip link, ikon dekoratif `aria-hidden`, tombol ikon saja wajib `aria-label`.
- Responsif tanpa gulir horizontal di 320/375/768/1024/1440; tabel melebar bergulir di dalam kartunya.
- Halaman error (`resources/views/errors/*`) berdiri sendiri agar tetap tampil walau aset gagal dimuat.
