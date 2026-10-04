<?php
// Import checks run only in newly-created disposable databases on a loopback server.
// No application database tables are read, changed or dropped.
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
set_exception_handler(static function (Throwable $error): void {
    // SQL payloads and credentials must never appear in test output.
    fwrite(STDERR, get_class($error) . ': ' . $error->getMessage() . "\n");
    exit(1);
});
$config = config('Database')->default;
if (!in_array($config['hostname'], ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('The import check requires a local database server.');
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connection = new mysqli($config['hostname'], $config['username'], $config['password'], '', (int) $config['port']);
$connection->set_charset('utf8mb4');
$manifest = json_decode(file_get_contents(__DIR__ . '/latest-database-manifest.json'), true, flags: JSON_THROW_ON_ERROR);
$created = [];
$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$run = static function (string $sql) use ($connection): void {
    $connection->multi_query($sql);
    do {
        if ($result = $connection->store_result()) $result->free();
        if (!$connection->more_results()) break;
        $connection->next_result();
    } while (true);
};
$originalColumns = [];
$originalSql = file_get_contents('C:/Users/warre/Downloads/u315645644_pharxmacotest (1).sql');
preg_match_all('/CREATE TABLE `([^`]+)` \(([\s\S]*?)\) ENGINE[^;]*;/', $originalSql, $matches, PREG_SET_ORDER);
foreach ($matches as $match) {
    preg_match_all('/^  `([^`]+)`/m', $match[2], $columns);
    $originalColumns[$match[1]] = $columns[1];
}
// Reject unexpected database/server commands before importing the supplied data.
// Inspect only statement starts, avoiding matches inside user-provided text values.
preg_match_all('/^(?:USE|CREATE DATABASE|DROP DATABASE|DROP TABLE|TRUNCATE|GRANT|REVOKE|CREATE (?:TRIGGER|EVENT|PROCEDURE|FUNCTION)|LOAD DATA)\b/mi', $originalSql, $unexpected);
$assert(!$unexpected[0], 'Unexpected server/database commands in the supplied export.');
$snapshot = static function () use ($connection, $originalColumns): array {
    $state = [];
    foreach ($originalColumns as $table => $columns) {
        $result = $connection->query('SELECT `' . implode('`,`', $columns) . '` FROM `' . $table . '` ORDER BY `id`');
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        // New migration history is expected. Hash only the original migration IDs.
        if ($table === 'migrations') $rows = array_values(array_filter($rows, static fn (array $row): bool => (int) $row['id'] <= 8));
        $state[$table] = ['count' => count($rows), 'hash' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
    }
    return $state;
};
$verifySchema = static function () use ($connection, $assert): void {
    foreach (['products', 'branch_products', 'categories', 'suppliers', 'discounts'] as $table) {
        $rows = $connection->query("SHOW COLUMNS FROM `{$table}` WHERE `Field` IN ('deleted_at','is_deleted','permanently_deleted_at','is_permanently_deleted')")->fetch_all(MYSQLI_ASSOC);
        $assert(count($rows) === 4, "Missing deletion fields in {$table}");
        foreach ($rows as $row) {
            if (in_array($row['Field'], ['is_deleted', 'is_permanently_deleted'], true)) {
                $assert($row['Null'] === 'NO' && (string) $row['Default'] === '0', "Invalid flag definition in {$table}");
            }
        }
        $invalid = $connection->query("SELECT COUNT(*) AS n FROM `{$table}` WHERE is_deleted <> IF(deleted_at IS NULL,0,1) OR is_permanently_deleted <> 0")->fetch_assoc();
        $assert((int) $invalid['n'] === 0, "Original deletion state was not retained in {$table}");
    }
    $history = $connection->query("SELECT version,class FROM migrations WHERE version LIKE '2026_10_03_%' AND namespace='App' AND `group`='default' ORDER BY version")->fetch_all(MYSQLI_ASSOC);
    $assert(count($history) === 4, 'Latest migration history missing or duplicated.');
    $classes = ['AddBranchProductTrash', 'AddSoftDeleteFlags', 'AddPermanentDeleteMarkers', 'AddPermanentDeleteFlags'];
    foreach ($history as $index => $row) {
        $assert($row['version'] === '2026_10_03_00000' . ($index + 6), 'Unexpected migration version.');
        $assert($row['class'] === 'App\\Database\\Migrations\\' . $classes[$index], 'Incorrect migration class.');
    }
};
$create = static function (string $label) use ($connection, &$created, $config): string {
    $name = 'phx_upgrade_check_' . $label . '_' . bin2hex(random_bytes(6));
    if (!preg_match('/^phx_upgrade_check_(?:patch|full)_[a-f0-9]{12}$/', $name) || $name === $config['database']) {
        throw new RuntimeException('Disposable database name guard failed.');
    }
    $connection->query("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created[] = $name;
    $connection->select_db($name);
    return $name;
};
try {
    $create('patch');
    $run($originalSql);
    $before = $snapshot();
    $upgrade = file_get_contents(ROOTPATH . $manifest['upgradePath']);
    $run($upgrade);
    $verifySchema();
    $assert($before === $snapshot(), 'Existing data changed during upgrade.');
    $historyCount = (int) $connection->query('SELECT COUNT(*) AS n FROM migrations')->fetch_assoc()['n'];
    $run($upgrade);
    $verifySchema();
    $assert($before === $snapshot(), 'Existing data changed during reimport.');
    $assert($historyCount === (int) $connection->query('SELECT COUNT(*) AS n FROM migrations')->fetch_assoc()['n'], 'Migration records duplicated on reimport.');
    // Verify manual flag recovery survives another import, even with its audit timestamp.
    $id = (int) $connection->query('SELECT MIN(id) AS id FROM branch_products')->fetch_assoc()['id'];
    $assert($id > 0, 'No branch inventory fixture.');
    $connection->query("UPDATE branch_products SET is_deleted=1,deleted_at='2026-10-03 12:00:00',is_permanently_deleted=0,permanently_deleted_at='2026-10-03 12:05:00' WHERE id={$id}");
    $run($upgrade);
    $flag = $connection->query("SELECT is_permanently_deleted,permanently_deleted_at FROM branch_products WHERE id={$id}")->fetch_assoc();
    $assert((int) $flag['is_permanently_deleted'] === 0 && $flag['permanently_deleted_at'] !== null, 'Reimport undid manual permanent flag recovery.');
    $create('full');
    $run(file_get_contents(ROOTPATH . $manifest['exportPath']));
    $verifySchema();
    $assert($before === $snapshot(), 'Updated full export changed the original records.');
    echo json_encode([
        'database_engine' => $connection->server_info,
        'original_tables' => count($before),
        'original_rows_verified' => array_sum(array_column($before, 'count')),
        'new_columns' => count($manifest['missingColumns']),
        'new_migrations' => 4,
        'upgrade' => 'passed',
        'repeat_import' => 'passed; no duplicate migration records',
        'manual_flag_recovery' => 'preserved on repeat import',
        'updated_full_export' => 'passed; all original row hashes match',
    ], JSON_PRETTY_PRINT) . "\n";
} finally {
    foreach (array_reverse($created) as $name) {
        if (!preg_match('/^phx_upgrade_check_(?:patch|full)_[a-f0-9]{12}$/', $name) || $name === $config['database']) {
            throw new RuntimeException('Disposable database cleanup guard failed.');
        }
        $connection->query("DROP DATABASE `{$name}`");
    }
    $connection->close();
}
