<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
$out = __DIR__ . '/report-formats';
if (!is_dir($out)) mkdir($out, 0775, true);
$base = ['branchName' => 'Main Branch - Santa Maria', 'generatedBy' => 'Export Preview', 'generatedAt' => 'September 21, 2026 08:30 PM', 'filterNotices' => []];
$product = ['product_name' => 'Ascorbic Acid + Zinc 500mg / 10mg Film-coated Tablet', 'sku' => 'VIT-C-ZINC-001', 'manufacturer' => 'Sample Healthcare Corporation', 'supplier_name' => 'Sample Medical Supplies', 'branch_name' => $base['branchName'], 'stock' => 15, 'expiration_date' => '2026-10-01', 'reorder_level' => 30, 'days_left' => 3, 'suggested_qty' => 120, 'urgency' => 'critical'];
$period = ['report_date' => '2026-09-21', 'total_transactions' => 15, 'gross_sales' => 15000.25, 'total_discount' => 500.25, 'net_sales' => 14500.0, 'cogs' => 10000.0, 'gross_profit' => 4500.0, 'forecast' => 14200.0];
$movement = ['rows' => [['date' => '2026-09-21', 'method' => 'cash', 'receipts' => 14500, 'refunds' => 350, 'net' => 14150]], 'totals' => ['receipts' => 14500, 'refunds' => 350, 'net' => 14150, 'cash_net' => 14150, 'unknown_refunds' => 0], 'returns' => [['return_condition' => 'resellable', 'quantity' => 2]]];
$sales = $base + ['reportDateFrom' => '2026-09-01', 'reportDateTo' => '2026-09-21', 'forecastDateFrom' => '2026-08-01', 'forecastDateTo' => '2026-09-21', 'reportType' => 'daily', 'forecastType' => 'weekly', 'salesSummary' => $period, 'profitSummary' => $period, 'averageTransactionValue' => 966.67, 'grossProfitMargin' => 31.03, 'nextForecastValue' => 16000.75, 'cashMovements' => $movement, 'reportHistory' => [$period], 'topProducts' => [['product_name_snapshot' => $product['product_name'], 'total_qty_sold' => 45, 'total_sales' => 7500.25]], 'recentSales' => [['invoice_no' => 'INV-20260921-193005-A4910', 'branch_name' => $base['branchName'], 'full_name' => 'Sample Cashier', 'status' => 'partially_refunded', 'remaining_total' => 14500, 'refunded_amount' => 350, 'sale_date' => '2026-09-21 19:30:05']], 'forecastHistory' => [$period], 'futureForecast' => [['forecast_date' => '2026-W40', 'forecast_value' => 16000.75]], 'reorderForecast' => [$product]];
$expiry = $base + ['summary' => ['total' => 4, 'expired' => 1, 'near' => 1, 'good' => 1, 'missing' => 1], 'products' => array_map(fn ($date) => array_replace($product, ['expiration_date' => $date]), ['2026-09-01', '2026-10-01', '2027-02-01', null]), 'today' => '2026-09-21', 'nearDate' => '2026-10-21', 'nearExpiryDays' => 30];
$log = ['id' => 123, 'product_name' => $product['product_name'], 'branch_name' => $base['branchName'], 'full_name' => 'Sample Administrator', 'action_type' => 'stock_adjustment', 'quantity' => -2, 'previous_stock' => 17, 'new_stock' => 15, 'remarks' => 'Physical stock count adjusted after branch inventory review.', 'created_at' => '2026-09-21 19:30:05'];
$stock = $base + ['logs' => [$log], 'dateFrom' => '2026-09-01', 'dateTo' => '2026-09-21', 'search' => '', 'actionTypeLabel' => 'All Types'];
$cases = ['sales' => ['admin/reports/reports_pdf', $sales], 'expiry' => ['admin/reports/expiry_report_pdf', $expiry], 'stock' => ['stock_logs/pdf', $stock], 'stock-long' => ['stock_logs/pdf', array_replace($stock, ['logs' => array_fill(0, 65, $log)])], 'sales-empty' => ['admin/reports/reports_pdf', array_replace($sales, ['cashMovements' => ['rows' => [], 'returns' => [], 'totals' => []], 'reportHistory' => [], 'topProducts' => [], 'recentSales' => [], 'forecastHistory' => [], 'futureForecast' => [], 'reorderForecast' => []])]];
foreach ($cases as $name => [$view, $data]) {
    if (isset($argv[1]) && $argv[1] !== $name) continue;
    $pdf = \App\Libraries\PdfFactory::create();
    $html = view($view, $data, ['saveData' => false]);
    $pdf->loadHtml($html, 'UTF-8');
    $pdf->setPaper('A4', 'landscape');
    $pdf->render();
    if (abs($pdf->getCanvas()->get_width() - 841.89) > 0.1 || abs($pdf->getCanvas()->get_height() - 595.28) > 0.1) {
        throw new RuntimeException('Expected A4 landscape dimensions for ' . $name);
    }
    $class = str_starts_with($name, 'stock') ? \App\Controllers\StockLogsController::class : \App\Controllers\Admin\ReportsController::class;
    $controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    (new ReflectionMethod($class, str_starts_with($name, 'stock') ? 'addPageNumbers' : 'addPdfPageNumbers'))->invoke($controller, $pdf);
    file_put_contents("$out/$name.pdf", $pdf->output());
    echo "$name: ", $pdf->getCanvas()->get_page_count(), " pages\n";
}
