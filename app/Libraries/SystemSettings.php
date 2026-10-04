<?php

namespace App\Libraries;

/**
 * Backward-compatible, database-free defaults.
 * New interface preferences are handled in the browser with localStorage.
 */
class SystemSettings
{
    public const DEFAULTS = [
        'business_name' => 'Pharxmaco',
        'business_type' => 'Drugstore',
        'receipt_thank_you' => 'Thank You!',
        'receipt_policy' => "Please keep this receipt as proof of purchase.\nItems sold are non-returnable unless defective. Valid within 7 days.",
        'low_stock_alert_enabled' => '1',
        'expired_stock_alert_enabled' => '1',
        'near_expiry_alert_enabled' => '1',
        'near_expiry_days' => '30',
    ];

    public function __construct($db = null)
    {
        // Intentionally database-free.
    }

    public function all(): array
    {
        return self::DEFAULTS;
    }

    public function get(string $key, ?string $fallback = null): string
    {
        return (string) (self::DEFAULTS[$key] ?? $fallback ?? '');
    }

    public function getInt(string $key, int $fallback = 0): int
    {
        return is_numeric(self::DEFAULTS[$key] ?? null) ? (int) self::DEFAULTS[$key] : $fallback;
    }

    public function getBool(string $key, bool $fallback = false): bool
    {
        if (!array_key_exists($key, self::DEFAULTS)) return $fallback;
        return in_array(strtolower((string) self::DEFAULTS[$key]), ['1', 'true', 'yes', 'on'], true);
    }

    public static function clearCache(): void
    {
        // No server-side cache is used.
    }
}
