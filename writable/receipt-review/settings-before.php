<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<style>
    .ui-settings-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(280px, 360px);
        gap: 20px;
        align-items: start;
        width: 100%;
        min-width: 0;
    }

    .ui-settings-shell > *,
    .ui-settings-card,
    .ui-settings-card .card-body,
    .ui-section,
    .setting-box,
    .settings-preview { min-width: 0; }

    .ui-settings-card { overflow: hidden; }
    .ui-settings-card .card-body { padding: 22px; }

    .ui-section + .ui-section {
        margin-top: 22px;
        padding-top: 22px;
        border-top: 1px solid var(--line);
    }

    .ui-section-head {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 16px;
    }

    .ui-section-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        color: var(--primary);
        background: var(--primary-light);
    }

    .ui-section-head h2 {
        margin: 0;
        font-size: 17px;
    }

    .ui-section-head p {
        margin: 4px 0 0;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.5;
    }

    .ui-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .setting-box {
        border: 1px solid var(--line);
        border-radius: 12px;
        padding: 15px;
        background: var(--surface-2);
    }

    .setting-box .label { margin-bottom: 7px; }

    .setting-help {
        display: block;
        margin-top: 7px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.45;
    }

    .choice-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .choice-option { position: relative; }
    .choice-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .choice-option span {
        min-height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 9px 10px;
        border: 1.5px solid var(--line-2);
        border-radius: 9px;
        background: var(--surface);
        color: var(--text-2);
        font-size: 13px;
        font-weight: 700;
        text-align: center;
        cursor: pointer;
        transition: border-color .15s, background .15s, color .15s, box-shadow .15s;
    }

    .choice-option input:checked + span {
        border-color: var(--primary);
        background: var(--primary-light);
        color: var(--primary);
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary) 14%, transparent);
    }

    .choice-option input:focus-visible + span {
        outline: 3px solid color-mix(in srgb, var(--primary) 22%, transparent);
        outline-offset: 2px;
    }

    .accent-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 9px;
    }

    .accent-option { position: relative; }
    .accent-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .accent-card {
        display: grid;
        justify-items: center;
        gap: 7px;
        min-height: 72px;
        padding: 11px 7px 9px;
        border: 1.5px solid var(--line-2);
        border-radius: 10px;
        background: var(--surface);
        cursor: pointer;
        color: var(--text-2);
        font-size: 12px;
        font-weight: 700;
    }

    .accent-dot {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        box-shadow: inset 0 0 0 4px rgba(255,255,255,.42), 0 1px 5px rgba(0,0,0,.14);
    }

    .accent-dot.blue { background: #2b7fff; }
    .accent-dot.green { background: #159b62; }
    .accent-dot.purple { background: #7c5ce7; }
    .accent-dot.orange { background: #d97706; }

    .accent-option input:checked + .accent-card {
        border-color: var(--primary);
        background: var(--primary-light);
        color: var(--primary);
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary) 14%, transparent);
    }

    .switch-list { display: grid; gap: 10px; }

    .switch-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        padding: 14px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: var(--surface-2);
        cursor: pointer;
    }

    .switch-copy strong {
        display: block;
        color: var(--text);
        font-size: 14px;
    }

    .switch-copy span {
        display: block;
        margin-top: 4px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.45;
    }

    .switch-control {
        width: 48px;
        height: 28px;
        position: relative;
        flex: 0 0 auto;
    }

    .switch-control input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .switch-track {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: var(--line-2);
        transition: background .2s;
    }

    .switch-track::after {
        content: '';
        position: absolute;
        width: 22px;
        height: 22px;
        top: 3px;
        left: 3px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,.2);
        transition: transform .2s;
    }

    .switch-control input:checked + .switch-track { background: var(--primary); }
    .switch-control input:checked + .switch-track::after { transform: translateX(20px); }
    .switch-control input:focus-visible + .switch-track {
        outline: 3px solid color-mix(in srgb, var(--primary) 22%, transparent);
        outline-offset: 2px;
    }

    .ui-settings-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 22px;
        padding-top: 18px;
        border-top: 1px solid var(--line);
    }

    .ui-settings-actions-right {
        display: flex;
        align-items: center;
        gap: 9px;
        flex-wrap: wrap;
    }

    .browser-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 13px 14px;
        border: 1px solid var(--line);
        border-radius: 11px;
        background: var(--primary-lighter);
        color: var(--text-2);
        font-size: 12px;
        line-height: 1.5;
        margin-bottom: 18px;
    }

    .settings-preview {
        position: sticky;
        top: 82px;
        overflow: hidden;
    }

    .preview-window {
        margin: 4px;
        border: 1px solid var(--line);
        border-radius: 12px;
        overflow: hidden;
        background: var(--bg);
    }

    .preview-topbar {
        height: 46px;
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 0 12px;
        border-bottom: 1px solid var(--line);
        background: var(--surface);
    }

    .preview-menu {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: var(--primary-light);
        color: var(--primary);
        display: grid;
        place-items: center;
        font-weight: 800;
    }

    .preview-content { padding: 14px; }
    .preview-title { height: 11px; width: 42%; border-radius: 999px; background: var(--text); opacity: .78; }
    .preview-subtitle { height: 7px; width: 68%; margin-top: 8px; border-radius: 999px; background: var(--muted); opacity: .55; }

    .preview-card {
        margin-top: 14px;
        padding: 14px;
        border: 1px solid var(--line);
        border-radius: 11px;
        background: var(--surface);
    }

    .preview-card-line { height: 8px; border-radius: 999px; background: var(--line-2); }
    .preview-card-line + .preview-card-line { margin-top: 9px; }
    .preview-card-line.short { width: 55%; }

    .preview-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 92px;
        min-height: 36px;
        margin-top: 13px;
        padding: 8px 13px;
        border-radius: 9px;
        background: var(--primary);
        color: #fff;
        font-size: 12px;
        font-weight: 800;
    }

    .save-message {
        display: none;
        margin-bottom: 16px;
    }

    .save-message.show { display: block; }
    .save-message.error {
        border-color: color-mix(in srgb, var(--danger) 45%, var(--line));
        background: var(--danger-bg);
        color: var(--danger-text);
    }

    .settings-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--muted);
        font-size: 12px;
    }
    .settings-status::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--muted);
        opacity: .65;
    }
    .settings-status.unsaved { color: var(--warning-text); }
    .settings-status.unsaved::before { background: #d97706; opacity: 1; }

    @media (max-width: 980px) {
        .ui-settings-shell { grid-template-columns: 1fr; }
        .settings-preview {
            position: static;
            order: 2;
        }
    }

    @media (max-width: 680px) {
        .ui-settings-shell { gap: 14px; }
        .ui-settings-card .card-body { padding: 15px; }
        .ui-section + .ui-section {
            margin-top: 18px;
            padding-top: 18px;
        }
        .ui-section-head {
            gap: 10px;
            margin-bottom: 13px;
        }
        .ui-section-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
        }
        .ui-section-head h2 { font-size: 16px; }
        .ui-grid { grid-template-columns: 1fr; }
        .setting-box { padding: 13px; }
        .accent-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .choice-row { grid-template-columns: 1fr; }
        .choice-option span { min-height: 46px; }
        .accent-card { min-height: 68px; }
        .switch-row {
            align-items: center;
            gap: 12px;
            padding: 13px;
        }
        .switch-copy { min-width: 0; }
        .switch-copy strong,
        .switch-copy span,
        .browser-note span { overflow-wrap: anywhere; }
        .browser-note {
            padding: 12px;
            margin-bottom: 15px;
        }
        .ui-settings-actions {
            align-items: stretch;
            flex-direction: column;
            margin-top: 18px;
            padding-top: 15px;
        }
        .ui-settings-actions-right {
            display: grid;
            grid-template-columns: 1fr;
        }
        .ui-settings-actions .btn {
            width: 100%;
            min-height: 46px;
            white-space: normal;
        }
        .settings-status { justify-content: center; }
        .preview-window { margin: 0; }
    }

    @media (max-width: 390px) {
        .ui-settings-card .card-body { padding: 13px; }
        .accent-grid { gap: 7px; }
        .accent-card { padding-inline: 5px; }
        .switch-row { align-items: flex-start; }
        .switch-control { margin-top: 1px; }
    }
</style>

<div class="page-header">
    <h1>Interface Settings</h1>
    <p>Adjust how the system looks and feels on this browser.</p>
</div>

<div class="alert success save-message" id="settingsSaved" role="status" aria-live="polite">
    Interface settings were saved on this device.
</div>
<div class="alert save-message error" id="settingsError" role="alert" aria-live="assertive">
    The browser could not save these settings. Check whether browser storage is allowed.
</div>

<div class="ui-settings-shell">
    <form class="card ui-settings-card" id="uiSettingsForm" novalidate>
        <div class="card-body">
            <div class="browser-note">
                <svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex:0 0 auto; margin-top:1px;">
                    <path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z"/><path d="M12 16v-4M12 8h.01"/>
                </svg>
                <span>These preferences are stored only in this browser. They do not create a database table or change pharmacy records. Anyone using the same browser profile will see the same interface preferences.</span>
            </div>

            <section class="ui-section">
                <div class="ui-section-head">
                    <div class="ui-section-icon" aria-hidden="true">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                        </svg>
                    </div>
                    <div>
                        <h2>Appearance</h2>
                        <p>Choose the color mode and main interface color.</p>
                    </div>
                </div>

                <div class="ui-grid">
                    <div class="setting-box">
                        <label class="label">Color Mode</label>
                        <div class="choice-row" role="radiogroup" aria-label="Color mode">
                            <label class="choice-option">
                                <input type="radio" name="theme" value="system">
                                <span>Use Device</span>
                            </label>
                            <label class="choice-option">
                                <input type="radio" name="theme" value="light">
                                <span>Light</span>
                            </label>
                            <label class="choice-option">
                                <input type="radio" name="theme" value="dark">
                                <span>Dark</span>
                            </label>
                        </div>
                        <span class="setting-help">Use Device follows the light or dark setting of the computer or phone.</span>
                    </div>

                    <div class="setting-box">
                        <label class="label">Main Color</label>
                        <div class="accent-grid" role="radiogroup" aria-label="Main interface color">
                            <label class="accent-option">
                                <input type="radio" name="accent" value="blue">
                                <span class="accent-card"><span class="accent-dot blue"></span>Blue</span>
                            </label>
                            <label class="accent-option">
                                <input type="radio" name="accent" value="green">
                                <span class="accent-card"><span class="accent-dot green"></span>Green</span>
                            </label>
                            <label class="accent-option">
                                <input type="radio" name="accent" value="purple">
                                <span class="accent-card"><span class="accent-dot purple"></span>Purple</span>
                            </label>
                            <label class="accent-option">
                                <input type="radio" name="accent" value="orange">
                                <span class="accent-card"><span class="accent-dot orange"></span>Orange</span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ui-section">
                <div class="ui-section-head">
                    <div class="ui-section-icon" aria-hidden="true">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M4 7V4h16v3M9 20h6M12 4v16"/>
                        </svg>
                    </div>
                    <div>
                        <h2>Text and spacing</h2>
                        <p>Make information easier to read or fit more records on the screen.</p>
                    </div>
                </div>

                <div class="ui-grid">
                    <div class="setting-box">
                        <label class="label">Text Size</label>
                        <div class="choice-row" role="radiogroup" aria-label="Text size">
                            <label class="choice-option">
                                <input type="radio" name="textSize" value="normal">
                                <span>Normal</span>
                            </label>
                            <label class="choice-option">
                                <input type="radio" name="textSize" value="large">
                                <span>Large</span>
                            </label>
                            <label class="choice-option">
                                <input type="radio" name="textSize" value="extra-large">
                                <span>Extra Large</span>
                            </label>
                        </div>
                    </div>

                    <div class="setting-box">
                        <label class="label">Table and Card Spacing</label>
                        <div class="choice-row" role="radiogroup" aria-label="Interface spacing">
                            <label class="choice-option">
                                <input type="radio" name="density" value="compact">
                                <span>Compact</span>
                            </label>
                            <label class="choice-option">
                                <input type="radio" name="density" value="comfortable">
                                <span>Comfortable</span>
                            </label>
                            <label class="choice-option">
                                <input type="radio" name="density" value="spacious">
                                <span>Spacious</span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ui-section">
                <div class="ui-section-head">
                    <div class="ui-section-icon" aria-hidden="true">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3M16 21h3a2 2 0 0 0 2-2v-3"/><rect x="7" y="7" width="10" height="10" rx="2"/>
                        </svg>
                    </div>
                    <div>
                        <h2>Accessibility</h2>
                        <p>Increase click areas and reduce visual movement when needed.</p>
                    </div>
                </div>

                <div class="switch-list">
                    <label class="switch-row">
                        <span class="switch-copy">
                            <strong>Larger buttons and fields</strong>
                            <span>Increases the height of buttons, search boxes, dropdowns, and form fields.</span>
                        </span>
                        <span class="switch-control">
                            <input type="checkbox" name="largeControls" value="1">
                            <span class="switch-track" aria-hidden="true"></span>
                        </span>
                    </label>

                    <label class="switch-row">
                        <span class="switch-copy">
                            <strong>Reduce animations</strong>
                            <span>Turns off most movement and transition effects in the interface.</span>
                        </span>
                        <span class="switch-control">
                            <input type="checkbox" name="reduceMotion" value="1">
                            <span class="switch-track" aria-hidden="true"></span>
                        </span>
                    </label>


                    <label class="switch-row">
                        <span class="switch-copy">
                            <strong>Higher contrast</strong>
                            <span>Makes borders, muted text, and keyboard focus indicators easier to see.</span>
                        </span>
                        <span class="switch-control">
                            <input type="checkbox" name="highContrast" value="1">
                            <span class="switch-track" aria-hidden="true"></span>
                        </span>
                    </label>

                    <label class="switch-row">
                        <span class="switch-copy">
                            <strong>Keep table headings visible</strong>
                            <span>Keeps column names at the top while scrolling through long product, sales, and inventory lists.</span>
                        </span>
                        <span class="switch-control">
                            <input type="checkbox" name="stickyHeaders" value="1">
                            <span class="switch-track" aria-hidden="true"></span>
                        </span>
                    </label>
                </div>
            </section>

            <div class="ui-settings-actions">
                <span class="settings-status" id="settingsStatus">No unsaved changes</span>
                <div class="ui-settings-actions-right">
                    <button type="button" class="btn btn-secondary" id="resetUiSettings">Restore Defaults</button>
                    <button type="submit" class="btn btn-primary" id="saveUiSettings">Save on This Device</button>
                </div>
            </div>
        </div>
    </form>

    <aside class="card settings-preview" aria-label="Interface preview">
        <div class="card-head">
            <div>
                <h2 style="margin:0; font-size:17px;">Interface Preview</h2>
                <p class="muted" style="margin:5px 0 0; font-size:12px;">The preview updates as you select options.</p>
            </div>
        </div>
        <div class="card-body" style="padding:16px;">
            <div class="preview-window" id="interfacePreview">
                <div class="preview-topbar">
                    <div class="preview-menu">☰</div>
                    <strong style="font-size:13px;">Products</strong>
                </div>
                <div class="preview-content">
                    <div class="preview-title"></div>
                    <div class="preview-subtitle"></div>
                    <div class="preview-card">
                        <div class="preview-card-line"></div>
                        <div class="preview-card-line short"></div>
                        <div class="preview-button">Save</div>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</div>

<script>
(function () {
    const STORAGE_KEY = 'pharxmaco-ui-settings';
    const LEGACY_THEME_KEY = 'pharxmaco-theme';
    const defaults = {
        theme: 'system',
        accent: 'blue',
        textSize: 'normal',
        density: 'comfortable',
        largeControls: false,
        reduceMotion: false,
        highContrast: false,
        stickyHeaders: false
    };

    const form = document.getElementById('uiSettingsForm');
    const savedMessage = document.getElementById('settingsSaved');
    const errorMessage = document.getElementById('settingsError');
    const statusMessage = document.getElementById('settingsStatus');
    const resetButton = document.getElementById('resetUiSettings');
    const preview = document.getElementById('interfacePreview');
    const allowed = {
        theme: ['system', 'light', 'dark'],
        accent: ['blue', 'green', 'purple', 'orange'],
        textSize: ['normal', 'large', 'extra-large'],
        density: ['compact', 'comfortable', 'spacious']
    };

    function normalizeSettings(settings) {
        const normalized = Object.assign({}, defaults, settings || {});
        Object.keys(allowed).forEach(function (key) {
            if (!allowed[key].includes(normalized[key])) normalized[key] = defaults[key];
        });
        ['largeControls', 'reduceMotion', 'highContrast', 'stickyHeaders'].forEach(function (key) {
            normalized[key] = Boolean(normalized[key]);
        });
        return normalized;
    }

    function readStoredSettings() {
        try {
            const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
            if (!stored.theme) {
                const legacyTheme = localStorage.getItem(LEGACY_THEME_KEY);
                if (legacyTheme === 'light' || legacyTheme === 'dark') stored.theme = legacyTheme;
            }
            return normalizeSettings(stored);
        } catch (error) {
            return normalizeSettings(defaults);
        }
    }

    function collectFormSettings() {
        const data = new FormData(form);
        return normalizeSettings({
            theme: String(data.get('theme') || defaults.theme),
            accent: String(data.get('accent') || defaults.accent),
            textSize: String(data.get('textSize') || defaults.textSize),
            density: String(data.get('density') || defaults.density),
            largeControls: Boolean(form.elements.largeControls.checked),
            reduceMotion: Boolean(form.elements.reduceMotion.checked),
            highContrast: Boolean(form.elements.highContrast.checked),
            stickyHeaders: Boolean(form.elements.stickyHeaders.checked)
        });
    }

    function fillForm(settings) {
        ['theme', 'accent', 'textSize', 'density'].forEach(function (name) {
            const value = settings[name] || defaults[name];
            const input = form.querySelector('input[name="' + name + '"][value="' + value + '"]');
            if (input) input.checked = true;
        });
        form.elements.largeControls.checked = Boolean(settings.largeControls);
        form.elements.reduceMotion.checked = Boolean(settings.reduceMotion);
        form.elements.highContrast.checked = Boolean(settings.highContrast);
        form.elements.stickyHeaders.checked = Boolean(settings.stickyHeaders);
    }

    function resolveDarkMode(theme) {
        if (theme === 'dark') return true;
        if (theme === 'light') return false;
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function applySettings(settings) {
        const html = document.documentElement;
        html.classList.toggle('dark', resolveDarkMode(settings.theme));
        html.dataset.themePreference = settings.theme;
        html.dataset.accent = settings.accent;
        html.dataset.textSize = settings.textSize;
        html.dataset.density = settings.density;
        html.classList.toggle('large-controls', Boolean(settings.largeControls));
        html.classList.toggle('reduce-motion', Boolean(settings.reduceMotion));
        html.classList.toggle('high-contrast', Boolean(settings.highContrast));
        html.classList.toggle('sticky-table-headers', Boolean(settings.stickyHeaders));
        try { localStorage.setItem(LEGACY_THEME_KEY, resolveDarkMode(settings.theme) ? 'dark' : 'light'); } catch (error) {}
    }

    function updatePreview(settings) {
        if (!preview) return;
        preview.dataset.previewTheme = resolveDarkMode(settings.theme) ? 'dark' : 'light';
        preview.dataset.previewAccent = settings.accent;
        preview.dataset.previewText = settings.textSize;
        preview.dataset.previewDensity = settings.density;
        preview.classList.toggle('preview-large-controls', Boolean(settings.largeControls));
        preview.classList.toggle('preview-high-contrast', Boolean(settings.highContrast));
    }

    function previewCurrentForm() {
        updatePreview(collectFormSettings());
    }

    const initial = readStoredSettings();
    fillForm(initial);
    updatePreview(initial);

    form.addEventListener('change', function () {
        previewCurrentForm();
        statusMessage.textContent = 'Unsaved changes';
        statusMessage.classList.add('unsaved');
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const settings = collectFormSettings();
        errorMessage.classList.remove('show');
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
            applySettings(settings);
            updatePreview(settings);
            statusMessage.textContent = 'No unsaved changes';
            statusMessage.classList.remove('unsaved');
            savedMessage.classList.add('show');
            savedMessage.scrollIntoView({ behavior: settings.reduceMotion ? 'auto' : 'smooth', block: 'nearest' });
            window.setTimeout(function () { savedMessage.classList.remove('show'); }, 3500);
        } catch (error) {
            errorMessage.classList.add('show');
            errorMessage.scrollIntoView({ behavior: 'auto', block: 'nearest' });
        }
    });

    resetButton.addEventListener('click', function () {
        if (!window.confirm('Restore the default interface settings on this browser?')) return;
        errorMessage.classList.remove('show');
        try {
            localStorage.removeItem(STORAGE_KEY);
            localStorage.removeItem(LEGACY_THEME_KEY);
            fillForm(defaults);
            applySettings(defaults);
            updatePreview(defaults);
            statusMessage.textContent = 'No unsaved changes';
            statusMessage.classList.remove('unsaved');
            savedMessage.textContent = 'Default interface settings were restored.';
            savedMessage.classList.add('show');
            window.setTimeout(function () {
                savedMessage.classList.remove('show');
                savedMessage.textContent = 'Interface settings were saved on this device.';
            }, 3500);
        } catch (error) {
            errorMessage.classList.add('show');
        }
    });

    if (window.matchMedia) {
        const scheme = window.matchMedia('(prefers-color-scheme: dark)');
        const refreshSystemPreview = function () {
            if (collectFormSettings().theme === 'system') updatePreview(collectFormSettings());
        };
        if (typeof scheme.addEventListener === 'function') scheme.addEventListener('change', refreshSystemPreview);
        else if (typeof scheme.addListener === 'function') scheme.addListener(refreshSystemPreview);
    }

    window.addEventListener('storage', function (event) {
        if (event.key !== STORAGE_KEY && event.key !== LEGACY_THEME_KEY) return;
        const settings = readStoredSettings();
        fillForm(settings);
        updatePreview(settings);
        statusMessage.textContent = 'Updated from another tab';
        statusMessage.classList.remove('unsaved');
    });

    window.addEventListener('pharxmaco:ui-settings-applied', function (event) {
        if (!event.detail) return;
        const settings = normalizeSettings(event.detail);
        fillForm(settings);
        updatePreview(settings);
        statusMessage.textContent = 'No unsaved changes';
        statusMessage.classList.remove('unsaved');
    });
})();
</script>

<style>
    #interfacePreview[data-preview-theme="dark"] {
        --preview-bg: #0f1623;
        --preview-surface: #161e2e;
        --preview-text: #e8edf5;
        --preview-line: #2e3f58;
        background: var(--preview-bg);
        color: var(--preview-text);
    }
    #interfacePreview[data-preview-theme="dark"] .preview-topbar,
    #interfacePreview[data-preview-theme="dark"] .preview-card { background: var(--preview-surface); border-color: var(--preview-line); }
    #interfacePreview[data-preview-theme="dark"] .preview-title { background: var(--preview-text); }
    #interfacePreview[data-preview-theme="dark"] .preview-card-line { background: var(--preview-line); }

    #interfacePreview[data-preview-accent="blue"] .preview-button,
    #interfacePreview[data-preview-accent="blue"] .preview-menu { background: #2b7fff; color: #fff; }
    #interfacePreview[data-preview-accent="green"] .preview-button,
    #interfacePreview[data-preview-accent="green"] .preview-menu { background: #159b62; color: #fff; }
    #interfacePreview[data-preview-accent="purple"] .preview-button,
    #interfacePreview[data-preview-accent="purple"] .preview-menu { background: #7c5ce7; color: #fff; }
    #interfacePreview[data-preview-accent="orange"] .preview-button,
    #interfacePreview[data-preview-accent="orange"] .preview-menu { background: #d97706; color: #fff; }

    #interfacePreview[data-preview-text="large"] { font-size: 1.06em; }
    #interfacePreview[data-preview-text="extra-large"] { font-size: 1.13em; }
    #interfacePreview[data-preview-density="compact"] .preview-content,
    #interfacePreview[data-preview-density="compact"] .preview-card { padding: 10px; }
    #interfacePreview[data-preview-density="spacious"] .preview-content,
    #interfacePreview[data-preview-density="spacious"] .preview-card { padding: 18px; }
    #interfacePreview.preview-large-controls .preview-button { min-height: 44px; min-width: 106px; }
    #interfacePreview.preview-high-contrast,
    #interfacePreview.preview-high-contrast .preview-card,
    #interfacePreview.preview-high-contrast .preview-topbar { border-color: #6f89a8; }
    #interfacePreview.preview-high-contrast .preview-card-line { background: #8296ae; }
    #interfacePreview[data-preview-theme="dark"].preview-high-contrast,
    #interfacePreview[data-preview-theme="dark"].preview-high-contrast .preview-card,
    #interfacePreview[data-preview-theme="dark"].preview-high-contrast .preview-topbar { border-color: #8ca0bc; }
</style>

<?= $this->endSection() ?>
