<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Tanggal merah kalender pegawai. Daftar yang aktif menggantikan config('hrrab.hari_libur') saat aplikasi berjalan
 * (AppServiceProvider), sehingga Kalender (hari kerja), Absensi (status libur), cuti, dan jadwal rekrutmen ikut.
 */
class HariLibur extends Model
{
    use Concerns\DicatatLog;

    public const CACHE_KEY = 'kalender.hari-libur';

    public const SUMBER_API = 'api';

    public const SUMBER_MANUAL = 'manual';

    public const JENIS = [
        'nasional' => 'Libur nasional',
        'cuti_bersama' => 'Cuti bersama',
        'yayasan' => 'Libur yayasan',
    ];

    protected $fillable = ['tanggal', 'nama', 'jenis', 'sumber', 'aktif', 'keterangan', 'dibuat_oleh'];

    protected $casts = [
        'tanggal' => 'date',
        'aktif' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** @return list<string> tanggal (Y-m-d) tanggal merah yang aktif; fallback ke config bila tabel belum ada */
    public static function daftarTanggal(): array
    {
        static $tabelAda = null;
        $tabelAda ??= Schema::hasTable('hari_liburs');

        if (! $tabelAda) {
            return config('hrrab.hari_libur', []);
        }

        return Cache::rememberForever(self::CACHE_KEY, fn () => self::where('aktif', true)->orderBy('tanggal')
            ->get(['tanggal'])->map(fn (self $h) => $h->tanggal->toDateString())->all());
    }

    public function jenisLabel(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    public function dariApi(): bool
    {
        return $this->sumber === self::SUMBER_API;
    }
}
