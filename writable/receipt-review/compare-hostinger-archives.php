<?php
// Read archive entries without extracting or executing them.
$workspace = dirname(__DIR__, 2);
$inputs = [
    ['archive' => 'C:/Users/warre/Downloads/app (1).zip', 'local_root' => $workspace . '/app', 'prefix' => 'app'],
    ['archive' => 'C:/Users/warre/Downloads/assets.zip', 'local_root' => $workspace . '/public/assets', 'prefix' => 'public/assets'],
];
$report = ['checked_at' => date(DATE_ATOM), 'comparison' => 'archive bytes against current workspace files', 'archives' => []];
foreach ($inputs as $input) {
    $zip = new ZipArchive();
    if ($zip->open($input['archive']) !== true) throw new RuntimeException('Cannot read archive.');
    $result = ['archive' => basename($input['archive']), 'local_root' => $input['prefix'], 'archive_sha256' => hash_file('sha256', $input['archive']), 'archive_files' => 0, 'identical' => [], 'line_endings_only' => [], 'different' => [], 'missing_on_hostinger' => [], 'hostinger_only' => []];
    $seen = [];
    try {
        for ($index = 0; $index < $zip->numFiles; ++$index) {
            $stat = $zip->statIndex($index);
            $relative = str_replace('\\', '/', $stat['name']);
            if (str_ends_with($relative, '/')) continue;
            if (str_starts_with($relative, '/') || preg_match('~(?:^|/)\.\.(?:/|$)|^[a-z]:~i', $relative)) throw new RuntimeException('Unsafe archive path.');
            if (isset($seen[$relative])) throw new RuntimeException('Duplicate archive path.');
            $seen[$relative] = true;
            ++$result['archive_files'];
            $path = $input['prefix'] . '/' . $relative;
            $localPath = $input['local_root'] . '/' . $relative;
            if (!is_file($localPath)) {
                $result['hostinger_only'][] = $path;
                continue;
            }
            $remote = $zip->getFromIndex($index);
            if ($remote === false) throw new RuntimeException('Cannot read archive file.');
            $local = file_get_contents($localPath);
            if ($remote === $local) {
                $result['identical'][] = $path;
            } elseif (preg_match('/\.(?:php|js|css|html|json|svg|md|txt)$/i', $relative)
                && str_replace(["\r\n", "\r"], "\n", $remote) === str_replace(["\r\n", "\r"], "\n", $local)) {
                $result['line_endings_only'][] = $path;
            } else {
                $result['different'][] = ['path' => $path, 'hostinger_bytes' => strlen($remote), 'local_bytes' => strlen($local), 'hostinger_sha256' => hash('sha256', $remote), 'local_sha256' => hash('sha256', $local)];
            }
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($input['local_root'], FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($input['local_root']) + 1));
            if (!isset($seen[$relative])) $result['missing_on_hostinger'][] = $input['prefix'] . '/' . $relative;
        }
        foreach (['identical', 'line_endings_only', 'missing_on_hostinger', 'hostinger_only'] as $key) sort($result[$key]);
        usort($result['different'], static fn (array $a, array $b): int => strcmp($a['path'], $b['path']));
        $report['archives'][] = $result;
    } finally {
        $zip->close();
    }
}
file_put_contents(__DIR__ . '/hostinger-files-comparison.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
foreach ($report['archives'] as $archive) {
    echo json_encode([
        'archive' => $archive['archive'], 'files' => $archive['archive_files'],
        'identical' => count($archive['identical']), 'line_endings_only' => count($archive['line_endings_only']),
        'different' => array_column($archive['different'], 'path'),
        'missing_on_hostinger' => $archive['missing_on_hostinger'], 'hostinger_only' => $archive['hostinger_only'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}
