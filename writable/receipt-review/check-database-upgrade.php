<?php
// Uses synthetic records in a disposable database; never changes the app database.
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$settings = config('Database')->default;
$connection = new mysqli($settings['hostname'], $settings['username'], $settings['password'], '', (int) $settings['port']);
$scratch = 'pharxmaco_upgrade_check_' . bin2hex(random_bytes(6));
$source = $settings['database'];
if (!preg_match('/^pharxmaco_upgrade_check_[a-f0-9]{12}$/', $scratch) || $scratch === $source) {
    throw new RuntimeException('Unsafe test database name.');
}
$sql = file_get_contents(ROOTPATH . 'database/upgrades/2026-09-17-latest-changes.sql');
$verificationSql = file_get_contents(ROOTPATH . 'database/upgrades/2026-09-17-verify-only.sql');
function verify(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function importUpgrade(mysqli $connection, string $sql): array
{
    $results = [];
    $connection->multi_query($sql);
    do {
        if ($result = $connection->store_result()) {
            $results[] = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
        if (!$connection->more_results()) break;
    } while ($connection->next_result());
    return $results;
}
function fields(mysqli $connection, string $schema): array
{
    $schema = $connection->real_escape_string($schema);
    return $connection->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND ((TABLE_NAME = 'users' AND COLUMN_NAME = 'session_version') OR (TABLE_NAME = 'refund_items' AND COLUMN_NAME IN ('return_condition','refund_method','refund_event_id'))) ORDER BY TABLE_NAME,COLUMN_NAME")->fetch_all(MYSQLI_ASSOC);
}
$created = false;
try {
    $connection->query("CREATE DATABASE `$scratch` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $created = true;
    $connection->select_db($scratch);
    $connection->query('CREATE TABLE users (id INT UNSIGNED PRIMARY KEY, username VARCHAR(100) NOT NULL)');
    $connection->query('CREATE TABLE refund_items (id INT UNSIGNED PRIMARY KEY, refund_subtotal DECIMAL(12,2) NOT NULL)');
    $quotedSource = str_replace('`', '``', $source);
    $connection->query("CREATE TABLE migrations LIKE `$quotedSource`.`migrations`");
    $connection->query("INSERT INTO migrations (`version`,`class`,`group`,`namespace`,`time`,`batch`) VALUES ('2026_08_20_000004','ExistingMigration','default','App',1,2)");
    $connection->query("INSERT INTO users VALUES (1,'existing-user')");
    $connection->query('INSERT INTO refund_items VALUES (1,112.62)');

    $missing = importUpgrade($connection, $verificationSql);
    verify(count($missing) === 4 && $missing[1] === [] && $missing[2] === [] && $missing[3] === [], 'Verification must expose missing columns and migration.');

    importUpgrade($connection, $sql);
    verify(fields($connection, $scratch) === fields($connection, $source), 'Upgrade fields differ from the migrated application database.');
    verify($connection->query('SELECT username FROM users WHERE id=1')->fetch_row()[0] === 'existing-user', 'Existing user changed.');
    $refund = $connection->query('SELECT * FROM refund_items WHERE id=1')->fetch_assoc();
    verify($refund['refund_subtotal'] === '112.62' && $refund['return_condition'] === 'resellable' && $refund['refund_method'] === null && $refund['refund_event_id'] === null, 'Historical refund values or defaults incorrect.');
    $history = $connection->query("SELECT * FROM migrations WHERE version='2026_09_16_000005'")->fetch_assoc();
    verify($history['class'] === 'App\\Database\\Migrations\\AddLogicSafeguards' && (int) $history['batch'] === 3, 'Migration history does not match CodeIgniter.');

    $connection->query("UPDATE users SET session_version='existing-session' WHERE id=1");
    $connection->query("UPDATE refund_items SET return_condition='damaged',refund_method='gcash',refund_event_id='existing-event' WHERE id=1");
    $before = $connection->query('SELECT * FROM refund_items')->fetch_all(MYSQLI_ASSOC);
    importUpgrade($connection, $sql);
    verify($before === $connection->query('SELECT * FROM refund_items')->fetch_all(MYSQLI_ASSOC), 'Re-import changed refund data.');
    verify($connection->query('SELECT session_version FROM users WHERE id=1')->fetch_row()[0] === 'existing-session', 'Re-import changed session data.');
    verify((int) $connection->query('SELECT COUNT(*) FROM migrations')->fetch_row()[0] === 2, 'Re-import duplicated history.');
    verify($history === $connection->query("SELECT * FROM migrations WHERE version='2026_09_16_000005'")->fetch_assoc(), 'Re-import changed original history.');

    // Simulate a partially applied upgrade, with some columns already present.
    $connection->query('ALTER TABLE refund_items DROP COLUMN refund_event_id');
    $connection->query("DELETE FROM migrations WHERE version='2026_09_16_000005'");
    $connection->query("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_BACKSLASH_ESCAPES'");
    importUpgrade($connection, $sql);
    verify(fields($connection, $scratch) === fields($connection, $source), 'Partial upgrade did not complete.');
    verify($connection->query('SELECT return_condition FROM refund_items WHERE id=1')->fetch_row()[0] === 'damaged', 'Partial upgrade changed existing condition.');
    verify($connection->query("SELECT class FROM migrations WHERE version='2026_09_16_000005'")->fetch_row()[0] === 'App\\Database\\Migrations\\AddLogicSafeguards', 'Namespace escaped incorrectly.');
    $snapshot = [];
    foreach (['users', 'refund_items', 'migrations'] as $table) {
        $snapshot[$table] = $connection->query("SELECT * FROM `$table` ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    }
    $verified = importUpgrade($connection, $verificationSql);
    verify(count($verified) === 4 && $verified[0][0]['selected_application_database'] === $scratch, 'Verification selected the wrong database.');
    verify(count($verified[1]) === 1 && count($verified[2]) === 3 && count($verified[3]) === 1, 'Verification result counts incorrect.');
    foreach ($snapshot as $table => $rows) {
        verify($connection->query("SELECT * FROM `$table` ORDER BY id")->fetch_all(MYSQLI_ASSOC) === $rows, 'Read-only verification changed records.');
    }
    verify($connection->query('SELECT DATABASE()')->fetch_row()[0] === $scratch, 'Verification changed selected database.');
    echo "PASS: September 17 SQL upgrade; records preserved; repeat and partial imports; migration history; strict SQL mode; read-only verification.\n";
} finally {
    if ($created) $connection->query("DROP DATABASE `$scratch`");
    $connection->close();
}
