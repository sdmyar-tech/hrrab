<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Master daftar jabatan: sumber semua dropdown & validasi jabatan. */
class Jabatan extends Model
{
    private const CACHE_KEY = 'master.jabatan';

    protected $fillable = ['nama'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
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

            DB::table('kepegawaians')->where('jabatan', $lama)->update(['jabatan' => $baru]);
            DB::table('kepegawaians')->where('jabatan_tambahan', $lama)->update(['jabatan_tambahan' => $baru]);
            DB::table('penugasans')->where('jabatan', $lama)->update(['jabatan' => $baru]);
            DB::table('lowongans')->where('jabatan', $lama)->update(['jabatan' => $baru]);
            DB::table('pengajuan_formasis')->where('jabatan', $lama)->update(['jabatan' => $baru]);
            DB::table('lamarans')->where('jabatan_tujuan', $lama)->update(['jabatan_tujuan' => $baru]);
        });
    }
}
