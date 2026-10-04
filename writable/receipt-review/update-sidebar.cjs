const fs = require('node:fs');
const file = 'app/Views/layouts/staff.php';
let source = fs.readFileSync(file, 'utf8');
const start = source.indexOf('    <aside class="sidebar"');
const end = source.indexOf('    </aside>', start) + '    </aside>'.length;
if (start < 0 || end < start) throw Error('Sidebar boundaries not found');
const old = source.slice(start, end);
const items = new Map();
for (const match of old.matchAll(/<a href="<\?= site_url\('([^']+)'\) \?>"[\s\S]*?<\/a>/g)) {
    const label = match[0].match(/<span class="nav-text">([\s\S]*?)<\/span>/)[1];
    const icon = match[0].match(/<svg[\s\S]*?<\/svg>/)[0].replace(/\s+/g, ' ');
    items.set(match[1], {label, icon});
}
const quote = s => "'" + s.replaceAll('\\', '\\\\').replaceAll("'", "\\'") + "'";
const groups = [
    ['Daily work', ['dashboard','cashier/sales','cashier/sales/history']],
    ['Inventory', ['products','categories','stock-logs','admin/expiry-report','admin/suppliers']],
    ['Business', ['admin/reports','admin/discounts','admin/sales-import']],
    ['Administration', ['admin/users','admin/branches','admin/activity-logs','admin/backup-restore']],
    ['Preferences', ['settings']],
];
const shared = ['cashier/sales','cashier/sales/history','products','stock-logs','admin/expiry-report'];
let view = `<?php
// Navigation order follows daily work. Route authorization remains in the filters.
$navigationGroups = [
`;
for (const [label, paths] of groups) {
    view += `    ['label' => ${quote(label)}, 'items' => [\n`;
    for (const route of paths) {
        const item = items.get(route); if (!item) throw Error('Missing route: ' + route);
        view += `        ['path' => ${quote(route)}, 'label' => ${quote(item.label.replace('&amp;', '&'))}, 'access' => '${route === 'settings' ? 'all' : shared.includes(route) ? 'staff' : 'admin'}', 'icon' => ${quote(item.icon)}],\n`;
    }
    view += '    ]],\n';
}
view += `];
$navigationPath = trim($currentUri, '/');
$navigationMatches = static fn (string $path): bool => $navigationPath === $path || str_starts_with($navigationPath, $path . '/');
$navigationActive = static function (string $path) use ($navigationMatches): bool {
    if ($path === 'cashier/sales') return $navigationMatches($path) && !$navigationMatches('cashier/sales/history');
    if ($path === 'cashier/sales/history') return $navigationMatches($path) || $navigationMatches('admin/sale-correction');
    return $navigationMatches($path);
};
?>
<aside class="sidebar" id="sidebar" aria-label="Main navigation" inert>
    <div class="brand">
        <div class="brand-icon"><img src="<?= base_url('assets/images/295259270_419609253521915_2551810649101629249_n.jpg') ?>" alt=""></div>
        <div class="brand-text"><div class="brand-name"><?= esc($layoutBusinessName) ?></div><div class="brand-sub"><?= esc($layoutBusinessType) ?></div></div>
    </div>
    <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close navigation" title="Close navigation">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
    </button>
    <div class="sidebar-context"><span class="sidebar-context-dot" aria-hidden="true"></span><span><?= esc($role === 'admin' ? 'All branches' : ($branchName ?: 'Your branch')) ?></span></div>
    <div class="sidebar-search">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
        <input id="sidebarSearch" type="search" placeholder="Find a page" aria-label="Find a navigation page" aria-controls="sidebarNavigation" autocomplete="off" spellcheck="false">
        <button id="sidebarSearchClear" type="button" aria-label="Clear navigation search" hidden>&times;</button>
    </div>
    <nav class="nav-section" id="sidebarNavigation" aria-label="Workspace pages">
        <?php foreach ($navigationGroups as $group): ?>
            <?php $visibleItems = array_filter($group['items'], static fn ($item) => $item['access'] === 'all' || $role === 'admin' || ($item['access'] === 'staff' && $role === 'cashier')); ?>
            <?php if (!$visibleItems) continue; ?>
            <section class="nav-group" data-nav-group>
                <h2 class="nav-label"><?= esc($group['label']) ?></h2>
                <?php foreach ($visibleItems as $item): ?>
                    <?php $active = $navigationActive($item['path']); ?>
                    <a href="<?= site_url($item['path']) ?>" class="nav-item<?= $active ? ' active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?> data-nav-link>
                        <span class="nav-icon" aria-hidden="true"><?= $item['icon'] ?></span><span class="nav-text"><?= esc($item['label']) ?></span>
                        <?php if ($active): ?><span class="nav-current-dot" aria-hidden="true"></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
        <div class="sidebar-empty" id="sidebarEmpty" hidden><strong>No matching pages</strong><span>Try sales, products, or settings.</span></div>
        <p class="sidebar-search-status" id="sidebarSearchStatus" role="status" aria-live="polite"></p>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-account"><span class="sidebar-avatar" aria-hidden="true"><?= esc($initials) ?></span><div><strong title="<?= esc($fullName ?: 'User', 'attr') ?>"><?= esc($fullName ?: 'User') ?></strong><span><?= esc($displayRole) ?></span></div></div>
        <a href="<?= site_url('logout') ?>" class="nav-item logout-item"><span class="nav-icon" aria-hidden="true">${items.get('logout').icon}</span><span class="nav-text">Sign out</span></a>
    </div>
</aside>
`;
fs.writeFileSync('app/Views/layouts/sidebar.php', view);
source = source.slice(0,start) + "    <?= view('layouts/sidebar', compact('role', 'fullName', 'branchName', 'displayRole', 'initials', 'currentUri', 'layoutBusinessName', 'layoutBusinessType')) ?>" + source.slice(end);
const link = '    <script src="<?= base_url(\'assets/js/workspace.js\') ?>?v=20260916" defer></script>';
if (!source.includes(link)) throw Error('Asset location missing');
source = source.replace(link, '    <link rel="stylesheet" href="<?= base_url(\'assets/css/sidebar.css\') ?>?v=20260921">\n' + link);
fs.writeFileSync(file, source);
