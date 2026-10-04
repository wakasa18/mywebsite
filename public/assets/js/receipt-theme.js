/* Load before the receipt stylesheet to apply the app's saved theme before paint. */
(() => {
    'use strict';
    const settingsKey = 'pharxmaco-ui-settings';
    const legacyKey = 'pharxmaco-theme';
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');

    const readSettings = () => {
        try {
            const settings = JSON.parse(localStorage.getItem(settingsKey) || '{}') || {};
            return {
                theme: settings.theme || localStorage.getItem(legacyKey) || 'system',
                accent: settings.accent || 'blue'
            };
        } catch (_) {
            return { theme: 'system', accent: 'blue' };
        }
    };

    const applyTheme = (settings = readSettings()) => {
        const theme = ['light', 'dark', 'system'].includes(settings.theme) ? settings.theme : 'system';
        const root = document.documentElement;
        root.classList.toggle('dark', theme === 'dark' || (theme === 'system' && systemTheme.matches));
        root.dataset.themePreference = theme;
        root.dataset.accent = ['blue', 'green', 'purple', 'orange'].includes(settings.accent) ? settings.accent : 'blue';
    };

    applyTheme();
    systemTheme.addEventListener('change', () => applyTheme());
    window.addEventListener('storage', event => {
        if (event.key === settingsKey || event.key === legacyKey || event.key === null) applyTheme();
    });
    window.addEventListener('pharxmaco:ui-settings-applied', event => applyTheme(event.detail || readSettings()));
    window.addEventListener('pageshow', () => applyTheme());
})();
