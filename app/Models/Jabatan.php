<?php

namespace App\Models;

use App\Models\Concerns\DicatatLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Master daftar jabatan: sumber semua dropdown & validasi jabatan. */
class Jabatan extends Model
{
    use DicatatLog;

    private const CACHE_KEY = 'master.jabatan';

    public const CACHE_PER_UNIT = 'master.jabatan-per-unit';

    protected $fillable = ['nama'];

    protected static function booted(): void
    {
        $lupakan = function () {
            Cache::forget(self::CACHE_KEY);
            Cache::forget(self::CACHE_PER_UNIT);
        };
        static::saved($lupakan);
        static::deleted($lupakan);
    }

    /** Unit tempat jabatan ini berada (satu jabatan boleh di beberapa unit, mis. guru di SMA/SMP/SMK). */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)->orderBy('urutan')->orderBy('nama');
    }

    /** Simpan daftar unit jabatan ini (id unit) dan segarkan cache jabatan per unit. */
    public function aturUnit(array $unitIds): void
    {
        $this->units()->sync(array_values(array_unique(array_map('intval', $unitIds))));
        Cache::forget(self::CACHE_PER_UNIT);
    }

    /**
     * Jabatan per unit dari Master Jabatan (dipakai dropdown jabatan yang mengikuti unit & aturan formasi).
     *
     * @return array<string, list<string>> nama unit => nama jabatan (A–Z)
     */
    public static function perUnit(): array
    {
        if (! Schema::hasTable('jabatan_unit')) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_PER_UNIT, fn () => DB::table('jabatan_unit')
            ->join('jabatans', 'jabatans.id', '=', 'jabatan_unit.jabatan_id')
            ->join('units', 'units.id', '=', 'jabatan_unit.unit_id')
            ->orderBy('jabatans.nama')
            ->get(['units.nama as unit', 'jabatans.nama as jabatan'])
            ->groupBy('unit')
            ->map(fn ($g) => $g->pluck('jabatan')->all())
            ->all());
    }

    /** @return list<string> */
    public static function daftar(): array
    {
        static $tabelAda = null;
        $tabelAda ??= Schema::hasTable('jabatans');

        if (! $tabelAda) {
            return config('kepegawaian.jabatan', []);
        }

        return Cache::rememberForever(self::CACHE_KEY, fn () => self::orderBy('nama')->pluck('nama')->all());
    }

    /** Jumlah data yang memakai jabatan ini (jabatan utama, tambahan, penugasan, lowongan, formasi). */
    public function jumlahDipakai(): int
    {
        return DB::table('kepegawaians')->where('jabatan', $this->nama)->count()
            + DB::table('kepegawaians')->where('jabatan_tambahan', $this->nama)->count()
            + DB::table('penugasans')->where('jabatan', $this->nama)->count()
            + DB::table('lowongans')->where('jabatan', $this->nama)->count()
            + DB::table('pengajuan_formasis')->where('jabatan', $this->nama)->count()
            + DB::table('lamarans')->where('jabatan_tujuan', $this->nama)->count();
    }

    /** Ganti nama sekaligus memperbarui semua data yang memakai nama lama. */
    public function ubahNama(string $baru): void
    {
        $lama = $this->nama;

        DB::transaction(function () use ($lama, $baru) {
            $this->update(['nama' => $baru]);
            self::pindahkanPemakai($lama, $baru);
        });
    }

    /** Hapus jabatan; data yang masih memakainya dipindahkan ke $pengganti lebih dulu. */
    public function hapusDanGanti(?Jabatan $pengganti): int
    {
        return DB::transaction(function () use ($pengganti) {
            $jumlah = $pengganti ? self::pindahkanPemakai($this->nama, $pengganti->nama) : 0;
            $this->delete();

            return $jumlah;
        });
    }

    /** Ganti semua pemakaian jabatan $lama menjadi $baru di seluruh tabel; mengembalikan jumlah baris yang berubah. */
    private static function pindahkanPemakai(string $lama, string $baru): int
    {
        return DB::table('kepegawaians')->where('jabatan', $lama)->update(['jabatan' => $baru])
            + DB::table('kepegawaians')->where('jabatan_tambahan', $lama)->update(['jabatan_tambahan' => $baru])
            + DB::table('penugasans')->where('jabatan', $lama)->update(['jabatan' => $baru])
            + DB::table('lowongans')->where('jabatan', $lama)->update(['jabatan' => $baru])
            + DB::table('pengajuan_formasis')->where('jabatan', $lama)->update(['jabatan' => $baru])
            + DB::table('lamarans')->where('jabatan_tujuan', $lama)->update(['jabatan_tujuan' => $baru]);
    }
}
