<?php
    $settings = \App\Libraries\SystemSettings::DEFAULTS;
    $canRefund = in_array($sale['status'] ?? '', ['completed', 'partially_refunded'], true);
    $extraActions = [['label' => 'Original receipt', 'url' => site_url('cashier/sales/receipt/' . (int) $sale['id'])]];
    if ($canRefund) $extraActions[] = ['label' => 'Refund remaining items', 'url' => site_url('cashier/sales/refund-form/' . (int) $sale['id'])];
    $payoutMethods = array_values(array_unique(array_map(static fn ($item) => !empty($item['refund_method']) ? strtoupper($item['refund_method']) : 'Not recorded', $refundItems)));
    $eventId = $refundItems[0]['refund_event_id'] ?? null;
    $exchange ??= null;
    if ($exchange) $extraActions[] = ['label' => 'Replacement receipt', 'url' => site_url('cashier/sales/receipt/' . (int) $exchangeSale['id'])];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/jpeg" href="<?= base_url('assets/images/pharxmaco-favicon.jpg') ?>?v=20260923">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Refund slip — <?= esc($sale['invoice_no']) ?></title>
    <script src="<?= base_url('assets/js/receipt-theme.js') ?>"></script>
    <link rel="stylesheet" href="<?= base_url('assets/css/receipts.css') ?>?v=20260930-1">
    <script src="<?= base_url('assets/js/receipts.js') ?>" defer></script>
</head>
<body class="receipt-page" data-paper-width="80">
<div class="receipt-shell">
    <?= view('partials/receipt_toolbar', ['heading' => 'Refund slip', 'subtitle' => 'Returned items and payment details.']) ?>
    <main class="receipt-layout">
        <div class="receipt-stage">
            <article class="receipt-paper receipt-paper--refund" aria-label="Refund slip for <?= esc($sale['invoice_no']) ?>">
                <?= view('partials/receipt_header', ['businessName' => $settings['business_name'], 'businessType' => $settings['business_type'], 'documentType' => 'Refund slip', 'sale' => $sale]) ?>
                <div class="receipt-status receipt-status--refund"><?= ($sale['status'] ?? '') === 'refunded' ? 'Sale fully refunded' : 'Sale partially refunded' ?></div>
                <hr class="receipt-rule">
                <dl class="receipt-meta">
                    <dt>Original invoice</dt><dd><?= esc($sale['invoice_no']) ?></dd>
                    <dt>Original sale date</dt><dd><?= \App\Libraries\DisplayDate::date($sale['sale_date']) ?></dd>
                    <dt>Refund date &amp; time</dt><dd><?= \App\Libraries\DisplayDate::dateTime($refundedAt) ?></dd>
                    <dt>Processed by</dt><dd><?= esc($refundedBy) ?></dd>
                    <dt>Original payment</dt><dd><?= esc(strtoupper($sale['payment_method'])) ?></dd>
                    <dt><?= $exchange ? 'Settlement method' : 'Refund paid via' ?></dt><dd><?= esc(implode(', ', $payoutMethods)) ?></dd>
                    <?php if ($exchange): ?><dt>Replacement invoice</dt><dd><?= esc($exchangeSale['invoice_no']) ?></dd><?php endif; ?>
                </dl>
                <hr class="receipt-rule">
                <table class="receipt-items" aria-label="Refunded items">
                    <thead><tr><th scope="col">Returned item</th><th scope="col">Refund</th></tr></thead>
                    <tbody>
                        <?php foreach ($refundItems as $ri): ?>
                        <tr>
                            <td>
                                <div class="receipt-item-name"><?= esc($ri['product_name_snapshot']) ?></div>
                                <div class="receipt-item-detail"><?= (int) $ri['quantity_refunded'] ?> × ₱<?= number_format((float) $ri['price_at_sale'], 2) ?></div>
                                <div class="receipt-item-detail">Condition: <?= esc(ucfirst($ri['return_condition'] ?? 'resellable')) ?></div>
                            </td>
                            <td>₱<?= number_format((float) $ri['refund_subtotal'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <table class="receipt-totals" aria-label="Refund totals">
                    <tr class="receipt-grand"><td><?= $exchange ? 'Return value' : 'This refund' ?></td><td>₱<?= number_format((float) $refundTotal, 2) ?></td></tr>
                    <?php if ($exchange): ?>
                    <tr><td>Credit applied to replacement</td><td>₱<?= number_format($exchange['credit'], 2) ?></td></tr>
                    <tr><td>Paid back to customer</td><td>₱<?= number_format($exchange['payout'], 2) ?></td></tr>
                    <?php endif; ?>
                    <tr><td>Original sale total</td><td>₱<?= number_format((float) $sale['final_total'], 2) ?></td></tr>
                </table>
                <?php if (!empty($reason)): ?><div class="receipt-note"><strong>Reason for refund</strong><?= nl2br(esc($reason)) ?></div><?php endif; ?>
                <hr class="receipt-rule">
                <footer class="receipt-footer">
                    <div class="receipt-thanks">Refund recorded</div>
                    This slip confirms this refund transaction.<br>Please keep it with your original receipt.
                    <span class="receipt-reference"><?= esc($eventId ? 'REF-' . strtoupper($eventId) : $sale['invoice_no'] . '-REF') ?></span>
                    <?= esc($settings['business_name']) ?> POS
                </footer>
            </article>
            <p class="receipt-view-note">Enlarged for easy reading. Prints on <span data-paper-label>80 mm</span> paper.</p>
        </div>
        <?= view('partials/receipt_print_options', ['sale' => $sale, 'printLabel' => 'Print refund slip', 'extraActions' => $extraActions]) ?>
    </main>
</div>
</body>
</html>
