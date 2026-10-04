<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/interface-settings.css') ?>?v=20260924-1">
<div class="interface-settings">
    <header class="settings-header">
        <div><span class="settings-eyebrow">Your workspace</span><h1>Interface settings</h1><p>Make the workspace comfortable for the way you work.</p></div>
        <span class="settings-device"><?= view('partials/icon', ['name' => 'phone']) ?> This browser only</span>
    </header>
    <p class="settings-browser-note">Preferences apply to anyone using this browser profile. Preview your changes, then save to use them throughout the app.</p>
    <noscript><p class="alert warning">Enable JavaScript to preview and save interface preferences.</p></noscript>
    <div class="settings-layout">
        <form id="uiSettingsForm" class="settings-form" data-auto-filter="off" novalidate>
            <section class="settings-section" aria-labelledby="appearanceTitle">
                <header class="settings-section-head"><span class="settings-section-icon" aria-hidden="true"><?= view('partials/icon', ['name' => 'layers']) ?></span><div><h2 id="appearanceTitle">Appearance</h2><p>Choose a theme and an accent for buttons and links.</p></div></header>
                <fieldset class="settings-field"><legend>Color mode</legend><div class="theme-options">
                    <?php foreach (['system' => ['Device', 'Follow your device'], 'light' => ['Light', 'A bright workspace'], 'dark' => ['Dark', 'A softer background']] as $value => [$label, $description]): ?>
                        <label class="settings-option theme-option"><input type="radio" name="theme" value="<?= esc($value) ?>"><span class="theme-choice"><span class="theme-sample theme-<?= esc($value) ?>" aria-hidden="true"><i></i><b></b><b></b></span><strong><?= esc($label) ?></strong><small><?= esc($description) ?></small><span class="choice-check" aria-hidden="true">✓</span></span></label>
                    <?php endforeach; ?>
                </div></fieldset>
                <fieldset class="settings-field"><legend>Accent color</legend><div class="accent-options">
                    <?php foreach (['blue' => 'Blue', 'green' => 'Green', 'purple' => 'Purple', 'orange' => 'Orange'] as $value => $label): ?>
                        <label class="settings-option accent-option"><input type="radio" name="accent" value="<?= esc($value) ?>"><span><i class="accent-dot accent-<?= esc($value) ?>" aria-hidden="true"></i><?= esc($label) ?><b class="choice-check" aria-hidden="true">✓</b></span></label>
                    <?php endforeach; ?>
                </div></fieldset>
            </section>
            <section class="settings-section" aria-labelledby="readingTitle">
                <header class="settings-section-head"><span class="settings-section-icon" aria-hidden="true">Aa</span><div><h2 id="readingTitle">Reading and spacing</h2><p>Choose a comfortable text size and amount of space.</p></div></header>
                <?php foreach ([
                    'textSize' => ['Text size', ['normal' => 'Normal', 'large' => 'Large', 'extra-large' => 'Extra large'], 'Larger text helps with labels, forms, and table entries.'],
                    'density' => ['Spacing', ['compact' => 'Compact', 'comfortable' => 'Comfortable', 'spacious' => 'Spacious'], 'Compact fits more information. Spacious adds room between controls and rows.'],
                ] as $name => [$legend, $options, $help]): ?>
                    <fieldset class="settings-field" aria-describedby="<?= esc($name) ?>Help"><legend><?= esc($legend) ?></legend><div class="segment-options">
                        <?php foreach ($options as $value => $label): ?>
                            <label class="settings-option"><input type="radio" name="<?= esc($name) ?>" value="<?= esc($value) ?>"><span><?= esc($label) ?><b class="choice-check" aria-hidden="true">✓</b></span></label>
                        <?php endforeach; ?>
                    </div><p class="settings-help" id="<?= esc($name) ?>Help"><?= esc($help) ?></p></fieldset>
                <?php endforeach; ?>
            </section>
            <section class="settings-section" aria-labelledby="comfortTitle">
                <header class="settings-section-head"><span class="settings-section-icon" aria-hidden="true"><?= view('partials/icon', ['name' => 'users']) ?></span><div><h2 id="comfortTitle">Comfort and accessibility</h2><p>Adjust controls, movement, and contrast.</p></div></header>
                <div class="settings-switches">
                <?php foreach ([
                    'largeControls' => ['Larger buttons and fields', 'Make supported buttons and form fields easier to tap.'],
                    'reduceMotion' => ['Reduce animations', 'Limit transitions and motion effects.'],
                    'highContrast' => ['Higher contrast', 'Make secondary text and borders easier to distinguish.'],
                    'stickyHeaders' => ['Keep table headings visible', 'Keep column names in view in scrollable tables. Mobile card lists use their own labels.'],
                ] as $name => [$label, $description]): ?>
                    <label class="settings-switch"><span><strong id="<?= esc($name) ?>Label"><?= esc($label) ?></strong><small id="<?= esc($name) ?>Help"><?= esc($description) ?></small></span><span class="settings-toggle"><input type="checkbox" name="<?= esc($name) ?>" aria-labelledby="<?= esc($name) ?>Label" aria-describedby="<?= esc($name) ?>Help"><span aria-hidden="true"></span></span></label>
                <?php endforeach; ?>
                </div>
            </section>
            <div class="settings-reset"><div><strong>Start fresh</strong><p>Load the default choices into your preview. Save when you’re ready.</p></div><button type="button" class="btn btn-secondary" id="resetUiSettings">Use defaults</button></div>
        </form>
        <aside class="settings-preview" aria-label="Settings preview">
            <details id="settingsPreviewDetails" open>
                <summary><span><strong>Preview</strong><small id="settingsPreviewSummary">Your selected appearance</small></span><span class="preview-disclosure" aria-hidden="true">⌄</span></summary>
                <div class="settings-preview-body">
                    <div id="interfacePreview" class="interface-preview" aria-label="Sample inventory preview">
                        <div class="sample-topbar"><span class="sample-brand">P</span><strong>Pharxmaco</strong><span class="sample-tag">Sample</span></div>
                        <div class="sample-content"><span class="sample-caption">Inventory overview</span><h3>Ready for the day</h3><p>Clear information, at a glance.</p>
                            <div class="sample-metrics"><div><span>Products</span><strong>128</strong></div><div><span>Low stock</span><strong>4</strong></div></div>
                            <div class="sample-table-wrap" tabindex="0" aria-label="Sample stock table, scroll to preview headings"><table class="sample-table"><thead><tr><th>Product</th><th>Stock</th></tr></thead><tbody><tr><td>Paracetamol<span>500mg tablet</span></td><td>24</td></tr><tr><td>Vitamin C<span>500mg tablet</span></td><td>18</td></tr><tr><td>Cetirizine<span>10mg tablet</span></td><td>12</td></tr><tr><td>Oral rehydration salts</td><td>32</td></tr></tbody></table></div>
                            <div class="sample-button" aria-hidden="true">Add product <span>+</span></div>
                        </div>
                    </div>
                    <p class="settings-preview-note">Sample content only. Your workspace changes when you save.</p>
                    <ul class="preview-features" id="settingsPreviewFeatures" aria-label="Selected accessibility options"></ul>
                </div>
            </details>
        </aside>
    </div>
    <div class="settings-savebar">
        <div><p id="settingsStatus" role="status" aria-live="polite">No unsaved changes</p><p id="settingsFeedback" role="status" aria-live="polite" hidden></p><p id="settingsError" role="alert" hidden></p></div>
        <div class="settings-save-actions"><button type="button" class="btn btn-secondary" id="discardUiSettings" disabled>Discard changes</button><button type="submit" form="uiSettingsForm" class="btn btn-primary" id="saveUiSettings" disabled>Save preferences</button></div>
    </div>
</div>
<script src="<?= base_url('assets/js/interface-settings.js') ?>?v=20260924-1" defer></script>
<?= $this->endSection() ?>
