<?php

use App\Libraries\CsvExport;
use CodeIgniter\Test\CIUnitTestCase;

final class CsvExportTest extends CIUnitTestCase
{
    public function testNumericFormattingAndFormulaProtection(): void
    {
        $stream = fopen('php://temp', 'w+');
        CsvExport::writeRow($stream, [1200.5, -25.0, 4, '=SUM(A1:A2)', '  +cmd'], 8);
        rewind($stream);
        $bytes = stream_get_contents($stream);
        $this->assertStringEndsWith("\r\n", $bytes);
        rewind($stream);
        $this->assertSame(['1200.50', '-25.00', '4', "'=SUM(A1:A2)", "'  +cmd", '', '', ''], fgetcsv($stream, null, ',', '"', ''));
        fclose($stream);
    }

    public function testQuotesCommasUnicodeAndMultilineTextRoundTrip(): void
    {
        $stream = fopen('php://temp', 'w+');
        $row = ['C:\\Stock\\', 'Product "A", 500mg', "First\nSecond", 'Peña - ₱', '000123'];
        CsvExport::writeRow($stream, $row, 5);
        rewind($stream);
        $this->assertSame($row, fgetcsv($stream, null, ',', '"', ''));
        fclose($stream);
    }
}
