<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<?php
    $paymentLabels = [
        'cash' => 'Cash',
        'gcash' => 'GCash',
        'card' => 'Card',
    ];
    $paymentMethod = strtolower((string) ($sale['payment_method'] ?? ''));
    $paymentLabel = $paymentLabels[$paymentMethod] ?? ucfirst($paymentMethod ?: 'Not specified');
    $saleDate = $sale['sale_date'] ?? null;
?>

<style>
.sale-complete-wrap {
    width: min(900px, 100%);
    min-width: 0;
    margin: 0 auto;
}
.sale-complete-wrap *,
.sale-complete-wrap *::before,
.sale-complete-wrap *::after { box-sizing: border-box; }
.sale-complete-card { overflow: hidden; }
.sale-complete-page-header { margin-bottom: 20px; }
.sale-complete-page-header > div { min-width: 0; }
.sale-complete-page-header h1,
.sale-complete-page-header p,
.sale-complete-subtitle { overflow-wrap: anywhere; }
.sale-complete-hero {
    text-align: center;
    padding: 34px 24px 26px;
    border-bottom: 1px solid var(--line);
}
.sale-complete-icon {
    width: 76px;
    height: 76px;
    margin: 0 auto 18px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: var(--success-bg);
    color: var(--success);
    border: 2px solid color-mix(in srgb, var(--success) 28%, transparent);
    font-size: 38px;
    font-weight: 800;
    line-height: 1;
}
.sale-complete-title {
    margin: 0;
    font-size: clamp(1.6rem, 4vw, 2.1rem);
}
.sale-complete-subtitle {
    margin: 8px auto 0;
    max-width: 560px;
    color: var(--muted);
    line-height: 1.55;
}
.sale-complete-body {
    padding: 24px;
}
.sale-complete-total {
    padding: 22px;
    border-radius: var(--radius-lg);
    background: var(--primary-lighter);
    border: 1px solid var(--line);
    text-align: center;
    margin-bottom: 20px;
}
.sale-complete-total .label {
    color: var(--muted);
    font-size: .82rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.sale-complete-total .amount {
    display: block;
    margin-top: 5px;
    color: var(--primary);
    font-size: clamp(2rem, 7vw, 3rem);
    font-weight: 800;
    line-height: 1.1;
}
.sale-summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
.sale-summary-item {
    min-width: 0;
    padding: 14px 15px;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface-2);
}
.sale-summary-item .summary-label {
    display: block;
    margin-bottom: 5px;
    color: var(--muted);
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.sale-summary-item .summary-value {
    display: block;
    color: var(--text);
    font-weight: 700;
    overflow-wrap: anywhere;
}
.sale-payment-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 12px;
}
.sale-complete-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    padding: 20px 24px 24px;
    border-top: 1px solid var(--line);
}
.sale-complete-actions .btn {
    min-height: 48px;
    justify-content: center;
    text-align: center;
    font-weight: 700;
}
.sale-complete-actions .start-new-sale {
    grid-column: 1 / -1;
    min-height: 54px;
    font-size: 1rem;
}
.sale-complete-note {
    margin: 18px 0 0;
    color: var(--muted);
    font-size: .88rem;
    text-align: center;
}

@media (max-width: 720px) {
    .sale-complete-page-header { margin-bottom: 14px; }
    .sale-complete-hero { padding: 26px 18px 21px; }
    .sale-complete-body { padding: 17px; }
    .sale-summary-grid,
    .sale-payment-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .sale-complete-actions { padding: 17px; }
}

@media (max-width: 480px) {
    .sale-complete-card { border-radius: 12px; }
    .sale-complete-hero { padding: 22px 14px 18px; }
    .sale-complete-icon {
        width: 62px;
        height: 62px;
        margin-bottom: 14px;
        font-size: 30px;
    }
    .sale-complete-title { font-size: 1.45rem; }
    .sale-complete-subtitle {
        margin-top: 7px;
        font-size: .9rem;
        line-height: 1.48;
    }
    .sale-complete-body { padding: 14px; }
    .sale-complete-total {
        padding: 18px 12px;
        margin-bottom: 14px;
    }
    .sale-complete-total .amount { font-size: clamp(1.9rem, 11vw, 2.55rem); }
    .sale-summary-grid,
    .sale-payment-grid,
    .sale-complete-actions { grid-template-columns: 1fr; }
    .sale-summary-grid,
    .sale-payment-grid { gap: 9px; }
    .sale-summary-item { padding: 12px 13px; }
    .sale-complete-actions {
        gap: 9px;
        padding: 14px;
    }
    .sale-complete-actions .start-new-sale { grid-column: auto; }
    .sale-complete-actions .btn {
        width: 100%;
        min-height: 48px;
        padding-inline: 12px;
        white-space: normal;
        line-height: 1.25;
    }
}

@media (max-width: 360px) {
    .sale-complete-hero,
    .sale-complete-body,
    .sale-complete-actions { padding-left: 12px; padding-right: 12px; }
    .sale-complete-total .amount { letter-spacing: -.04em; }
}
</style>

<div class="page-header sale-complete-page-header">
    <div>
        <h1 style="margin:0;">Sale Completed</h1>
        <p class="muted" style="margin:6px 0 0;">The payment was saved successfully. Choose what you want to do next.</p>
    </div>
</div>

<div class="sale-complete-wrap">
    <div class="card sale-complete-card">
        <div class="sale-complete-hero">
            <div class="sale-complete-icon" aria-hidden="true">✓</div>
            <h2 class="sale-complete-title">Payment Successful</h2>
            <p class="sale-complete-subtitle">
                Invoice <strong><?= esc($sale['invoice_no'] ?? '—') ?></strong> has been recorded and the stock levels have been updated.
            </p>
        </div>

        <div class="sale-complete-body">
            <div class="sale-complete-total">
                <span class="label">Total Paid</span>
                <strong class="amount">₱<?= number_format((float) ($sale['final_total'] ?? 0), 2) ?></strong>
            </div>

            <div class="sale-summary-grid">
                <div class="sale-summary-item">
                    <span class="summary-label">Invoice Number</span>
                    <span class="summary-value"><?= esc($sale['invoice_no'] ?? '—') ?></span>
                </div>
                <div class="sale-summary-item">
                    <span class="summary-label">Date and Time</span>
                    <span class="summary-value"><?= \App\Libraries\DisplayDate::dateTime($saleDate) ?></span>
                </div>
                <div class="sale-summary-item">
                    <span class="summary-label">Branch</span>
                    <span class="summary-value"><?= esc($sale['branch_name'] ?? '—') ?></span>
                </div>
                <div class="sale-summary-item">
                    <span class="summary-label">Cashier</span>
                    <span class="summary-value"><?= esc($sale['full_name'] ?? '—') ?></span>
                </div>
                <div class="sale-summary-item">
                    <span class="summary-label">Products</span>
                    <span class="summary-value"><?= number_format((int) ($lineCount ?? 0)) ?> product line<?= ((int) ($lineCount ?? 0) === 1) ? '' : 's' ?></span>
                </div>
                <div class="sale-summary-item">
                    <span class="summary-label">Total Quantity</span>
                    <span class="summary-value"><?= number_format((int) ($totalQuantity ?? 0)) ?> item<?= ((int) ($totalQuantity ?? 0) === 1) ? '' : 's' ?></span>
                </div>
            </div>

            <div class="sale-payment-grid">
                <div class="sale-summary-item">
                    <span class="summary-label">Payment Method</span>
                    <span class="summary-value"><?= esc($paymentLabel) ?></span>
                </div>
                <div class="sale-summary-item">
                    <span class="summary-label">Amount Received</span>
                    <span class="summary-value">₱<?= number_format((float) ($sale['amount_paid'] ?? 0), 2) ?></span>
                </div>
                <div class="sale-summary-item">
                    <span class="summary-label">Change</span>
                    <span class="summary-value">₱<?= number_format((float) ($sale['change_amount'] ?? 0), 2) ?></span>
                </div>
            </div>

            <?php if ((float) ($sale['discount_amount'] ?? 0) > 0): ?>
                <p class="sale-complete-note">
                    Discount applied: <strong>₱<?= number_format((float) $sale['discount_amount'], 2) ?></strong>
                </p>
            <?php endif; ?>
        </div>

        <div class="sale-complete-actions">
            <a href="<?= site_url('cashier/sales') ?>" class="btn btn-primary start-new-sale">Start New Sale</a>
            <a href="<?= site_url('cashier/sales/receipt/' . $sale['id']) ?>" class="btn secondary">View Receipt</a>
            <a href="<?= site_url('cashier/sales/receipt/' . $sale['id'] . '?print=1') ?>"
               class="btn secondary" target="_blank" rel="noopener">Print Receipt</a>
            <a href="<?= site_url('cashier/sales/history') ?>" class="btn secondary">Sales History</a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
