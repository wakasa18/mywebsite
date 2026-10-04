<?php

namespace App\Libraries;

final class SaleRevision
{
    public static function fingerprint(array $sale, array $items): string
    {
        $values = [];
        foreach (['id', 'status', 'notes', 'payment_method', 'reference_no', 'amount_paid', 'final_total'] as $key) {
            $values[] = (string) ($sale[$key] ?? '');
        }
        usort($items, static fn ($a, $b) => (int) $a['id'] <=> (int) $b['id']);
        foreach ($items as $item) {
            foreach (['id', 'quantity', 'price', 'subtotal', 'discount_applied', 'cost_price_at_sale'] as $key) {
                $values[] = (string) ($item[$key] ?? '');
            }
        }
        return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
    }
}
