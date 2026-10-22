# Panduan Aktivasi "Daftar / Masuk dengan Google" (Pelamar)

> Status: **belum diaktifkan** — kode sudah siap, tinggal membuat kredensial Google dan mengisinya di server.
> Jangan pernah menyimpan Client ID / Client Secret di file ini atau di Git.

## Cara kerja di aplikasi

- Tombol **Daftar dengan Google** (halaman daftar) dan **Masuk dengan Google** (login pelamar) hanya muncul bila
  `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` terisi di `.env`.
- Pelamar baru → akun dibuat otomatis → diarahkan melengkapi **Data Pelamar**.
- Pelamar lama → langsung masuk.
- Email milik akun internal (pegawai/admin) **ditolak** agar tidak tercampur dengan akun pelamar.
- Kode terkait: `app/Http/Controllers/Auth/GoogleController.php`, `resources/views/auth/_google-button.blade.php`,
  rute `google.redirect` dan `google.callback` (`/auth/google/callback`).

## Langkah aktivasi

### 1. Buat project Google Cloud
1. Buka <https://console.cloud.google.com> dengan **akun Google milik yayasan** (bukan akun pribadi).
2. Pemilih project (kiri atas) → **New Project** → nama mis. `HRRAB` → **Create**.

### 2. OAuth consent screen
**APIs & Services → OAuth consent screen**

| Isian | Nilai |
|---|---|
| User Type | External |
| App name | HRRAB – Yayasan Abdurrab |
| User support email & Developer contact | email yayasan |
| Authorized domain | domain situs (lihat catatan domain) |
| Scopes | bawaan saja (email, profile, openid) |

Lalu klik **Publish App** → status **In production**.
(Jika masih *Testing*, hanya email di daftar *Test users* yang bisa login.)

### 3. Buat kredensial
**APIs & Services → Credentials → Create Credentials → OAuth client ID**

- Application type: **Web application**
- Authorized JavaScript origins:
  `https://aliceblue-okapi-840900.hostingersite.com`
- Authorized redirect URIs (harus persis sama):
  `https://aliceblue-okapi-840900.hostingersite.com/auth/google/callback`

Klik **Create**, salin **Client ID** & **Client Secret** (simpan pribadi, jangan dibagikan di chat).

> Jika nanti pindah ke domain sendiri, ganti kedua alamat di atas dan `APP_URL` di server.

### 4. Isi di server (SSH)
```bash
cd ~/domains/aliceblue-okapi-840900.hostingersite.com/public_html
nano .env
```
```ini
GOOGLE_CLIENT_ID=isi-client-id
GOOGLE_CLIENT_SECRET=isi-client-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```
Pastikan `APP_URL=https://aliceblue-okapi-840900.hostingersite.com`, lalu:
```bash
php artisan config:cache
```

### 5. Uji
Buka halaman **Daftar** pelamar → tombol **Daftar dengan Google** muncul → coba dengan Gmail pribadi →
harus diarahkan ke Data Pelamar.

### Catatan domain
Domain sementara `…hostingersite.com` milik Hostinger, jadi Google bisa menolaknya sebagai *authorized domain*.
- Uji coba: biarkan status **Testing** + tambahkan email penguji di *Test users*.
- Pemakaian umum: gunakan domain sendiri (mis. `rekrutmen.abdurrab.ac.id`), lalu sesuaikan langkah 3 & `APP_URL`.

Error umum: `redirect_uri_mismatch` → alamat di langkah 3 tidak persis sama dengan `APP_URL/auth/google/callback`
(cek `https`, garis miring di akhir, dan nama domain).

## Mengganti akun Google pemilik project

Akun pelamar yang sudah terdaftar **tetap aman** dengan cara mana pun: ID akun Google seseorang sama di semua
project, dan aplikasi juga mencocokkan lewat email sebagai cadangan.

### Cara 1 — Pindah kepemilikan (disarankan, tanpa ubah server)
1. **IAM & Admin → IAM → Grant access** → email akun baru → role **Owner**.
2. Login dengan akun baru → hapus akun lama dari IAM.
3. **OAuth consent screen** → ganti *User support email* & *Developer contact* ke email baru.

Client ID & Secret tidak berubah.

### Cara 2 — Project baru di akun lain
1. Ulangi langkah 1–3 di atas dengan akun baru.
2. Ganti `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` di `.env` server → `php artisan config:cache`.
3. Hapus project lama bila sudah tidak dipakai.

### Saran
- Gunakan akun Google **milik yayasan** sebagai pemilik.
- Tambahkan **2 Owner** sejak awal (mis. akun yayasan + akun pengelola) agar akses tidak terkunci.
