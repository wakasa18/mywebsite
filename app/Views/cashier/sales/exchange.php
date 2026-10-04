<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<?php
$input ??= [];
$money = static fn ($cents) => '₱' . number_format($cents / 100, 2);
$value = static function ($group, $id, $default = 0) use ($input) {
    $value = is_array($input[$group] ?? null) ? ($input[$group][$id] ?? $default) : $default;
    return is_scalar($value) ? $value : $default;
};
$scalar = static fn ($name, $default = '') => is_scalar($input[$name] ?? null) ? $input[$name] : $default;
?>
<style>
.exchange-page { max-width:1200px;margin:0 auto; }
.exchange-page .card { margin-bottom:20px; }
.exchange-page .exchange-body { padding:20px; }
.exchange-page .exchange-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px; }
.exchange-page .exchange-actions { display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin:20px 0; }
.exchange-page .exchange-scroll { max-height:480px;overflow:auto; }
.exchange-page .exchange-qty { width:90px;min-width:0;max-width:100%;min-height:44px; }
.exchange-page .exchange-condition { width:100%;min-width:0;max-width:100%;min-height:44px; }
.exchange-page .exchange-summary { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px; }
.exchange-page .exchange-summary div { background:var(--surface-2,var(--surface));border:1px solid var(--line);padding:18px;border-radius:12px; }
.exchange-page .exchange-summary strong { display:block;font-size:24px;margin-top:8px; }
.exchange-page td { overflow-wrap:anywhere; }
.exchange-page [hidden] { display:none !important; }
.exchange-page .exchange-search { max-width:420px;margin-top:12px; }
@media(max-width:640px) { .exchange-page .exchange-grid,.exchange-page .exchange-summary { grid-template-columns:1fr; } .exchange-page .exchange-body { padding:14px; } .exchange-page .exchange-actions .btn { flex:1;min-height:44px; } }
</style>
<div class="exchange-page">
    <div class="page-header"><h1>Exchange Product</h1><p>Original invoice: <?= esc($sale['invoice_no']) ?> · <?= esc(\App\Libraries\DisplayDate::dateTime($sale['sale_date'])) ?></p></div>
    <?php $message = $error ?? session()->getFlashdata('error'); ?>
    <?php if ($message): ?><div class="alert danger" role="alert"><?= esc($message) ?></div><?php endif; ?>
    <?php if (!isset($quote)): ?>
    <p class="muted">Choose the returned items and replacements from the original selling branch. Review the exact payment difference before saving. Your POS cart is kept separately.</p>
    <form method="post" action="<?= site_url('cashier/sales/exchange/' . (int) $sale['id'] . '/review') ?>">
        <?= csrf_field() ?><input type="hidden" name="exchange_token" value="<?= esc($token, 'attr') ?>">
        <section class="card">
            <div class="card-head"><div><h2>1. Items being returned</h2><p>Return value uses the original amount paid after discounts. Select a condition for each returned item.</p></div></div>
            <div class="table-wrap"><table class="mobile-record-table"><thead><tr><th>Product</th><th>Available to return</th><th>Remaining paid value</th><th>Return quantity</th><th>Condition</th></tr></thead><tbody>
            <?php foreach ($items as $item): if ($item['remaining_qty'] <= 0) continue; $id = (int) $item['id']; ?>
                <tr><td><?= esc($item['product_name_snapshot']) ?></td><td><?= (int) $item['remaining_qty'] ?></td><td><?= $money($item['remaining_cents']) ?></td>
                <td><input class="input exchange-qty" type="number" name="returns[<?= $id ?>]" min="0" max="<?= (int) $item['remaining_qty'] ?>" step="1" value="<?= esc($value('returns', $id), 'attr') ?>" aria-label="Return quantity for <?= esc($item['product_name_snapshot'], 'attr') ?>"></td>
                <td class="record-wide"><select class="input exchange-condition" name="conditions[<?= $id ?>]" aria-label="Return condition for <?= esc($item['product_name_snapshot'], 'attr') ?>">
                <?php foreach (['quarantined' => 'Quarantine / inspect', 'resellable' => 'Resellable', 'damaged' => 'Damaged', 'expired' => 'Expired'] as $condition => $label): ?><option value="<?= $condition ?>" <?= $value('conditions', $id, 'quarantined') === $condition ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                </select></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        </section>
        <section class="card">
            <div class="card-head"><div><h2>2. Replacement products</h2><p>Choose a different product from the original order. Current prices apply, and only available stock is offered.</p><label class="label" for="exchangeSearch">Find replacement products</label><input id="exchangeSearch" type="search" class="input exchange-search" placeholder="Search product or SKU" autocomplete="off"></div></div>
            <div class="table-wrap exchange-scroll" tabindex="0" role="region" aria-label="Replacement products"><table class="mobile-record-table"><thead><tr><th>Product</th><th>Price</th><th>In stock</th><th>Replacement quantity</th></tr></thead><tbody>
            <?php foreach ($products as $product): $id = (int) $product['product_id']; ?>
                <tr data-exchange-product><td><?= esc($product['product_name']) ?><br><small class="muted"><?= esc($product['sku']) ?></small></td><td>₱<?= number_format((float) $product['price'], 2) ?></td><td><?= (int) $product['stock'] ?> <?= esc($product['unit']) ?></td>
                <td><input class="input exchange-qty" type="number" name="replacements[<?= $id ?>]" min="0" max="<?= min(100000, (int) $product['stock']) ?>" step="1" value="<?= esc($value('replacements', $id), 'attr') ?>" aria-label="Replacement quantity for <?= esc($product['product_name'], 'attr') ?>"></td></tr>
            <?php endforeach; ?>
            <?php if (!$products): ?><tr><td colspan="4">No different replacement products are available in this branch.</td></tr><?php else: ?><tr id="exchangeNoMatches" hidden><td colspan="4">No matching replacement products. Try another product name or SKU.</td></tr><?php endif; ?>
            </tbody></table></div>
            <div class="exchange-body"><p id="exchangeSearchStatus" class="muted" role="status"></p><p class="muted">Selected products remain visible when you search.</p></div>
        </section>
        <section class="card"><div class="card-head"><div><h2>3. Reason and settlement</h2><p>The review will show whether to collect a payment, pay a refund, or exchange evenly.</p></div></div>
            <div class="exchange-body exchange-grid">
                <div class="field"><label class="label" for="exchangeDiscount">Discount on replacement items</label><select id="exchangeDiscount" name="discount_id" class="input"><option value="">No discount</option><?php foreach ($discounts as $discount): ?><option value="<?= (int) $discount['id'] ?>" <?= (string) $scalar('discount_id') === (string) $discount['id'] ? 'selected' : '' ?>><?= esc($discount['discount_name']) ?><?= (int) ($sale['discount_id'] ?? 0) === (int) $discount['id'] ? ' (original discount)' : '' ?></option><?php endforeach; ?></select><p class="muted">The original discount is selected when still active. Reusing it counts eligible products the customer keeps toward its minimum purchase. Their existing discounts stay unchanged; fixed discounts and discount caps are shared with those kept items.</p></div>
                <div class="field"><label class="label" for="exchangeMethod">Payment / payout method</label><select id="exchangeMethod" name="settlement_method" class="input"><?php foreach (['cash' => 'Cash', 'gcash' => 'GCash', 'card' => 'Card'] as $method => $label): ?><option value="<?= $method ?>" <?= $scalar('settlement_method', 'cash') === $method ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                <div class="field"><label class="label" for="exchangeReason">Exchange reason</label><textarea id="exchangeReason" name="reason" class="input" minlength="3" maxlength="300" required><?= esc($scalar('reason')) ?></textarea></div>
                <div class="field"><label class="label" for="exchangeReference">Payment / payout reference</label><input id="exchangeReference" name="reference" class="input" maxlength="100" value="<?= esc($scalar('reference'), 'attr') ?>"><p class="muted">Required for a GCash or card payment difference. Leave blank for cash or an even exchange.</p></div>
            </div>
        </section>
        <div class="exchange-actions"><a class="btn secondary" href="<?= site_url('cashier/sales/history') ?>">Cancel</a><button class="btn btn-primary" type="submit">Review exchange</button></div>
    </form>
    <script>
    document.getElementById('exchangeSearch').addEventListener('input', function () {
        const query = this.value.trim().toLowerCase(); let visible = 0;
        document.querySelectorAll('[data-exchange-product]').forEach(row => {
            const selected = Number(row.querySelector('input').value) > 0;
            row.hidden = !selected && !row.textContent.toLowerCase().includes(query);
            if (!row.hidden) visible++;
        });
        const empty = document.getElementById('exchangeNoMatches');
        if (empty) empty.hidden = visible > 0;
        document.getElementById('exchangeSearchStatus').textContent = visible + (visible === 1 ? ' product shown' : ' products shown');
    });
    document.getElementById('exchangeSearch').dispatchEvent(new Event('input'));
    </script>
    <?php else: ?>
    <section class="card"><div class="card-head"><div><h2>Review your exchange</h2><p>Nothing has been saved yet. Confirm the items and settlement below.</p></div></div><div class="exchange-body">
        <div class="exchange-summary"><div>Returned item value<strong><?= $money($quote['credit']) ?></strong></div><div>Replacement total<strong><?= $money($quote['total']) ?></strong></div><div><?= $quote['difference'] > 0 ? 'Collect from customer' : ($quote['difference'] < 0 ? 'Pay back to customer' : 'Even exchange') ?><strong><?= $money(abs($quote['difference'])) ?></strong></div></div>
        <p>Settlement method: <strong><?= esc(strtoupper($quote['method'])) ?></strong>. <?= $quote['difference'] === 0 ? 'No money changes hands.' : 'Settle exactly the difference shown above.' ?></p>
        <p>Replacement discount: <?= $money($quote['discount']) ?>. Reason: <?= esc($quote['reason']) ?></p>
        <?php if (($quote['discountContext']['eligible_cents'] ?? 0) > 0): ?><p class="muted">Eligible products kept from this order: <?= $money($quote['discountContext']['eligible_cents']) ?> before discounts. This amount counts toward the minimum purchase; those products are not charged or discounted again.</p><?php endif; ?>
        <?php if ($quote['reference'] !== '' && $quote['method'] !== 'cash'): ?><p>Reference: <?= esc($quote['reference']) ?></p><?php endif; ?>
    </div></section>
    <div class="exchange-grid">
        <section class="card"><div class="card-head"><h2>Return these items</h2></div><div class="exchange-body">
        <?php foreach ($quote['returns'] as $entry): ?><p><strong><?= (int) $entry['quantity'] ?> × <?= esc($entry['item']['product_name_snapshot']) ?></strong><br><?= $money($entry['cents']) ?> · <?= esc(ucfirst($entry['condition'])) ?><?= $entry['restock'] ? ' — returned to stock' : ' — excluded from available stock' ?></p><?php endforeach; ?>
        </div></section>
        <section class="card"><div class="card-head"><h2>Give these replacements</h2></div><div class="exchange-body">
        <?php foreach ($quote['replacements'] as $entry): ?><p><strong><?= (int) $entry['quantity'] ?> × <?= esc($entry['name']) ?></strong><br><?= $money($entry['subtotal'] - $entry['discount']) ?> after discount</p><?php endforeach; ?>
        </div></section>
    </div>
    <form method="post" action="<?= site_url('cashier/sales/exchange/' . (int) $sale['id'] . '/complete') ?>">
        <?= csrf_field() ?><input type="hidden" name="exchange_token" value="<?= esc($token, 'attr') ?>">
        <label><input type="checkbox" required> I checked the returned items, replacements and payment / payout difference.</label>
        <div class="exchange-actions"><a class="btn secondary" href="<?= site_url('cashier/sales/exchange/' . (int) $sale['id']) ?>">Change selection</a><button class="btn btn-primary" type="submit">Confirm exchange</button></div>
    </form>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
