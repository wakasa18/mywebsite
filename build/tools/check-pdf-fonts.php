<?php
// Run with `php tools/check-pdf-fonts.php` from the application directory.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR;
require $root . 'vendor/autoload.php';
$manifest = json_decode(file_get_contents(__DIR__ . '/pdf-font-checksums.json'), true, 512, JSON_THROW_ON_ERROR);
$failures = 0;
foreach ($manifest as $relative => $expected) {
    $path = $root . $relative;
    if (!is_readable($path) || hash_file('sha256', $path) !== $expected) {
        fwrite(STDERR, "FAIL: missing, unreadable or changed font asset: $relative\n");
        $failures++;
        continue;
    }
    if (str_ends_with($relative, '.ttf')) {
        $font = null;
        try {
            $font = \FontLib\Font::load($path);
            $font->parse();
            if (!is_array($font->getData('cmap', 'subtables'))) {
                throw new RuntimeException('Missing cmap table');
            }
            $map = $font->getUnicodeCharMap();
            if (!is_array($map) || !isset($map[0x20B1])) {
                throw new RuntimeException('Missing Unicode peso mapping');
            }
        } catch (Throwable $error) {
            fwrite(STDERR, "FAIL: $relative: {$error->getMessage()}\n");
            $failures++;
        } finally {
            $font?->close();
        }
    }
}
if ($failures) {
    fwrite(STDERR, "$failures asset(s) failed. Re-extract the repair archive in the correct application directory.\n");
    exit(1);
}
echo 'PASS: all ' . count($manifest) . " bundled font assets match; TrueType fonts have Unicode character maps.\n";
