<?php

namespace App\Support;

use App\Models\HariLibur;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Sinkronisasi otomatis tanggal merah dari API (kalender libur Google → cadangan Nager.Date).
 * Aturan: libur manual tidak pernah disentuh; libur API boleh diperbarui nama/jenisnya tetapi status aktifnya tetap
 * (pengelola bisa menonaktifkan); libur API yang hilang dari sumber utama dihapus (hanya untuk tahun yang ada datanya).
 * Bila sumber utama gagal, sumber cadangan hanya menambah tanggal yang belum ada — tidak mengubah/menghapus.
 */
class SinkronLibur
{
    public const CACHE_TERAKHIR = 'kalender.sinkron-terakhir';

    /**
     * @param  list<int>|null  $tahun  bawaan: tahun ini & tahun depan
     * @return array{sumber: string, tahun: list<int>, tambah: int, ubah: int, hapus: int, dilewati: int, waktu: string}
     */
    public static function jalankan(?array $tahun = null): array
    {
        $tahun = array_values(array_unique($tahun ?? [now()->year, now()->year + 1]));
        [$sumber, $lengkap, $libur] = self::ambil($tahun);
        // Hanya sumber utama (lengkap) yang boleh memperbarui & menghapus; sumber cadangan cukup menambah tanggal yang belum ada.
        $cakupJenis = $lengkap ? ['nasional', 'cuti_bersama'] : [];
        $tahunAda = array_values(array_unique(array_map(fn ($t) => (int) substr($t, 0, 4), array_keys($libur))));

        $hitung = ['tambah' => 0, 'ubah' => 0, 'hapus' => 0, 'dilewati' => 0];
        DB::transaction(function () use ($libur, $tahunAda, $cakupJenis, $lengkap, &$hitung) {
            $ada = HariLibur::where(function ($q) use ($tahunAda) {
                foreach ($tahunAda as $t) {
                    $q->orWhereYear('tanggal', $t);
                }
            })->get()->keyBy(fn (HariLibur $h) => $h->tanggal->toDateString());

            foreach ($libur as $tanggal => $data) {
                $h = $ada->get($tanggal);
                if ($h && ! $h->dariApi()) {
                    $hitung['dilewati']++;
                } elseif ($h && ! $lengkap) {
                    continue;
                } elseif ($h) {
                    $h->fill($data);
                    if ($h->isDirty()) {
                        $h->save();
                        $hitung['ubah']++;
                    }
                } else {
                    HariLibur::create($data + ['tanggal' => $tanggal, 'sumber' => HariLibur::SUMBER_API, 'aktif' => true]);
                    $hitung['tambah']++;
                }
            }

            $ada->filter(fn (HariLibur $h, $tanggal) => $h->dariApi() && ! isset($libur[$tanggal]) && in_array($h->jenis, $cakupJenis, true))
                ->each(function (HariLibur $h) use (&$hitung) {
                    $h->delete();
                    $hitung['hapus']++;
                });
        });
        Cache::forget(HariLibur::CACHE_KEY);

        $hasil = ['sumber' => $sumber, 'tahun' => $tahunAda, ...$hitung, 'waktu' => now()->toDateTimeString()];
        Cache::forever(self::CACHE_TERAKHIR, $hasil);

        return $hasil;
    }

    /** Sinkron otomatis diperlukan bila belum pernah berhasil atau sudah lewat interval (bawaan 30 hari). */
    public static function perluOtomatis(): bool
    {
        $terakhir = Cache::get(self::CACHE_TERAKHIR);

        return ! $terakhir || Carbon::parse($terakhir['waktu'])->lt(now()->subDays((int) config('hrrab.libur_api.interval_hari', 30)));
    }

    /** @return array{sumber: string, tahun: list<int>, tambah: int, ubah: int, hapus: int, dilewati: int, waktu: string}|null */
    public static function terakhir(): ?array
    {
        return Cache::get(self::CACHE_TERAKHIR);
    }

    /**
     * Ambil tanggal merah dari sumber pertama yang berhasil: [nama sumber, sumber utama/lengkap?, tanggal => data].
     * Nager.Date hanya cadangan: datanya lebih sedikit (tanpa cuti bersama, sebagian hari kedua lebaran) dan bernama lain.
     *
     * @param  list<int>  $tahun
     * @return array{0: string, 1: bool, 2: array<string, array{nama: string, jenis: string, keterangan: ?string}>}
     */
    private static function ambil(array $tahun): array
    {
        $galat = [];
        $sumber = [
            'Kalender Google' => [true, fn () => self::dariGoogle($tahun)],
            'Nager.Date' => [false, fn () => self::dariNager($tahun)],
        ];
        foreach ($sumber as $nama => [$lengkap, $ambil]) {
            try {
                if ($hasil = $ambil()) {
                    return [$nama, $lengkap, $hasil];
                }
                $galat[] = "{$nama}: tidak ada data untuk " . implode(', ', $tahun);
            } catch (Throwable $e) {
                $galat[] = "{$nama}: " . $e->getMessage();
            }
        }

        throw new RuntimeException('Sinkron tanggal merah gagal — ' . implode(' | ', $galat));
    }

    /** Kalender libur Indonesia Google (iCal): libur nasional & cuti bersama; "Perayaan" (bukan tanggal merah) diabaikan. */
    private static function dariGoogle(array $tahun): array
    {
        $isi = Http::timeout(20)->get(config('hrrab.libur_api.google_ics'))->throw()->body();
        $isi = preg_replace("/\r?\n[ \t]/", '', $isi);   // gabungkan baris lanjutan iCal
        $hasil = [];

        preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $isi, $acara);
        foreach ($acara[1] as $blok) {
            $ambil = fn (string $kunci) => preg_match('/^' . $kunci . '[^:\r\n]*:(.*)$/m', $blok, $m) ? trim($m[1]) : '';
            $deskripsi = str_replace(['\\n', '\\,', '\\;'], ["\n", ',', ';'], $ambil('DESCRIPTION'));
            if (stripos($deskripsi, 'Perayaan') === 0 || ! preg_match('/^\d{8}$/', $mulai = $ambil('DTSTART'))) {
                continue;
            }
            $nama = str_replace(['\\,', '\\;', '\\n'], [',', ';', ' '], $ambil('SUMMARY'));
            $selesai = preg_match('/^\d{8}$/', $s = $ambil('DTEND')) ? Carbon::createFromFormat('Ymd', $s)->startOfDay() : null;
            $hari = Carbon::createFromFormat('Ymd', $mulai)->startOfDay();
            do {
                if (in_array($hari->year, $tahun, true)) {
                    self::tambah($hasil, $hari->toDateString(), $nama, stripos($deskripsi, 'tentatif') !== false ? 'Tanggal tentatif, dapat berubah.' : null);
                }
                $hari->addDay();
            } while ($selesai && $hari->lt($selesai));
        }

        return $hasil;
    }

    /** Nager.Date: hanya libur nasional (tanpa cuti bersama). */
    private static function dariNager(array $tahun): array
    {
        $hasil = [];
        foreach ($tahun as $t) {
            $data = Http::timeout(20)->get(str_replace('{tahun}', (string) $t, config('hrrab.libur_api.nager')))->throw()->json() ?? [];
            foreach ($data as $libur) {
                if (in_array('Public', $libur['types'] ?? [], true)) {
                    self::tambah($hasil, $libur['date'], $libur['localName'] ?? $libur['name'], null);
                }
            }
        }

        return $hasil;
    }

    /** Satu tanggal bisa berisi beberapa acara (mis. libur + cuti bersama): nama digabung, libur nasional menang atas cuti bersama. */
    private static function tambah(array &$hasil, string $tanggal, string $nama, ?string $keterangan): void
    {
        $jenis = stripos($nama, 'cuti bersama') === 0 ? 'cuti_bersama' : 'nasional';
        if (isset($hasil[$tanggal])) {
            $lama = $hasil[$tanggal];
            $hasil[$tanggal] = [
                'nama' => str_contains($lama['nama'], $nama) ? $lama['nama'] : mb_substr($lama['nama'] . ' / ' . $nama, 0, 150),
                'jenis' => $lama['jenis'] === 'nasional' || $jenis === 'nasional' ? 'nasional' : 'cuti_bersama',
                'keterangan' => $lama['keterangan'] ?? $keterangan,
            ];

            return;
        }
        $hasil[$tanggal] = ['nama' => mb_substr($nama, 0, 150), 'jenis' => $jenis, 'keterangan' => $keterangan];
    }
}
