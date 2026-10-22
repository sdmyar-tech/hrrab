<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Penulis file .xlsx minimal (Office Open XML) tanpa dependensi.
 * Tiap sheet: header berwarna, baris judul dibekukan, AutoFilter aktif, lebar kolom otomatis.
 * Semua sel ditulis sebagai teks agar NIK/NIP/No. HP tidak kehilangan angka 0 di depan.
 */
class XlsxWriter
{
    /**
     * @param  list<array{name: string, headers: list<string>, rows: iterable<array>}>  $sheets
     * @return string path file sementara
     */
    public static function tulis(array $sheets): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file Excel.');
        }

        $nama = self::namaSheetUnik(array_column($sheets, 'name'));

        $rentang = [];
        foreach (array_values($sheets) as $i => $sheet) {
            [$xml, $rentang[$i]] = self::worksheet($sheet['headers'], $sheet['rows']);
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $xml);
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', self::relsRoot());
        $zip->addFromString('xl/workbook.xml', self::workbook($nama, $rentang));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::relsWorkbook(count($sheets)));
        $zip->addFromString('xl/styles.xml', self::styles());

        $zip->close();

        return $path;
    }

    /**
     * @param  list<string>  $headers
     * @return array{0: string, 1: string} XML sheet & rentang AutoFilter (mis. A1:F20)
     */
    private static function worksheet(array $headers, iterable $rows): array
    {
        $jumlahKolom = max(count($headers), 1);
        $lebar = array_map(fn ($h) => mb_strlen((string) $h), $headers);
        $isi = '';
        $baris = 1;

        $isi .= self::baris($baris, $headers, 1);

        foreach ($rows as $row) {
            $baris++;
            $nilai = array_values(is_array($row) ? $row : (array) $row);
            foreach ($nilai as $k => $v) {
                $lebar[$k] = max($lebar[$k] ?? 0, min(mb_strlen((string) $v), 60));
            }
            $isi .= self::baris($baris, $nilai, 0);
        }

        $kolom = '';
        for ($k = 0; $k < $jumlahKolom; $k++) {
            $kolom .= '<col min="' . ($k + 1) . '" max="' . ($k + 1) . '" width="' . (max($lebar[$k] ?? 8, 8) + 3) . '" customWidth="1"/>';
        }

        $rentang = 'A1:' . self::hurufKolom($jumlahKolom - 1) . max($baris, 1);

        return ['<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>' . $kolom . '</cols>'
            . '<sheetData>' . $isi . '</sheetData>'
            . '<autoFilter ref="' . $rentang . '"/>'
            . '</worksheet>', $rentang];
    }

    private static function baris(int $nomor, array $nilai, int $gaya): string
    {
        $sel = '';
        foreach (array_values($nilai) as $k => $v) {
            $teks = self::bersihkan($v);
            $sel .= '<c r="' . self::hurufKolom($k) . $nomor . '" t="inlineStr" s="' . $gaya . '"><is><t xml:space="preserve">'
                . htmlspecialchars($teks, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
        }

        return '<row r="' . $nomor . '">' . $sel . '</row>';
    }

    private static function bersihkan(mixed $v): string
    {
        $teks = $v === null || $v === '' ? '-' : (string) $v;

        // Buang karakter kontrol yang tidak valid di XML.
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $teks) ?? '';
    }

    private static function hurufKolom(int $indeks): string
    {
        $huruf = '';
        for ($n = $indeks + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $huruf = chr(65 + ($n - 1) % 26) . $huruf;
        }

        return $huruf;
    }

    /**
     * @param  list<string>  $nama
     * @return list<string>
     */
    private static function namaSheetUnik(array $nama): array
    {
        $hasil = [];
        foreach ($nama as $i => $n) {
            $dasar = mb_substr(trim(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', (string) $n)) ?: 'Sheet' . ($i + 1), 0, 28);
            $kandidat = $dasar;
            for ($j = 2; in_array($kandidat, $hasil, true); $j++) {
                $kandidat = $dasar . ' ' . $j;
            }
            $hasil[] = $kandidat;
        }

        return $hasil;
    }

    private static function contentTypes(int $jumlah): string
    {
        $sheet = '';
        for ($i = 1; $i <= $jumlah; $i++) {
            $sheet .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $sheet . '</Types>';
    }

    private static function relsRoot(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    /**
     * @param  list<string>  $nama
     * @param  list<string>  $rentang
     */
    private static function workbook(array $nama, array $rentang): string
    {
        $sheets = '';
        $filter = '';
        foreach ($nama as $i => $n) {
            $sheets .= '<sheet name="' . htmlspecialchars($n, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
            // Nama tersembunyi _FilterDatabase membuat Excel mengenali rentang AutoFilter.
            // Isi definedName juga teks XML: nama sheet seperti "Mutasi & Rangkap" wajib di-escape, kalau tidak file korup.
            $rujukan = "'" . str_replace("'", "''", $n) . "'!" . self::absolut($rentang[$i] ?? 'A1:A1');
            $filter .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">' . htmlspecialchars($rujukan, ENT_XML1 | ENT_NOQUOTES, 'UTF-8') . '</definedName>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets>'
            . '<definedNames>' . $filter . '</definedNames>'
            . '</workbook>';
    }

    private static function absolut(string $rentang): string
    {
        return preg_replace('/([A-Z]+)(\d+)/', '\$$1\$$2', $rentang);
    }

    private static function relsWorkbook(int $jumlah): string
    {
        $rel = '';
        for ($i = 1; $i <= $jumlah; $i++) {
            $rel .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $rel .= '<Relationship Id="rId' . ($jumlah + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rel . '</Relationships>';
    }

    /** Gaya 0 = isi (border tipis), gaya 1 = header (maroon, teks putih tebal). */
    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF880F12"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD9D9D9"/></left><right style="thin"><color rgb="FFD9D9D9"/></right>'
            . '<top style="thin"><color rgb="FFD9D9D9"/></top><bottom style="thin"><color rgb="FFD9D9D9"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment vertical="top" wrapText="0"/></xf>'
            . '<xf numFmtId="49" fontId="1" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
