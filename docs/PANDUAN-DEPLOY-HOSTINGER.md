# Panduan Deploy HRRAB ke Hostinger

> Server: `aliceblue-okapi-840900.hostingersite.com` (ganti bila pindah domain).
> Folder aplikasi = `~/domains/<domain>/public_html` — seluruh proyek ada di sini, `.htaccess` di root
> meneruskan request ke `public/` dan memblok folder internal (`.env`, `app`, `vendor`, `.git`, dst.).
> Jangan pernah menyimpan isi `.env`, password, atau dump database di file ini / di Git.

## Ringkasan alur

```
Laptop (Laragon) ──git push──► GitHub (pribadi + sdmyar-tech) ──git pull──► Hostinger (SSH)
                                                                            └─ composer, migrate, optimize
```

Semua perubahan (fitur, perbaikan, troubleshoot) dikerjakan di laptop → commit → push → tarik di server.
**Jangan mengedit kode langsung di server**; perubahan di server akan bentrok saat `git pull` berikutnya.

---

## A. Deploy pertama kali

### 1. Siapkan hPanel

| Menu hPanel | Pengaturan |
|---|---|
| Advanced → **SSH Access** | Aktifkan, catat host, port, username (`u123456789`) |
| Advanced → **PHP Configuration** → PHP Version | **8.3** |
| PHP Configuration → PHP Extensions | pastikan aktif: `zip`, `gd`, `fileinfo`, `mbstring`, `xml`, `xmlreader`, `xmlwriter`, `intl`, `pdo_mysql` |
| PHP Configuration → PHP Options | `upload_max_filesize` **25M**, `post_max_size` **30M**, `memory_limit` **256M**, `max_execution_time` **120** (impor absensi s.d. 20 MB & Excel) |
| Databases → **MySQL Databases** | buat database + user, catat nama lengkapnya (berawalan `u123456789_`) |
| Security → **SSL** | pasang SSL, aktifkan *Force HTTPS* |

### 2. Hubungkan server ke GitHub (deploy key)

Repo privat, jadi server perlu kunci SSH sendiri (hanya-baca):

```bash
ssh -p 65002 u123456789@IP-SERVER          # lihat di SSH Access
ssh-keygen -t ed25519 -C "hostinger-hrrab" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub
```

Salin hasilnya ke GitHub → repo `sdmyar-tech/hrrab` → **Settings → Deploy keys → Add deploy key**
(jangan centang *Allow write access*). Uji:

```bash
ssh -T git@github.com     # "Hi sdmyar-tech/hrrab! You've successfully authenticated..."
```

### 3. Ambil kode

`public_html` harus kosong (hapus `default.php` bawaan Hostinger dulu):

```bash
cd ~/domains/aliceblue-okapi-840900.hostingersite.com
rm -rf public_html/* public_html/.[!.]*        # PASTIKAN folder ini memang belum berisi data
git clone git@github.com:sdmyar-tech/hrrab.git public_html
cd public_html
```

### 4. Pasang dependensi

```bash
php -v                                  # harus 8.3; bila bukan, pakai /opt/alt/php83/usr/bin/php
composer install --no-dev --optimize-autoloader
```

Aset tampilan ada di `public/assets-*` (sudah di Git) — **tidak perlu `npm`**.

### 5. Buat `.env`

```bash
cp .env.example .env
nano .env
php artisan key:generate
```

Isian yang wajib diubah dari `.env.example`:

```ini
APP_NAME="HRRAB"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://aliceblue-okapi-840900.hostingersite.com
STAFF_LOGIN_PATH=portal-sdm            # ganti dengan alamat rahasia login internal
APP_TIMEZONE=Asia/Jakarta

LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u123456789_hrrab
DB_USERNAME=u123456789_hrrab
DB_PASSWORD=password-database

SESSION_SECURE_COOKIE=true
```

`GOOGLE_*` dibiarkan kosong sampai login Google diaktifkan (lihat `PANDUAN-LOGIN-GOOGLE.md`).

### 6. Isi database

**Jalur utama — pindahkan data dari laptop** (data pegawai asli):

1. Laptop: Laragon → **Database** (HeidiSQL) → klik kanan `hrabdurrab` → *Export database as SQL*
   (centang *Create tables* + *Data*). Simpan di luar folder proyek.
2. hPanel → Databases → **phpMyAdmin** → pilih database → **Import** → unggah file `.sql`.
   File besar: kompres jadi `.zip`/`.gz` dulu.
3. Server:
   ```bash
   php artisan migrate --force           # menjalankan migrasi yang belum ada di dump (bila ada)
   ```

**Database kosong** (instalasi baru tanpa data):

```bash
php artisan migrate --force --seed
```

Migrasi hanya membuat struktur tabel — semua tabel kosong (termasuk unit, jabatan, tanggal merah).
Seeder hanya membuat 1 akun awal, belum tertaut ke data pegawai:

| Akun | Email | Password awal |
|---|---|---|
| Admin | `admin@example.com` | `password123` |

Akun ini **wajib ganti password** saat login pertama. Urutan penyiapan oleh Admin:

0. **Kelola Akun** — ganti email Admin ke email asli, lalu buat akun Kepala SDM.
1. **Master Unit** — tambah semua unit kerja.
2. **Master Jabatan** — tambah jabatan dan centang unitnya (dropdown jabatan mengikuti unit).
3. **Master Lokasi Kerja** — tambah tempat/area kerja (pilihan lokasi di pegawai & lowongan).
4. **Data Pegawai** (input/impor Excel) → akun pegawai, GM, koordinator unit.
5. **Kalender** — tanggal merah terisi otomatis dari API saat halaman internal pertama dibuka
   (atau tekan *Sinkronkan sekarang*); tambahkan libur yayasan secara manual.

Seeder aman dijalankan ulang (akun yang sudah ada dilewati), tetapi tidak perlu dijalankan di server yang
sudah berisi data.

### 7. File unggahan & folder storage

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

Dokumen pegawai/pelamar tidak ada di Git. Pindahkan dari laptop:
zip `storage/app/public/dokumen-pegawai` (dan `dokumen-pelamar` bila ada) → hPanel **File Manager** →
unggah ke `public_html/storage/app/public/` → *Extract* → hapus file zip-nya.

### 8. Optimasi

```bash
php artisan optimize
```

### 9. Cron (tugas terjadwal)

hPanel → Advanced → **Cron Jobs** → *Custom*, **setiap menit** (`* * * * *`):

```
/usr/bin/php /home/u123456789/domains/aliceblue-okapi-840900.hostingersite.com/public_html/artisan schedule:run >> /dev/null 2>&1
```

Yang dijalankan (lihat `routes/console.php`, cek dengan `php artisan schedule:list`):

| Perintah | Jadwal | Fungsi |
|---|---|---|
| `lowongan:tutup-kedaluwarsa` | harian 00:01 | tutup lowongan lewat tanggal |
| `penugasan:sinkron` | harian 00:02 | berlakukan mutasi/rangkap terjadwal |
| `kontrak:evaluasi` | harian 06:00 | buat evaluasi kontrak H-30 |
| `rekrutmen:pengingat` | Senin–Jumat 07:30 | pengingat onboarding/interview/kontrak |
| `log:bersihkan` | tanggal 1, 01:00 | arsipkan & hapus log lama |
| `kalender:sinkron-libur` | tanggal 1, 01:15 | sinkron tanggal merah dari API |

Tidak perlu *queue worker*: aplikasi tidak memakai job antrean.

### 10. Cek setelah deploy

- [ ] `https://<domain>` terbuka, `https://<domain>/<STAFF_LOGIN_PATH>` menampilkan login internal
- [ ] `https://<domain>/.env` dan `https://<domain>/vendor/` → **403**
- [ ] Login Admin berhasil, foto/dokumen pegawai tampil
- [ ] Menu **Kalender** berisi tanggal merah (kosong → `php artisan kalender:sinkron-libur`)
- [ ] Impor absensi kecil berhasil (menguji batas upload & ekstensi `zip`)

---

## B. Update rutin (maintenance / fitur baru / perbaikan)

### Di laptop

```powershell
git add <file-yang-diubah>        # hindari "git add ." bila ada folder/berkas asing (mis. hrrab.git/)
git commit -m "Perbaiki ..."
git push                          # origin mengirim ke repo pribadi + sdmyar-tech sekaligus
```

### Di server

Simpan sekali sebagai `~/deploy-hrrab.sh` lalu `chmod +x ~/deploy-hrrab.sh`:

```bash
#!/bin/bash
set -e
cd ~/domains/aliceblue-okapi-840900.hostingersite.com/public_html

php artisan down --retry=30                 # tampilkan halaman pemeliharaan selama update
trap 'php artisan up' EXIT                  # situs tetap dinaikkan lagi walau ada langkah gagal

git pull --ff-only origin main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

echo "Deploy selesai: $(git log -1 --format='%h %s')"
```

Setiap update cukup:

```bash
ssh -p 65002 u123456789@IP-SERVER
~/deploy-hrrab.sh
```

Sebelum update yang berisi **migrasi** (folder `database/migrations` berubah), backup database dulu:
hPanel → Databases → phpMyAdmin → Export, atau hPanel → **Backups** → Database backups.

### Rollback (update bermasalah)

```bash
cd ~/domains/aliceblue-okapi-840900.hostingersite.com/public_html
git log --oneline -5                       # cari commit terakhir yang normal
git reset --hard <kode-commit>
composer install --no-dev --optimize-autoloader
php artisan optimize:clear && php artisan optimize
```

Bila update tadi sempat menjalankan migrasi yang merusak data, pulihkan dari backup database.
Lalu perbaiki di laptop → push → deploy ulang (server akan kembali sejajar dengan `main`).

---

## Alternatif: tanpa SSH/Git (unggah manual)

Hanya bila SSH tidak tersedia. Update jadi lebih repot (selalu unggah ulang), jadi pakai cara A/B bila bisa.

1. Laptop, di folder proyek:
   ```powershell
   composer install --no-dev --optimize-autoloader
   ```
2. Zip isi folder proyek **kecuali** `.env`, `.git`, `hrrab.git`, `node_modules`, `storage/logs/*`,
   `storage/framework/{cache,sessions,views}/*` (folder `vendor` **ikut**).
3. hPanel → File Manager → `public_html` → unggah zip → *Extract*.
4. Buat `.env` lewat File Manager (isi seperti langkah A.5); `APP_KEY` disalin dari hasil
   `php artisan key:generate --show` di laptop.
5. Database: impor `.sql` lewat phpMyAdmin (langkah A.6). Untuk instalasi kosong, impor dump dari laptop
   yang baru dibuat dengan `php artisan migrate:fresh --seed` di database lokal terpisah.
6. Symlink storage tanpa SSH: buat Cron Job sekali jalan
   `/usr/bin/php /home/u123456789/domains/<domain>/public_html/artisan storage:link`, lalu hapus cron itu.
7. Pasang cron `schedule:run` (langkah A.9).
8. Setelah dipakai lagi di laptop, jalankan `composer install` (tanpa `--no-dev`) agar alat dev kembali.

---

## C. Troubleshooting

Selalu lihat log dulu: `tail -n 50 storage/logs/laravel.log`

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| **500 Server Error** | izin folder / `.env` / cache lama | `chmod -R 775 storage bootstrap/cache`, cek `APP_KEY`, `php artisan optimize:clear` |
| Perubahan `.env` tidak berlaku | config ter-cache | `php artisan optimize:clear && php artisan optimize` |
| `Class ... not found` | autoload belum diperbarui | `composer install --no-dev --optimize-autoloader` |
| Foto/dokumen **404** | symlink storage hilang | `php artisan storage:link` |
| **419 Page Expired** saat login | `APP_URL` bukan https / cookie | samakan `APP_URL` dengan alamat di browser, `SESSION_SECURE_COOKIE=true`, hapus cookie browser |
| Upload gagal / file terlalu besar | batas PHP | naikkan `upload_max_filesize` & `post_max_size` di PHP Options |
| `git pull` gagal *Your local changes...* | ada file diedit di server | `git status` → `git checkout -- <file>` (perubahan server dibuang) lalu ulangi |
| Tugas otomatis tidak jalan | cron belum/ salah path | cek Cron Jobs, jalankan manual `php artisan schedule:run` dan lihat outputnya |
| Tanggal merah tidak bertambah | API libur gagal diakses | `php artisan kalender:sinkron-libur`; bila URL mati ganti `LIBUR_ICS_URL` / `LIBUR_NAGER_URL` di `.env` |
| Tampilan CSS/JS lama | cache browser | Ctrl+F5 |
| Situs tertahan "pemeliharaan" | skrip deploy terhenti paksa | `php artisan up` |

Untuk mengecek error tanpa membuka `APP_DEBUG` ke publik: jalankan `php artisan tinker` di server, atau baca
`laravel.log`. **Jangan** menyalakan `APP_DEBUG=true` di server yang dipakai pegawai.
