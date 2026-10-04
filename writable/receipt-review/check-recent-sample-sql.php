<?php
// Executes the SQL against connection-local TEMPORARY sales/item/refund/log tables.
// Application tables with those names are shadowed, never written by this check.
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
set_exception_handler(static function (Throwable $error): void { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); });
$db = \Config\Database::connect('default');
$stockBefore = $db->query('SELECT id, stock FROM branch_products ORDER BY id')->getResultArray();
$shadowed = [];
$run = static function (string $file) use ($db): array {
    $sql = file_get_contents($file);
    $connection = $db->connID;
    $output = [];
    if (in_array('--sequential', $_SERVER['argv'] ?? [], true)) {
        // Split this fixture's semicolon-terminated SQL, preserving quoted text
        // such as the semicolon inside its synthetic-history note.
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        preg_match_all("/(?:[^;']|'(?:''|[^'])*')+;/s", $sql, $statements);
        foreach ($statements[0] as $statement) {
            $result = $connection->query(trim($statement));
            if ($result === false) throw new RuntimeException($connection->error);
            if ($result instanceof mysqli_result) {
                $output[] = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
            }
        }
        return $output;
    }
    if (!$connection->multi_query($sql)) throw new RuntimeException($connection->error);
    do {
        if ($result = $connection->store_result()) {
            $output[] = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
        if (!$connection->more_results()) break;
        if (!$connection->next_result()) throw new RuntimeException($connection->error);
    } while (true);
    return $output;
};
$assert = static function ($condition, string $message) {
    if (!$condition) throw new RuntimeException($message);
};
try {
    foreach (['sales', 'sale_items', 'refund_items', 'stock_logs'] as $table) {
        $shape = 'phx_test_shape_' . $table;
        $db->query("CREATE TEMPORARY TABLE `{$shape}` LIKE `{$table}`");
        $shadowed[] = $shape;
        $db->query("CREATE TEMPORARY TABLE `{$table}` LIKE `{$shape}`");
        $shadowed[] = $table;
    }
    $file = ROOTPATH . 'database/samples/recent-forecast-sales.sql';
    $first = $run($file);
    $count = $db->table('sales')->countAllResults();
    $assert($count > 0, 'No demo candidates in local database.');
    $assert($count === $db->table('sale_items')->countAllResults(), 'Sale/item count mismatch.');
    $run($file);
    $assert($count === $db->table('sales')->countAllResults(), 'Duplicate sales on reimport.');
    $assert($count === $db->table('sale_items')->countAllResults(), 'Duplicate items on reimport.');
    $bad = $db->query('SELECT COUNT(*) AS n FROM sales s JOIN sale_items si ON si.sale_id=s.id WHERE s.final_total <> si.subtotal OR s.total_amount <> si.subtotal OR si.profit <> ROUND((si.price-si.cost_price_at_sale)*si.quantity,2)')->getRowArray();
    $assert((int) $bad['n'] === 0, 'Totals are inconsistent.');
    $reflection = new ReflectionClass(\App\Libraries\ReorderForecastService::class);
    $service = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('db')->setValue($service, $db);
    $dates = $db->query('SELECT MIN(DATE(sale_date)) AS first_day, MAX(DATE(sale_date)) AS last_day FROM sales')->getRowArray();
    $rows = $service->generate($dates['first_day'], $dates['last_day'], '', .3, .2, false, null);
    $sampled = $db->query('SELECT DISTINCT s.branch_id, si.product_id FROM sales s JOIN sale_items si ON si.sale_id=s.id')->getResultArray();
    $keys = array_fill_keys(array_map(static fn ($row) => $row['branch_id'] . ':' . $row['product_id'], $sampled), true);
    $ready = 0;
    foreach ($rows as $row) {
        if (!isset($keys[$row['branch_id'] . ':' . $row['product_id']])) continue;
        $assert($row['days_left'] !== null, 'Sampled item still has no demand estimate.');
        $assert($row['suggested_qty'] > 0, 'Expected a positive sample reorder suggestion.');
        ++$ready;
    }
    $assert($ready === count($keys), 'Missing product forecasts.');
    $branchId = $sampled[0]['branch_id'];
    $db->table('sales')->insert(['invoice_no'=>'DEMO-FC-CONTROL','branch_id'=>$branchId,'notes'=>'UNRELATED CONTROL FIXTURE','status'=>'void']);
    $controlId = $db->insertID();
    $run(ROOTPATH . 'database/samples/remove-recent-forecast-sales.sql');
    $assert($db->table('sales')->countAllResults() === 1, 'Cleanup left demo sales or deleted control.');
    $assert((int) $db->table('sales')->get()->getRowArray()['id'] === (int) $controlId, 'Control sale was changed.');
    $assert($db->table('sale_items')->countAllResults() === 0, 'Cleanup left demo items.');
    $assert($stockBefore === $db->query('SELECT id, stock FROM branch_products ORDER BY id')->getResultArray(), 'Stock changed.');
    echo json_encode(['temporary_demo_sales' => $count, 'forecast_ready_products' => $ready, 'dates' => $dates,
        'reimport' => 'no duplicates', 'cleanup' => 'passed; unrelated control preserved', 'stock' => 'unchanged']) . "\n";
} finally {
    foreach (array_reverse($shadowed) as $table) $db->query("DROP TEMPORARY TABLE IF EXISTS `{$table}`");
}
