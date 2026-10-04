<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
try {
    $db = \Config\Database::connect('default');
    $column = $db->query("SHOW COLUMNS FROM forecasting_data WHERE Field = 'method_used'")->getRowArray();
    echo json_encode(['method_used_type' => $column['Type'] ?? null]) . PHP_EOL;
    $tables = $db->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('products', 'forecasting_data')")->getResultArray();
    echo json_encode($tables) . PHP_EOL;
} catch (\Throwable $exception) {
    echo 'Schema check unavailable: ' . get_class($exception) . PHP_EOL;
    exit(1);
}
