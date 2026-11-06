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

    public const CACHE_LOKASI = 'master.jabatan-lokasi';

    protected $fillable = ['nama'];

    protected static function booted(): void
    {
        $lupakan = function () {
            Cache::forget(self::CACHE_KEY);
            Cache::forget(self::CACHE_PER_UNIT);
            Cache::forget(self::CACHE_LOKASI);
        };
        static::saved($lupakan);
        static::deleted($lupakan);
    }

    /**
     * Lokasi kerja tiap jabatan di tiap bagian (Master Jabatan → Atur lokasi). Satu jabatan boleh di beberapa lokasi
     * (mis. CS di beberapa gedung); jabatan sama di bagian berbeda boleh berlainan lokasi.
     *
     * @return array<string, array<string, list<string>>> nama bagian => [NAMA JABATAN => nama lokasi (urut master lokasi)]
     */
    public static function lokasiPerUnit(): array
    {
        if (! Schema::hasTable('jabatan_unit_lokasi')) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_LOKASI, fn () => DB::table('jabatan_unit_lokasi')
            ->join('jabatans', 'jabatans.id', '=', 'jabatan_unit_lokasi.jabatan_id')
            ->join('units', 'units.id', '=', 'jabatan_unit_lokasi.unit_id')
            ->join('lokasi_kerjas', 'lokasi_kerjas.id', '=', 'jabatan_unit_lokasi.lokasi_kerja_id')
            ->orderBy('lokasi_kerjas.urutan')->orderBy('lokasi_kerjas.nama')
            ->get(['units.nama as unit', 'jabatans.nama as jabatan', 'lokasi_kerjas.nama as lokasi'])
            ->groupBy('unit')
            ->map(fn ($g) => $g->groupBy(fn ($r) => mb_strtoupper($r->jabatan))->map(fn ($x) => $x->pluck('lokasi')->all())->all())
            ->all());
    }

    /** @return list<string> lokasi tertaut untuk $jabatan di bagian $unit ([] = belum diatur: semua lokasi boleh) */
    public static function lokasiUntuk(?string $unit, ?string $jabatan): array
    {
        return self::lokasiPerUnit()[(string) $unit][mb_strtoupper(trim((string) $jabatan))] ?? [];
    }

    /** Jabatan boleh di bagian $unit: bagian belum punya jabatan tertaut (semua boleh), jabatan kosong, atau termasuk jabatan bagian itu. */
    public static function jabatanSah(?string $unit, ?string $jabatan): bool
    {
        $daftar = self::perUnit()[(string) $unit] ?? [];

        return ! $daftar || blank($jabatan) || in_array(mb_strtoupper(trim($jabatan)), array_map('mb_strtoupper', $daftar), true);
    }

    /** Lokasi $lokasi boleh untuk $jabatan di $unit: belum diatur (semua boleh), lokasi kosong, atau termasuk lokasi tertaut. */
    public static function lokasiSah(?string $unit, ?string $jabatan, ?string $lokasi): bool
    {
        $tertaut = self::lokasiUntuk($unit, $jabatan);

        return ! $tertaut || blank($lokasi) || in_array($lokasi, $tertaut, true);
    }

    /** Simpan lokasi jabatan ini di satu bagian (id lokasi kerja). */
    public function aturLokasi(int $unitId, array $lokasiIds): void
    {
        DB::transaction(function () use ($unitId, $lokasiIds) {
            DB::table('jabatan_unit_lokasi')->where('jabatan_id', $this->id)->where('unit_id', $unitId)->delete();
            DB::table('jabatan_unit_lokasi')->insert(collect($lokasiIds)->map('intval')->unique()
                ->map(fn ($l) => ['jabatan_id' => $this->id, 'unit_id' => $unitId, 'lokasi_kerja_id' => $l])->values()->all());
        });
        Cache::forget(self::CACHE_LOKASI);
    }

    /**
     * Unit tempat jabatan ini berada (satu jabatan boleh di beberapa unit, mis. guru di SMA/SMP/SMK).
     * Jabatan hanya dipakai untuk struktur & alur persetujuan; hak akses fitur ada di permission role/akun.
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)->orderBy('urutan')->orderBy('nama');
    }

    /** Simpan daftar unit jabatan ini (id unit) dan segarkan cache jabatan per unit; lokasi di bagian yang dilepas ikut dihapus. */
    public function aturUnit(array $unitIds): void
    {
        $unitIds = array_values(array_unique(array_map('intval', $unitIds)));
        $this->units()->sync($unitIds);
        if (Schema::hasTable('jabatan_unit_lokasi')) {
            DB::table('jabatan_unit_lokasi')->where('jabatan_id', $this->id)->whereNotIn('unit_id', $unitIds ?: [0])->delete();
        }
        Cache::forget(self::CACHE_PER_UNIT);
        Cache::forget(self::CACHE_LOKASI);
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
