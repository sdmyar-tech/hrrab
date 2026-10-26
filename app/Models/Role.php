<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Master Role.
 * - Role bawaan (User::INTERNAL_ROLES) tetap menentukan panel & alur persetujuan. Baris bawaan=true hanya menimpa
 *   nama tampilan, keterangan, dan hak aksesnya; tanpa baris, nilai bawaan dari kode (User::ABILITIES) yang dipakai.
 * - Role kustom (bawaan=false) dipasang ke akun lewat users.role_id; users.role tetap berisi jenis dasarnya,
 *   sehingga alur sama dengan jenis dasar tetapi hak aksesnya mengikuti role kustom.
 */
class Role extends Model
{
    use Concerns\DicatatLog;

    public const CACHE_KEY = 'master.role';

    /** Hak akses yang bisa diatur per role: kode => [label, keterangan]. */
    public const HAK_AKSES = [
        User::ABILITY_LIHAT_DASHBOARD => ['Dashboard', 'Membuka dashboard ringkasan.'],
        User::ABILITY_MENGAJUKAN => ['Mengajukan', 'Mengajukan cuti, lembur & izin keluar (mengikuti alur jenis dasar).'],
        User::ABILITY_APPROVE => ['Menyetujui pengajuan', 'Membuka daftar persetujuan; tahap yang bisa disetujui tetap mengikuti alur jenis dasar.'],
        User::ABILITY_LIHAT_LAPORAN => ['Lihat laporan pengajuan', 'Melihat daftar cuti, lembur & izin keluar.'],
        User::ABILITY_LIHAT_TABEL_KARYAWAN => ['Lihat data pegawai', 'Membuka daftar & detail pegawai.'],
        User::ABILITY_KELOLA_PEGAWAI => ['Kelola data pegawai', 'Menambah, mengubah & menghapus data pegawai.'],
        User::ABILITY_KELOLA_PENUGASAN => ['Mutasi, rangkap & unit', 'Mengelola mutasi, rangkap tugas & koordinator unit.'],
        User::ABILITY_LIHAT_ABSENSI => ['Lihat absensi', 'Melihat absensi harian & rekap.'],
        User::ABILITY_KELOLA_ABSENSI => ['Kelola absensi', 'Import data mesin, koreksi & pengaturan absensi.'],
        User::ABILITY_KELOLA_PENGUMUMAN => ['Kelola pengumuman', 'Membuat pengumuman & absen acara.'],
        User::ABILITY_KIRIM_NOTIFIKASI => ['Kirim notifikasi', 'Mengirim notifikasi ke pegawai.'],
        User::ABILITY_KELOLA_SISTEM => ['Kelola sistem', 'Kelola akun, master data, log & editor data.'],
    ];

    /** Pengelompokan hak akses untuk tampilan (matriks & form): judul => [ikon, daftar hak]. */
    public const GRUP_HAK = [
        'Umum' => ['bi-grid', [User::ABILITY_LIHAT_DASHBOARD, User::ABILITY_MENGAJUKAN]],
        'Pengajuan' => ['bi-check2-square', [User::ABILITY_APPROVE, User::ABILITY_LIHAT_LAPORAN]],
        'Kepegawaian' => ['bi-people', [User::ABILITY_LIHAT_TABEL_KARYAWAN, User::ABILITY_KELOLA_PEGAWAI, User::ABILITY_KELOLA_PENUGASAN]],
        'Absensi' => ['bi-fingerprint', [User::ABILITY_LIHAT_ABSENSI, User::ABILITY_KELOLA_ABSENSI]],
        'Informasi' => ['bi-megaphone', [User::ABILITY_KELOLA_PENGUMUMAN, User::ABILITY_KIRIM_NOTIFIKASI]],
        'Sistem' => ['bi-gear', [User::ABILITY_KELOLA_SISTEM]],
    ];

    /** Ikon per jenis dasar role. */
    public const IKON_DASAR = [
        User::ROLE_PEMBINA => 'bi-award',
        User::ROLE_GM => 'bi-briefcase',
        User::ROLE_KEPALA_BAGIAN => 'bi-person-badge',
        User::ROLE_KEPALA_SDM => 'bi-person-gear',
        User::ROLE_STAFF => 'bi-person',
        User::ROLE_ADMIN => 'bi-shield-lock',
    ];

    /** Hak akses yang tidak bisa dicabut (mencegah Admin terkunci di luar sistem). */
    public const DIKUNCI = [User::ROLE_ADMIN => [User::ABILITY_KELOLA_SISTEM]];

    /** Jenis dasar yang boleh dipakai role kustom (Admin tidak: banyak hak Admin diperiksa langsung dari jenis rolenya). */
    public const JENIS_DASAR_KUSTOM = [User::ROLE_STAFF, User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM, User::ROLE_GM, User::ROLE_PEMBINA];

    protected $fillable = ['kode', 'nama', 'keterangan', 'jenis_dasar', 'hak_akses', 'bawaan'];

    protected $casts = ['hak_akses' => 'array', 'bawaan' => 'boolean'];

    /** Memo per proses (hak akses dicek berkali-kali tiap request); dikosongkan bersama cache saat role berubah. */
    private static ?Collection $memo = null;

    protected static function booted(): void
    {
        $lupakan = function () {
            Cache::forget(self::CACHE_KEY);
            self::$memo = null;
        };
        static::saved($lupakan);
        static::deleted($lupakan);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Semua baris role (bawaan yang disesuaikan + kustom), di-cache. */
    public static function semua(): Collection
    {
        static $tabelAda = null;
        $tabelAda ??= Schema::hasTable('roles');

        if (! $tabelAda) {
            return new Collection;
        }

        return self::$memo ??= Cache::rememberForever(self::CACHE_KEY, fn () => self::orderBy('nama')->get());
    }

    /** Penyesuaian untuk role bawaan, atau null bila masih memakai nilai dari kode. */
    public static function penyesuaian(?string $kode): ?self
    {
        return self::semua()->first(fn (self $r) => $r->bawaan && $r->kode === $kode);
    }

    public static function kustom(): Collection
    {
        return self::semua()->reject(fn (self $r) => $r->bawaan)->values();
    }

    /** @return list<string> hak akses role bawaan (penyesuaian bila ada, selain itu bawaan kode) */
    public static function hakAksesBawaan(string $role): array
    {
        return self::rapikan($role, self::penyesuaian($role)?->hak_akses ?? (User::ABILITIES[$role] ?? []));
    }

    /** Hak akses hanya dari daftar yang dikenal, ditambah hak yang dikunci untuk jenis dasar itu. */
    public static function rapikan(string $jenisDasar, array $hakAkses): array
    {
        return array_values(array_unique([
            ...array_intersect($hakAkses, array_keys(self::HAK_AKSES)),
            ...(self::DIKUNCI[$jenisDasar] ?? []),
        ]));
    }

    /** @return list<string> hak akses efektif role ini */
    public function hakAksesEfektif(): array
    {
        return self::rapikan($this->jenis_dasar, $this->hak_akses ?? []);
    }
}
