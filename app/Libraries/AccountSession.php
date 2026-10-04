<?php

namespace App\Libraries;

use App\Models\UserModel;

final class AccountSession
{
    public static function fingerprint(array $user): string
    {
        return hash('sha256', json_encode([
            (int) $user['id'], $user['password'], $user['role'],
            (int) ($user['branch_id'] ?? 0), $user['status'],
            $user['session_version'] ?? '',
        ], JSON_THROW_ON_ERROR));
    }

    public static function check(): bool
    {
        $session = session();
        if (!$session->get('isLoggedIn')) {
            return false;
        }
        $user = (new UserModel())->find((int) $session->get('user_id'));
        $valid = $user && $user['status'] === 'active'
            && in_array($user['role'], ['admin', 'cashier'], true)
            && hash_equals(self::fingerprint($user), (string) $session->get('account_fingerprint'));
        if (!$valid) {
            // Remove carts and branch-bound state as well as authentication.
            $session->remove(array_keys($session->get()));
            $session->regenerate(true);
            return false;
        }
        $session->set([
            'role' => $user['role'], 'branch_id' => $user['branch_id'],
            'full_name' => $user['full_name'], 'username' => $user['username'],
        ]);
        return true;
    }
}
