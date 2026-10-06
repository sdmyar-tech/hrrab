<?php

namespace App\Support\Import;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/** Pembaca .xlsx minimal (ext-zip + SimpleXML), tanpa dependensi tambahan. */
class XlsxReader
{
    /**
     * @return array<string, array<int, array<string, string|null>>> [namaSheet => [nomorBaris => [kolom => nilai]]]
     */
    public static function baca(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException("Tidak bisa membuka file Excel: {$path}");
        }

        $shared = self::sharedStrings($zip);
        $dateStyles = self::dateStyles($zip);

        $workbook = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $rels = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $target = [];
        foreach ($rels->Relationship as $rel) {
            $target[(string) $rel['Id']] = 'xl/' . ltrim(str_replace('/xl/', '', (string) $rel['Target']), '/');
        }

        $hasil = [];
        foreach ($workbook->sheets->sheet as $sheet) {
            $rid = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $hasil[(string) $sheet['name']] = self::bacaSheet(
                simplexml_load_string($zip->getFromName($target[$rid])),
                $shared,
                $dateStyles
            );
        }

        $zip->close();

        return $hasil;
    }

    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $shared = [];
        foreach (simplexml_load_string($xml)->si as $si) {
            if (isset($si->t)) {
                $shared[] = (string) $si->t;
                continue;
            }
            $teks = '';
            foreach ($si->r as $run) {
                $teks .= (string) $run->t;
            }
            $shared[] = $teks;
        }

        return $shared;
    }

    private static function dateStyles(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/styles.xml');
        if ($xml === false) {
            return [];
        }

        $styles = simplexml_load_string($xml);
        $custom = [];
        foreach ($styles->numFmts->numFmt ?? [] as $fmt) {
            $custom[(int) $fmt['numFmtId']] = (string) $fmt['formatCode'];
        }

        $hasil = [];
        foreach ($styles->cellXfs->xf ?? [] as $i => $xf) {
            $id = (int) $xf['numFmtId'];
            $pola = preg_replace('/\[[^\]]*\]|"[^"]*"/', '', $custom[$id] ?? '');
            $hasil[] = ($id >= 14 && $id <= 22) || ($pola !== '' && preg_match('/[dmy]{2,}/i', $pola));
        }

        return $hasil;
    }

    private static function bacaSheet(SimpleXMLElement $sheet, array $shared, array $dateStyles): array
    {
        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $nomor = (int) $row['r'];
            foreach ($row->c as $cell) {
                $kolom = preg_replace('/\d+/', '', (string) $cell['r']);
                $tipe = (string) $cell['t'];
                $nilai = isset($cell->v) ? (string) $cell->v : (isset($cell->is->t) ? (string) $cell->is->t : null);

                if ($tipe === 's' && $nilai !== null) {
                    $nilai = $shared[(int) $nilai] ?? $nilai;
                } elseif ($nilai !== null && $tipe === '' && is_numeric($nilai) && ($dateStyles[(int) $cell['s']] ?? false)) {
                    $nilai = gmdate('Y-m-d', (int) round(((float) $nilai - 25569) * 86400));
                }

                $rows[$nomor][$kolom] = is_string($nilai) ? trim($nilai) : $nilai;
            }
        }

        return $rows;
    }
}
