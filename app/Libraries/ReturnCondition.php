<?php

namespace App\Libraries;

final class ReturnCondition
{
    public const LABELS = [
        'quarantined' => 'Quarantine — hold for inspection',
        'resellable' => 'Resellable — return to available stock',
        'damaged' => 'Damaged — exclude from available stock',
        'expired' => 'Expired — exclude from available stock',
    ];

    public static function restockQuantity(string $condition, int $quantity, ?string $expiry, string $today): int
    {
        if (!isset(self::LABELS[$condition])) {
            throw new \InvalidArgumentException('Select a valid return condition for every refunded item.');
        }
        if ($condition !== 'resellable') {
            return 0;
        }
        if ($expiry && $expiry < $today) {
            throw new \InvalidArgumentException('Expired inventory cannot be returned to available stock. Select Expired or Quarantine.');
        }
        return $quantity;
    }
}
