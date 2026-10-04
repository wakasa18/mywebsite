<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/jpeg" href="<?= base_url('assets/images/pharxmaco-favicon.jpg') ?>?v=20260923">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Sign In — Pharxmaco Drugstore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800;900&family=DM+Mono:wght@400;500&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Page shell: full-screen dark bg matching the right panel ── */
        html, body {
            height: 100%;
            font-family: 'DM Sans', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body {
            min-height: 100vh;
            background: #0d1b2e;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            /* subtle dot grid over the dark bg */
            background-image: radial-gradient(rgba(255,255,255,.045) 1px, transparent 1px);
            background-size: 28px 28px;
            background-color: #0d1b2e;
        }

        /* ambient glow blobs in the bg */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(ellipse 700px 520px at 75% 30%, rgba(43,127,255,.13) 0%, transparent 70%),
                radial-gradient(ellipse 500px 400px at 20% 80%, rgba(22,163,74,.09) 0%, transparent 70%);
            z-index: 0;
        }

        /* ── Card: the centered split panel ── */
        .login-card {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 420px 1fr;
            width: 100%;
            max-width: 1060px;
            min-height: 620px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow:
                0 0 0 1px rgba(255,255,255,.07),
                0 32px 80px rgba(0,0,0,.55),
                0 8px 24px rgba(0,0,0,.3);
        }

        /* ══════════════════════════════
           LEFT — LOGIN FORM
        ══════════════════════════════ */
        .left {
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 52px 44px;
            position: relative;
            z-index: 1;
        }

        /* Brand */
        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 40px;
        }
        .brand-logo {
            width: 40px; height: 40px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 2px 10px rgba(43,127,255,.25);
        }
        .brand-logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .brand-text .name {
            font-size: 15px; font-weight: 800;
            color: #0d1b2e; letter-spacing: -.3px; line-height: 1;
        }
        .brand-text .sub {
            font-size: 11px; color: #6b82a0;
            margin-top: 3px; font-weight: 500;
        }

        /* Heading */
        .form-heading { margin-bottom: 28px; }
        .form-heading h1 {
            font-size: 26px; font-weight: 900;
            color: #0d1b2e; letter-spacing: -.5px;
            line-height: 1.2; margin-bottom: 7px;
        }
        .form-heading p { font-size: 13px; color: #6b82a0; font-weight: 500; }

        /* Alerts */
        .alert {
            display: flex; align-items: flex-start; gap: 9px;
            padding: 11px 13px; border-radius: 10px;
            font-size: 13px; font-weight: 500;
            margin-bottom: 18px; line-height: 1.5;
        }
        .alert svg { flex-shrink: 0; margin-top: 1px; }
        .alert.error   { background: #fff5f5; color: #9b1c1c; border: 1.5px solid #fecaca; }
        .alert.success { background: #f0fdf4; color: #166534; border: 1.5px solid #bbf7d0; }

        /* Fields */
        .form-group { margin-bottom: 15px; }
        .form-label {
            display: block; font-size: 11.5px; font-weight: 700;
            color: #2c3e5a; margin-bottom: 6px;
            letter-spacing: .2px; text-transform: uppercase;
        }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%);
            color: #6b82a0; pointer-events: none; display: flex;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 11px 13px 11px 40px;
            background: #f4f8ff;
            border: 1.5px solid #dce7f5;
            border-radius: 10px;
            font-size: 14px; font-family: inherit;
            color: #0d1b2e; outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
            font-weight: 500;
        }
        input::placeholder { color: #6b82a0; font-weight: 400; }
        input:focus {
            border-color: #2b7fff; background: #fff;
            box-shadow: 0 0 0 3px rgba(43,127,255,.12);
        }

        /* Password toggle */
        .pw-toggle {
            position: absolute; right: 10px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #6b82a0; padding: 5px; border-radius: 6px;
            display: flex; align-items: center;
            transition: color .12s, background .12s;
        }
        .pw-toggle:hover { color: #2c3e5a; background: #eef3fb; }

        /* Submit */
        .submit-btn {
            width: 100%; padding: 12px;
            background: #2b7fff; color: #fff;
            border: none; border-radius: 10px;
            font-size: 14.5px; font-weight: 800;
            font-family: inherit; cursor: pointer;
            margin-top: 8px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            letter-spacing: -.1px;
            box-shadow: 0 4px 16px rgba(43,127,255,.35);
            transition: background .15s, box-shadow .15s, transform .1s;
        }
        .submit-btn:hover  { background: #1a6fe8; box-shadow: 0 4px 22px rgba(43,127,255,.45); }
        .submit-btn:active { transform: scale(.99); }

        /* Footer */
        .form-footer {
            margin-top: 28px; padding-top: 18px;
            border-top: 1.5px solid #eef3fb;
            display: flex; align-items: center; gap: 7px;
            font-size: 12px; color: #6b82a0; font-weight: 500;
        }
        .online-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #16a34a;
            box-shadow: 0 0 6px rgba(22,163,74,.5);
            animation: blink 2.4s infinite; flex-shrink: 0;
        }
        @keyframes blink { 0%,100% { opacity:1; } 50% { opacity:.35; } }

        /* ══════════════════════════════
           RIGHT — DECORATIVE PANEL
        ══════════════════════════════ */
        .right {
            background: #0d1b2e;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 44px 40px;
        }

        /* Blobs inside the right panel (layered on top of body blobs) */
        .right::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px; border-radius: 50%;
            background: radial-gradient(circle, rgba(43,127,255,.2) 0%, transparent 65%);
            top: -180px; right: -120px; pointer-events: none;
        }
        .right::after {
            content: '';
            position: absolute;
            width: 360px; height: 360px; border-radius: 50%;
            background: radial-gradient(circle, rgba(22,163,74,.12) 0%, transparent 65%);
            bottom: -100px; left: -60px; pointer-events: none;
        }

        /* Dot grid overlay */
        .right-grid {
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,.065) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
        }

        /* Separator line between panels */
        .right::before { border-left: 1px solid rgba(255,255,255,.07); }

        .right-content { position: relative; z-index: 1; width: 100%; max-width: 460px; }

        /* ── Mock POS UI ── */
        .pos-mock {
            background: rgba(22,30,46,.9);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 14px; overflow: hidden;
            box-shadow: 0 20px 56px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.04);
            backdrop-filter: blur(8px);
        }
        .mock-topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 9px 14px;
            background: rgba(9,21,37,.6);
            border-bottom: 1px solid rgba(255,255,255,.07); gap: 10px;
        }
        .mock-dots { display: flex; gap: 5px; }
        .mock-dot  { width: 8px; height: 8px; border-radius: 50%; }
        .mock-dot.r { background: #f85149; }
        .mock-dot.y { background: #f0a520; }
        .mock-dot.g { background: #3fb950; }
        .mock-title {
            font-size: 10px; font-weight: 700;
            color: rgba(255,255,255,.3); letter-spacing: .4px;
            text-transform: uppercase; font-family: 'DM Mono', monospace;
        }
        .mock-live {
            display: flex; align-items: center; gap: 5px;
            font-size: 10px; color: #3fb950; font-weight: 600;
        }
        .mock-live-dot {
            width: 5px; height: 5px; border-radius: 50%;
            background: #3fb950; box-shadow: 0 0 5px #3fb950;
            animation: blink 1.8s infinite;
        }
        .mock-body {
            display: grid;
            grid-template-columns: 1fr 136px 126px;
            height: 240px;
        }
        .mock-products {
            border-right: 1px solid rgba(255,255,255,.07);
            padding: 9px; display: flex; flex-direction: column; gap: 5px;
        }
        .mock-search {
            height: 22px; background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08); border-radius: 5px;
            display: flex; align-items: center; padding: 0 7px; gap: 5px; margin-bottom: 2px;
        }
        .mock-search-bar { flex: 1; height: 2px; background: rgba(255,255,255,.12); border-radius: 2px; }
        .mock-prod-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 4px; flex: 1; }
        .mock-prod {
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
            border-radius: 6px; padding: 6px 5px; display: flex; flex-direction: column; gap: 4px;
        }
        .mock-prod.active { background: rgba(43,127,255,.14); border-color: rgba(43,127,255,.38); }
        .mock-prod-line { height: 5px; border-radius: 3px; background: rgba(255,255,255,.14); }
        .mock-prod-line.short { width: 55%; background: rgba(43,127,255,.5); }
        .mock-prod-price { height: 7px; width: 65%; border-radius: 3px; background: rgba(255,255,255,.22); }
        .mock-stock { height: 4px; width: 40%; border-radius: 3px; background: rgba(63,185,80,.4); }
        .mock-cart {
            border-right: 1px solid rgba(255,255,255,.07);
            padding: 9px; display: flex; flex-direction: column; gap: 5px;
        }
        .mock-zone-label {
            font-size: 7.5px; font-weight: 800; color: rgba(255,255,255,.22);
            text-transform: uppercase; letter-spacing: .6px; margin-bottom: 2px;
            font-family: 'DM Mono', monospace;
        }
        .mock-cart-item {
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
            border-radius: 6px; padding: 5px 7px; display: flex; flex-direction: column; gap: 4px;
        }
        .mock-cart-item:first-of-type { background: rgba(43,127,255,.1); border-color: rgba(43,127,255,.24); }
        .mock-item-name { height: 5px; width: 80%; border-radius: 3px; background: rgba(255,255,255,.18); }
        .mock-item-row { display: flex; align-items: center; justify-content: space-between; gap: 4px; }
        .mock-qty-ctrl { display: flex; gap: 3px; align-items: center; }
        .mock-btn { width: 12px; height: 12px; border-radius: 3px; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1); }
        .mock-num { width: 14px; height: 12px; border-radius: 3px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08); }
        .mock-sub { height: 7px; width: 38%; border-radius: 3px; background: rgba(43,127,255,.5); }
        .mock-total-bar {
            margin-top: auto; background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07); border-radius: 6px;
            padding: 6px 7px; display: flex; justify-content: space-between; align-items: center;
        }
        .mock-total-l { height: 5px; width: 30%; border-radius: 3px; background: rgba(255,255,255,.14); }
        .mock-total-r { height: 9px; width: 40%; border-radius: 3px; background: rgba(255,255,255,.28); }
        .mock-pay { padding: 9px; display: flex; flex-direction: column; gap: 5px; }
        .mock-summary {
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
            border-radius: 6px; padding: 6px 7px; display: flex; flex-direction: column; gap: 4px;
        }
        .mock-sum-row { display: flex; justify-content: space-between; align-items: center; }
        .mock-sum-l { height: 4px; width: 35%; border-radius: 3px; background: rgba(255,255,255,.1); }
        .mock-sum-r { height: 4px; width: 28%; border-radius: 3px; background: rgba(255,255,255,.18); }
        .mock-sum-row.grand .mock-sum-l { background: rgba(43,127,255,.4); }
        .mock-sum-row.grand .mock-sum-r { background: rgba(43,127,255,.65); height: 7px; }
        .mock-methods { display: grid; grid-template-columns: repeat(3,1fr); gap: 4px; }
        .mock-method { height: 24px; border-radius: 5px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07); }
        .mock-method.active { background: rgba(43,127,255,.18); border-color: rgba(43,127,255,.38); }
        .mock-field { height: 20px; border-radius: 5px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07); }
        .mock-complete {
            margin-top: auto; height: 26px; border-radius: 6px;
            background: linear-gradient(135deg, #16a34a, #15803d);
            box-shadow: 0 3px 10px rgba(22,163,74,.3);
            display: flex; align-items: center; justify-content: center;
        }
        .mock-complete-text { height: 6px; width: 55%; border-radius: 3px; background: rgba(255,255,255,.5); }

        /* Stats */
        .mock-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 9px; margin-top: 12px; }
        .mock-stat {
            background: rgba(22,30,46,.9); border: 1px solid rgba(255,255,255,.08);
            border-radius: 9px; padding: 10px 12px; backdrop-filter: blur(8px);
        }
        .mock-stat-label { height: 4px; width: 55%; border-radius: 3px; background: rgba(255,255,255,.14); margin-bottom: 7px; }
        .mock-stat-val   { height: 11px; width: 70%; border-radius: 4px; background: rgba(255,255,255,.28); margin-bottom: 5px; }
        .mock-stat-sub   { height: 4px; width: 45%; border-radius: 3px; background: rgba(43,127,255,.4); }

        /* Tagline */
        .right-tagline { margin-top: 22px; text-align: center; }
        .right-tagline h2 {
            font-size: 19px; font-weight: 900; color: #fff;
            letter-spacing: -.4px; margin-bottom: 7px;
        }
        .right-tagline p {
            font-size: 12.5px; color: rgba(255,255,255,.38);
            line-height: 1.6; font-weight: 500;
        }

        /* Pills */
        .right-pills { display: flex; justify-content: center; gap: 6px; flex-wrap: wrap; margin-top: 14px; }
        .pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 600;
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
            color: rgba(255,255,255,.5);
        }
        .pill-dot { width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }

        /* ══════════════════════════════
           RESPONSIVE
        ══════════════════════════════ */

        /* Tablet: stack vertically, right panel below */
        @media (max-width: 860px) {
            body { padding: 0; align-items: stretch; }
            .login-card {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto;
                border-radius: 0;
                min-height: 100vh;
                max-width: 100%;
                box-shadow: none;
            }
            .left {
                padding: 48px 32px;
                justify-content: center;
            }
            .right {
                padding: 40px 24px;
                min-height: 400px;
            }
        }

        /* Mobile: hide right panel, form fills screen */
        @media (max-width: 560px) {
            .right { display: none; }
            .left  { padding: 40px 24px; min-height: 100vh; }
        }
        body { background: #eef3f6; }
        body::before { display: none; }
        .login-card { border: 1px solid #dce5eb; border-radius: 24px; box-shadow: 0 24px 70px rgba(19,43,59,.12); }
        .brand-text .name, .form-heading h1, .right-tagline h2 { font-family: 'Manrope', 'DM Sans', system-ui, sans-serif; }
        .brand-text .sub, .form-heading p, .form-footer { color: #587084; }
        .form-heading h1 { font-size: 30px; letter-spacing: -.8px; }
        .input-wrap input { border-width: 1px; background: #f7f9fc; border-color: #bdceda; }
        input::placeholder { color: #587084; }
        .right { background: #132b3b; }
        .right::before, .right::after, .right-grid { display: none; }
        .right-tagline h2 { font-size: 23px; }
        .right-tagline p { font-size: 14px; color: #b8ccdb; }
        .pill { color: #cbdbe6; padding: 6px 11px; }
        .online-dot { animation: none; box-shadow: none; }
        #password { padding-right: 52px; }
        .pw-toggle { right: 4px; width: 44px; height: 44px; justify-content: center; }
        .submit-btn { min-height: 48px; background: #2166d1; box-shadow: none; }
        button:focus-visible { outline: 3px solid #2166d1; outline-offset: 3px; }
        @media (max-width: 860px) {
            html, body { height: auto; min-height: 100%; }
            body { min-height: 100dvh; padding: 24px 16px; align-items: center; }
            .login-card { max-width: 480px; min-height: 0; border-radius: 18px; grid-template-rows: auto; }
            .left { min-height: 0; padding: 36px 28px; }
            .right { display: none; }
            .brand { margin-bottom: 28px; }
            input[type="text"], input[type="password"] { font-size: 16px; min-height: 48px; }
        }
        @media (max-width: 380px) {
            body { padding: 12px; }
            .left { padding: 28px 20px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

    <div class="login-card">

        <!-- ═══ LEFT: FORM ═══ -->
        <div class="left">
            <div class="brand">
                <div class="brand-logo">
                    <img src="<?= base_url('assets/images/295259270_419609253521915_2551810649101629249_n.jpg') ?>" alt="Pharxmaco">
                </div>
                <div class="brand-text">
                    <div class="name">Pharxmaco</div>
                    <div class="sub">Drugstore System</div>
                </div>
            </div>

            <div class="form-heading">
                <h1>Welcome back</h1>
                <p>Sign in to access your dashboard.</p>
            </div>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert error">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                    </svg>
                    <?= esc(session()->getFlashdata('error')) ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert success">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M5 13l4 4L19 7"/>
                    </svg>
                    <?= esc(session()->getFlashdata('success')) ?>
                </div>
            <?php endif; ?>

            <?php
                // Submit to the current hostname and installation folder.
                // This avoids losing the session when www/non-www differs from app.baseURL,
                // and it also works when the project is inside a local subfolder.
                $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
                $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
                $loginAction = ($basePath === '' || $basePath === '.') ? '/login' : $basePath . '/login';
            ?>
            <form method="post" action="<?= esc($loginAction) ?>" autocomplete="on">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <input type="text" id="username" name="username" value="<?= old('username') ?>"
                               placeholder="Enter your username" required autocomplete="username" autocapitalize="none" spellcheck="false">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password"
                               placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="pw-toggle" id="togglePass" title="Show password" aria-label="Show password" aria-pressed="false">
                            <svg id="eyeIcon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                    </svg>
                    Sign In
                </button>
            </form>

            <div class="form-footer">
                <div class="online-dot"></div>
                Pharxmaco Drugstore Management System
            </div>
        </div>

        <!-- ═══ RIGHT: DECORATIVE ═══ -->
        <div class="right">
            <div class="right-grid"></div>

            <div class="right-content">
                <div class="pos-mock">
                    <div class="mock-topbar">
                        <div class="mock-dots">
                            <div class="mock-dot r"></div>
                            <div class="mock-dot y"></div>
                            <div class="mock-dot g"></div>
                        </div>
                        <div class="mock-title">Point of Sale</div>
                        <div class="mock-live"><div class="mock-live-dot"></div> Live</div>
                    </div>

                    <div class="mock-body">
                        <div class="mock-products">
                            <div class="mock-search">
                                <svg width="9" height="9" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,.3)" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                <div class="mock-search-bar"></div>
                            </div>
                            <div class="mock-prod-grid">
                                <?php for ($i = 0; $i < 9; $i++): ?>
                                <div class="mock-prod <?= $i === 2 ? 'active' : '' ?>">
                                    <div class="mock-prod-line <?= $i === 2 ? 'short' : '' ?>"></div>
                                    <div class="mock-prod-price"></div>
                                    <div class="mock-stock"></div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mock-cart">
                            <div class="mock-zone-label">Cart</div>
                            <?php for ($i = 0; $i < 3; $i++): ?>
                            <div class="mock-cart-item">
                                <div class="mock-item-name"></div>
                                <div class="mock-item-row">
                                    <div class="mock-qty-ctrl">
                                        <div class="mock-btn"></div>
                                        <div class="mock-num"></div>
                                        <div class="mock-btn"></div>
                                    </div>
                                    <div class="mock-sub"></div>
                                </div>
                            </div>
                            <?php endfor; ?>
                            <div class="mock-total-bar">
                                <div class="mock-total-l"></div>
                                <div class="mock-total-r"></div>
                            </div>
                        </div>

                        <div class="mock-pay">
                            <div class="mock-zone-label">Payment</div>
                            <div class="mock-summary">
                                <div class="mock-sum-row"><div class="mock-sum-l"></div><div class="mock-sum-r"></div></div>
                                <div class="mock-sum-row"><div class="mock-sum-l"></div><div class="mock-sum-r"></div></div>
                                <div class="mock-sum-row grand"><div class="mock-sum-l"></div><div class="mock-sum-r"></div></div>
                            </div>
                            <div class="mock-methods">
                                <div class="mock-method active"></div>
                                <div class="mock-method"></div>
                                <div class="mock-method"></div>
                            </div>
                            <div class="mock-field"></div>
                            <div class="mock-field"></div>
                            <div class="mock-complete"><div class="mock-complete-text"></div></div>
                        </div>
                    </div>
                </div>

                <div class="mock-stats">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="mock-stat">
                        <div class="mock-stat-label"></div>
                        <div class="mock-stat-val"></div>
                        <div class="mock-stat-sub"></div>
                    </div>
                    <?php endfor; ?>
                </div>

                <div class="right-tagline">
                    <h2>Built for pharmacy operations</h2>
                    <p>Manage inventory, process sales, and track expiry across multiple branches — all in one place.</p>
                </div>

                <div class="right-pills">
                    <div class="pill"><div class="pill-dot" style="background:#3fb950;"></div> Multi-branch</div>
                    <div class="pill"><div class="pill-dot" style="background:#2b7fff;"></div> Real-time POS</div>
                    <div class="pill"><div class="pill-dot" style="background:#f0a520;"></div> Expiry alerts</div>
                    <div class="pill"><div class="pill-dot" style="background:#a78bfa;"></div> Analytics</div>
                </div>
            </div>
        </div>

    </div><!-- /login-card -->

    <script>
        const pass = document.getElementById('password');
        const btn  = document.getElementById('togglePass');
        const icon = document.getElementById('eyeIcon');
        const eyeOpen   = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
        const eyeClosed = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19M1 1l22 22"/>';
        btn.addEventListener('click', () => {
            const show = pass.type === 'password';
            pass.type  = show ? 'text' : 'password';
            icon.innerHTML = show ? eyeClosed : eyeOpen;
            btn.setAttribute('aria-pressed', String(show));
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.title = show ? 'Hide password' : 'Show password';
        });
    </script>
</body>
</html>
