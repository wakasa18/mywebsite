<?php

namespace App\Libraries;

final class SaleCorrectionPricing
{
    public function __construct(private $db) {}

    /** Older sales have no discount snapshot. Only use a linked rule that
     * reproduces their recorded totals and per-item discount allocations. */
    public function context(array $sale, array $items, bool $lock = false): array
    {
        $context = ['rule'=>null, 'eligible'=>[], 'error'=>null];
        if (DiscountPolicy::hasDiscount($sale, $items)) {
            $discount = $this->db->query('SELECT * FROM discounts WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [(int) ($sale['discount_id'] ?? 0)])->getRowArray();
            $rule = $discount ? DiscountPolicy::fromDiscount($discount) : null;
            $scope = $discount['applies_to'] ?? '';
            if (!$rule || !in_array($scope, ['all','product','category'], true)
                || ($scope !== 'all' && (int) ($discount[$scope.'_id'] ?? 0) <= 0)) {
                $context['error'] = 'The recorded discount rule is unavailable. Quantities cannot be recalculated; notes and payment details can still be corrected.';
            } else {
                $categories = [];
                if ($scope === 'category') {
                    $ids = array_unique(array_map('intval', array_column($items, 'product_id')));
                    sort($ids);
                    foreach ($ids as $id) {
                        $product = $this->db->query('SELECT id, category_id FROM products WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [$id])->getRowArray();
                        $categories[$id] = (int) ($product['category_id'] ?? 0);
                    }
                }
                $eligible = [];
                $gross = 0;
                $consistent = true;
                foreach ($items as $item) {
                    $subtotal = DiscountPolicy::cents((float) $item['subtotal']);
                    $gross += $subtotal;
                    $consistent = $consistent && $subtotal === DiscountPolicy::cents((float) $item['price']) * (int) $item['quantity'];
                    if ($scope === 'all' || ($scope === 'product' && (int) $item['product_id'] === (int) $discount['product_id'])
                        || ($scope === 'category' && ($categories[$item['product_id']] ?? 0) === (int) $discount['category_id'])) {
                        $eligible[(int) $item['id']] = $subtotal / 100;
                    }
                }
                $amount = DiscountPolicy::amount(array_sum($eligible), $rule);
                $allocated = DiscountAllocator::allocate($eligible, $amount);
                $consistent = $consistent && $gross === DiscountPolicy::cents((float) $sale['total_amount'])
                    && DiscountPolicy::cents($amount) === DiscountPolicy::cents((float) $sale['discount_amount'])
                    && $gross - DiscountPolicy::cents($amount) === DiscountPolicy::cents((float) $sale['final_total']);
                foreach ($items as $item) {
                    $consistent = $consistent && DiscountPolicy::cents((float) $item['discount_applied']) === DiscountPolicy::cents($allocated[$item['id']] ?? 0);
                }
                if (!$consistent) {
                    $context['error'] = 'The discount settings no longer match this sale. Quantities cannot be recalculated; notes and payment details can still be corrected.';
                } else {
                    $context['rule'] = $rule;
                    $context['eligible'] = array_keys($eligible);
                }
            }
        }
        $context['revision'] = hash('sha256', json_encode([(int) ($sale['discount_id'] ?? 0), $context], JSON_THROW_ON_ERROR));
        return $context;
    }

    public function calculate(array $sale, array $items, array $quantities, array $context): array
    {
        $plans = [];
        $changed = false;
        $totalQuantity = 0;
        $gross = 0;
        $eligible = [];
        foreach ($items as $item) {
            $id = (int) $item['id'];
            $old = (int) $item['quantity'];
            $qty = array_key_exists($id, $quantities) ? (int) $quantities[$id] : $old;
            $changed = $changed || $qty !== $old;
            $totalQuantity += $qty;
            $subtotal = DiscountPolicy::cents((float) $item['price']) * $qty;
            $gross += $subtotal;
            if (in_array($id, $context['eligible'], true)) $eligible[$id] = $subtotal / 100;
            $plans[] = ['item'=>$item, 'old_qty'=>$old, 'new_qty'=>$qty, 'qty_diff'=>$qty-$old, 'subtotal'=>$subtotal/100];
        }
        if ($totalQuantity <= 0) throw new \InvalidArgumentException('A completed sale must keep at least one item. Use the refund function when the entire sale needs to be reversed.');
        if ($changed && $context['error']) throw new \InvalidArgumentException($context['error']);
        if ($gross > 9999999999) throw new \InvalidArgumentException('The corrected sale total is too large.');
        $amount = $changed && $context['rule'] ? DiscountPolicy::amount(array_sum($eligible), $context['rule']) : (float) $sale['discount_amount'];
        $allocated = $changed ? DiscountAllocator::allocate($eligible, $amount) : [];
        foreach ($plans as &$plan) {
            $item = $plan['item'];
            $plan['discount'] = $changed ? ($allocated[$item['id']] ?? 0.0) : (float) $item['discount_applied'];
            $plan['profit'] = $changed ? (DiscountPolicy::cents($plan['subtotal']) - DiscountPolicy::cents($plan['discount'])
                - DiscountPolicy::cents((float) $item['cost_price_at_sale']) * $plan['new_qty']) / 100 : (float) $item['profit'];
            if (!$changed) $plan['subtotal'] = (float) $item['subtotal'];
        }
        unset($plan);
        $total = $changed ? $gross / 100 : (float) $sale['total_amount'];
        $final = $changed ? ($gross - DiscountPolicy::cents($amount)) / 100 : (float) $sale['final_total'];
        return ['plans'=>$plans, 'changed'=>$changed, 'total'=>$total, 'discount'=>$amount, 'final'=>$final,
            'difference'=>DiscountPolicy::cents($final) - DiscountPolicy::cents((float) $sale['final_total'])];
    }
}
