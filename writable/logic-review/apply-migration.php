<?php

// Local CLI deployment helper. Refuse accidental execution from HTTP or without the flag.
if (PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--apply') {
    exit(1);
}
define('ENVIRONMENT', 'production');
define('FCPATH', dirname(__DIR__, 2) . '/public/');
require FCPATH . '../app/Config/Paths.php';
$paths = new \Config\Paths();
require $paths->systemDirectory . '/Boot.php';
\CodeIgniter\Boot::bootConsole($paths);
$db = \Config\Database::connect();
$backup = (new \App\Libraries\DatabaseBackupService($db))->createBackup('before-logic-safeguards');
echo 'Safety backup: ' . $backup['filename'] . PHP_EOL;
if (!service('migrations')->setNamespace('App')->latest()) {
    throw new RuntimeException('Migration failed. Review the database migration log.');
}
foreach (['users' => ['session_version'], 'refund_items' => ['return_condition', 'refund_method', 'refund_event_id']] as $table => $fields) {
    foreach ($fields as $field) {
        if (!$db->fieldExists($field, $table)) {
            throw new RuntimeException('Expected migration field missing: ' . $table . '.' . $field);
        }
    }
}
echo 'Safeguards migration applied and required fields verified.' . PHP_EOL;
