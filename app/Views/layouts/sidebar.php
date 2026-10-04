<?php
// Navigation order follows daily work. Route authorization remains in the filters.
$navigationGroups = [
    ['label' => 'Daily work', 'items' => [
        ['path' => 'dashboard', 'label' => 'Dashboard', 'access' => 'admin', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/> <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/> </svg>'],
        ['path' => 'cashier/sales', 'label' => 'Point of Sale', 'access' => 'staff', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/> <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/> </svg>'],
        ['path' => 'cashier/sales/history', 'label' => 'Sales History', 'access' => 'staff', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/> <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/> <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/> </svg>'],
    ]],
    ['label' => 'Inventory', 'items' => [
        ['path' => 'products', 'label' => 'Products', 'access' => 'staff', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/> </svg>'],
        ['path' => 'categories', 'label' => 'Categories', 'access' => 'admin', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path d="M4 6h16M4 10h16M4 14h10"/> </svg>'],
        ['path' => 'stock-logs', 'label' => 'Stock Logs', 'access' => 'staff', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path d="M5 8h14M5 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"/> <path d="M10 12h4"/> </svg>'],
        ['path' => 'admin/expiry-report', 'label' => 'Expiry Report', 'access' => 'staff', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <circle cx="12" cy="12" r="10"/> <polyline points="12 6 12 12 16 14"/> </svg>'],
        ['path' => 'admin/suppliers', 'label' => 'Suppliers', 'access' => 'admin', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <rect x="1" y="3" width="15" height="13" rx="1"/> <path d="M16 8h4l3 5v3h-7V8zM5.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM18.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/> </svg>'],
    ]],
    ['label' => 'Business', 'items' => [
        ['path' => 'admin/reports', 'label' => 'Reports', 'access' => 'admin', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/> <line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/> </svg>'],
        ['path' => 'admin/discounts', 'label' => 'Discounts', 'access' => 'admin', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path d="M9 14l6-6M9.5 9a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zM14.5 15a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/> <path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/> </svg>'],
    ]],
    ['label' => 'Administration', 'items' => [
        ['path' => 'admin/users', 'label' => 'Users', 'access' => 'admin', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/> <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/> </svg>'],
        ['path' => 'admin/branches', 'label' => 'Branches', 'access' => 'admin', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/> <polyline points="9 22 9 12 15 12 15 22"/> </svg>'],
        ['path' => 'admin/activity-logs', 'label' => 'Activity Logs', 'access' => 'admin', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/> <rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/> </svg>'],
    ]],
    ['label' => 'Preferences', 'items' => [
        ['path' => 'settings', 'label' => 'Interface Settings', 'access' => 'all', 'icon' => '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <circle cx="12" cy="12" r="3"/> <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21H9.6v-.1A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3V9.6h.1A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.3.37.5.7.6 1 .1.35.12.72.1 1.1v1.8c.02.38 0 .75-.1 1.1-.1.3-.3.63-.6 1z"/> </svg>'],
    ]],
];
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
        <a href="<?= site_url('logout') ?>" class="nav-item logout-item"><span class="nav-icon" aria-hidden="true"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/> <polyline points="16 17 21 12 16 7"/> <line x1="21" y1="12" x2="9" y2="12"/> </svg></span><span class="nav-text">Sign out</span></a>
    </div>
</aside>
