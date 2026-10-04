<?php

namespace App\Libraries;

final class DiscountPolicy
{
    public static function cents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public static function fromDiscount(array $discount): ?array
    {
        $rule = [
            'type' => $discount['discount_type'],
            'value' => (float) $discount['discount_value'],
            'minimum' => (float) ($discount['minimum_purchase'] ?? 0),
            'maximum' => isset($discount['max_discount_amount']) ? (float) $discount['max_discount_amount'] : null,
        ];
        if (!in_array($rule['type'] ?? '', ['percentage', 'fixed'], true)
            || !is_numeric($rule['value']) || (float) $rule['value'] <= 0
            || !is_numeric($rule['minimum']) || (float) $rule['minimum'] < 0
            || ($rule['type'] === 'percentage' && (float) $rule['value'] > 100)
            || ($rule['maximum'] !== null && (!is_numeric($rule['maximum']) || (float) $rule['maximum'] <= 0))) {
            return null;
        }
        return $rule;
    }

    /** Same centavo calculation as public/assets/js/discount-math.js. */
    public static function amount(float $eligibleSubtotal, array $rule): float
    {
        $eligible = max(0, self::cents($eligibleSubtotal));
        if ($eligible < self::cents((float) $rule['minimum'])) return 0.0;
        $value = self::cents((float) $rule['value']);
        $discount = $rule['type'] === 'percentage'
            ? (int) round($eligible * $value / 10000)
            : $value;
        if ($rule['maximum'] !== null) $discount = min($discount, self::cents((float) $rule['maximum']));
        return min($eligible, max(0, $discount)) / 100;
    }

    public static function hasDiscount(array $sale, array $items): bool
    {
        return !empty($sale['discount_id']) || (float) ($sale['discount_amount'] ?? 0) > 0
            || array_sum(array_column($items, 'discount_applied')) > 0;
    }
}
