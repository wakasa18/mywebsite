<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * CSRF Protection Method
     * 'session' ties the token to the server-side session, which is
     * harder to steal than a cookie-based token.
     *
     * @var string 'cookie' or 'session'
     */
    public string $csrfProtection = 'session';

    /**
     * CSRF Token Randomization
     * Kept false — randomizing per-render causes stale-token errors on pages
     * that have multiple forms (like the POS). The token still regenerates
     * after every POST submission, which is the important protection.
     */
    public bool $tokenRandomize = false;

    /**
     * CSRF Token Name
     * Renamed from the default 'csrf_test_name' to avoid fingerprinting
     * this app as a CodeIgniter project.
     */
    public string $tokenName = 'csrf_token';

    /**
     * CSRF Header Name (used by AJAX requests via X- header)
     */
    public string $headerName = 'X-CSRF-TOKEN';

    /**
     * CSRF Cookie Name (unused in session mode, kept for reference)
     */
    public string $cookieName = 'csrf_cookie';

    /**
     * CSRF Expires
     * Two hours — matches a typical cashier shift window.
     */
    public int $expires = 7200;

    /**
     * CSRF Regenerate
     * Always regenerate the token after each POST submission.
     */
    public bool $regenerate = true;

    /**
     * CSRF Redirect
     * In production, redirect back with an error instead of showing a raw 403.
     */
    public bool $redirect = true;
}
