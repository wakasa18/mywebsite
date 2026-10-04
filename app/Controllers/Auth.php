<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;
use App\Libraries\AuthenticationAudit;
use App\Libraries\AccountSession;

class Auth extends Controller
{
    // Maximum failed attempts before lockout
    private const MAX_ATTEMPTS = 5;

    // Lockout duration in seconds (15 minutes)
    private const LOCKOUT_SECONDS = 15 * 60;

    public function login()
    {
        // Revalidate the account so revoked sessions cannot bypass sign-in.
        if (AccountSession::check()) {
            return redirect()->to(site_url(session('role') === 'admin' ? 'dashboard' : 'cashier/sales'));
        }

        return view('auth/login');
    }

    public function attemptLogin()
    {
        $session = session();
        $model   = new UserModel();
        $db      = \Config\Database::connect();
        $cache   = \Config\Services::cache();

        // ── Throttle check ────────────────────────────────────────────────
        $ip        = $this->request->getIPAddress();
        $lockKey   = 'login_locked_'   . md5($ip);
        $countKey  = 'login_attempts_' . md5($ip);

        $lockedUntil = $cache->get($lockKey);

        if ($lockedUntil !== null && $lockedUntil > time()) {
            AuthenticationAudit::record('locked', null, $ip);
            $minutes = ceil(($lockedUntil - time()) / 60);
            return redirect()
                ->back()
                ->with('error', "Too many failed login attempts. Please try again in {$minutes} minute(s).");
        }
        // ──────────────────────────────────────────────────────────────────

        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            AuthenticationAudit::record('validation', null, $ip);
            return redirect()->back()->withInput()->with('error', 'Username and password are required.');
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        $user = $db->table('users')
            ->select('users.*, branches.branch_name')
            ->join('branches', 'branches.id = users.branch_id', 'left')
            ->where('users.username', $username)
            ->get()
            ->getRowArray();

        // ── Failed login: record attempt ───────────────────────────────────
        if (!$user || !password_verify($password, $user['password'])) {
            $attempts = (int) ($cache->get($countKey) ?? 0) + 1;
            $cache->save($countKey, $attempts, self::LOCKOUT_SECONDS);

            $remaining = self::MAX_ATTEMPTS - $attempts;

            if ($attempts >= self::MAX_ATTEMPTS) {
                AuthenticationAudit::record('lockout', isset($user['id']) ? (int) $user['id'] : null, $ip);
                $cache->save($lockKey, time() + self::LOCKOUT_SECONDS, self::LOCKOUT_SECONDS);
                $cache->delete($countKey);
                return redirect()
                    ->back()
                    ->with('error', 'Too many failed login attempts. Your IP has been locked out for 15 minutes.');
            }

            $warn = $remaining === 1
                ? ' Warning: 1 attempt remaining before lockout.'
                : " {$remaining} attempts remaining before lockout.";

            AuthenticationAudit::record('credentials', isset($user['id']) ? (int) $user['id'] : null, $ip);
            return redirect()->back()->withInput()->with('error', 'Invalid username or password.' . $warn);
        }
        // ──────────────────────────────────────────────────────────────────

        if (($user['status'] ?? 'active') !== 'active') {
            AuthenticationAudit::record('inactive', (int) $user['id'], $ip);
            return redirect()->back()->withInput()->with('error', 'Your account is inactive. Please contact the administrator.');
        }

        if (!in_array($user['role'], ['admin', 'cashier'], true)) {
            AuthenticationAudit::record('role', (int) $user['id'], $ip);
            return redirect()->to('/login')->with('error', 'Invalid role.');
        }

        // ── Successful login: clear throttle counters ─────────────────────
        $cache->delete($lockKey);
        $cache->delete($countKey);
        // ──────────────────────────────────────────────────────────────────

        $session->remove(array_keys($session->get()));
        $session->regenerate(true);
        $session->set([
            'account_fingerprint' => \App\Libraries\AccountSession::fingerprint($user),
            'user_id'     => $user['id'],
            'full_name'   => $user['full_name'],
            'username'    => $user['username'],
            'role'        => $user['role'],
            'branch_id'   => $user['branch_id']   ?? null,
            'branch_name' => $user['branch_name'] ?? null,
            'isLoggedIn'  => true,
        ]);

        AuthenticationAudit::record('success', (int) $user['id'], $ip);
        if ($user['role'] === 'admin') {
            return redirect()->to('/dashboard');
        }

        if ($user['role'] === 'cashier') {
            return redirect()->to('/cashier/sales');
        }

        return redirect()->to('/login')->with('error', 'Invalid role.');
    }

    public function logout()
    {
        if (session('isLoggedIn') && session('user_id')) {
            AuthenticationAudit::record('logout', (int) session('user_id'), $this->request->getIPAddress());
        }
        session()->destroy();
        return redirect()->to('/login')->with('success', 'You have been logged out.');
    }
}
