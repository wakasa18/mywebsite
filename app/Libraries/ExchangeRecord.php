<?php

namespace App\Libraries;

/** Exchange invoices link to one immutable return event without additional columns. */
final class ExchangeRecord
{
    public static function identity(array $sale): ?array
    {
        if (!preg_match('/^EXC-([1-9][0-9]*)-([a-f0-9]{32})$/D', (string) ($sale['invoice_no'] ?? ''), $match)) return null;
        return ['original_id' => (int) $match[1], 'event_id' => $match[2]];
    }

    public static function forSale(array $sale, $db = null): ?array
    {
        $identity = self::identity($sale);
        if (!$identity) return null;
        $db ??= db_connect();
        $original = $db->table('sales')->where('id', $identity['original_id'])->where('branch_id', $sale['branch_id'])->get()->getRowArray();
        $refund = $db->table('refund_items')->selectSum('refund_subtotal', 'amount')
            ->where('sale_id', $identity['original_id'])->where('refund_event_id', $identity['event_id'])->get()->getRowArray();
        if (!$original || $refund['amount'] === null) return null;
        $returned = DiscountPolicy::cents((float) $refund['amount']);
        $total = DiscountPolicy::cents((float) $sale['final_total']);
        return $identity + ['original_invoice' => $original['invoice_no'], 'returned' => $returned / 100,
            'credit' => min($returned, $total) / 100, 'due' => max(0, $total - $returned) / 100,
            'payout' => max(0, $returned - $total) / 100];
    }
}
