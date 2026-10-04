<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
$db = \Config\Database::connect('default');
foreach (['sales', 'sale_items', 'users', 'branch_products', 'stock_logs', 'refund_items'] as $table) {
    echo $table . "\n";
    foreach ($db->query("SHOW COLUMNS FROM {$table}")->getResultArray() as $c) {
        echo $c['Field'] . ' ' . $c['Type'] . ' nullable=' . $c['Null'] . ' default=' . ($c['Default'] ?? 'NULL') . "\n";
    }
}
echo 'Server: ' . $db->query('SELECT VERSION() AS v')->getRowArray()['v'] . "\n";
