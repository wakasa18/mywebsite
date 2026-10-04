<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * LoginThrottleFilter
 *
 * Prevents brute-force attacks on the login route.
 * Tracks failed attempts by IP address in the CI4 cache.
 *
 * Limits: 5 failed attempts → 15-minute lockout.
 * Attempt counter resets automatically after the lockout window.
 */
class LoginThrottleFilter implements FilterInterface
{
    // Maximum failed attempts before lockout
    private const MAX_ATTEMPTS = 5;

    // Lockout duration in seconds (15 minutes)
    private const LOCKOUT_SECONDS = 15 * 60;

    public function before(RequestInterface $request, $arguments = null)
    {
        // Only throttle POST (actual login submissions)
        if (strtolower($request->getMethod()) !== 'post') {
            return;
        }

        $cache = \Config\Services::cache();
        $ip    = $request->getIPAddress();

        $lockKey    = 'login_locked_'    . md5($ip);
        $countKey   = 'login_attempts_'  . md5($ip);
        $lockedUntil = $cache->get($lockKey);

        // IP is currently locked out
        if ($lockedUntil !== null) {
            $secondsLeft = $lockedUntil - time();

            if ($secondsLeft > 0) {
                \App\Libraries\AuthenticationAudit::record('locked', null, $ip);
                $minutes = ceil($secondsLeft / 60);
                return redirect()
                    ->back()
                    ->with(
                        'error',
                        "Too many failed login attempts. Please try again in {$minutes} minute(s)."
                    );
            }

            // Lock has expired — clean up
            $cache->delete($lockKey);
            $cache->delete($countKey);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed on the way out — recording failures happens in Auth controller
    }
}
