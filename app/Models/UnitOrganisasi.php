<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master Unit (tanpa koordinator). Berbeda dari Bagian (App\Models\Unit: koordinator & alur persetujuan);
 * Unit hanya pengelompokan yang dipilih di data pegawai (kepegawaians.unit_organisasi).
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
        $lupakan = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($lupakan);
        static::deleted($lupakan);
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
