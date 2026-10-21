<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Master unit kerja. Daftarnya menggantikan config('kepegawaian.unit_kerja') saat aplikasi berjalan
 * (AppServiceProvider), sehingga semua dropdown & validasi unit ikut master ini.
 * Koordinator (kepala) unit tetap dicatat di Penugasan (peran kepala) — diatur lewat PenugasanService::tetapkanKoordinator().
 */
class Unit extends Model
{
    use Concerns\DicatatLog;

    public const CACHE_KEY = 'master.unit';

    /** Kolom nama unit di tabel lain yang ikut diperbarui saat unit diganti nama. */
    private const KOLOM_UNIT = ['unit_kerja', 'unit_kerja_tujuan'];

    protected $fillable = ['nama', 'urutan', 'keterangan'];

    protected $casts = ['urutan' => 'integer'];

    protected static function booted(): void
    {
        $lupakan = function () {
            Cache::forget(self::CACHE_KEY);
            Cache::forget(Jabatan::CACHE_PER_UNIT);
        };
        static::saved($lupakan);
        static::deleted($lupakan);
    }

    public function jabatans(): BelongsToMany
    {
        return $this->belongsToMany(Jabatan::class)->orderBy('nama');
    }

    /** @return list<string> nama unit, berurutan (fallback ke config bila tabel belum ada) */
    public static function daftar(): array
    {
        static $tabelAda = null;
        $tabelAda ??= Schema::hasTable('units');

        if (! $tabelAda) {
            return config('kepegawaian.unit_kerja', []);
        }

        return Cache::rememberForever(self::CACHE_KEY, fn () => self::orderBy('urutan')->orderBy('nama')->pluck('nama')->all());
    }

    /**
     * Pemakaian nama unit di semua tabel yang punya kolom unit (pegawai, penugasan, pengajuan, lowongan, ...).
     *
     * @return array<string, int> "tabel.kolom" => jumlah baris
     */
    public function pemakaian(): array
    {
        $hasil = [];
        foreach (self::kolomUnit() as [$tabel, $kolom]) {
            if ($n = DB::table($tabel)->where($kolom, $this->nama)->count()) {
                $hasil["{$tabel}.{$kolom}"] = $n;
            }
        }

        return $hasil;
    }

    /**
     * Ganti nama unit sekaligus semua data yang memakai nama lama (termasuk bidang GM & kepala unit GM di akun).
     * Unit yang disebut di pengaturan sistem (config) tidak bisa diganti nama dari aplikasi.
     */
    public function ubahNama(string $baru): int
    {
        $lama = $this->nama;
        if ($lama === $baru) {
            return 0;
        }
        if ($dipakaiConfig = self::dipakaiConfig($lama)) {
            throw ValidationException::withMessages(['nama' => "Unit {$lama} dipakai di pengaturan sistem ({$dipakaiConfig}). Nama unit ini tidak bisa diganti dari aplikasi."]);
        }

        return DB::transaction(function () use ($lama, $baru) {
            $this->update(['nama' => $baru]);
            $jumlah = 0;
            foreach (self::kolomUnit() as [$tabel, $kolom]) {
                $jumlah += DB::table($tabel)->where($kolom, $lama)->update([$kolom => $baru]);
            }
            // Kolom JSON daftar unit di akun (bidang GM & unit yang dipimpin GM tanpa data pegawai).
            foreach (User::whereNotNull('divisi')->orWhereNotNull('kepala_unit_gm')->get() as $u) {
                $ganti = fn (?array $daftar) => $daftar ? array_values(array_map(fn ($x) => $x === $lama ? $baru : $x, $daftar)) : $daftar;
                if (in_array($lama, $u->divisi ?? [], true) || in_array($lama, $u->kepala_unit_gm ?? [], true)) {
                    $u->forceFill(['divisi' => $ganti($u->divisi), 'kepala_unit_gm' => $ganti($u->kepala_unit_gm)])->save();
                    $jumlah++;
                }
            }

            return $jumlah;
        });
    }

    /**
     * Nama aturan config yang menyebut unit ini dan memengaruhi logika (unit_tanpa_pengajuan, unit_langsung_pembina), atau null.
     * (gm_bidang hanya preset centang di Kelola Akun — bidang GM yang berlaku tersimpan di akun dan ikut diperbarui.)
     */
    public static function dipakaiConfig(string $nama): ?string
    {
        $label = array_filter([
            in_array($nama, config('kepegawaian.unit_tanpa_pengajuan', []), true) ? 'unit tanpa pengajuan' : null,
            in_array($nama, config('kepegawaian.unit_langsung_pembina', []), true) ? 'unit langsung Pembina' : null,
        ]);

        return $label ? implode(', ', $label) : null;
    }

    /** @return list<array{0: string, 1: string}> [tabel, kolom] yang menyimpan nama unit */
    private static function kolomUnit(): array
    {
        return once(function () {
            $hasil = [];
            foreach (Schema::getTables() as $t) {
                foreach (array_intersect(Schema::getColumnListing($t['name']), self::KOLOM_UNIT) as $kolom) {
                    $hasil[] = [$t['name'], $kolom];
                }
            }

            return $hasil;
        });
    }
}
