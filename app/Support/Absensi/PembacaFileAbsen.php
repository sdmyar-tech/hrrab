<?php

namespace App\Support\Absensi;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/**
 * Membaca file absensi dari flashdisk mesin Solution X105.
 *
 * Format yang didukung:
 *  1. Laporan harian (.xls/.xlsx/.csv) berkolom "AC-No., Name, Date, ..., Clock In, Clock Out, ..."
 *     → hanya AC-No., Name, Date, Clock In, Clock Out yang dipakai; kolom lain (Late/Early/OT) diabaikan.
 *  2. Log mentah (.dat/.txt) "ID  YYYY-MM-DD HH:MM:SS ..." → digabung per ID per tanggal (scan pertama & terakhir).
 */
class PembacaFileAbsen
{
    /** Pola judul kolom (huruf kecil, tanpa spasi ganda). */
    private const KOLOM = [
        'id' => '/^(ac[\s.\-]*no\.?|no\.?\s*id|user\s*id|id|finger\s*id|pin)$/',
        'nama' => '/^(name|nama)$/',
        'tanggal' => '/^(date|tanggal|tgl)$/',
        'masuk' => '/^(clock\s*in|jam\s*masuk|scan\s*masuk|masuk|check\s*in)$/',
        'keluar' => '/^(clock\s*out|jam\s*(keluar|pulang)|scan\s*(keluar|pulang)|keluar|pulang|check\s*out)$/',
    ];

    /**
     * @return array{
     *   format: string,
     *   baris: list<array{baris: int, finger_id: string, nama: ?string, tanggal: string, masuk: ?string, keluar: ?string}>,
     *   tidak_valid: list<array{baris: int, alasan: string, isi: string}>,
     *   kosong: int
     * }
     */
    public static function baca(string $path, string $namaAsli): array
    {
        $ext = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));

        return in_array($ext, ['dat', 'txt'], true) ? self::bacaLogMentah($path) : self::bacaLaporan($path);
    }

    private static function bacaLaporan(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($path)->getActiveSheet();
        } catch (\Throwable $e) {
            throw new RuntimeException('File tidak bisa dibaca sebagai Excel/CSV. Pastikan file asli dari mesin (.xls/.xlsx/.csv).');
        }

        $data = $sheet->toArray(null, true, false, false);
        [$barisJudul, $peta] = self::cariJudul($data);

        $hasil = ['format' => 'Laporan harian (Excel)', 'baris' => [], 'tidak_valid' => [], 'kosong' => 0];
        foreach (array_slice($data, $barisJudul + 1, null, true) as $i => $sel) {
            $nomor = $i + 1; // nomor baris seperti di Excel
            $idMentah = trim((string) ($sel[$peta['id']] ?? ''));
            $tanggalMentah = $sel[$peta['tanggal']] ?? null;

            if ($idMentah === '' && ($tanggalMentah === null || $tanggalMentah === '')) {
                continue; // baris kosong / pemisah
            }

            $id = self::normalisasiId($idMentah);
            $tanggal = self::tanggal($tanggalMentah);
            $isi = implode(' | ', array_filter([$idMentah, $sel[$peta['nama'] ?? -1] ?? null, is_scalar($tanggalMentah) ? (string) $tanggalMentah : null]));

            if ($id === null) {
                $hasil['tidak_valid'][] = ['baris' => $nomor, 'alasan' => 'ID mesin tidak valid', 'isi' => $isi];

                continue;
            }
            if ($tanggal === null) {
                $hasil['tidak_valid'][] = ['baris' => $nomor, 'alasan' => 'Tanggal tidak terbaca', 'isi' => $isi];

                continue;
            }

            $masuk = self::jam($sel[$peta['masuk']] ?? null);
            $keluar = self::jam($sel[$peta['keluar']] ?? null);
            if ($masuk === null && $keluar === null) {
                $hasil['kosong']++; // tidak ada scan hari itu → status ditentukan sistem (libur/cuti/alpa)

                continue;
            }

            $hasil['baris'][] = [
                'baris' => $nomor,
                'finger_id' => $id,
                'nama' => self::nama($sel[$peta['nama'] ?? -1] ?? null),
                'tanggal' => $tanggal,
                'masuk' => $masuk ?? $keluar,              // hanya satu scan → dianggap scan masuk
                'keluar' => $masuk !== null ? $keluar : null,
            ];
        }

        return $hasil;
    }

    /** @return array{0: int, 1: array<string, int>} indeks baris judul & peta kolom */
    private static function cariJudul(array $data): array
    {
        foreach (array_slice($data, 0, 40, true) as $i => $sel) {
            $peta = [];
            foreach ($sel as $kol => $teks) {
                $teks = strtolower(trim(preg_replace('/\s+/', ' ', (string) $teks)));
                foreach (self::KOLOM as $kunci => $pola) {
                    if (! isset($peta[$kunci]) && $teks !== '' && preg_match($pola, $teks)) {
                        $peta[$kunci] = $kol;
                    }
                }
            }
            if (isset($peta['id'], $peta['tanggal']) && (isset($peta['masuk']) || isset($peta['keluar']))) {
                $peta += ['masuk' => $peta['keluar'], 'keluar' => $peta['masuk']];

                return [$i, $peta];
            }
        }

        throw new RuntimeException('Kolom "AC-No.", "Date", "Clock In", "Clock Out" tidak ditemukan. Pastikan file adalah laporan absensi dari mesin.');
    }

    private static function bacaLogMentah(string $path): array
    {
        $hasil = ['format' => 'Log mentah mesin', 'baris' => [], 'tidak_valid' => [], 'kosong' => 0];
        $kelompok = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $i => $teks) {
            if (! preg_match('/^\s*(-?\d+)\s+(\d{4}-\d{2}-\d{2})\s+(\d{1,2}:\d{2})/', $teks, $m)) {
                $hasil['tidak_valid'][] = ['baris' => $i + 1, 'alasan' => 'Format baris tidak dikenal', 'isi' => mb_substr(trim($teks), 0, 80)];

                continue;
            }
            $id = self::normalisasiId($m[1]);
            if ($id === null) {
                $hasil['tidak_valid'][] = ['baris' => $i + 1, 'alasan' => 'ID mesin tidak valid', 'isi' => mb_substr(trim($teks), 0, 80)];

                continue;
            }
            $jam = str_pad($m[3], 5, '0', STR_PAD_LEFT);
            $k = "{$id}|{$m[2]}";
            $kelompok[$k] ??= ['baris' => $i + 1, 'finger_id' => $id, 'nama' => null, 'tanggal' => $m[2], 'scan' => []];
            $kelompok[$k]['scan'][] = $jam;
        }

        foreach ($kelompok as $g) {
            sort($g['scan']);
            $hasil['baris'][] = [
                'baris' => $g['baris'], 'finger_id' => $g['finger_id'], 'nama' => null, 'tanggal' => $g['tanggal'],
                'masuk' => $g['scan'][0],
                'keluar' => count($g['scan']) > 1 ? end($g['scan']) : null,
            ];
        }

        return $hasil;
    }

    /** ID mesin: angka positif; nol di depan dibuang ("0013" → "13"). Data rusak (mis. "-16969335") ditolak. */
    public static function normalisasiId(string $id): ?string
    {
        $id = trim($id);
        if (preg_match('/^\d+(\.0+)?$/', $id)) {
            $id = ltrim(explode('.', $id)[0], '0');

            return $id === '' || strlen($id) > 12 ? null : $id;
        }

        return null;
    }

    private static function nama(mixed $nilai): ?string
    {
        $nama = trim((string) $nilai);

        return $nama !== '' && preg_match('/\p{L}/u', $nama) ? mb_substr($nama, 0, 100) : null;
    }

    /** Tanggal dari angka serial Excel atau teks (dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd). */
    public static function tanggal(mixed $nilai): ?string
    {
        if (is_numeric($nilai) && $nilai > 20000 && $nilai < 80000) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $nilai))->toDateString();
        }

        $teks = trim((string) $nilai);
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd.m.Y', 'Y/m/d'] as $format) {
            $t = \DateTime::createFromFormat('!' . $format, $teks);
            if ($t && $t->format($format) === $teks) {
                return $t->format('Y-m-d');
            }
        }

        return null;
    }

    /** Jam (H:i) dari pecahan hari Excel atau teks "7:30", "07:30:15". */
    public static function jam(mixed $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }
        if (is_numeric($nilai)) {
            $pecahan = fmod((float) $nilai, 1.0);
            $menit = (int) round($pecahan * 1440);

            return $menit >= 1440 ? null : sprintf('%02d:%02d', intdiv($menit, 60), $menit % 60);
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', trim((string) $nilai), $m) && (int) $m[1] < 24 && (int) $m[2] < 60) {
            return sprintf('%02d:%02d', $m[1], $m[2]);
        }

        return null;
    }
}
