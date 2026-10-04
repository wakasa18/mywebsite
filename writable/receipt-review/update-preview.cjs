const fs = require('fs');
// Normalize characters lost by Windows PowerShell's default ASCII pipeline.
for (const file of ['receipt', 'refund_slip']) {
  const path = `app/Views/cashier/sales/${file}.php`;
  let s = fs.readFileSync(path, 'utf8');
  s = s.replaceAll('quantity ? price', 'quantity × price')
    .replaceAll('?<?= number_format', '₱<?= number_format')
    .replaceAll('?> ? ₱', '?> × ₱')
    .replaceAll('?₱', '−₱')
    .replaceAll('Sales receipt ? Reprint', 'Sales receipt · Reprint')
    .replaceAll("'M d, Y ? h:i A'", "'M d, Y · h:i A'")
    .replaceAll("?? '?'", "?? '—'")
    .replaceAll("' ? Reprinted copy'", "' · Reprinted copy'")
    .replaceAll('?> ? <?=', '?> — <?=')
    .replaceAll('Refund slip ? <?=', 'Refund slip — <?=');
  fs.writeFileSync(path, s);
}
const path = 'app/Views/cashier/sales/refund_partial.php';
let s = fs.readFileSync(path, 'utf8').replace(/\r\n/g, '\n');
const styleStart = s.indexOf('/* ── Thermal receipt preview');
const styleEnd = s.indexOf('</style>', styleStart);
if (styleStart < 0 || styleEnd < 0) throw new Error('Preview style boundaries missing');
s = s.slice(0, styleStart) + '.refund-receipt-col { min-width: 0; }\n.rp-wrap { position: sticky; top: 80px; min-width: 0; }\n.rp-label { margin: 0 0 10px; font-size: 13px; font-weight: 700; color: var(--text); }\n@media (max-width: 1100px) { .rp-wrap { position: static; } }\n' + s.slice(styleEnd);
s = s.replace('</style>', '</style>\n<link rel="stylesheet" href="<?= base_url(\'assets/css/receipts.css\') ?>">');
const start = s.indexOf('    <div class="refund-receipt-col">');
const end = s.indexOf('</div><!-- /.refund-layout -->', start);
if (start < 0 || end < 0) throw new Error('Preview markup boundaries missing');
s = s.slice(0, start) + String.raw`    <div class="refund-receipt-col">
        <div class="rp-wrap">
            <p class="rp-label">Refund preview</p>
            <article class="receipt-paper receipt-paper--refund" aria-label="Refund preview">
                <?= view('partials/receipt_header', ['businessName' => $refundBusinessName, 'businessType' => $refundBusinessType, 'documentType' => 'Refund preview', 'sale' => $sale]) ?>
                <div class="receipt-status receipt-status--refund">Preview · not yet submitted</div>
                <hr class="receipt-rule">
                <dl class="receipt-meta">
                    <dt>Original invoice</dt><dd><?= esc($sale['invoice_no']) ?></dd>
                    <dt>Original sale date</dt><dd><?= date('M d, Y', strtotime($sale['sale_date'])) ?></dd>
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

` + s.slice(end);
s = s.replace('            const amount = price * qty;', "            const amount = price * qty;\n            const conditionField = document.getElementsByName('return_condition[' + rowId + ']')[0];\n            const condition = conditionField ? conditionField.value : '';");
s = s.replace('rows.push({ name, qty, price, amount });', 'rows.push({ name, qty, price, amount, condition });');
s = s.replace('        rpGrand.textContent = fmt(total);', "        rpGrand.textContent = fmt(total);\n        const method = document.getElementById('refund-method');\n        document.getElementById('rp-method').textContent = method && method.value ? method.options[method.selectedIndex].text : 'Select a payment method';");
s = s.replace("'<tr class=\"empty-row\"><td colspan=\"4\" style=\"text-align:center;padding:6px 0;\">No items selected</td></tr>'", "'<tr><td colspan=\"2\" class=\"receipt-empty\">Select items to preview the refund.</td></tr>'");
s = s.replace("+ '<td>' + escapeHtml(r.name) + '</td>'\n                    + '<td>' + r.qty + '</td>'\n                    + '<td>₱' + r.price.toFixed(2) + '</td>'", "+ '<td><div class=\"receipt-item-name\">' + escapeHtml(r.name) + '</div>'\n                    + '<div class=\"receipt-item-detail\">' + r.qty + ' × ' + fmt(r.price) + '</div>'\n                    + '<div class=\"receipt-item-detail\">Condition: ' + escapeHtml(r.condition.charAt(0).toUpperCase() + r.condition.slice(1)) + '</div></td>'");
s = s.replace('    // Update reason on receipt preview', "    document.querySelectorAll('[name^=\"return_condition[\"], #refund-method').forEach(field => field.addEventListener('change', recalc));\n\n    // Update reason on receipt preview");
fs.writeFileSync(path, s);
