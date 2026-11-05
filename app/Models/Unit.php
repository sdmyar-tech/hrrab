<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public const CACHE_UNIT_ORG = 'master.bagian-unit-organisasi';

    /** Kolom nama unit di tabel lain yang ikut diperbarui saat unit diganti nama. */
    private const KOLOM_UNIT = ['unit_kerja', 'unit_kerja_tujuan'];

    protected $fillable = ['nama', 'urutan', 'keterangan', 'unit_organisasi_id'];

    protected $casts = ['urutan' => 'integer'];

    protected static function booted(): void
    {
        $lupakan = function () {
            Cache::forget(self::CACHE_KEY);
            Cache::forget(self::CACHE_UNIT_ORG);
            Cache::forget(Jabatan::CACHE_PER_UNIT);
            Cache::forget(Jabatan::CACHE_LOKASI);
        };
        static::saved($lupakan);
        static::deleted($lupakan);
    }

    public function jabatans(): BelongsToMany
    {
        return $this->belongsToMany(Jabatan::class)->orderBy('nama');
    }

    /** Unit (Master Unit) yang membawahi bagian ini. */
    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class);
    }

    /**
     * Unit tiap bagian (Master Unit → bagian yang dibawahi): dipakai mengisi otomatis unit pegawai dari bagiannya.
     *
     * @return array<string, string> nama bagian => nama unit
     */
    public static function unitOrganisasiPerBagian(): array
    {
        if (! Schema::hasColumn('units', 'unit_organisasi_id')) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_UNIT_ORG, fn () => DB::table('units')
            ->join('unit_organisasis', 'unit_organisasis.id', '=', 'units.unit_organisasi_id')
            // Alias wajib: kedua tabel punya kolom "nama".
            ->get(['units.nama as bagian', 'unit_organisasis.nama as unit'])->pluck('unit', 'bagian')->all());
    }

    /** Nama unit yang membawahi $bagian, atau null bila bagian belum dimasukkan ke unit mana pun. */
    public static function unitOrganisasiDari(?string $bagian): ?string
    {
        return self::unitOrganisasiPerBagian()[(string) $bagian] ?? null;
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
            throw ValidationException::withMessages(['nama' => "Bagian {$lama} dipakai di pengaturan sistem ({$dipakaiConfig}). Nama bagian ini tidak bisa diganti dari aplikasi."]);
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
            in_array($nama, config('kepegawaian.unit_tanpa_pengajuan', []), true) ? 'bagian tanpa pengajuan' : null,
            in_array($nama, config('kepegawaian.unit_langsung_pembina', []), true) ? 'bagian langsung Pembina' : null,
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
