<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
$db = \Config\Database::connect('default');
foreach (['forecasting_data', 'activity_logs', 'branches'] as $table) {
    echo $table . "\n";
    foreach ($db->query("SHOW COLUMNS FROM {$table}")->getResultArray() as $column) {
        echo $column['Field'] . ' ' . $column['Type'] . ' nullable=' . $column['Null'] . "\n";
    }
}
