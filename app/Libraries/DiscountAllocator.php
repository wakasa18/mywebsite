<?php

namespace App\Libraries;

final class DiscountAllocator
{
    /** Allocate exact centavos using largest remainders, preserving item keys. */
    public static function allocate(array $subtotals, float $discount): array
    {
        $cents = array_map(static fn ($value): int => max(0, (int) round($value * 100)), $subtotals);
        $total = array_sum($cents);
        $target = min($total, max(0, (int) round($discount * 100)));
        $allocated = array_fill_keys(array_keys($cents), 0);
        if ($total === 0 || $target === 0) {
            return $allocated;
        }
        $remainders = [];
        foreach ($cents as $id => $subtotal) {
            $share = ($subtotal / $total) * $target;
            $allocated[$id] = min($subtotal, (int) floor($share));
            $remainders[$id] = $share - $allocated[$id];
        }
        arsort($remainders, SORT_NUMERIC);
        $remaining = $target - array_sum($allocated);
        foreach ($remainders as $id => $unused) {
            if ($remaining === 0) {
                break;
            }
            if ($allocated[$id] < $cents[$id]) {
                $allocated[$id]++;
                $remaining--;
            }
        }
        return array_map(static fn (int $value): float => $value / 100, $allocated);
    }
}
