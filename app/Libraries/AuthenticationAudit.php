<?php

namespace App\Libraries;

use App\Models\ActivityLogModel;

class AuthenticationAudit
{
    /** Never accept request bodies, usernames, passwords, or session tokens here. */
    public static function record(string $event, ?int $userId, string $ip): void
    {
        $labels = [
            'success' => 'Login succeeded',
            'credentials' => 'Login failed: invalid credentials',
            'validation' => 'Login failed: required fields missing',
            'inactive' => 'Login denied: inactive account',
            'role' => 'Login denied: invalid role',
            'locked' => 'Login blocked: rate limit active',
            'lockout' => 'Login failed: rate limit reached',
            'logout' => 'Logout succeeded',
        ];
        if (!isset($labels[$event])) throw new \InvalidArgumentException('Unknown authentication event.');
        $address = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unavailable';
        $activity = 'Authentication: ' . $labels[$event] . '; IP: ' . $address;
        try {
            if (!(new ActivityLogModel())->insert([
                'user_id' => $userId && $userId > 0 ? $userId : null,
                'activity' => $activity, 'log_time' => date('Y-m-d H:i:s'),
            ])) throw new \RuntimeException('Audit insert failed.');
        } catch (\Throwable $exception) {
            log_message('error', 'Authentication audit fallback: {activity}; account_id={account}', [
                'activity' => $activity, 'account' => $userId ?? 'unknown',
            ]);
        }
    }
}
