<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<?php
    $paymentLabels = [
        'cash' => 'Cash',
        'gcash' => 'GCash',
        'card' => 'Card',
    ];
    $statusLabels = [
        'completed' => 'Completed',
        'partially_refunded' => 'Partially Refunded',
        'refunded' => 'Refunded',
        'void' => 'Void',
    ];
    $statusClasses = [
        'completed' => 'badge-success',
        'partially_refunded' => 'badge-warning',
        'refunded' => 'badge-danger',
        'void' => 'badge-danger',
    ];

    $status = strtolower((string) ($sale['status'] ?? ''));
    $paymentMethod = strtolower((string) ($sale['payment_method'] ?? ''));
    $saleDateTimestamp = !empty($sale['sale_date']) ? strtotime((string) $sale['sale_date']) : false;
    $canRefund = in_array($status, ['completed', 'partially_refunded'], true);
    $exchange = \App\Libraries\ExchangeRecord::forSale($sale);
    $canCorrect = session('role') === 'admin' && $status === 'completed' && !$exchange;
?>

<style>
.sale-details-page {
    display: grid;
    gap: 18px;
    width: 100%;
    min-width: 0;
}
.sale-details-page *,
.sale-details-page *::before,
.sale-details-page *::after { box-sizing: border-box; }
.sale-details-page > *,
.sale-details-grid > *,
.sale-details-header > * { min-width: 0; }
.sale-details-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.sale-details-header h1 {
    margin: 0;
}
.sale-details-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}
.sale-details-actions .btn {
    min-height: 42px;
    justify-content: center;
}
.sale-details-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.sale-summary-card {
    min-width: 0;
    padding: 16px;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface);
}
.sale-summary-card.primary {
    background: var(--primary-lighter);
}
.sale-summary-label {
    display: block;
    margin-bottom: 6px;
    color: var(--muted);
    font-size: .76rem;
    font-weight: 750;
    letter-spacing: .05em;
    text-transform: uppercase;
}
.sale-summary-value {
    display: block;
    color: var(--text);
    font-size: 1rem;
    font-weight: 750;
    line-height: 1.35;
    overflow-wrap: anywhere;
}
.sale-summary-card.primary .sale-summary-value {
    color: var(--primary);
    font-size: 1.35rem;
}
.sale-details-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(280px, .75fr);
    gap: 18px;
    align-items: start;
}
.sale-info-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0;
}
.sale-info-item {
    padding: 13px 0;
    border-bottom: 1px solid var(--line);
}
.sale-info-item:nth-child(odd) {
    padding-right: 18px;
}
.sale-info-item:nth-child(even) {
    padding-left: 18px;
    border-left: 1px solid var(--line);
}
.sale-info-item .label {
    display: block;
    margin-bottom: 4px;
    color: var(--muted);
    font-size: .8rem;
    font-weight: 700;
}
.sale-info-item .value {
    color: var(--text);
    font-weight: 650;
    overflow-wrap: anywhere;
}
.sale-notes {
    margin-top: 14px;
    padding: 14px;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface-2);
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}
.sale-total-list {
    display: grid;
    gap: 11px;
}
.sale-total-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    color: var(--muted);
}
.sale-total-row strong {
    color: var(--text);
    text-align: right;
}
.sale-total-row.grand {
    margin-top: 4px;
    padding-top: 13px;
    border-top: 1.5px solid var(--line);
    color: var(--text);
    font-size: 1.08rem;
    font-weight: 750;
}
.sale-total-row.grand strong {
    color: var(--primary);
    font-size: 1.25rem;
}
.sale-total-row.refund strong {
    color: var(--danger);
}
.sale-items-wrap {
    overflow-x: auto;
}
.sale-items-table {
    min-width: 760px;
}
.sale-items-table th,
.sale-items-table td {
    vertical-align: middle;
}
.product-cell strong,
.product-cell span {
    display: block;
}
.product-cell span {
    margin-top: 3px;
    color: var(--muted);
    font-size: .8rem;
}
.refund-qty {
    color: var(--danger);
    font-weight: 700;
}
.refund-history-list {
    display: grid;
    gap: 10px;
}
.refund-history-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 10px 18px;
    padding: 14px;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface-2);
}
.refund-history-item .refund-product {
    font-weight: 750;
}
.refund-history-item .refund-meta,
.refund-history-item .refund-reason {
    margin-top: 4px;
    color: var(--muted);
    font-size: .84rem;
    line-height: 1.45;
}
.refund-history-item .refund-amount {
    color: var(--danger);
    font-weight: 800;
    white-space: nowrap;
}
.empty-note {
    padding: 18px;
    color: var(--muted);
    text-align: center;
}

@media (max-width: 980px) {
    .sale-details-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .sale-details-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .sale-details-page { gap: 14px; }
    .sale-details-header,
    .sale-details-actions { width: 100%; }
    .sale-details-header h1 { font-size: 1.35rem; }
    .sale-details-header p { line-height: 1.45; overflow-wrap: anywhere; }
    .sale-details-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }
    .sale-details-actions .btn {
        width: 100%;
        min-height: 46px;
        padding-inline: 10px;
        white-space: normal;
        line-height: 1.2;
    }
    .sale-details-summary { grid-template-columns: 1fr; gap: 9px; }
    .sale-summary-card { padding: 13px 14px; }
    .sale-info-list { grid-template-columns: 1fr; }
    .sale-info-item:nth-child(odd),
    .sale-info-item:nth-child(even) {
        padding: 11px 0;
        border-left: 0;
    }
    .sale-total-row {
        align-items: flex-start;
        gap: 12px;
    }
    .sale-total-row span { min-width: 0; }
    .sale-total-row strong { flex: 0 0 auto; }

    /* Turn the products table into readable mobile cards. */
    .sale-items-wrap {
        overflow: visible;
        padding: 0 13px 13px;
    }
    .sale-items-table { min-width: 0; width: 100%; }
    .sale-items-table thead { display: none; }
    .sale-items-table tbody,
    .sale-items-table tr,
    .sale-items-table td { display: block; width: 100%; }
    .sale-items-table tbody { display: grid; gap: 10px; }
    .sale-items-table tbody tr {
        overflow: hidden;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--surface-2);
    }
    .sale-items-table tbody tr:hover { background: var(--surface-2); }
    .sale-items-table td {
        display: grid;
        grid-template-columns: minmax(92px, .44fr) minmax(0, 1fr);
        gap: 10px;
        align-items: start;
        padding: 9px 11px;
        border-bottom: 1px solid var(--line);
        text-align: right;
        overflow-wrap: anywhere;
    }
    .sale-items-table td:last-child { border-bottom: 0; }
    .sale-items-table td::before {
        content: attr(data-label);
        color: var(--muted);
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-align: left;
        text-transform: uppercase;
    }
    .sale-items-table td.product-cell {
        display: block;
        padding-top: 12px;
        padding-bottom: 12px;
        text-align: left;
    }
    .sale-items-table td.product-cell::before {
        display: block;
        margin-bottom: 6px;
    }
    .sale-items-table td.empty-note {
        display: block;
        padding: 20px 12px;
        text-align: center;
    }
    .sale-items-table td.empty-note::before { display: none; }

    .refund-history-item { grid-template-columns: 1fr; padding: 12px; }
    .refund-history-item .refund-amount { justify-self: start; }
}

@media (max-width: 420px) {
    .sale-details-actions { grid-template-columns: 1fr; }
    .sale-items-table td { grid-template-columns: 82px minmax(0, 1fr); }
    .sale-total-row.grand { font-size: 1rem; }
    .sale-total-row.grand strong { font-size: 1.1rem; }
}
</style>

<div class="sale-details-page">
    <div class="sale-details-header">
        <div>
            <h1>Sale Details</h1>
            <p class="muted" style="margin:6px 0 0;">
                Review the transaction information without opening the printable receipt.
            </p>
        </div>

        <div class="sale-details-actions">
            <a href="<?= site_url('cashier/sales/history') ?>" class="btn secondary">← Sales History</a>
            <a href="<?= site_url('cashier/sales/receipt/' . $sale['id']) ?>" class="btn secondary">View Receipt</a>
            <a href="<?= site_url('cashier/sales/receipt/' . $sale['id'] . '?print=1') ?>"
               class="btn secondary" target="_blank" rel="noopener">Print Receipt</a>
            <?php if ($canCorrect): ?>
                <a href="<?= site_url('admin/sale-correction/' . $sale['id']) ?>" class="btn secondary">Correct Sale</a>
            <?php endif; ?>
            <?php if ($canRefund): ?>
                <a href="<?= site_url('cashier/sales/exchange/' . (int) $sale['id']) ?>" class="btn btn-secondary">Exchange Product</a>
                <a href="<?= site_url('cashier/sales/refund-form/' . $sale['id']) ?>" class="btn danger">Process Refund</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="sale-details-summary">
        <div class="sale-summary-card primary">
            <span class="sale-summary-label">Final Total</span>
            <span class="sale-summary-value">₱<?= number_format((float) ($sale['final_total'] ?? 0), 2) ?></span>
        </div>
        <div class="sale-summary-card">
            <span class="sale-summary-label">Invoice Number</span>
            <span class="sale-summary-value"><?= esc($sale['invoice_no'] ?? '—') ?></span>
        </div>
        <div class="sale-summary-card">
            <span class="sale-summary-label">Status</span>
            <span class="sale-summary-value">
                <span class="badge <?= esc($statusClasses[$status] ?? 'badge-warning') ?>">
                    <?= esc($statusLabels[$status] ?? ucfirst($status ?: 'Unknown')) ?>
                </span>
            </span>
        </div>
        <div class="sale-summary-card">
            <span class="sale-summary-label">Sale Date</span>
            <span class="sale-summary-value">
                <?= $saleDateTimestamp ? esc(\App\Libraries\DisplayDate::dateTime($saleDateTimestamp)) : '—' ?>
            </span>
        </div>
    </div>

    <div class="sale-details-grid">
        <div class="card">
            <div class="card-head">
                <div>
                    <h2 style="margin:0;">Transaction Information</h2>
                    <p class="muted" style="margin:5px 0 0;">Branch, cashier, and payment information.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="sale-info-list">
                    <div class="sale-info-item">
                        <span class="label">Branch</span>
                        <span class="value"><?= esc($sale['branch_name'] ?? '—') ?></span>
                    </div>
                    <div class="sale-info-item">
                        <span class="label">Cashier</span>
                        <span class="value"><?= esc($sale['full_name'] ?? '—') ?></span>
                    </div>
                    <div class="sale-info-item">
                        <span class="label">Payment Method</span>
                        <span class="value"><?= esc($paymentLabels[$paymentMethod] ?? ucfirst($paymentMethod ?: 'Not specified')) ?></span>
                    </div>
                    <div class="sale-info-item">
                        <span class="label">Payment Reference</span>
                        <span class="value">
                            <?= trim((string) ($sale['reference_no'] ?? '')) !== '' ? esc($sale['reference_no']) : 'Not provided' ?>
                        </span>
                    </div>
                    <div class="sale-info-item">
                        <span class="label"><?= $exchange ? 'Additional Payment' : 'Amount Received' ?></span>
                        <span class="value">₱<?= number_format((float) ($exchange ? $exchange['due'] : ($sale['amount_paid'] ?? 0)), 2) ?></span>
                    </div>
                    <div class="sale-info-item">
                        <span class="label"><?= $exchange ? 'Exchange Payout' : 'Change Given' ?></span>
                        <span class="value">₱<?= number_format((float) ($exchange ? $exchange['payout'] : ($sale['change_amount'] ?? 0)), 2) ?></span>
                    </div>
                    <div class="sale-info-item">
                        <span class="label">Applied Discount</span>
                        <span class="value"><?= esc($sale['discount_name'] ?? ((float) ($sale['discount_amount'] ?? 0) > 0 ? 'Discount applied' : 'None')) ?></span>
                    </div>
                    <div class="sale-info-item">
                        <span class="label">Branch Contact</span>
                        <span class="value"><?= esc($sale['branch_contact'] ?? '—') ?></span>
                    </div>
                </div>

                <div style="margin-top:16px;">
                    <?php if ($exchange): ?><p>Exchange credit: ₱<?= number_format($exchange['credit'], 2) ?> · <a href="<?= site_url('cashier/sales/view/' . $exchange['original_id']) ?>">Original invoice <?= esc($exchange['original_invoice']) ?></a></p><?php endif; ?>
                    <span class="label" style="display:block;margin-bottom:7px;">Notes</span>
                    <div class="sale-notes"><?= trim((string) ($sale['notes'] ?? '')) !== '' ? esc($sale['notes']) : 'No notes were added to this sale.' ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h2 style="margin:0;">Payment Summary</h2>
                    <p class="muted" style="margin:5px 0 0;">Amounts recorded for this transaction.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="sale-total-list">
                    <div class="sale-total-row">
                        <span>Subtotal</span>
                        <strong>₱<?= number_format((float) ($sale['total_amount'] ?? 0), 2) ?></strong>
                    </div>
                    <div class="sale-total-row">
                        <span>Discount</span>
                        <strong>− ₱<?= number_format((float) ($sale['discount_amount'] ?? 0), 2) ?></strong>
                    </div>
                    <div class="sale-total-row grand">
                        <span>Final Total</span>
                        <strong>₱<?= number_format((float) ($sale['final_total'] ?? 0), 2) ?></strong>
                    </div>
                    <?php if ((float) ($totalRefundedAmount ?? 0) > 0): ?>
                        <div class="sale-total-row refund">
                            <span>Refunded</span>
                            <strong>− ₱<?= number_format((float) $totalRefundedAmount, 2) ?></strong>
                        </div>
                        <div class="sale-total-row grand">
                            <span>Amount After Refunds</span>
                            <strong>₱<?= number_format((float) ($netAmountAfterRefunds ?? 0), 2) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2 style="margin:0;">Purchased Products</h2>
                <p class="muted" style="margin:5px 0 0;">Products and quantities included in this sale.</p>
            </div>
        </div>
        <div class="sale-items-wrap">
            <table class="sale-items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Discount</th>
                        <th>Line Total</th>
                        <th>Refunded</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="product-cell" data-label="Product">
                                    <strong><?= esc($item['product_name_snapshot'] ?? 'Unknown product') ?></strong>
                                    <span>
                                        SKU: <?= esc($item['sku'] ?? '—') ?>
                                        <?php if (!empty($item['unit'])): ?> · <?= esc($item['unit']) ?><?php endif; ?>
                                    </span>
                                </td>
                                <td data-label="Quantity"><?= number_format((int) ($item['quantity'] ?? 0)) ?></td>
                                <td data-label="Unit Price">₱<?= number_format((float) ($item['price'] ?? 0), 2) ?></td>
                                <td data-label="Discount">₱<?= number_format((float) ($item['discount_applied'] ?? 0), 2) ?></td>
                                <td data-label="Line Total"><strong>₱<?= number_format((float) ($item['line_total_after_discount'] ?? 0), 2) ?></strong></td>
                                <td data-label="Refunded">
                                    <?php if ((int) ($item['refunded_quantity'] ?? 0) > 0): ?>
                                        <span class="refund-qty">
                                            <?= number_format((int) $item['refunded_quantity']) ?> item(s)<br>
                                            ₱<?= number_format((float) ($item['refunded_amount'] ?? 0), 2) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="muted">None</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-note" data-label="">No sale items were found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($refundHistory)): ?>
        <div class="card">
            <div class="card-head">
                <div>
                    <h2 style="margin:0;">Refund History</h2>
                    <p class="muted" style="margin:5px 0 0;">
                        <?= number_format((int) ($totalRefundedQuantity ?? 0)) ?> item(s) refunded from this sale.
                    </p>
                </div>
            </div>
            <div class="card-body">
                <div class="refund-history-list">
                    <?php foreach ($refundHistory as $refund): ?>
                        <?php $refundTimestamp = !empty($refund['created_at']) ? strtotime((string) $refund['created_at']) : false; ?>
                        <div class="refund-history-item">
                            <div>
                                <div class="refund-product">
                                    <?= esc($refund['product_name_snapshot'] ?? 'Unknown product') ?>
                                    · <?= number_format((int) ($refund['quantity_refunded'] ?? 0)) ?> item(s)
                                </div>
                                <div class="refund-meta">
                                    Processed by <?= esc($refund['refunded_by_name'] ?? 'Unknown user') ?>
                                    <?php if ($refundTimestamp): ?>
                                        on <?= esc(\App\Libraries\DisplayDate::dateTime($refundTimestamp)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="refund-reason">
                                    Reason: <?= trim((string) ($refund['reason'] ?? '')) !== '' ? esc($refund['reason']) : 'No reason provided' ?>
                                </div>
                            </div>
                            <div class="refund-amount">− ₱<?= number_format((float) ($refund['refund_subtotal'] ?? 0), 2) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
