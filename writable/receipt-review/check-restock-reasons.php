<?php
// Read-only diagnosis: no forecasts or application records are saved.
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
$db = \Config\Database::connect('default');
$summary = $db->query("SELECT MIN(sale_date) AS first_sale, MAX(sale_date) AS last_sale, COUNT(*) AS sales FROM sales WHERE status IN ('completed','partially_refunded')")->getRowArray();
echo json_encode(['eligible_sales' => $summary]) . "\n";
$reflection = new ReflectionClass(\App\Libraries\ReorderForecastService::class);
$service = $reflection->newInstanceWithoutConstructor();
$reflection->getProperty('db')->setValue($service, $db);
$today = date('Y-m-d');
$last = substr((string) ($summary['last_sale'] ?? $today), 0, 10);
$windows = [
    ['current_30_days', date('Y-m-d', strtotime($today . ' -29 days')), $today],
    ['ending_at_last_sale', date('Y-m-d', strtotime($last . ' -29 days')), $last],
    ['current_24_months', date('Y-m-d', strtotime($today . ' -24 months')), $today],
];
foreach ($windows as [$name, $from, $to]) {
    $rows = $service->generate($from, $to, '', .3, .2, false, null);
    $history = $db->query("SELECT si.product_id, s.branch_id, SUM(GREATEST(0, si.quantity - COALESCE(r.qty,0))) AS units, MAX(s.sale_date) AS last_sale FROM sale_items si JOIN sales s ON s.id=si.sale_id LEFT JOIN (SELECT sale_item_id,SUM(quantity_refunded) AS qty FROM refund_items GROUP BY sale_item_id) r ON r.sale_item_id=si.id WHERE s.status IN ('completed','partially_refunded') AND s.sale_date >= ? AND s.sale_date <= ? GROUP BY si.product_id,s.branch_id", [$from . ' 00:00:00', $to . ' 23:59:59'])->getResultArray();
    $lookup = [];
    foreach ($history as $entry) $lookup[$entry['branch_id'] . ':' . $entry['product_id']] = $entry;
    $counts = ['listed' => count($rows), 'manual_without_net_sales' => 0, 'manual_despite_net_sales' => 0, 'positive_forecast' => 0];
    $lastDates = [];
    foreach ($rows as $row) {
        $entry = $lookup[$row['branch_id'] . ':' . $row['product_id']] ?? [];
        if (($entry['units'] ?? 0) > 0) $lastDates[] = $entry['last_sale'];
        if ($row['days_left'] !== null) ++$counts['positive_forecast'];
        elseif (($entry['units'] ?? 0) > 0) ++$counts['manual_despite_net_sales'];
        else ++$counts['manual_without_net_sales'];
    }
    if ($lastDates) $counts['latest_sale_for_listed_products'] = max($lastDates);
    echo json_encode(['window' => $name, 'from' => $from, 'to' => $to] + $counts) . "\n";
}
