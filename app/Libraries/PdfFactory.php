<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

final class PdfFactory
{
    public static function create(): Dompdf
    {
        // Rebuild generated font metrics on this host, outside the vendor tree.
        $root = WRITEPATH . 'cache/pdf-v1/';
        foreach (['fonts', 'tmp'] as $directory) {
            $path = $root . $directory;
            if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
                throw new RuntimeException('Unable to create the PDF runtime directory: ' . $path);
            }
            if (!is_writable($path)) {
                throw new RuntimeException('The PDF runtime directory is not writable: ' . $path);
            }
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setFontDir($root . 'fonts');
        $options->setFontCache($root . 'fonts');
        $options->setTempDir($root . 'tmp');

        return new Dompdf($options);
    }
}
