<?php
// Render the current real view with synthetic rows and CLI-only session state.
// This neither authenticates a browser nor changes application records.
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url', 'form']);
$base = ['branch_name' => 'Preview Branch', 'full_name' => 'Preview Staff', 'final_total' => 125,
    'payment_method' => 'cash', 'status' => 'completed', 'sale_date' => '2026-10-04 10:00:00'];
$rows = [
    $base + ['id' => 101, 'invoice_no' => 'INV-PREVIEW-101'],
    array_replace($base, ['id' => 102, 'invoice_no' => 'INV-PREVIEW-102', 'status' => 'partially_refunded']),
    $base + ['id' => 103, 'invoice_no' => 'EXC-101-' . str_repeat('a', 32)],
];
$results = [];
foreach (['admin', 'cashier'] as $role) {
    session()->set(['role' => $role, 'branch_id' => 1, 'full_name' => 'Preview Staff']);
    $html = view('cashier/sales/history', [
        'sales' => $rows, 'branches' => [], 'pager' => null, 'keyword' => '',
        'dateFrom' => '', 'dateTo' => '', 'filterBranch' => '', 'filterPaymentMethod' => '', 'filterStatus' => '',
    ], ['saveData' => false]);
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    foreach ($xpath->query('//table[@aria-label="Sales history"]/tbody/tr') as $index => $row) {
        $results[] = ['role' => $role, 'invoice' => $rows[$index]['invoice_no'], 'status' => $rows[$index]['status'],
            'correct_button_visible' => $xpath->query('.//a[contains(@href,"admin/sale-correction/")]', $row)->length === 1];
    }
}
if (count($results) !== 6 || array_column($results, 'correct_button_visible') !== [true, false, false, false, false, false]) {
    throw new RuntimeException('Unexpected visibility in the actual Sales History view.');
}
file_put_contents(__DIR__ . '/correct-button-visibility.json', json_encode($results, JSON_PRETTY_PRINT) . "\n");
echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
