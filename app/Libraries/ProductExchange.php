<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;

final class ProductExchange
{
    public function __construct(private BaseConnection $db) {}

    public function sale(int $id, int $branchId, string $role, bool $lock = false): array
    {
        $sale = $this->db->query('SELECT * FROM sales WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [$id])->getRowArray();
        if (!$sale || !in_array($role, ['admin', 'cashier'], true) || ($role !== 'admin' && (int) $sale['branch_id'] !== $branchId)) {
            throw new InvalidArgumentException('Sale not found or unavailable for your branch.');
        }
        if (!$this->db->table('branches')->where('id', $sale['branch_id'])->where('status', 'active')->countAllResults()) {
            throw new InvalidArgumentException('Exchanges require an active selling branch.');
        }
        return $sale;
    }

    public function remaining(array $sale, bool $lock = false): array
    {
        $items = $this->db->query('SELECT * FROM sale_items WHERE sale_id = ? ORDER BY id' . ($lock ? ' FOR UPDATE' : ''), [$sale['id']])->getResultArray();
        foreach ($items as &$item) {
            $used = $this->db->table('refund_items')->selectSum('quantity_refunded', 'qty')->selectSum('refund_subtotal', 'amount')
                ->where('sale_item_id', $item['id'])->get()->getRowArray();
            $item['remaining_qty'] = max(0, (int) $item['quantity'] - (int) ($used['qty'] ?? 0));
            $item['remaining_cents'] = max(0, DiscountPolicy::cents((float) $item['subtotal'] - (float) $item['discount_applied']) - DiscountPolicy::cents((float) ($used['amount'] ?? 0)));
        }
        unset($item);
        return $items;
    }

    private function quantities($input): array
    {
        if (!is_array($input) || count($input) > 1000) throw new InvalidArgumentException('Select valid item quantities.');
        $result = [];
        foreach ($input as $id => $qty) {
            if (!ctype_digit((string) $id) || (int) $id < 1 || !is_scalar($qty)
                || filter_var($qty, FILTER_VALIDATE_INT) === false || (int) $qty < 0 || (int) $qty > 100000) {
                throw new InvalidArgumentException('Quantities must be whole numbers between 0 and 100,000.');
            }
            if ((int) $qty > 0) $result[(int) $id] = (int) $qty;
        }
        if (!$result) throw new InvalidArgumentException('Choose at least one returned item and one replacement item.');
        ksort($result);
        return $result;
    }

    private function matchesDiscount(array $rule, int $productId, int $categoryId): bool
    {
        return $rule['applies_to'] === 'all'
            || ($rule['applies_to'] === 'product' && (int) $rule['product_id'] === $productId)
            || ($rule['applies_to'] === 'category' && (int) $rule['category_id'] > 0 && (int) $rule['category_id'] === $categoryId);
    }

    /** Remaining merchandise in the same discount/order lineage, excluding this return.
     * Existing line discounts are preserved and consume the order's fixed/capped allowance.
     */
    private function retainedDiscountContext(array $sale, array $returns, array $rule, bool $lock): array
    {
        $result = ['eligible_cents' => 0, 'discount_cents' => 0];
        if ((int) $sale['discount_id'] !== (int) $rule['id']) return $result;
        $root = $sale;
        $visited = [];
        while ($identity = ExchangeRecord::identity($root)) {
            if (isset($visited[$root['id']]) || count($visited) >= 200) throw new InvalidArgumentException('The linked exchange history needs administrator review.');
            $visited[$root['id']] = true;
            $parent = $this->db->query('SELECT * FROM sales WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [$identity['original_id']])->getRowArray();
            if (!$parent || (int) $parent['branch_id'] !== (int) $sale['branch_id'] || (int) $parent['discount_id'] !== (int) $rule['id']) break;
            $root = $parent;
        }
        $queue = [$root];
        $visited = [];
        while ($queue) {
            $node = array_shift($queue);
            if (isset($visited[$node['id']])) continue;
            if (count($visited) >= 200) throw new InvalidArgumentException('The linked exchange history needs administrator review.');
            $visited[$node['id']] = true;
            if ($lock) $node = $this->db->query('SELECT * FROM sales WHERE id = ? FOR UPDATE', [$node['id']])->getRowArray();
            if (in_array($node['status'], ['completed', 'partially_refunded', 'refunded'], true)) {
                foreach ($this->remaining($node, $lock) as $item) {
                    $selected = (int) $node['id'] === (int) $sale['id'] ? ($returns[$item['id']] ?? null) : null;
                    $kept = $item['remaining_qty'] - ($selected['quantity'] ?? 0);
                    if ($kept <= 0) continue;
                    $gross = (int) round(DiscountPolicy::cents((float) $item['subtotal']) * $kept / (int) $item['quantity']);
                    $net = $item['remaining_cents'] - ($selected['cents'] ?? 0);
                    $result['discount_cents'] += max(0, $gross - $net);
                    $product = $this->db->table('products')->select('category_id')->where('id', $item['product_id'])->get()->getRowArray();
                    if ($this->matchesDiscount($rule, (int) $item['product_id'], (int) ($product['category_id'] ?? 0))) $result['eligible_cents'] += $gross;
                }
            }
            $children = $this->db->table('sales')->like('invoice_no', 'EXC-' . $node['id'] . '-', 'after')
                ->where('branch_id', $sale['branch_id'])->where('discount_id', $rule['id'])->orderBy('id')->get()->getResultArray();
            foreach ($children as $child) {
                if (ExchangeRecord::identity($child)) $queue[] = $child;
            }
        }
        return $result;
    }

    public function quote(int $saleId, array $input, int $branchId, string $role, bool $lock = false): array
    {
        $sale = $this->sale($saleId, $branchId, $role, $lock);
        if (!in_array($sale['status'], ['completed', 'partially_refunded'], true)) throw new InvalidArgumentException('This sale has no items available for exchange.');
        $reason = is_string($input['reason'] ?? null) ? trim($input['reason']) : '';
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 300) throw new InvalidArgumentException('Enter an exchange reason between 3 and 300 characters.');
        $method = $input['settlement_method'] ?? '';
        if (!in_array($method, ['cash', 'gcash', 'card'], true)) throw new InvalidArgumentException('Select a settlement method.');
        $reference = is_string($input['reference'] ?? null) ? trim($input['reference']) : '';
        if (mb_strlen($reference) > 100) throw new InvalidArgumentException('The payment reference is too long.');
        $returnQty = $this->quantities($input['returns'] ?? null);
        $replacementQty = $this->quantities($input['replacements'] ?? null);
        $items = array_column($this->remaining($sale, $lock), null, 'id');
        $originalProductIds = array_map('intval', array_column($items, 'product_id'));
        if (array_intersect(array_keys($replacementQty), $originalProductIds)) {
            throw new InvalidArgumentException('Choose a different replacement product. Products from the original sale cannot be selected as replacements.');
        }
        $conditions = is_array($input['conditions'] ?? null) ? $input['conditions'] : [];
        $productIds = array_keys($replacementQty);
        foreach ($returnQty as $id => $qty) {
            if (!isset($items[$id]) || $qty > $items[$id]['remaining_qty']) throw new InvalidArgumentException('A returned quantity exceeds the quantity still available.');
            $productIds[] = (int) $items[$id]['product_id'];
        }
        $productIds = array_unique($productIds);
        sort($productIds);
        $inventory = [];
        foreach ($productIds as $id) {
            // Deterministic inventory lock order protects concurrent exchanges.
            $row = $this->db->query('SELECT * FROM branch_products WHERE product_id = ? AND branch_id = ?' . ($lock ? ' FOR UPDATE' : ''), [$id, $sale['branch_id']])->getRowArray();
            if (!$row) throw new InvalidArgumentException('An item no longer has inventory in this branch.');
            $product = $this->db->query('SELECT * FROM products WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [$id])->getRowArray();
            if (!$product) throw new InvalidArgumentException('A product record is missing.');
            $inventory[$id] = $row + ['product' => $product];
        }
        $returns = [];
        $credit = 0;
        foreach ($returnQty as $id => $qty) {
            $item = $items[$id];
            $condition = $conditions[$id] ?? '';
            if (!is_string($condition)) throw new InvalidArgumentException('Select a return condition.');
            $bp = $inventory[$item['product_id']];
            $restock = ReturnCondition::restockQuantity($condition, $qty, $bp['expiration_date'], date('Y-m-d'));
            $amount = $qty === $item['remaining_qty'] ? $item['remaining_cents'] : (int) round($item['remaining_cents'] * $qty / $item['remaining_qty']);
            $credit += $amount;
            $returns[$id] = ['item' => $item, 'quantity' => $qty, 'condition' => $condition, 'restock' => $restock, 'cents' => $amount];
        }
        $replacements = [];
        foreach ($replacementQty as $id => $qty) {
            $bp = $inventory[$id];
            $product = $bp['product'];
            if ($bp['status'] !== 'active' || (!empty($bp['deleted_at']) || !empty($bp['is_deleted']) || !empty($bp['is_permanently_deleted'])) || $product['status'] !== 'active' || (!empty($product['deleted_at']) || !empty($product['is_deleted']) || !empty($product['is_permanently_deleted']))
                || (!empty($bp['expiration_date']) && $bp['expiration_date'] < date('Y-m-d')) || (int) $bp['stock'] < $qty) {
                throw new InvalidArgumentException('Replacement "' . $product['product_name'] . '" is unavailable, expired or has insufficient stock.');
            }
            $price = DiscountPolicy::cents((float) $bp['price']);
            $cost = DiscountPolicy::cents((float) $bp['cost_price']);
            if ($price < 0 || $cost < 0 || $price * $qty > 9999999999 || $cost * $qty > 9999999999) throw new InvalidArgumentException('A product price is outside the supported range.');
            $replacements[$id] = ['product_id' => $id, 'name' => $product['product_name'], 'category_id' => $product['category_id'], 'quantity' => $qty, 'price' => $price, 'cost' => $cost, 'subtotal' => $price * $qty, 'discount' => 0];
        }
        $discountId = $input['discount_id'] ?? '';
        if (!is_scalar($discountId) || ($discountId !== '' && (!ctype_digit((string) $discountId) || (int) $discountId < 1))) throw new InvalidArgumentException('Select a valid replacement discount.');
        $discount = 0;
        $discountContext = ['eligible_cents' => 0, 'discount_cents' => 0];
        if ($discountId !== '') {
            $ruleRow = $this->db->query('SELECT * FROM discounts WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [(int) $discountId])->getRowArray();
            if (!$ruleRow || $ruleRow['status'] !== 'active' || (!empty($ruleRow['deleted_at']) || !empty($ruleRow['is_deleted']) || !empty($ruleRow['is_permanently_deleted']))
                || (!empty($ruleRow['start_date']) && $ruleRow['start_date'] > date('Y-m-d'))
                || (!empty($ruleRow['end_date']) && $ruleRow['end_date'] < date('Y-m-d')) || !($rule = DiscountPolicy::fromDiscount($ruleRow))) {
                throw new InvalidArgumentException('The replacement discount is invalid or no longer active.');
            }
            $eligible = [];
            foreach ($replacements as $id => $item) {
                if ($this->matchesDiscount($ruleRow, $id, (int) $item['category_id'])) $eligible[$id] = $item['subtotal'] / 100;
            }
            $eligibleCents = DiscountPolicy::cents(array_sum($eligible));
            if ($eligibleCents <= 0) throw new InvalidArgumentException('This discount does not apply to the selected replacement products. Check its product or category restrictions, or choose No discount.');
            $discountContext = $this->retainedDiscountContext($sale, $returns, $ruleRow, $lock);
            $qualifyingCents = $eligibleCents + $discountContext['eligible_cents'];
            if ($qualifyingCents < DiscountPolicy::cents($rule['minimum'])) {
                throw new InvalidArgumentException('This discount requires PHP ' . number_format($rule['minimum'], 2)
                    . ' in eligible products. The qualifying amount after this exchange is PHP ' . number_format($qualifyingCents / 100, 2)
                    . '. Only matching products kept under the same discount count toward the minimum.');
            }
            // Eligibility uses the kept merchandise, but only replacement lines receive a new discount.
            $replacementRule = $rule;
            $replacementRule['minimum'] = 0;
            $discount = DiscountPolicy::cents(DiscountPolicy::amount(array_sum($eligible), $replacementRule));
            $budget = null;
            if ($rule['type'] === 'fixed') $budget = DiscountPolicy::cents($rule['value']);
            if ($rule['maximum'] !== null) $budget = min($budget ?? PHP_INT_MAX, DiscountPolicy::cents($rule['maximum']));
            if ($budget !== null) $discount = min($discount, max(0, $budget - $discountContext['discount_cents']));
            foreach (DiscountAllocator::allocate($eligible, $discount / 100) as $id => $amount) $replacements[$id]['discount'] = DiscountPolicy::cents($amount);
        }
        $subtotal = array_sum(array_column($replacements, 'subtotal'));
        if ($subtotal > 9999999999) throw new InvalidArgumentException('The replacement total is too large.');
        $total = $subtotal - $discount;
        $difference = $total - $credit;
        if ($difference !== 0 && $method !== 'cash' && $reference === '') throw new InvalidArgumentException('Enter the reference number for the additional payment or payout.');
        $quote = compact('sale', 'returns', 'replacements', 'inventory', 'reason', 'method', 'reference', 'credit', 'subtotal', 'discount', 'total', 'difference', 'discountId', 'discountContext');
        $quote['revision'] = hash('sha256', json_encode([$sale, $returns, $replacements, $credit, $total, $reason, $method, $reference, $discountId, $discountContext], JSON_THROW_ON_ERROR));
        return $quote;
    }

    public function complete(int $saleId, array $input, int $branchId, string $role, int $userId, string $token, string $revision): int
    {
        if (!preg_match('/^[a-f0-9]{48}$/D', $token)) throw new InvalidArgumentException('The exchange session has expired.');
        $event = substr(hash('sha256', $userId . ':' . $saleId . ':' . $token), 0, 32);
        $invoice = 'EXC-' . $saleId . '-' . $event;
        $this->db->transBegin();
        try {
            $this->sale($saleId, $branchId, $role, true);
            // The original sale lock serializes retries even on databases without checkout_token_hash.
            $existing = $this->db->table('sales')->where('invoice_no', $invoice)->where('user_id', $userId)->get()->getRowArray();
            if ($existing) { $this->db->transCommit(); return (int) $existing['id']; }
            $q = $this->quote($saleId, $input, $branchId, $role, true);
            if (!hash_equals($q['revision'], $revision)) throw new InvalidArgumentException('Prices, discounts or returned quantities changed. Review the exchange again before confirming.');
            $now = date('Y-m-d H:i:s');
            $branch = (int) $q['sale']['branch_id'];
            $notes = 'Exchange for ' . $q['sale']['invoice_no'] . '. Return value: PHP ' . number_format($q['credit'] / 100, 2, '.', '')
                . '. Additional payment: PHP ' . number_format(max(0, $q['difference']) / 100, 2, '.', '')
                . '. Payout: PHP ' . number_format(max(0, -$q['difference']) / 100, 2, '.', '') . '. Reason: ' . $q['reason'];
            $this->insert('sales', ['invoice_no' => $invoice, 'user_id' => $userId, 'branch_id' => $branch, 'discount_id' => $q['discountId'] ?: null,
                'total_amount' => $q['subtotal'] / 100, 'discount_amount' => $q['discount'] / 100, 'final_total' => $q['total'] / 100,
                'payment_method' => $q['method'], 'reference_no' => $q['method'] === 'cash' ? null : ($q['reference'] ?: null),
                'amount_paid' => $q['total'] / 100, 'change_amount' => 0, 'status' => 'completed', 'notes' => $notes, 'sale_date' => $now]);
            $newId = (int) $this->db->insertID();
            foreach ($q['returns'] as $id => $entry) {
                $item = $entry['item'];
                $this->insert('refund_items', ['sale_id' => $saleId, 'sale_item_id' => $id, 'product_id' => $item['product_id'], 'product_name_snapshot' => $item['product_name_snapshot'],
                    'quantity_refunded' => $entry['quantity'], 'price_at_sale' => round($entry['cents'] / 100 / $entry['quantity'], 2), 'refund_subtotal' => $entry['cents'] / 100,
                    'refunded_by' => $userId, 'reason' => 'Exchange to ' . $invoice . '. ' . $q['reason'], 'return_condition' => $entry['condition'],
                    'refund_method' => $q['method'], 'refund_event_id' => $event, 'created_at' => $now]);
                if ($entry['restock'] > 0) $this->move($q['inventory'][$item['product_id']], $entry['restock'], $userId, 'returned', 'Exchange return: ' . $invoice, $now);
            }
            foreach ($q['replacements'] as $id => $entry) {
                $this->insert('sale_items', ['sale_id' => $newId, 'product_id' => $id, 'product_name_snapshot' => $entry['name'], 'cost_price_at_sale' => $entry['cost'] / 100,
                    'quantity' => $entry['quantity'], 'price' => $entry['price'] / 100, 'subtotal' => $entry['subtotal'] / 100, 'discount_applied' => $entry['discount'] / 100,
                    'profit' => ($entry['subtotal'] - $entry['cost'] * $entry['quantity'] - $entry['discount']) / 100]);
                $this->move($q['inventory'][$id], -$entry['quantity'], $userId, 'stock_out', 'Exchange replacement: ' . $invoice, $now);
            }
            foreach ($q['inventory'] as $id => $unused) {
                $sum = $this->db->table('branch_products')->selectSum('stock', 'total')->where('product_id', $id)->get()->getRowArray();
                $this->update('products', $id, ['stock' => (int) $sum['total'], 'updated_at' => $now]);
            }
            $remaining = array_sum(array_column($this->remaining($q['sale']), 'remaining_qty'));
            $this->update('sales', $saleId, ['status' => $remaining > 0 ? 'partially_refunded' : 'refunded', 'updated_at' => $now]);
            $this->insert('activity_logs', ['user_id' => $userId, 'activity' => $notes . ' New invoice: ' . $invoice, 'log_time' => $now]);
            if (!$this->db->transStatus()) throw new \RuntimeException('Exchange transaction failed.');
            $this->db->transCommit();
            return $newId;
        } catch (\Throwable $error) {
            $this->db->transRollback();
            throw $error;
        }
    }

    private function insert(string $table, array $values): void
    {
        if (!$this->db->table($table)->insert($values)) throw new \RuntimeException('Could not save exchange record.');
    }

    private function update(string $table, int $id, array $values): void
    {
        if (!$this->db->table($table)->where('id', $id)->update($values)) throw new \RuntimeException('Could not update exchange record.');
    }

    private function move(array &$bp, int $delta, int $user, string $action, string $remarks, string $now): void
    {
        $previous = (int) $bp['stock'];
        $next = $previous + $delta;
        if ($next < 0) throw new InvalidArgumentException('Insufficient replacement stock.');
        $this->update('branch_products', (int) $bp['id'], ['stock' => $next, 'updated_at' => $now]);
        $this->insert('stock_logs', ['product_id' => $bp['product_id'], 'branch_id' => $bp['branch_id'], 'user_id' => $user,
            'action_type' => $action, 'quantity' => abs($delta), 'previous_stock' => $previous, 'new_stock' => $next, 'remarks' => $remarks, 'created_at' => $now]);
        $bp['stock'] = $next;
    }
}
