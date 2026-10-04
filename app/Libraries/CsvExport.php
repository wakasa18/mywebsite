<?php

namespace App\Libraries;

/** Shared Excel-friendly CSV serialization; callers write the UTF-8 BOM once. */
final class CsvExport
{
    public static function writeRow($output, array $row, int $columns): void
    {
        $row = array_map(static function ($value) {
            // Numeric values stay numeric in Excel, including negative amounts.
            if (is_float($value)) {
                return number_format($value, 2, '.', '');
            }
            if (is_string($value)) {
                $value = str_replace(["\r\n", "\r"], "\n", $value);
                if (preg_match('/^[\s]*[=+\-@]/u', $value)) {
                    return "'" . $value;
                }
            }
            return $value;
        }, $row);
        // Explicit escaping and CRLF records preserve quotes, commas and newlines
        // across standard CSV readers without PHP's legacy backslash escaping.
        if (fputcsv($output, array_pad($row, $columns, ''), ',', '"', '', "\r\n") === false) {
            throw new \RuntimeException('Unable to write the CSV export.');
        }
    }
}
