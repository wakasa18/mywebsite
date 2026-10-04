<?php
// Local template smoke check. Uses the framework's in-memory test database.
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
set_exception_handler(static function (Throwable $error): void {
    while (ob_get_level()) ob_end_clean();
    fwrite(STDERR, $error->getMessage() . ' at ' . $error->getFile() . ':' . $error->getLine() . "\n");
    exit(1);
});
helper(['url', 'form']);
foreach (['workspace', 'editors'] as $stylesheet) {
    (new \Sabberworm\CSS\Parser(
        file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/' . $stylesheet . '.css'),
        \Sabberworm\CSS\Settings::create()->withLenientParsing(false)
    ))->parse();
}
$session = session();
$session->set(['role' => 'cashier', 'full_name' => 'UI Review', 'branch_id' => null, 'branch_name' => 'Review Branch']);
$product = [
    'id' => 1, 'product_id' => 1, 'product_name' => 'Paracetamol 500 mg tablets',
    'sku' => 'PARA-500', 'category_id' => 1, 'category_name' => 'Pain relief',
    'price' => 12.50, 'cost_price' => 8, 'stock' => 20, 'quantity' => 2, 'subtotal' => 25,
    'expiration_date' => '2028-06-01', 'status' => 'active', 'branch_id' => 1,
    'branch_name' => 'Review Branch', 'manufacturer' => 'Sample manufacturer',
    'supplier_name' => 'Sample supplier', 'unit' => 'Tablet', 'reorder_level' => 10,
];
$data = [
    'products' => [$product], 'cart' => [$product], 'discounts' => [],
    'heldSales' => [['label' => 'Sample held sale', 'cart' => [$product]]],
    'branches' => [], 'categories' => [], 'branchId' => '', 'categoryId' => '',
    'filterBranch' => '', 'dateFrom' => '', 'dateTo' => '',
    'sales' => [['id' => 1, 'invoice_no' => 'REVIEW-001', 'branch_name' => 'Review Branch', 'full_name' => 'UI Review', 'final_total' => 25, 'payment_method' => 'cash', 'status' => 'completed', 'sale_date' => '2026-09-16 10:00:00']],
];
$variants = [
    'login' => ['auth/login', []],
    'pos' => ['cashier/sales/index', $data],
    'pos-empty' => ['cashier/sales/index', array_replace($data, ['products' => [], 'cart' => [], 'heldSales' => []])],
    'products' => ['products/index', $data],
    'history' => ['cashier/sales/history', $data],
    'history-empty' => ['cashier/sales/history', array_replace($data, ['sales' => []])],
    'settings' => ['admin/settings/index', []],
    'categories' => ['categories/index', ['categories' => []]],
    'users' => ['admin/users/index', ['users' => [], 'branches' => [], 'totalUsers' => 4, 'activeUsers' => 3, 'inactiveUsers' => 1, 'adminUsers' => 1, 'cashierUsers' => 3, 'pager' => new class { public function links(): string { return ''; } }]],
    'reports' => ['admin/reports/index', [
        'branches' => [], 'branchId' => '', 'branchName' => 'All branches',
        'reportDateFrom' => '2026-09-01', 'reportDateTo' => '2026-09-16',
        'forecastDateFrom' => '2026-06-01', 'forecastDateTo' => '2026-09-16',
        'salesSummary' => [], 'profitSummary' => [], 'reportHistory' => [], 'forecastHistory' => [],
        'futureForecast' => [], 'reorderForecast' => [], 'topProducts' => [], 'recentSales' => [],
        'forecastingDataLog' => [], 'exportHistory' => [], 'forecastLog' => [],
    ]],
    'dashboard' => ['dashboard/index', [
        'totalBranches' => 2, 'totalUsers' => 4, 'totalProducts' => 120, 'totalBranchInventory' => 160,
        'totalStock' => 2400, 'todayTransactions' => 18, 'totalSales' => 500,
        'todayRevenue' => 4260, 'yesterdayRevenue' => 3800,
        'lowStockCount' => 3, 'outOfStockCount' => 1, 'nearExpiryCount' => 2, 'expiredCount' => 0,
        'dailyLabels' => [], 'dailyRevenue' => [], 'dailyTxns' => [],
        'monthlyLabels' => [], 'monthlyRevenue' => [], 'monthlyTxns' => [],
        'topProductLabels' => [], 'topProductRevenue' => [], 'topProductUnits' => [],
        'branchChartLabels' => [], 'branchChartData' => [], 'salesByBranch' => [],
        'inventoryByBranch' => [], 'lowStockItems' => [], 'nearExpiryItems' => [],
        'expiredItems' => [], 'recentSales' => [], 'recentLogs' => [],
    ]],
];
$editorData = array_replace($data, [
    'product' => $product + ['supplier_id' => null], 'branchProduct' => $product,
    'item' => $product, 'items' => [], 'branchId' => 1,
    'branches' => [['id' => 1, 'branch_name' => 'Review Branch']], 'suppliers' => [],
    'isCashier' => false, 'assignedBranch' => ['id' => 1, 'branch_name' => 'Review Branch'],
    'category' => ['id' => 1, 'category_name' => 'Pain relief'],
    'branch' => ['id' => 1, 'branch_name' => 'Review Branch', 'branch_code' => 'REVIEW', 'address' => '', 'contact_number' => '', 'status' => 'active'],
    'user' => ['id' => 1, 'full_name' => 'Review User', 'username' => 'review', 'role' => 'cashier', 'branch_id' => 1, 'status' => 'active'],
    'supplier' => ['id' => 1, 'supplier_name' => 'Review Supplier', 'status' => 'active'],
    'form' => ['discount_name' => 'Sample discount'], 'discount' => ['id' => 1],
    'validation' => service('validation'),
    'sale' => $data['sales'][0] + ['amount_paid' => 25, 'total_amount' => 25, 'discount_amount' => 0],
]);
foreach (['products', 'categories', 'admin/users', 'admin/branches', 'admin/suppliers', 'admin/discounts'] as $module) {
    foreach (['create', 'edit'] as $operation) {
        $variants['editor-' . str_replace('/', '-', $module) . '-' . $operation] = [$module . '/' . $operation, $editorData];
    }
}
foreach (['admin/users/password', 'admin/branch_inventory/edit', 'admin/stock_transfer/create', 'admin/sale_correction/edit'] as $view) {
    $variants['editor-' . str_replace('/', '-', $view)] = [$view, $editorData];
}
$variants['editor-products-cashier'] = ['products/create', array_replace($editorData, ['isCashier' => true])];
$contracts = [];
$results = [];
foreach ($variants as $name => [$view, $values]) {
    $html = view($view, $values, ['saveData' => false]);
    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    // libxml's HTML4 parser can mistake HTML inside JS template strings for DOM.
    $domHtml = preg_replace('#(<script\b[^>]*>).*?(</script\s*>)#si', '$1$2', $html);
    $document->loadHTML('<?xml encoding="UTF-8">' . $domHtml);
    libxml_clear_errors();
    $xpath = new DOMXPath($document);
    if (str_starts_with($name, 'editor-products-')) {
        if ($xpath->query('//div[@class="product-create-layout"]/div[1]/div[contains(@class,"card")]')->length !== 3) {
            throw new RuntimeException('Product editor must have three main sections');
        }
        if ($xpath->query('//form/div[@class="editor-actions"]')->length !== 1) {
            throw new RuntimeException('Product save actions must remain inside the form');
        }
    }
    if (str_starts_with($name, 'editor-')) {
        $forms = [];
        foreach ($xpath->query('//form') as $formElement) {
            $fields = [];
            foreach ($xpath->query('.//*[@name]', $formElement) as $control) {
                if (str_contains($control->getAttribute('name'), 'csrf')) continue;
                $attributes = [];
                foreach (['name', 'type', 'value', 'required', 'disabled', 'readonly', 'min', 'max', 'step', 'maxlength', 'minlength'] as $attribute) {
                    if ($control->hasAttribute($attribute)) $attributes[$attribute] = $control->getAttribute($attribute);
                }
                if ($control->tagName === 'textarea') $attributes['text'] = $control->textContent;
                if ($control->tagName === 'select') {
                    $attributes['options'] = [];
                    foreach ($control->getElementsByTagName('option') as $option) $attributes['options'][] = [$option->getAttribute('value'), $option->hasAttribute('selected')];
                }
                $fields[] = $attributes;
            }
            $forms[] = [$formElement->getAttribute('action'), strtolower($formElement->getAttribute('method')), $fields];
        }
        $contracts[$name] = $forms;
    }
    foreach ($xpath->query('//style') as $style) {
        (new \Sabberworm\CSS\Parser($style->textContent, \Sabberworm\CSS\Settings::create()->withLenientParsing(false)))->parse();
    }
    $ids = [];
    foreach ($xpath->query('//*[@id]') as $element) {
        $id = $element->getAttribute('id');
        if (isset($ids[$id])) throw new RuntimeException("Duplicate ID in $name: $id");
        $ids[$id] = true;
    }
    foreach ($xpath->query('//label[@for]') as $label) {
        if (!isset($ids[$label->getAttribute('for')])) throw new RuntimeException("Missing label target in $name");
    }
    foreach ($xpath->query('//*[@aria-controls]') as $control) {
        if (!isset($ids[$control->getAttribute('aria-controls')])) throw new RuntimeException("Missing control target in $name");
    }
    $scripts = [];
    preg_match_all('#<script\b([^>]*)>(.*?)</script\s*>#si', $html, $scriptBlocks, PREG_SET_ORDER);
    foreach ($scriptBlocks as $script) {
        if (!preg_match('/\bsrc\s*=/i', $script[1])) $scripts[] = $script[2];
    }
    file_put_contents(__DIR__ . "/$name.js", implode("\n", $scripts));
    file_put_contents(__DIR__ . "/$name.html", $html);
    $results[] = "$name rendered; labels, controls and unique IDs passed";
}
$baseline = __DIR__ . '/editor-contracts.json';
if (in_array('--baseline', $argv, true)) {
    file_put_contents($baseline, json_encode($contracts, JSON_PRETTY_PRINT));
} elseif (is_file($baseline)) {
    if (json_decode(file_get_contents($baseline), true) !== $contracts) throw new RuntimeException('Editor form submission contract changed');
    $results[] = 'All editor actions, field names, values and validation attributes match the baseline';
}
echo implode("\n", $results), "\n";
