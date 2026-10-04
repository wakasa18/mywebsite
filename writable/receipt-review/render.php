<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url', 'form']);
session()->set(['role' => 'cashier', 'branch_id' => null, 'full_name' => 'Preview Staff']);
$sale = [
    'id' => 1, 'invoice_no' => 'INV-20260917-101805-D0360', 'sale_date' => '2026-09-17 10:18:05',
    'branch_name' => 'Main Branch', 'branch_address' => 'Santa Maria, Bulacan', 'branch_contact' => '09123456789',
    'full_name' => 'System Administrator', 'payment_method' => 'cash', 'reference_no' => '',
    'total_amount' => 455, 'discount_amount' => 45.5, 'final_total' => 409.5, 'amount_paid' => 500,
    'change_amount' => 90.5, 'status' => 'completed', 'notes' => '',
];
$items = [
    ['id' => 1, 'product_name_snapshot' => 'Carbocisteine 500mg Capsule', 'quantity' => 2, 'price' => 125, 'subtotal' => 250, 'discount_applied' => 25],
    ['id' => 2, 'product_name_snapshot' => 'Ascorbic Acid + Zinc 500mg / 10mg Film-coated Tablet', 'quantity' => 5, 'price' => 41, 'subtotal' => 205, 'discount_applied' => 20.5],
];
$refundItem = ['id' => 1, 'product_name_snapshot' => 'Carbocisteine 500mg Capsule', 'quantity_refunded' => 1, 'price_at_sale' => 112.5, 'refund_subtotal' => 112.5, 'return_condition' => 'damaged', 'refund_method' => 'gcash', 'refund_event_id' => str_repeat('a2', 16)];
$refundData = ['sale' => array_replace($sale, ['status' => 'partially_refunded']), 'refundItems' => [$refundItem], 'refundTotal' => 112.5, 'reason' => 'Packaging damaged. Customer returned one capsule.', 'refundedAt' => '2026-09-17 10:25:00', 'refundedBy' => 'System Administrator'];
$previewItems = array_map(static fn ($item) => $item + ['already_refunded' => 0, 'refundable_qty' => $item['quantity'], 'refund_unit_price' => $item['price'] * 0.9], $items);
$variants = [
    'sale' => ['receipt', ['sale' => $sale, 'items' => $items, 'isReprint' => false, 'autoPrint' => false]],
    'auto-print' => ['receipt', ['sale' => $sale, 'items' => $items, 'isReprint' => false, 'autoPrint' => true]],
    'reprint' => ['receipt', ['sale' => array_replace($sale, ['payment_method' => 'card', 'reference_no' => str_repeat('R', 90), 'notes' => str_repeat('A long note to verify receipt wrapping. ', 8)]), 'items' => $items, 'isReprint' => true, 'autoPrint' => false]],
    'refund' => ['refund_slip', $refundData],
    'full-refund' => ['refund_slip', array_replace($refundData, ['sale' => array_replace($sale, ['status' => 'refunded'])])],
    'legacy-refund' => ['refund_slip', array_replace($refundData, ['refundItems' => [array_replace($refundItem, ['refund_method' => null, 'refund_event_id' => null])]])],
    'preview' => ['refund_partial', ['sale' => $sale, 'items' => $previewItems, 'refundHistory' => [], 'refundToken' => 'preview-token']],
];
foreach ($variants as $name => [$view, $data]) {
    $html = view('cashier/sales/' . $view, $data);
    $html = preg_replace('~https?://[^"\s]+/assets/~', '/assets/', $html);
    file_put_contents(__DIR__ . '/' . $name . '.html', $html);
    preg_match_all('~<script\b[^>]*>(.*?)</script>~is', $html, $matches);
    file_put_contents(__DIR__ . '/' . $name . '.js', implode("\n", $matches[1]));
    if (!str_contains($html, 'receipt-paper')) throw new RuntimeException('Missing paper component: ' . $name);
}
(new \Sabberworm\CSS\Parser(file_get_contents(FCPATH . 'assets/css/receipts.css'), \Sabberworm\CSS\Settings::create()->withLenientParsing(false)))->parse();
echo 'Rendered seven receipt variants; shared CSS parsed successfully.' . PHP_EOL;
