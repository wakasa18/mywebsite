<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<?php
$discountedSale = \App\Libraries\DiscountPolicy::hasDiscount($sale, $items);
$discountContext ??= (new \App\Libraries\SaleCorrectionPricing(db_connect()))->context($sale, $items);
$quantitiesLocked = !empty($discountContext['error']);
$formInput ??= [];
$postedItems = is_array($formInput['items'] ?? null) ? $formInput['items'] : [];
$postedValue = static function ($name, $default = '') use ($formInput) {
    return is_scalar($formInput[$name] ?? null) ? $formInput[$name] : old($name, $default);
};
?>

<div class="editor-page editor-page--wide">

<style>
    .correction-page { min-width: 0; }
    .correction-grid {
        display: grid;
        grid-template-columns: var(--grid-sidebar-340);
        gap: 20px;
        align-items: start;
        min-width: 0;
    }
    .correction-main,
    .correction-side { min-width: 0; }
    .correction-side { position: sticky; top: 80px; }
    .sc-card {
        background: var(--surface);
        border: 1.5px solid var(--line);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-bottom: 18px;
        min-width: 0;
    }
    .sc-card:last-child { margin-bottom: 0; }
    .sc-head { padding: 16px 20px; border-bottom: 1.5px solid var(--line); background: var(--primary-light); }
    .sc-head h3 { margin: 0; font-size: 16px; font-weight: 800; color: var(--primary-dark); overflow-wrap: anywhere; }
    .sc-head p { font-size: 13px; color: var(--muted); margin: 2px 0 0; line-height: 1.45; }
    .sc-body { padding: 18px 20px; min-width: 0; }
    .warning-banner {
        background: #fffbeb;
        border: 1.5px solid #fde68a;
        border-radius: 10px;
        padding: 12px 16px;
        margin: 16px 18px;
        font-size: 13.5px;
        line-height: 1.5;
        color: #92400e;
        font-weight: 500;
    }
    #settlementPanel label, #settlementConfirmationLabel { color: #92400e; }
    .field-row { display: grid; grid-template-columns: var(--grid-2col); gap: 14px; margin-bottom: 14px; min-width: 0; }
    .field-row > div { min-width: 0; }
    .field-row.single { grid-template-columns: 1fr; }
    .co-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--text-2); margin-bottom: 5px; }
    .co-input {
        width: 100%;
        min-width: 0;
        padding: 10px 12px;
        border: 1.5px solid var(--line-2);
        border-radius: 9px;
        font-size: 14px;
        font-family: inherit;
        color: var(--text);
        background: var(--surface);
        outline: none;
        transition: border-color .15s, box-shadow .15s;
    }
    .co-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(43,127,255,.1); }
    .co-input[readonly] { background: var(--surface-2); color: var(--muted); cursor: default; }
    .item-table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .item-table { width: 100%; min-width: 680px; border-collapse: collapse; }
    .item-table th { background: var(--surface-2); font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); padding: 10px 12px; text-align: left; border-bottom: 1.5px solid var(--line); white-space: nowrap; }
    .item-table td { padding: 11px 12px; border-bottom: 1px solid var(--line); font-size: 14px; color: var(--text-2); vertical-align: middle; }
    .item-table tbody tr:last-child td { border-bottom: none; }
    .item-table tfoot td { padding: 12px 14px; background: var(--surface-2); border-top: 1.5px solid var(--line); font-weight: 700; }
    .qty-input { width: 76px; min-height: 40px; padding: 7px 8px; border: 2px solid var(--line-2); border-radius: 8px; font-size: 15px; font-weight: 600; text-align: center; outline: none; font-family: inherit; background: var(--surface); color: var(--text); transition: border-color .15s; }
    .qty-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(43,127,255,.1); }
    .qty-changed { border-color: #f59e0b !important; background: #fffbeb; }
    .save-btn { width: 100%; min-height: 48px; padding: 13px 16px; background: var(--primary); color: #fff; border: none; border-radius: 11px; font-size: 16px; font-weight: 800; font-family: inherit; cursor: pointer; transition: background .15s, opacity .15s; letter-spacing: -.1px; }
    .save-btn:hover { background: var(--primary-dark); }
    .save-btn:disabled { opacity: .55; cursor: not-allowed; }
    .history-item { padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 13px; }
    .history-item:last-child { border-bottom: none; }
    .history-who { font-weight: 700; color: var(--text); overflow-wrap: anywhere; }
    .history-when { font-size: 11.5px; color: var(--muted); margin-top: 1px; }
    .history-what { color: var(--text-2); margin-top: 4px; line-height: 1.5; overflow-wrap: anywhere; }
    .mono { font-variant-numeric: tabular-nums; }

    @media (max-width: 1100px) {
        .correction-grid { grid-template-columns: 1fr; }
        .correction-side { position: static; }
    }

    @media (max-width: 700px) {
        .correction-page .page-header {
            align-items: stretch !important;
            flex-direction: column;
            gap: 12px !important;
        }
        .correction-page .page-header > div { min-width: 0; }
        .correction-page .page-header h1 { font-size: clamp(21px, 7vw, 28px); overflow-wrap: anywhere; }
        .correction-page .page-header p { line-height: 1.5; overflow-wrap: anywhere; }
        .correction-page .page-header .btn { width: 100%; min-height: 46px; justify-content: center; }
        .correction-grid { gap: 14px; }
        .field-row { grid-template-columns: 1fr; gap: 11px; margin-bottom: 12px; }
        .sc-card { border-radius: 12px; margin-bottom: 14px; }
        .sc-head { padding: 14px 15px; }
        .sc-body { padding: 15px; }
        .warning-banner { margin: 13px 14px; padding: 11px 12px; font-size: 13px; }
        .co-input { min-height: 44px; font-size: 16px; }
        textarea.co-input { min-height: 92px; }

        .item-table-wrap { overflow: visible; padding: 0 13px 14px; }
        .item-table,
        .item-table tbody,
        .item-table tfoot,
        .item-table tr,
        .item-table td { display: block; width: 100%; min-width: 0; }
        .item-table { min-width: 0; border-collapse: separate; }
        .item-table thead { display: none; }
        .item-table tbody { display: grid; gap: 10px; }
        .item-table tbody tr {
            border: 1px solid var(--line);
            border-radius: 11px;
            overflow: hidden;
            background: var(--surface);
            box-shadow: 0 3px 12px rgba(15,23,42,.05);
        }
        .item-table tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 10px 12px !important;
            border-bottom: 1px solid var(--line) !important;
            text-align: right;
            overflow-wrap: anywhere;
        }
        .item-table tbody td:last-child { border-bottom: 0 !important; }
        .item-table tbody td::before {
            content: attr(data-label);
            flex: 0 0 42%;
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .35px;
            text-transform: uppercase;
            text-align: left;
        }
        .item-table tbody td.product-cell {
            align-items: flex-start;
            background: var(--surface-2);
            color: var(--text);
        }
        .item-table tbody td.product-cell::before { padding-top: 2px; }
        .qty-input { width: 92px; min-height: 44px; font-size: 16px; }

        .item-table tfoot { margin-top: 12px; border: 1px solid var(--line); border-radius: 11px; overflow: hidden; }
        .item-table tfoot tr { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--surface-2); }
        .item-table tfoot tr + tr { border-top: 1px solid var(--line); }
        .item-table tfoot td {
            width: auto;
            padding: 11px 12px !important;
            border: 0 !important;
            background: transparent !important;
            text-align: left !important;
            font-size: 12.5px !important;
        }
        .item-table tfoot td:last-child { margin-left: auto; text-align: right !important; white-space: nowrap; }
        .save-btn,
        .correction-side .btn { min-height: 48px; }
    }

    @media (max-width: 390px) {
        .sc-head { padding: 13px; }
        .sc-body { padding: 13px; }
        .warning-banner { margin-inline: 12px; }
        .item-table-wrap { padding-inline: 11px; }
        .item-table tbody td::before { flex-basis: 46%; }
        .item-table tfoot tr { gap: 6px; }
        .item-table tfoot td { padding-inline: 10px !important; }
    }
</style>

<div class="correction-page">

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <div>
        <h1>✏️ Correct Sale Record</h1>
        <p>Invoice <strong><?= esc($sale['invoice_no']) ?></strong> — all changes are fully audit-logged.</p>
    </div>
    <a href="<?= site_url('cashier/sales/history') ?>" class="btn btn-secondary">← Back to History</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php
// Collect all validation errors
$allErrors = $validation->getErrors();
$itemError = $itemError ?? null;
?>
<?php if (!empty($allErrors) || $itemError): ?>
    <div class="alert error" style="margin-bottom:18px;">
        <strong>Please fix the following:</strong>
        <ul style="margin:6px 0 0; padding-left:18px;">
            <?php foreach ($allErrors as $err): ?>
                <li><?= esc($err) ?></li>
            <?php endforeach; ?>
            <?php if ($itemError): ?>
                <li><?= esc($itemError) ?></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= site_url('admin/sale-correction/update/' . $sale['id']) ?>" id="correctionForm">
    <?= csrf_field() ?>
        <input type="hidden" name="sale_revision" value="<?= esc(\App\Libraries\SaleRevision::fingerprint($sale, $items)) ?>">
        <input type="hidden" name="discount_revision" value="<?= esc($discountContext['revision']) ?>">
        <input type="hidden" name="settlement_difference" id="settlementDifference" value="0">

    <div class="correction-grid">

        <!-- ── LEFT COLUMN ── -->
        <div class="correction-main">

            <!-- Sale Info (read-only) -->
            <div class="sc-card">
                <div class="sc-head">
                    <h3>📋 Sale Information</h3>
                    <p>Read-only summary</p>
                </div>
                <div class="sc-body">
                    <div class="field-row">
                        <div>
                            <div class="co-label">Invoice No.</div>
                            <input type="text" class="co-input mono" value="<?= esc($sale['invoice_no']) ?>" readonly>
                        </div>
                        <div>
                            <div class="co-label">Sale Date</div>
                            <input type="text" class="co-input" value="<?= esc(\App\Libraries\DisplayDate::dateTime($sale['sale_date'])) ?>" readonly>
                        </div>
                    </div>
                    <div class="field-row">
                        <div>
                            <div class="co-label">Cashier</div>
                            <input type="text" class="co-input" value="<?= esc($sale['full_name']) ?>" readonly>
                        </div>
                        <div>
                            <div class="co-label">Branch</div>
                            <input type="text" class="co-input" value="<?= esc($sale['branch_name']) ?>" readonly>
                        </div>
                    </div>
                    <div class="field-row">
                        <div>
                            <div class="co-label">Status</div>
                            <input type="text" class="co-input" value="<?= ucfirst(esc($sale['status'])) ?>" readonly>
                        </div>
                        <div>
                            <div class="co-label">Amount Paid</div>
                            <input type="text" class="co-input mono" value="₱<?= number_format((float)$sale['amount_paid'], 2) ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Editable header fields -->
            <div class="sc-card">
                <div class="sc-head">
                    <h3>🔧 Editable Sale Fields</h3>
                    <p>Correct payment method, reference number, and notes</p>
                </div>
                <div class="sc-body">
                    <div class="field-row">
                        <div>
                            <div class="co-label">Payment Method</div>
                            <select name="payment_method" id="paymentMethod" class="co-input" required>
                                <option value="cash"  <?= $postedValue('payment_method', $sale['payment_method']) === 'cash'  ? 'selected' : '' ?>>💵 Cash</option>
                                <option value="gcash" <?= $postedValue('payment_method', $sale['payment_method']) === 'gcash' ? 'selected' : '' ?>>📱 GCash</option>
                                <option value="card"  <?= $postedValue('payment_method', $sale['payment_method']) === 'card'  ? 'selected' : '' ?>>💳 Card</option>
                            </select>
                        </div>
                        <div>
                            <div class="co-label">Reference No. <span id="referenceHint" style="color:var(--muted);font-weight:400;">(optional)</span></div>
                            <input type="text" name="reference_no" id="referenceNo" class="co-input" maxlength="100"
                                value="<?= esc($postedValue('reference_no', $sale['reference_no'] ?? ''), 'attr') ?>"
                                placeholder="Optional GCash or card reference number">
                        </div>
                    </div>
                    <div class="field-row single">
                        <div>
                            <div class="co-label">Notes <span style="color:var(--muted);font-weight:400;">(optional)</span></div>
                            <textarea name="notes" class="co-input" rows="2" maxlength="500" placeholder="Optional notes…"><?= esc($postedValue('notes', $sale['notes'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item quantity corrections -->
            <div class="sc-card">
                <div class="sc-head">
                    <h3>📦 Correct Item Quantities</h3>
                    <p>Adjust quantities if an error was made. Stock adjusts automatically.</p>
                </div>
                <div class="warning-banner">
                    <?php if ($quantitiesLocked): ?>
                        <?= esc($discountContext['error']) ?>
                    <?php elseif ($discountedSale): ?>
                        Quantities can be corrected. The linked discount is recalculated using the original selling prices. Minimum purchases, eligible products and discount caps still apply. Settle the payment difference before saving.
                    <?php else: ?>
                        Changing a quantity will auto-adjust branch stock and create a stock adjustment log entry.
                    <?php endif; ?>
                </div>
                <div class="item-table-wrap">
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Original Qty</th>
                                <th>Corrected Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php foreach ($items as $item): ?>
                            <tr data-price="<?= (float)$item['price'] ?>"
                                data-discount-eligible="<?= in_array((int) $item['id'], $discountContext['eligible'], true) ? '1' : '0' ?>"
                                data-product-id="<?= (int)$item['product_id'] ?>">
                                <td class="product-cell" data-label="Product" style="font-weight:700;font-size:14px;"><?= esc($item['product_name_snapshot']) ?></td>
                                <td data-label="Unit Price" class="mono" style="font-weight:700;color:var(--primary-dark);">₱<?= number_format((float)$item['price'], 2) ?></td>
                                <td data-label="Original Qty" style="font-weight:600;color:var(--muted);"><?= esc($item['quantity']) ?></td>
                                <td data-label="Corrected Qty">
                                    <input type="number"
                                        name="items[<?= (int)$item['id'] ?>]"
                                        value="<?= esc(is_scalar($postedItems[$item['id']] ?? null) ? $postedItems[$item['id']] : $item['quantity'], 'attr') ?>"
                                        min="0"
                                        max="100000"
                                        step="1"
                                        <?= $quantitiesLocked ? 'readonly aria-readonly="true"' : '' ?>
                                        required
                                        class="qty-input"
                                        data-original="<?= (int)$item['quantity'] ?>">
                                </td>
                                <td data-label="Subtotal" class="subtotal-cell mono" style="font-weight:800;color:var(--primary-dark);">
                                    ₱<?= number_format((float)$item['subtotal'], 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align:right;font-size:13px;color:var(--muted);">New Total (before discount):</td>
                                <td id="newTotalCell" class="mono" style="font-size:16px;color:var(--primary-dark);">
                                    ₱<?= number_format((float)$sale['total_amount'], 2) ?>
                                </td>
                            </tr>
                            <?php if ($discountedSale): ?>
                            <tr>
                                <td colspan="4" style="text-align:right;font-size:13px;color:var(--muted);">Discount:</td>
                                <td id="discountTotalCell" class="mono" style="font-size:14px;color:var(--danger);">
                                    − ₱<?= number_format((float)$sale['discount_amount'], 2) ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="4" style="text-align:right;font-size:14px;font-weight:800;color:var(--text);">Final Total:</td>
                                <td id="finalTotalCell" class="mono" style="font-size:18px;font-weight:800;color:var(--primary-dark);">
                                    ₱<?= number_format((float)$sale['final_total'], 2) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

        <!-- ── RIGHT COLUMN ── -->
        <aside class="correction-side">

            <div class="sc-card">
                <div class="sc-head">
                    <h3>📝 Correction Reason</h3>
                    <p>Required — saved in audit log</p>
                </div>
                <div class="sc-body">
                    <div style="margin-bottom:16px;">
                        <div class="co-label">Reason <span style="color:var(--danger);">*</span></div>
                        <textarea name="correction_reason" class="co-input" rows="5" maxlength="500"
                            placeholder="Describe why this correction is needed…"
                            required
                            style="resize:vertical;"><?= esc($postedValue('correction_reason')) ?></textarea>
                        <small style="font-size:12px;color:var(--muted);">Min. 5 characters. Stored permanently in the audit log.</small>
                    </div>

                    <div id="correctionPreviewNotice" class="alert warning" style="display:none;margin-bottom:12px;"></div>
                    <div id="settlementPanel" class="warning-banner" style="margin:0 0 16px;" hidden>
                        <strong id="settlementAction"></strong>
                        <p style="margin:6px 0 12px;">Use the selected payment method. Original cash change is already returned and does not cover this difference.</p>
                        <label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer;">
                            <input type="checkbox" name="settlement_confirmed" id="settlementConfirmed" value="1" style="margin-top:4px;flex-shrink:0;">
                            <span id="settlementConfirmationLabel">I have settled the exact payment difference with the customer.</span>
                        </label>
                    </div>
                    <button type="submit" class="save-btn" data-busy-label="Applying…">Apply Correction</button>
                    <a href="<?= site_url('cashier/sales/history') ?>"
                        class="btn btn-secondary btn-full" style="margin-top:10px;display:flex;justify-content:center;">✕ Cancel</a>
                </div>
            </div>

            <?php if (!empty($correctionHistory)): ?>
            <div class="sc-card">
                <div class="sc-head">
                    <h3>🕓 Correction History</h3>
                    <p><?= count($correctionHistory) ?> previous correction(s)</p>
                </div>
                <div class="sc-body">
                    <?php foreach ($correctionHistory as $log): ?>
                    <div class="history-item">
                        <div class="history-who"><?= esc($log['full_name'] ?? $log['username'] ?? 'Unknown') ?></div>
                        <div class="history-when"><?= esc(\App\Libraries\DisplayDate::dateTime($log['log_time'])) ?></div>
                        <div class="history-what"><?= esc($log['activity']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </aside>

    </div>
</form>

<script src="<?= base_url('assets/js/discount-math.js') ?>?v=20260920"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const quantitiesLocked = <?= $quantitiesLocked ? 'true' : 'false' ?>;
    const discountRule = <?= json_encode($discountContext['rule'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const originalTotal = <?= (float) $sale['total_amount'] ?>;
    const originalFinalTotal = <?= (float) $sale['final_total'] ?>;
    const originalDiscount = <?= (float) $sale['discount_amount'] ?>;
    const money = window.PharxmacoDiscount;
    const paymentMethod = document.getElementById('paymentMethod');
    const referenceNo = document.getElementById('referenceNo');
    const referenceHint = document.getElementById('referenceHint');
    const submitButton = document.querySelector('.save-btn');
    const settlementPanel = document.getElementById('settlementPanel');
    const settlementConfirmed = document.getElementById('settlementConfirmed');
    const settlementDifference = document.getElementById('settlementDifference');
    let lastPreviewKey = null;

    function fmt(v) {
        return '₱' + parseFloat(v).toFixed(2);
    }

    function recalc() {
        let totalCents = 0;
        let eligibleCents = 0;
        let quantitiesChanged = false;
        let totalQuantity = 0;
        let invalidQuantity = false;
        const quantities = [];
        document.querySelectorAll('#itemsBody tr').forEach(function(row) {
            const price    = parseFloat(row.getAttribute('data-price') || 0);
            const input    = row.querySelector('.qty-input');
            const original = parseInt(input.getAttribute('data-original') || 0);
            const rawQty   = Number(input.value);
            invalidQuantity = invalidQuantity || input.value.trim() === '' || !Number.isSafeInteger(rawQty) || rawQty < 0 || rawQty > 100000;
            const qty      = Number.isSafeInteger(rawQty) && rawQty >= 0 ? rawQty : 0;
            const sub      = money.cents(price) * qty / 100;
            quantities.push(qty);
            totalQuantity += qty;
            totalCents += money.cents(sub);
            if (row.dataset.discountEligible === '1') eligibleCents += money.cents(sub);
            row.querySelector('.subtotal-cell').textContent = fmt(sub);
            if (qty !== original) {
                quantitiesChanged = true;
                input.classList.add('qty-changed');
            } else {
                input.classList.remove('qty-changed');
            }
        });

        const newTotal = document.getElementById('newTotalCell');
        const discountCell = document.getElementById('discountTotalCell');
        const finalCell = document.getElementById('finalTotalCell');
        const total = quantitiesChanged ? totalCents / 100 : originalTotal;
        const discountTotal = quantitiesChanged ? (discountRule ? money.calculate(eligibleCents / 100, discountRule) : 0) : originalDiscount;
        const finalTotal = quantitiesChanged ? Math.max(0, totalCents - money.cents(discountTotal)) / 100 : originalFinalTotal;
        const difference = money.cents(finalTotal) - money.cents(originalFinalTotal);
        const previewKey = JSON.stringify([quantities, difference, paymentMethod.value, referenceNo.value]);
        if (previewKey !== lastPreviewKey) settlementConfirmed.checked = false;
        lastPreviewKey = previewKey;
        settlementDifference.value = String(difference);
        settlementPanel.hidden = difference === 0;
        settlementConfirmed.required = difference !== 0;
        const settlementText = difference > 0 ? 'Collect an additional ' + fmt(difference / 100) : 'Pay back ' + fmt(-difference / 100);
        document.getElementById('settlementAction').textContent = settlementText;
        document.getElementById('settlementConfirmationLabel').textContent = difference > 0
            ? 'I collected the additional ' + fmt(difference / 100) + ' from the customer.'
            : 'I paid back ' + fmt(-difference / 100) + ' to the customer.';
        if (newTotal) newTotal.textContent = fmt(total);
        if (discountCell) discountCell.textContent = '− ' + fmt(discountTotal);
        if (finalCell) {
            finalCell.textContent = fmt(finalTotal);
            finalCell.style.color = 'var(--primary-dark)';
        }
        const notice = document.getElementById('correctionPreviewNotice');
        const emptySale = totalQuantity <= 0;
        const quantityLocked = quantitiesLocked && quantitiesChanged;
        const tooLarge = totalCents > 9999999999;
        if (notice) {
            if (invalidQuantity || tooLarge) {
                notice.textContent = 'Enter whole quantities from 0 to 100,000 and keep the total within the supported amount.';
                notice.style.display = 'block';
            } else if (quantityLocked) {
                notice.textContent = 'This sale’s discount cannot be verified. Only notes and payment details can be corrected.';
                notice.style.display = 'block';
            } else if (emptySale) {
                notice.textContent = 'Keep at least one item in a completed sale. Use Refund to reverse the entire sale.';
                notice.style.display = 'block';
            } else if (quantitiesChanged && discountRule && eligibleCents < money.cents(discountRule.minimum || 0)) {
                notice.textContent = 'The eligible items no longer meet the discount minimum of ' + fmt(discountRule.minimum) + '. The corrected total has no discount.';
                notice.style.display = 'block';
            } else {
                notice.textContent = '';
                notice.style.display = 'none';
            }
        }
        if (submitButton) submitButton.disabled = invalidQuantity || tooLarge || emptySale || quantityLocked || (difference !== 0 && !settlementConfirmed.checked);
        updateReferenceRequirement(difference);
    }

    function updateReferenceRequirement(difference) {
        referenceNo.required = difference !== 0 && paymentMethod.value !== 'cash';
        referenceHint.textContent = referenceNo.required ? '(required for payment difference)' : '(optional)';
        referenceNo.placeholder = paymentMethod.value === 'gcash'
            ? 'Optional GCash reference number'
            : paymentMethod.value === 'card'
                ? 'Optional card approval/reference number'
                : 'Not needed for cash';
        if (paymentMethod.value === 'cash') referenceNo.value = '';
    }

    document.querySelectorAll('.qty-input').forEach(function(inp) {
        inp.addEventListener('input', recalc);
    });
    paymentMethod.addEventListener('change', recalc);
    referenceNo.addEventListener('input', recalc);
    settlementConfirmed.addEventListener('change', recalc);
    recalc();
});
</script>

</div>
</div>

<?= $this->endSection() ?>
