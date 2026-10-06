<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** Ekspor data sebagai file .xls berisi tabel HTML asli. */
class ExcelExporter
{
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            echo "\xEF\xBB\xBF";

            echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-family:Calibri,Arial,sans-serif;font-size:11pt;">';

            echo '<thead><tr>';
            foreach ($headers as $header) {
                echo '<th style="background-color:#880f12;color:#ffffff;font-weight:bold;white-space:nowrap;text-align:left;">'
                    . e($header) . '</th>';
            }
            echo '</tr></thead><tbody>';

            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td style="mso-number-format:\'\@\';">' . e((string) ($cell ?? '-')) . '</td>';
                }
                echo '</tr>';
            }

            echo '</tbody></table>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public static function downloadMultiSheet(string $filename, array $sheets): StreamedResponse
    {
        return response()->streamDownload(function () use ($sheets) {
            echo "\xEF\xBB\xBF";

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta charset="UTF-8">';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>';
            foreach ($sheets as $sheet) {
                echo '<x:ExcelWorksheet><x:Name>' . e($sheet['name']) . '</x:Name>'
                    . '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
            }
            echo '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '</head><body>';

            foreach ($sheets as $sheet) {
                echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-family:Calibri,Arial,sans-serif;font-size:11pt;">';

                echo '<thead><tr>';
                foreach ($sheet['headers'] as $header) {
                    echo '<th style="background-color:#880f12;color:#ffffff;font-weight:bold;white-space:nowrap;text-align:left;">'
                        . e($header) . '</th>';
                }
                echo '</tr></thead><tbody>';

                foreach ($sheet['rows'] as $row) {
                    echo '<tr>';
                    foreach ($row as $cell) {
                        echo '<td style="mso-number-format:\'\@\';">' . e((string) ($cell ?? '-')) . '</td>';
                    }
                    echo '</tr>';
                }

                echo '</tbody></table>';
            }

            echo '</body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }
}
