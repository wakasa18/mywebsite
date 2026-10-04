<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url', 'form']);
session()->set(['role' => 'admin', 'branch_id' => null, 'full_name' => 'Maria Santos']);
$item = ['product_name' => 'Ascorbic Acid + Zinc 500mg / 10mg Film-coated Tablet', 'sku' => 'VIT-C-ZINC-001', 'branch_name' => 'Santa Maria Main Branch', 'stock' => 3, 'reorder_level' => 20, 'expiration_date' => '2026-10-02'];
$data = [
    'title' => 'Dashboard', 'today' => '2026-09-24', 'todayRevenue' => 24580.75, 'yesterdayRevenue' => 31200,
    'todayTransactions' => 48, 'totalSales' => 3410, 'totalProducts' => 1250, 'totalBranchInventory' => 2460,
    'lowStockCount' => 24, 'outOfStockCount' => 7, 'nearExpiryCount' => 12, 'expiredCount' => 3,
    'totalStock' => 15840, 'totalBranches' => 3, 'totalUsers' => 12,
    'lowStockItems' => [$item, array_replace($item, ['product_name' => 'Paracetamol 500mg Tablet', 'stock' => 0])],
    'nearExpiryItems' => [$item], 'expiredItems' => [array_replace($item, ['expiration_date' => '2026-08-01'])],
    'salesByBranch' => [['branch_name' => 'Santa Maria Main Branch', 'total_transactions' => 150, 'total_amount' => 125000], ['branch_name' => 'Patag Branch', 'total_transactions' => 80, 'total_amount' => 94000]],
    'inventoryByBranch' => [['branch_name' => 'Santa Maria Main Branch', 'total_products' => 240, 'total_stock' => 12340], ['branch_name' => 'Patag Branch', 'total_products' => 170, 'total_stock' => 3500]],
    'recentSales' => [['id' => 1, 'invoice_no' => 'INV-20260924-105600-123ABC', 'branch_name' => 'Santa Maria Main Branch', 'full_name' => 'Cristina Dela Cruz', 'payment_method' => 'cash', 'status' => 'completed', 'remaining_total' => 455.75, 'sale_date' => '2026-09-24 10:56:00'], ['id' => 2, 'invoice_no' => 'INV-20260924-105701-987ZYX', 'branch_name' => 'Patag Branch', 'full_name' => 'Cristina Dela Cruz', 'payment_method' => 'gcash', 'status' => 'partially_refunded', 'remaining_total' => 120, 'sale_date' => '2026-09-24 10:57:00']],
    'recentLogs' => [['full_name' => 'Cristina Dela Cruz', 'username' => 'cristina', 'activity' => 'Updated inventory for Ascorbic Acid + Zinc at Santa Maria Main Branch.', 'log_time' => '2026-09-24 10:56:00']],
    'dailyLabels' => array_map(fn($i) => 'Sep ' . $i, range(1,30)),
    'dailyRevenue' => array_map(fn($i) => 1500 + ($i * 391 % 1700), range(1,30)), 'dailyTxns' => array_map(fn($i) => 15 + ($i * 7 % 20), range(1,30)),
    'monthlyLabels' => ['Oct','Nov','Dec','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep'],
    'monthlyRevenue' => [41000,48000,71000,52000,55000,51000,57000,59000,65000,61000,74000,68000], 'monthlyTxns' => [320,400,570,400,420,390,410,415,440,430,495,500],
    'topProductLabels' => [$item['product_name'],'Paracetamol 500mg Tablet','Cetirizine 10mg','Carbocisteine 500mg','Rubbing Alcohol 70%','Amoxicillin 500mg','Oral Rehydration Salts','Hydrocortisone Cream','Loratadine 10mg','Multivitamins + Iron'],
    'topProductRevenue' => [9500,8100,7200,6700,6300,5100,4200,3900,3700,3200], 'topProductUnits' => [250,450,200,170,150,180,160,100,120,90],
    'branchChartLabels' => ['Santa Maria Main Branch','Patag Branch'], 'branchChartData' => [125000,94000],
];
foreach (['full','empty'] as $state) {
    $fixture = $data;
    if ($state === 'empty') {
        foreach ($fixture as $key => $value) {
            if (is_array($value)) $fixture[$key] = [];
            elseif (is_numeric($value)) $fixture[$key] = 0;
        }
        $fixture['dailyLabels'] = $data['dailyLabels'];
        $fixture['monthlyLabels'] = $data['monthlyLabels'];
        $fixture['dailyRevenue'] = $fixture['dailyTxns'] = array_fill(0,30,0);
        $fixture['monthlyRevenue'] = $fixture['monthlyTxns'] = array_fill(0,12,0);
    }
    $html = view('dashboard/index', $fixture, ['saveData' => false]);
    $html = preg_replace('~https?://[^"\s]+/assets/~', '/assets/', $html);
    $html = str_replace('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', '/chart.umd.min.js', $html);
    file_put_contents(__DIR__ . '/dashboard-' . $state . '.html', $html);
    if ($state === 'full') file_put_contents(__DIR__ . '/dashboard-offline.html', str_replace('<script src="/chart.umd.min.js"></script>', '', $html));
}
echo "Rendered dashboard fixtures.\n";
