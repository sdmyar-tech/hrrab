<?php

namespace App\Models;

use App\Support\Izin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Master Role.
 * - Role sistem (User::INTERNAL_ROLES, bawaan=true) menentukan panel & alur persetujuan; nama, keterangan dan
 *   centang permission-nya bisa disesuaikan. Barisnya diisi RbacSeeder.
 * - Role kustom (bawaan=false) dipasang ke akun lewat users.role_id; users.role tetap berisi jenis dasarnya,
 *   sehingga alur sama dengan jenis dasar tetapi permission-nya mengikuti role kustom.
 * - Permission tiap role = centang di role_permission (lihat App\Support\Izin). Tidak ada hak akses dari kode.
 */
class Role extends Model
{
    use Concerns\DicatatLog;

    public const CACHE_KEY = 'master.role';

    /** Ikon per jenis dasar role. */
    public const IKON_DASAR = [
        User::ROLE_PEMBINA => 'bi-award',
        User::ROLE_GM => 'bi-briefcase',
        User::ROLE_KEPALA_BAGIAN => 'bi-person-badge',
        User::ROLE_KEPALA_SDM => 'bi-person-gear',
        User::ROLE_STAFF => 'bi-person',
        User::ROLE_ADMIN => 'bi-shield-lock',
    ];

    /** Jenis dasar yang boleh dipakai role kustom (Admin tidak: role penuh hanya satu, semua permission terkunci). */
    public const JENIS_DASAR_KUSTOM = [User::ROLE_STAFF, User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM, User::ROLE_GM, User::ROLE_PEMBINA];

    /** Role bawaan yang tidak bisa dihapus (dinonaktifkan). */
    public const TIDAK_BISA_DIHAPUS = [User::ROLE_ADMIN];

    /** Role bawaan yang juga dipasang otomatis oleh sistem (pegawai baru, kepala unit) → wajib punya pengganti bila dihapus. */
    public const DIPASANG_OTOMATIS = [User::ROLE_STAFF, User::ROLE_KEPALA_BAGIAN];

    protected $fillable = ['kode', 'nama', 'keterangan', 'jenis_dasar', 'bawaan', 'aktif', 'pengganti'];

    protected $casts = ['bawaan' => 'boolean', 'aktif' => 'boolean'];

    /** Memo per proses (hak akses dicek berkali-kali tiap request); dikosongkan bersama cache saat role berubah. */
    private static ?Collection $memo = null;

    protected static function booted(): void
    {
        static::saved(fn () => self::lupakanCache());
        static::deleted(fn () => self::lupakanCache());
    }

    /** Panggil setelah centang permission role berubah (sync pivot tidak memicu event model). */
    public static function lupakanCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$memo = null;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /** Simpan centang permission role ini (kode), lalu segarkan cache. Role penuh (Admin) selalu memegang semuanya. */
    public function aturPermission(array $kode): void
    {
        $kode = self::rapikan($this->jenis_dasar, $kode);
        $this->permissions()->sync(array_values(array_intersect_key(Permission::idPerKode(), array_flip($kode))));
        self::lupakanCache();
    }

    /** Semua baris role (sistem + kustom) beserta permission-nya, di-cache. */
    public static function semua(): Collection
    {
        static $tabelAda = null;
        $tabelAda ??= Schema::hasTable('role_permission');

        if (! $tabelAda) {
            return new Collection;
        }

        return self::$memo ??= Cache::rememberForever(self::CACHE_KEY, fn () => self::with('permissions:id,kode')->orderBy('nama')->get());
    }

    /** Baris role sistem $kode (nama tampilan, keterangan, status aktif, permission). */
    public static function penyesuaian(?string $kode): ?self
    {
        return self::semua()->first(fn (self $r) => $r->bawaan && $r->kode === $kode);
    }

    /** Role sistem yang nama/keterangan/permission-nya sudah berbeda dari bawaan sistem (Izin::BAWAAN_ROLE). */
    public static function disesuaikan(string $kode): bool
    {
        $r = self::penyesuaian($kode);
        if (! $r) {
            return false;
        }
        $urut = fn (array $a) => collect($a)->sort()->values()->all();

        return $r->nama !== User::LABEL_ROLE[$kode] || (bool) $r->keterangan
            || $urut($r->hakAksesEfektif()) !== $urut(Izin::bawaanRole($kode));
    }

    public static function kustom(): Collection
    {
        return self::semua()->reject(fn (self $r) => $r->bawaan)->values();
    }

    /** @return list<string> kode role bawaan yang sudah dihapus (dinonaktifkan) */
    public static function bawaanNonaktif(): array
    {
        return self::semua()->filter(fn (self $r) => $r->bawaan && $r->aktif === false)->pluck('kode')->values()->all();
    }

    /** @return list<string> kode role bawaan internal yang masih aktif (urutan seperti User::INTERNAL_ROLES) */
    public static function bawaanAktif(): array
    {
        return array_values(array_diff(User::INTERNAL_ROLES, self::bawaanNonaktif()));
    }

    /**
     * Urai pilihan role ("<kode bawaan>" atau "kustom:<id>") yang masih aktif.
     *
     * @return array{role: string, role_id: ?int}|null
     */
    public static function urai(?string $nilai): ?array
    {
        $nilai = (string) $nilai;
        if (str_starts_with($nilai, 'kustom:')) {
            $kustom = self::kustom()->firstWhere('id', (int) substr($nilai, 7));

            return $kustom ? ['role' => $kustom->jenis_dasar, 'role_id' => $kustom->id] : null;
        }

        return in_array($nilai, self::bawaanAktif(), true) ? ['role' => $nilai, 'role_id' => null] : null;
    }

    /**
     * Role tujuan untuk akun yang mendapat role bawaan yang sudah dihapus (mis. pegawai baru = Staff).
     * Mengikuti rantai pengganti sampai ketemu role aktif.
     *
     * @return array{role: string, role_id: ?int}|null
     */
    public static function penggantiUntuk(string $kode): ?array
    {
        for ($i = 0; $i < 8; $i++) {
            $baris = self::penyesuaian($kode);
            if (! $baris || $baris->aktif !== false || ! $baris->pengganti) {
                return null;
            }
            if ($tujuan = self::urai($baris->pengganti)) {
                return $tujuan;
            }
            $kode = $baris->pengganti; // pengganti juga sudah dihapus → ikuti penggantinya
        }

        return null;
    }

    /**
     * Pilihan role aktif untuk dropdown: "<kode bawaan>" / "kustom:<id>" => nama (+ dasar untuk kustom).
     *
     * @param  list<string>  $kecuali  nilai yang tidak ditampilkan
     * @return array<string, string>
     */
    public static function pilihanAktif(array $kecuali = []): array
    {
        $bawaan = collect(self::bawaanAktif())->mapWithKeys(fn ($k) => [$k => User::roleLabelFor($k)]);
        $kustom = self::kustom()->mapWithKeys(fn (self $r) => ['kustom:' . $r->id => $r->nama . ' (dasar ' . User::roleLabelFor($r->jenis_dasar) . ')']);

        return $bawaan->merge($kustom)->except($kecuali)->all();
    }

    /** @return list<string> permission role sistem $role (centang di database; sebelum diisi seeder: bawaan katalog) */
    public static function hakAksesBawaan(string $role): array
    {
        $baris = self::penyesuaian($role);

        return $baris ? $baris->hakAksesEfektif() : self::rapikan($role, Izin::bawaanRole($role));
    }

    /** @return list<string> permission yang tidak bisa dilepas untuk jenis dasar ini */
    public static function terkunci(string $jenisDasar): array
    {
        return $jenisDasar === Izin::ROLE_PENUH ? Izin::semua() : [];
    }

    /** Hanya kode yang ada di katalog, ditambah permission yang dikunci untuk jenis dasar itu. */
    public static function rapikan(string $jenisDasar, array $kode): array
    {
        return array_values(array_unique([...array_intersect($kode, Izin::semua()), ...self::terkunci($jenisDasar)]));
    }

    /** @return list<string> permission efektif role ini */
    public function hakAksesEfektif(): array
    {
        return self::rapikan($this->jenis_dasar, $this->permissions->pluck('kode')->all());
    }
}
