const fs = require('fs');
function edit(path, fn) { const s = fs.readFileSync(path, 'utf8').replace(/\r\n/g, '\n'); fs.writeFileSync(path, fn(s)); }
function replace(s, a, b) { if (!s.includes(a)) throw new Error('Missing: ' + a.slice(0,80)); return s.replace(a,b); }
edit('app/Controllers/Cashier/SalesController.php', s => {
 const a = s.indexOf('    public function refundPartial(');
 const b = s.indexOf('    public function refundSlip(', a);
 let part = s.slice(a,b);
 part = replace(part, "            'reason' => 'required|min_length[3]|max_length[500]',", "            'reason' => 'required|min_length[3]|max_length[500]',\n            'refund_method' => 'required|in_list[cash,gcash,card]',");
 part = replace(part, "'A reason is required (minimum 3 characters).'", "'Enter a reason (at least 3 characters) and select the refund payment method.'");
 part = replace(part, '        $saleBranchId =', "        $conditions = $this->request->getPost('return_condition');\n        $conditions = is_array($conditions) ? $conditions : [];\n        $refundMethod = (string) $this->request->getPost('refund_method');\n        $saleBranchId =");
 part = replace(part, '            $item = $saleItemMap[$itemId];', `            $condition = $conditions[$itemId] ?? '';
            if (!is_string($condition) || !isset(\\App\\Libraries\\ReturnCondition::LABELS[$condition])) {
                return redirect()->back()->withInput()->with('error', 'Select a return condition for every refunded item.');
            }
            $item = $saleItemMap[$itemId];`);
 part = replace(part, "                'item' => $item,", "                'item' => $item,\n                'return_condition' => $condition,");
 part = replace(part, "        $now = date('Y-m-d H:i:s');", "        $now = date('Y-m-d H:i:s');\n        $refundEventId = bin2hex(random_bytes(16));");
 part = replace(part, '            $newStock      = $previousStock + $qty;', `            try {
                $restockQty = \\App\\Libraries\\ReturnCondition::restockQuantity(
                    $entry['return_condition'], $qty, $branchProduct['expiration_date'] ?? null, date('Y-m-d')
                );
            } catch (\\InvalidArgumentException $error) {
                $this->db->transRollback();
                return redirect()->back()->withInput()->with('error', $error->getMessage());
            }
            $newStock = $previousStock + $restockQty;

            if ($restockQty > 0) {`);
 part = replace(part, '            // Record the refund line item', '            }\n\n            // Non-resellable returns stay in the refund ledger, outside available stock.\n            // Record the refund line item');
 part = replace(part, "                'refunded_by'           => $userId,", "                'refunded_by'           => $userId,\n                'return_condition'      => $entry['return_condition'],\n                'refund_method'         => $refundMethod,\n                'refund_event_id'       => $refundEventId,");
 part = replace(part, '        $fullyRefunded = true;', "        $allSaleItems = $this->saleItemModel->where('sale_id', $saleId)->findAll();\n        $fullyRefunded = true;");
 part = replace(part, "'?ts=' . urlencode($now)", "'?event=' . $refundEventId");
 s = s.slice(0,a) + part + s.slice(b);
 s = replace(s, "        if ($ts) {\n            $refundItemsQuery", "        $eventId = (string) ($this->request->getGet('event') ?? '');\n        if ($eventId !== '') {\n            $refundItemsQuery->where('refund_items.refund_event_id', $eventId);\n        } elseif ($ts) {\n            $refundItemsQuery");
 return s;
});
edit('app/Views/cashier/sales/refund_partial.php', s => {
 s = replace(s, '<th style="text-align:right; min-width:120px;">Qty to Refund</th>', '<th>Return condition</th>\n                                <th style="text-align:right; min-width:120px;">Qty to Refund</th>');
 const pos = s.indexOf('name="refund[');
 const td = s.lastIndexOf('<td ', pos);
 if (pos < 0 || td < 0) throw new Error('Refund quantity field missing');
 s = s.slice(0,td) + `<td data-label="Condition">
                                        <select class="input" name="return_condition[<?= (int) $item['id'] ?>]" aria-label="Return condition for <?= esc($item['product_name_snapshot']) ?>" <?= $disabled ? 'disabled' : '' ?> style="min-width:140px;max-width:100%;">
                                            <?php foreach (\\App\\Libraries\\ReturnCondition::LABELS as $value => $label): ?>
                                                <option value="<?= esc($value) ?>" <?= old('return_condition.' . $item['id'], 'quarantined') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    ` + s.slice(td);
 s = s.replace('colspan="6"', 'colspan="7"');
 s = replace(s, '<?= csrf_field() ?>', `<?= csrf_field() ?>
            <div class="card" style="padding:18px;margin-bottom:16px;">
                <label for="refund-method" class="label">Refund payment method</label>
                <select id="refund-method" name="refund_method" class="input" required>
                    <option value="">Select how the customer is repaid</option>
                    <?php foreach (['cash' => 'Cash', 'gcash' => 'GCash', 'card' => 'Card'] as $value => $label): ?>
                        <option value="<?= esc($value) ?>" <?= old('refund_method') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="muted" style="margin-top:10px;">Only items marked Resellable return to available stock. Quarantined, damaged, and expired returns are recorded separately. Submit separate refunds if units of the same item have different conditions.</p>
            </div>`);
 s = replace(s, '<th>Processed By</th>', '<th>Condition / Payout</th>\n                            <th>Processed By</th>');
 s = replace(s, '<td data-label="Processed By">', `<td data-label="Condition / Payout"><?= esc(ucfirst($r['return_condition'] ?? 'resellable')) ?> / <?= esc(strtoupper($r['refund_method'] ?? $sale['payment_method'])) ?></td>
                                <td data-label="Processed By">`);
 return s;
});
edit('app/Views/cashier/sales/refund_slip.php', s => replace(s,
 "<?= esc($ri['product_name_snapshot']) ?>",
 "<?= esc($ri['product_name_snapshot']) ?><br><small><?= esc(ucfirst($ri['return_condition'] ?? 'resellable')) ?> · <?= esc(strtoupper($ri['refund_method'] ?? $sale['payment_method'])) ?></small>"));
