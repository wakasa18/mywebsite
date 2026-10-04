(() => {
    'use strict';
    const key = 'pharxmaco-ui-settings';
    const scheme = matchMedia('(prefers-color-scheme: dark)');
    const read = () => {
        try {
            const value = JSON.parse(localStorage.getItem(key) || '{}');
            return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
        } catch (_) { return {}; }
    };
    let settings = read();
    const applyTheme = () => {
        const theme = ['light','dark'].includes(settings.theme) ? settings.theme : 'system';
        const dark = theme === 'dark' || (theme === 'system' && scheme.matches);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.classList.toggle('reduce-motion', settings.reduceMotion === true);
        document.documentElement.classList.toggle('high-contrast', settings.highContrast === true);
        document.documentElement.dataset.textSize = ['large','extra-large'].includes(settings.textSize) ? settings.textSize : 'normal';
        const button = document.getElementById('loginThemeToggle');
        if (button) {
            button.hidden = false;
            button.setAttribute('aria-pressed', String(dark));
            button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
            button.title = button.getAttribute('aria-label');
        }
    };
    applyTheme();
    if (typeof scheme.addEventListener === 'function') scheme.addEventListener('change', applyTheme);
    else if (typeof scheme.addListener === 'function') scheme.addListener(applyTheme);
    window.addEventListener('storage', event => {
        if (event.key === key || event.key === null) { settings = read(); applyTheme(); }
    });
    document.addEventListener('DOMContentLoaded', () => {
        applyTheme();
        document.getElementById('loginThemeToggle').addEventListener('click', () => {
            settings = {...read(), theme: document.documentElement.classList.contains('dark') ? 'light' : 'dark'};
            const notice = document.getElementById('loginThemeNotice');
            try {
                localStorage.setItem(key, JSON.stringify(settings));
                notice.hidden = true;
                notice.textContent = '';
            }
            catch (_) {
                notice.textContent = 'Theme changed for this page only. Browser preferences could not be saved.';
                notice.hidden = false;
            }
            applyTheme();
        });
        const form = document.getElementById('loginForm');
        const password = document.getElementById('password');
        const toggle = document.getElementById('togglePass');
        const submit = document.getElementById('loginSubmit');
        const label = document.getElementById('loginSubmitLabel');
        const caps = document.getElementById('capsLockNotice');
        const stage = document.querySelector('.login-stage');
        // Stop the entrance permanently after interaction, rather than restarting on blur.
        stage.addEventListener('focusin', () => stage.classList.add('login-interacted'), {once: true});
        toggle.hidden = false;
        const hidePassword = () => {
            password.type = 'password';
            toggle.textContent = 'Show';
            toggle.setAttribute('aria-label', 'Show password');
            toggle.setAttribute('aria-pressed', 'false');
        };
        toggle.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.textContent = show ? 'Hide' : 'Show';
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            toggle.setAttribute('aria-pressed', String(show));
        });
        const showCapsLock = event => { caps.hidden = !event.getModifierState?.('CapsLock'); };
        password.addEventListener('keydown', showCapsLock);
        password.addEventListener('keyup', showCapsLock);
        password.addEventListener('blur', () => { caps.hidden = true; });
        document.addEventListener('visibilitychange', () => { if (document.hidden) hidePassword(); });
        form.addEventListener('submit', event => {
            if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
            submit.disabled = true;
            label.textContent = 'Signing in…';
        });
        window.addEventListener('pageshow', event => {
            if (event.persisted) {
                settings = read();
                applyTheme();
                stage.classList.add('login-interacted');
            }
            delete form.dataset.submitting;
            form.removeAttribute('aria-busy');
            submit.disabled = false;
            label.textContent = 'Sign in';
            caps.hidden = true;
            hidePassword();
        });
    });
})();
