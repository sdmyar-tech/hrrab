<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Ekspor data ke file .xlsx (header beku + AutoFilter). Urutan baris mengikuti query: data terbaru paling bawah. */
class ExcelExporter
{
    public static function download(string $filename, array $headers, iterable $rows, string $namaSheet = 'Data'): BinaryFileResponse
    {
        return self::downloadMultiSheet($filename, [['name' => $namaSheet, 'headers' => $headers, 'rows' => $rows]]);
    }

    /** @param list<array{name: string, headers: list<string>, rows: iterable<array>}> $sheets */
    public static function downloadMultiSheet(string $filename, array $sheets): BinaryFileResponse
    {
        $filename = preg_replace('/\.xlsx?$/i', '', $filename) . '.xlsx';

        return response()->download(XlsxWriter::tulis($sheets), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /** Nama file standar: <bagian>-<tanggal-jam>.xlsx */
    public static function namaFile(string $bagian): string
    {
        return $bagian . '-' . now()->format('Ymd-His') . '.xlsx';
    }
}
