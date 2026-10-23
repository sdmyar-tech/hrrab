<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master lokasi (area) kerja. Daftarnya menggantikan config('kepegawaian.lokasi_kerja') saat aplikasi berjalan
 * (AppServiceProvider), sehingga semua dropdown & validasi lokasi kerja (pegawai, lowongan, kandidat) ikut master ini.
 */
class LokasiKerja extends Model
{
    use Concerns\DicatatLog;

    public const CACHE_KEY = 'master.lokasi-kerja';

    /** [tabel, kolom] yang menyimpan nama lokasi kerja; ikut diperbarui saat lokasi diganti nama. */
    private const KOLOM = [
        ['kepegawaians', 'lokasi_kerja'],
        ['lowongans', 'lokasi_kerja'],
        ['lamarans', 'lokasi_kerja_tujuan'],
    ];

    protected $fillable = ['nama', 'urutan', 'keterangan'];

    protected $casts = ['urutan' => 'integer'];

    protected static function booted(): void
    {
        $lupakan = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($lupakan);
        static::deleted($lupakan);
    }

    /** @return list<string> nama lokasi, berurutan (fallback ke config bila tabel belum ada) */
    public static function daftar(): array
    {
        static $tabelAda = null;
        $tabelAda ??= Schema::hasTable('lokasi_kerjas');

        if (! $tabelAda) {
            return config('kepegawaian.lokasi_kerja', []);
        }

        return Cache::rememberForever(self::CACHE_KEY, fn () => self::orderBy('urutan')->orderBy('nama')->pluck('nama')->all());
    }

    /** Daftarkan nama lokasi yang belum ada di master (mis. hasil impor Excel), di urutan paling akhir. */
    public static function daftarkan(iterable $nama): void
    {
        $ada = self::pluck('nama')->map(fn ($n) => mb_strtoupper($n))->all();
        foreach (collect($nama)->map(fn ($n) => trim((string) $n))->filter()->unique(fn ($n) => mb_strtoupper($n)) as $n) {
            if (! in_array(mb_strtoupper($n), $ada, true)) {
                self::create(['nama' => $n, 'urutan' => (int) self::max('urutan') + 1]);
            }
        }
    }

    /** @return array<string, int> "tabel.kolom" => jumlah baris yang memakai lokasi ini */
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

    /** Ganti nama lokasi sekaligus semua data yang memakai nama lama; mengembalikan jumlah baris yang ikut berubah. */
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
