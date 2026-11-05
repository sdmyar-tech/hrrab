<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master Unit (tanpa koordinator). Berbeda dari Bagian (App\Models\Unit: koordinator & alur persetujuan);
 * Unit membawahi beberapa bagian (units.unit_organisasi_id) dan mengelompokkan pegawai (kepegawaians.unit_organisasi),
 * yang terisi otomatis dari bagian pegawai.
 */
class UnitOrganisasi extends Model
{
    use Concerns\DicatatLog;

    public const CACHE_KEY = 'master.unit-organisasi';

    /** [tabel, kolom] yang menyimpan nama unit; ikut diperbarui saat unit diganti nama. */
    private const KOLOM = [
        ['kepegawaians', 'unit_organisasi'],
    ];

    protected $fillable = ['nama', 'urutan', 'keterangan'];

    protected $casts = ['urutan' => 'integer'];

    protected static function booted(): void
    {
        $lupakan = function () {
            Cache::forget(self::CACHE_KEY);
            Cache::forget(Unit::CACHE_UNIT_ORG);
        };
        static::saved($lupakan);
        static::deleted($lupakan);
    }

    /** Bagian yang dibawahi unit ini. */
    public function bagians(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('urutan')->orderBy('nama');
    }

    /**
     * Tetapkan bagian yang dibawahi unit ini (bagian yang dipindah dari unit lain ikut pindah) lalu samakan unit
     * pegawai: pegawai di bagian-bagian ini → unit ini; pegawai di bagian yang dilepas → tanpa unit.
     *
     * @param  list<int>  $bagianIds
     * @return int jumlah data pegawai yang unitnya ikut diperbarui
     */
    public function aturBagian(array $bagianIds): int
    {
        $bagianIds = array_values(array_unique(array_map('intval', $bagianIds)));

        return DB::transaction(function () use ($bagianIds) {
            $dilepas = Unit::where('unit_organisasi_id', $this->id)->whereNotIn('id', $bagianIds ?: [0])->pluck('nama');
            Unit::where('unit_organisasi_id', $this->id)->whereNotIn('id', $bagianIds ?: [0])->update(['unit_organisasi_id' => null]);
            Unit::whereIn('id', $bagianIds)->update(['unit_organisasi_id' => $this->id]);
            Cache::forget(Unit::CACHE_UNIT_ORG);

            $nama = Unit::whereIn('id', $bagianIds)->pluck('nama');

            return DB::table('kepegawaians')->whereIn('unit_kerja', $nama)->where(fn ($q) => $q->whereNull('unit_organisasi')->orWhere('unit_organisasi', '!=', $this->nama))
                ->update(['unit_organisasi' => $this->nama])
                + DB::table('kepegawaians')->whereIn('unit_kerja', $dilepas)->where('unit_organisasi', $this->nama)->update(['unit_organisasi' => null]);
        });
    }

    /** @return list<string> nama unit, berurutan (kosong bila tabel belum dimigrasi) */
    public static function daftar(): array
    {
        static $tabelAda = null;
        $tabelAda ??= Schema::hasTable('unit_organisasis');

        if (! $tabelAda) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, fn () => self::orderBy('urutan')->orderBy('nama')->pluck('nama')->all());
    }

    /** @return array<string, int> "tabel.kolom" => jumlah baris yang memakai unit ini */
    public function pemakaian(): array
    {
        $hasil = [];
        foreach (self::KOLOM as [$tabel, $kolom]) {
            if ($n = DB::table($tabel)->where($kolom, $this->nama)->count()) {
                $hasil["{$tabel}.{$kolom}"] = $n;
            }
        }

        return $hasil;
    }

    /** Ganti nama unit sekaligus semua data yang memakai nama lama; mengembalikan jumlah baris yang ikut berubah. */
    public function ubahNama(string $baru): int
    {
        $lama = $this->nama;
        if ($lama === $baru) {
            return 0;
        }

        return DB::transaction(function () use ($lama, $baru) {
            $this->update(['nama' => $baru]);
            $jumlah = 0;
            foreach (self::KOLOM as [$tabel, $kolom]) {
                $jumlah += DB::table($tabel)->where($kolom, $lama)->update([$kolom => $baru]);
            }

            return $jumlah;
        });
    }
}
