<?php

namespace Tests\Unit;

use App\Libraries\PdfFactory;
use CodeIgniter\Test\CIUnitTestCase;
use FontLib\Font;

final class PdfFactoryTest extends CIUnitTestCase
{
    public function testBundledReportFontsContainUnicodeMappings(): void
    {
        $root = ROOTPATH . 'vendor/dompdf/dompdf/lib/fonts/';
        foreach (['DejaVuSans', 'DejaVuSans-Bold', 'DejaVuSans-Oblique', 'DejaVuSans-BoldOblique'] as $name) {
            $font = Font::load($root . $name . '.ttf');
            $this->assertNotNull($font);
            try {
                $font->parse();
                $this->assertIsArray($font->getData('cmap', 'subtables'), $name . ' character map is invalid');
                $map = $font->getUnicodeCharMap();
                $this->assertIsArray($map);
                $this->assertArrayHasKey(0x20B1, $map, $name . ' must support the peso symbol');
            } finally {
                $font->close();
            }
        }
    }

    public function testPdfUsesWritableRuntimeAndEmbedsUnicodeFonts(): void
    {
        $pdf = PdfFactory::create();
        $options = $pdf->getOptions();
        foreach ([$options->getFontDir(), $options->getFontCache(), $options->getTempDir()] as $path) {
            $this->assertStringStartsWith(WRITEPATH, $path);
            $this->assertDirectoryIsWritable($path);
        }
        $this->assertFalse($options->getIsRemoteEnabled());
        $pdf->loadHtml('<meta charset="UTF-8"><p>Expiry report: ₱123.45 — Muñoz</p><p><b>Bold</b> <i>Italic</i> <b><i>Both</i></b></p>', 'UTF-8');
        $pdf->render();
        $bytes = $pdf->output();
        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertStringContainsString('/FontFile2', $bytes);
        $this->assertStringContainsString('/ToUnicode', $bytes);
    }
}
