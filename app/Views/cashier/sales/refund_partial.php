<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<?php
    $refundBusinessName = 'Pharxmaco';
    $refundBusinessType = 'Drugstore';
?>

<style>
/* ── Two-column layout ── */
.refund-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(330px, 370px);
    gap: 20px;
    align-items: start;
    width: 100%;
    min-width: 0;
}
.refund-layout *,
.refund-layout *::before,
.refund-layout *::after { box-sizing: border-box; }
.refund-layout > *,
.refund-layout form,
.refund-layout .card,
.refund-layout .table-wrap { min-width: 0; }
.refund-page-header strong,
.refund-sale-meta,
.refund-summary-item strong { overflow-wrap: anywhere; }
.refund-summary-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px 28px;
}
.refund-summary-item { min-width: 0; }
.refund-card-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.refund-form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 12px;
}
.refund-items-table,
.refund-history-table { width: 100%; }
.refund-layout > *,
.refund-receipt-col {
    min-width: 0;
}
@media (max-width: 1050px) {
    .refund-layout {
        grid-template-columns: minmax(0, 1fr) 330px;
        gap: 16px;
    }
    .refund-summary-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 900px) {
    .refund-layout { grid-template-columns: 1fr; }
    .refund-receipt-col { order: 0; }
    .rp-wrap { position: static; }
}
@media (max-width: 700px) {
    .refund-page-header { margin-bottom: 14px !important; }
    .refund-page-header h1 { font-size: 1.35rem; }
    .refund-page-header p { line-height: 1.45; overflow-wrap: anywhere; }
    .refund-sale-head {
        align-items: stretch;
        flex-direction: column;
    }
    .refund-sale-meta { line-height: 1.55; }
    .refund-card-actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        width: 100%;
    }
    .refund-card-actions .btn,
    .refund-card-actions .badge {
        min-height: 42px;
        width: 100%;
        justify-content: center;
    }
    .refund-summary-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .refund-summary-item {
        padding: 11px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: var(--surface-2);
    }
    #refund-all-btn {
        width: 100%;
        min-height: 46px;
        justify-content: center;
    }

    /* Mobile card layout for refundable products. */
    .refund-items-wrap,
    .refund-history-wrap {
        overflow: visible;
        padding: 0 13px 13px;
    }
    .refund-items-table,
    .refund-history-table { min-width: 0; }
    .refund-items-table thead,
    .refund-history-table thead { display: none; }
    .refund-items-table tbody,
    .refund-items-table tr,
    .refund-items-table td,
    .refund-history-table tbody,
    .refund-history-table tr,
    .refund-history-table td { display: block; width: 100%; }
    .refund-items-table tbody,
    .refund-history-table tbody { display: grid; gap: 10px; }
    .refund-items-table tbody tr,
    .refund-history-table tbody tr {
        overflow: hidden;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--surface-2);
    }
    .refund-items-table tbody tr:hover,
    .refund-history-table tbody tr:hover { background: var(--surface-2); }
    .refund-items-table td,
    .refund-history-table td {
        display: grid;
        grid-template-columns: minmax(100px, .46fr) minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        padding: 9px 11px !important;
        border-bottom: 1px solid var(--line);
        text-align: right !important;
        overflow-wrap: anywhere;
    }
    .refund-items-table td:last-child,
    .refund-history-table td:last-child { border-bottom: 0; }
    .refund-items-table td::before,
    .refund-history-table td::before {
        content: attr(data-label);
        color: var(--muted);
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-align: left;
        text-transform: uppercase;
    }
    .refund-items-table td.refund-product-cell {
        display: block;
        padding-top: 12px !important;
        padding-bottom: 12px !important;
        text-align: left !important;
    }
    .refund-items-table td.refund-product-cell::before {
        display: block;
        margin-bottom: 6px;
    }
    .refund-qty-input {
        width: min(120px, 100%) !important;
        min-height: 44px;
        font-size: 16px;
    }
    .refund-items-table tfoot { display: block; margin-top: 10px; }
    .refund-items-table tfoot tr {
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 10px;
        padding: 13px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--primary-lighter) !important;
    }
    .refund-items-table tfoot td {
        display: block;
        width: auto;
        padding: 0 !important;
        border: 0;
        text-align: left !important;
    }
    .refund-items-table tfoot td:last-child {
        text-align: right !important;
        white-space: nowrap;
    }
    .refund-form-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
    .refund-form-actions .btn {
        width: 100%;
        min-height: 46px;
        white-space: normal;
    }
    .rp-wrap { padding: 12px; }
    .rp { padding-left: 10px; padding-right: 10px; }
}
@media (max-width: 440px) {
    .refund-summary-row,
    .refund-card-actions,
    .refund-form-actions { grid-template-columns: 1fr; }
    .refund-items-table td,
    .refund-history-table td { grid-template-columns: 88px minmax(0, 1fr); }
    .refund-items-table tfoot tr { grid-template-columns: 1fr; }
    .refund-items-table tfoot td:last-child { text-align: left !important; font-size: 1.05rem !important; }
    .rp-wrap { margin-inline: -1px; padding: 10px; }
    .rp { max-width: 100%; }
}

.refund-receipt-col { min-width: 0; }
.rp-wrap { position: sticky; top: 80px; min-width: 0; }
.rp-label { margin: 0 0 10px; font-size: 13px; font-weight: 700; color: var(--text); }
@media (max-width: 1100px) { .rp-wrap { position: static; } }
</style>
<link rel="stylesheet" href="<?= base_url('assets/css/receipts.css') ?>?v=20260930-1">

<div class="page-header refund-page-header" style="margin-bottom:20px;">
    <h1 style="margin:0;">Process Refund</h1>
    <p class="muted" style="margin:6px 0 0;">
        Select items and quantities to refund for invoice
        <strong><?= esc($sale['invoice_no']) ?></strong>.
    </p>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <div class="sp"></div>
<?php endif; ?>

<div class="refund-layout">

    <!-- ════════════════════════════════════
         LEFT COLUMN — form
    ════════════════════════════════════ -->
    <div>

        <!-- Sale summary card -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-head refund-sale-head">
                <div>
                    <h2 style="margin:0;">Sale Details</h2>
                    <p class="muted refund-sale-meta" style="margin:4px 0 0;">
                        <?= esc($sale['branch_name'] ?? '—') ?> &nbsp;·&nbsp;
                        Cashier: <?= esc($sale['full_name'] ?? '—') ?> &nbsp;·&nbsp;
                        <?= esc(\App\Libraries\DisplayDate::dateTime($sale['sale_date'])) ?>
                    </p>
                </div>
                <div class="refund-card-actions">
                    <?php
                    $statusClass = match($sale['status']) {
                        'completed'          => 'badge-success',
                        'partially_refunded' => 'badge-warning',
                        'refunded'           => 'badge-danger',
                        default              => 'badge-neutral',
                    };
                    $statusLabel = match($sale['status']) {
                        'completed'          => 'Completed',
                        'partially_refunded' => 'Partially Refunded',
                        'refunded'           => 'Fully Refunded',
                        default              => ucfirst($sale['status']),
                    };
                    ?>
                    <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                    <a href="<?= site_url('cashier/sales/history') ?>" class="btn secondary btn-sm">← Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="refund-summary-row">
                    <div class="refund-summary-item"><span class="label">Invoice</span><strong><?= esc($sale['invoice_no']) ?></strong></div>
                    <div class="refund-summary-item"><span class="label">Original Total</span><strong>₱<?= number_format((float)$sale['final_total'], 2) ?></strong></div>
                    <div class="refund-summary-item"><span class="label">Payment</span><strong><?= esc(ucfirst($sale['payment_method'])) ?></strong></div>
                    <?php if (!empty($sale['reference_no'])): ?>
                        <div class="refund-summary-item"><span class="label">Ref No.</span><strong><?= esc($sale['reference_no']) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Refund form -->
        <form method="post" action="<?= site_url('cashier/sales/refund-partial/' . $sale['id']) ?>">
            <?= csrf_field() ?>
            <div class="card" style="padding:18px;margin-bottom:16px;">
                <label for="refund-method" class="label">Refund payment method</label>
                <select id="refund-method" name="refund_method" class="input" required>
                    <option value="">Select how the customer is repaid</option>
                    <?php foreach (['cash' => 'Cash', 'gcash' => 'GCash', 'card' => 'Card'] as $value => $label): ?>
                        <option value="<?= esc($value) ?>" <?= old('refund_method') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="muted" style="margin-top:10px;">Only items marked Resellable return to available stock. Quarantined, damaged, and expired returns are recorded separately. Submit separate refunds if units of the same item have different conditions.</p>
            </div>
            <input type="hidden" name="_refund_token" value="<?= esc($refundToken) ?>">

            <div class="card" style="margin-bottom:16px;">
                <?php
                $hasRefundableItems = false;
                foreach ($items as $refundItem) {
                    if ((int) ($refundItem['refundable_qty'] ?? 0) > 0) {
                        $hasRefundableItems = true;
                        break;
                    }
                }
                ?>
                <div class="card-head" style="gap:12px; flex-wrap:wrap;">
                    <div>
                        <h2 style="margin:0;">Select Items to Refund</h2>
                        <p class="muted" style="margin:4px 0 0;">
                            Enter 0 for items you are <em>not</em> refunding.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn secondary"
                        id="refund-all-btn"
                        title="Select the full remaining refundable quantity for every item"
                        <?= $hasRefundableItems ? '' : 'disabled' ?>
                    >
                        Refund All
                    </button>
                </div>

                <div class="table-wrap refund-items-wrap">
                    <table class="refund-items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="text-align:right;">Refund Price</th>
                                <th style="text-align:right;">Sold</th>
                                <th style="text-align:right;">Refunded</th>
                                <th style="text-align:right;">Remaining</th>
                                <th>Return condition</th>
                                <th style="text-align:right; min-width:120px;">Qty to Refund</th>
                                <th style="text-align:right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <?php
                                $refundable = (int) $item['refundable_qty'];
                                $disabled   = $refundable <= 0;
                                ?>
                                <tr <?= $disabled ? 'style="opacity:.4;"' : '' ?>>
                                    <td class="refund-product-cell" data-label="Product">
                                        <strong><?= esc($item['product_name_snapshot']) ?></strong>
                                        <?php if ($disabled): ?>
                                            <br><small class="muted" style="font-size:11px;">Fully refunded</small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Refund Price" style="text-align:right;">
                                        ₱<?= number_format((float) $item['refund_unit_price'], 2) ?>
                                        <?php if ((float) ($item['discount_applied'] ?? 0) > 0): ?>
                                            <br><small class="muted" style="font-size:10px;">after discount</small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Sold" style="text-align:right;"><?= (int)$item['quantity'] ?></td>
                                    <td data-label="Refunded" style="text-align:right;"><?= (int)$item['already_refunded'] ?></td>
                                    <td data-label="Remaining" style="text-align:right; font-weight:600;">
                                        <?= $refundable ?>
                                    </td>
                                    <td data-label="Condition">
                                        <select class="input" name="return_condition[<?= (int) $item['id'] ?>]" aria-label="Return condition for <?= esc($item['product_name_snapshot']) ?>" <?= $disabled ? 'disabled' : '' ?> style="min-width:140px;max-width:100%;">
                                            <?php foreach (\App\Libraries\ReturnCondition::LABELS as $value => $label): ?>
                                                <option value="<?= esc($value) ?>" <?= old('return_condition.' . $item['id'], 'quarantined') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td data-label="Qty to Refund" style="text-align:right;">
                                        <input
                                            type="number"
                                            name="refund[<?= (int)$item['id'] ?>]"
                                            value="0"
                                            min="0"
                                            max="<?= $refundable ?>"
                                            step="1"
                                            <?= $disabled ? 'disabled' : '' ?>
                                            class="input refund-qty-input"
                                            style="width:85px; text-align:right; display:inline-block;"
                                            data-price="<?= (float) $item['refund_unit_price'] ?>"
                                            data-name="<?= esc($item['product_name_snapshot'], 'attr') ?>"
                                            data-row-id="<?= (int)$item['id'] ?>"
                                        >
                                    </td>
                                    <td data-label="Amount" style="text-align:right; font-weight:600;" id="row-amount-<?= (int)$item['id'] ?>">
                                        ₱0.00
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:var(--surface-2);">
                                <td colspan="7" style="text-align:right; font-weight:700; padding:12px 16px;">
                                    Total Refund Amount:
                                </td>
                                <td style="text-align:right; font-weight:700; font-size:15px; padding:12px 16px;" id="total-refund-amount">
                                    ₱0.00
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="card-body" style="border-top:1.5px solid var(--line);">
                    <div class="field">
                        <label class="label">Reason for Refund <span style="color:var(--danger);">*</span></label>
                        <textarea
                            name="reason"
                            class="input"
                            rows="3"
                            placeholder="e.g. Customer received wrong item, product was damaged, etc."
                            maxlength="500"
                            required
                            id="refund-reason"
                        ><?= esc(old('reason')) ?></textarea>
                        <small class="muted" style="font-size:12px;">Required. Min 3 characters.</small>
                    </div>

                    <div class="refund-form-actions">
                        <a href="<?= site_url('cashier/sales/history') ?>" class="btn secondary">Cancel</a>
                        <button type="submit" class="btn btn-danger" id="submit-refund-btn" disabled>
                            Process Refund
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Past refund history -->
        <?php if (!empty($refundHistory)): ?>
        <div class="card">
            <div class="card-head">
                <div>
                    <h2 style="margin:0;">Refund History</h2>
                    <p class="muted" style="margin:4px 0 0;">Past refund transactions for this sale.</p>
                </div>
            </div>
            <div class="table-wrap refund-history-wrap">
                <table class="refund-history-table">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Product</th>
                            <th style="text-align:right;">Qty</th>
                            <th style="text-align:right;">Amount</th>
                            <th>Condition / Payout</th>
                            <th>Processed By</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($refundHistory as $r): ?>
                            <tr>
                                <td data-label="Date &amp; Time"><?= esc(\App\Libraries\DisplayDate::dateTime($r['created_at'])) ?></td>
                                <td data-label="Product"><?= esc($r['product_name_snapshot']) ?></td>
                                <td data-label="Qty" style="text-align:right;"><?= (int)$r['quantity_refunded'] ?></td>
                                <td data-label="Amount" style="text-align:right;">₱<?= number_format((float)$r['refund_subtotal'], 2) ?></td>
                                <td data-label="Condition / Payout"><?= esc(ucfirst($r['return_condition'] ?? 'resellable')) ?> / <?= esc(strtoupper($r['refund_method'] ?? $sale['payment_method'])) ?></td>
                                <td data-label="Processed By"><?= esc($r['refunded_by_name'] ?? '—') ?></td>
                                <td data-label="Reason"><?= esc($r['reason'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ════════════════════════════════════
         RIGHT COLUMN — live receipt preview
    ════════════════════════════════════ -->
    <div class="refund-receipt-col">
        <div class="rp-wrap">
            <p class="rp-label">Refund preview</p>
            <article class="receipt-paper receipt-paper--refund" aria-label="Refund preview">
                <?= view('partials/receipt_header', ['businessName' => $refundBusinessName, 'businessType' => $refundBusinessType, 'documentType' => 'Refund preview', 'sale' => $sale]) ?>
                <div class="receipt-status receipt-status--refund">Preview · not yet submitted</div>
                <hr class="receipt-rule">
                <dl class="receipt-meta">
                    <dt>Original invoice</dt><dd><?= esc($sale['invoice_no']) ?></dd>
                    <dt>Original sale date</dt><dd><?= \App\Libraries\DisplayDate::date($sale['sale_date']) ?></dd>
                    <dt>Refund paid via</dt><dd id="rp-method">Select a payment method</dd>
                </dl>
                <hr class="receipt-rule">
                <table class="receipt-items" aria-label="Items selected for refund">
                    <thead><tr><th scope="col">Item / quantity × price</th><th scope="col">Refund</th></tr></thead>
                    <tbody id="rp-items-body"><tr><td colspan="2" class="receipt-empty">Select items to preview the refund.</td></tr></tbody>
                </table>
                <table class="receipt-totals" aria-label="Preview totals">
                    <tr class="receipt-grand"><td>This refund</td><td id="rp-grand-total" aria-live="polite">₱0.00</td></tr>
                    <tr><td>Original sale total</td><td>₱<?= number_format((float) $sale['final_total'], 2) ?></td></tr>
                </table>
                <div class="receipt-note"><strong>Reason for refund</strong><span id="rp-reason">—</span></div>
                <hr class="receipt-rule">
                <div class="receipt-footer">A printable refund slip will be available after you submit.</div>
            </article>
        </div>
    </div>

</div><!-- /.refund-layout -->

<script>
(function () {
    const inputs    = document.querySelectorAll('.refund-qty-input');
    const totalEl   = document.getElementById('total-refund-amount');
    const submitBtn = document.getElementById('submit-refund-btn');
    const rpBody    = document.getElementById('rp-items-body');
    const rpGrand   = document.getElementById('rp-grand-total');
    const rpReason     = document.getElementById('rp-reason');
    const reasonTxt    = document.getElementById('refund-reason');
    const refundAllBtn = document.getElementById('refund-all-btn');

    function fmt(n) {
        return '₱' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[character];
        });
    }

    function recalc() {
        let total       = 0;
        let hasAny      = false;
        let hasEligible = false;
        let allAtMax    = true;
        const rows      = [];

        inputs.forEach(function (input) {
            const rowId  = input.dataset.rowId;
            const price  = parseFloat(input.dataset.price) || 0;
            const name   = input.dataset.name || '—';
            const qty    = parseInt(input.value, 10) || 0;
            const maxQty = parseInt(input.max, 10) || 0;
            const amount = price * qty;
            const conditionField = document.getElementsByName('return_condition[' + rowId + ']')[0];
            const condition = conditionField ? conditionField.value : '';

            if (!input.disabled && maxQty > 0) {
                hasEligible = true;
                if (qty !== maxQty) allAtMax = false;
            }

            // Update inline table cell
            const cellEl = document.getElementById('row-amount-' + rowId);
            if (cellEl) cellEl.textContent = fmt(amount);

            total += amount;
            if (qty > 0) {
                hasAny = true;
                rows.push({ name, qty, price, amount, condition });
            }
        });

        // Update totals
        totalEl.textContent = fmt(total);
        submitBtn.disabled  = !hasAny;
        rpGrand.textContent = fmt(total);
        rpReason.textContent = reasonTxt?.value.trim() || '—';
        const method = document.getElementById('refund-method');
        document.getElementById('rp-method').textContent = method && method.value ? method.options[method.selectedIndex].text : 'Select a payment method';

        if (refundAllBtn) {
            refundAllBtn.disabled = !hasEligible || allAtMax;
            refundAllBtn.textContent = allAtMax && hasEligible ? 'All Items Selected' : 'Refund All';
        }

        // Rebuild receipt preview rows
        if (rows.length === 0) {
            rpBody.innerHTML = '<tr><td colspan="2" class="receipt-empty">Select items to preview the refund.</td></tr>';
        } else {
            rpBody.innerHTML = rows.map(function (r) {
                return '<tr>'
                    + '<td><div class="receipt-item-name">' + escapeHtml(r.name) + '</div>'
                    + '<div class="receipt-item-detail">' + r.qty + ' × ' + fmt(r.price) + '</div>'
                    + '<div class="receipt-item-detail">Condition: ' + escapeHtml(r.condition.charAt(0).toUpperCase() + r.condition.slice(1)) + '</div></td>'
                    + '<td>' + fmt(r.amount) + '</td>'
                    + '</tr>';
            }).join('');
        }
    }

    document.querySelectorAll('[name^="return_condition["], #refund-method').forEach(field => field.addEventListener('change', recalc));

    // Update reason on receipt preview
    if (reasonTxt) {
        reasonTxt.addEventListener('input', function () {
            rpReason.textContent = this.value.trim() || '—';
        });
    }

    inputs.forEach(function (input) {
        input.addEventListener('input', recalc);
    });

    if (refundAllBtn) {
        refundAllBtn.addEventListener('click', function () {
            inputs.forEach(function (input) {
                if (input.disabled) return;

                const maxQty = parseInt(input.max, 10) || 0;
                input.value = maxQty;
            });

            recalc();
            if (reasonTxt) reasonTxt.focus();
        });
    }

    recalc();
})();
</script>

<?= $this->endSection() ?>
