        <header class="topbar">
            <!-- Left: toggle + page context -->
            <div class="topbar-left">
                <button type="button" class="topbar-toggle" id="sidebarToggle" title="Open navigation" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <a class="topbar-brand" href="<?= site_url($role === 'admin' ? 'dashboard' : 'cashier/sales') ?>" aria-label="<?= esc($layoutFullName) ?> home">
                    <img src="<?= base_url('assets/images/295259270_419609253521915_2551810649101629249_n.jpg') ?>" alt="" width="34" height="34">
                    <span><strong><?= esc($layoutBusinessName) ?></strong><small>Pharmacy workspace</small></span>
                </a>
            </div>

            <!-- Center: page breadcrumb -->
            <div class="topbar-crumb">
                <div class="topbar-crumb-icon" id="topbarIcon">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </div>
                <div class="topbar-crumb-text">
                    <span class="topbar-crumb-label"><?= esc($role === 'admin' ? 'All branches' : ($branchName ?: 'Your branch')) ?></span>
                    <span class="topbar-crumb-title" id="topbarTitle">Workspace</span>
                </div>
            </div>

            <!-- Right: datetime + user -->
            <div class="topbar-right">
                <div class="topbar-datetime" id="topbarClock" title="Philippine time (Asia/Manila)">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span><span id="clockDate"></span><time id="clockDisplay">—</time></span>
                </div>

                <!-- Inventory notifications -->
                <div class="notification-wrap" id="notificationWrap">
                    <button type="button" class="notification-btn" id="notificationButton"
                        aria-label="Inventory notifications<?= $notificationData['total'] > 0 ? ': ' . (int) $notificationData['total'] . ' alerts' : '' ?>"
                        aria-haspopup="true" aria-expanded="false" aria-controls="notificationMenu" title="Inventory notifications">
                        <svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <?php if ($notificationData['total'] > 0): ?>
                            <span class="notification-badge" aria-hidden="true"><?= esc($notificationData['badge']) ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="notification-menu" id="notificationMenu" role="menu" aria-label="Inventory notifications">
                        <div class="notification-head">
                            <div>
                                <strong>Inventory Alerts</strong>
                                <span><?= esc($notificationData['scope_label']) ?> · updated when the page loads</span>
                            </div>
                            <?php if ($notificationData['total'] > 0): ?>
                                <span class="notification-total"><?= (int) $notificationData['total'] ?> alert<?= (int) $notificationData['total'] === 1 ? '' : 's' ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="notification-list">
                            <?php if (!empty($notificationData['items'])): ?>
                                <?php foreach ($notificationData['items'] as $alert): ?>
                                    <?php $opensLowStock = ($alert['action'] ?? '') === 'show_low_stock'; ?>
                                    <?php if ($opensLowStock): ?>
                                        <button type="button" class="notification-item" role="menuitem" data-open-low-stock aria-controls="lowStockModal">
                                    <?php else: ?>
                                        <a class="notification-item" href="<?= esc($alert['url']) ?>" role="menuitem">
                                    <?php endif; ?>
                                        <span class="notification-icon <?= esc($alert['type']) ?>">
                                            <?php if ($alert['type'] === 'danger'): ?>
                                                <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            <?php elseif ($alert['type'] === 'warning'): ?>
                                                <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                            <?php else: ?>
                                                <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            <?php endif; ?>
                                        </span>
                                        <span class="notification-copy">
                                            <strong><?= esc($alert['title']) ?></strong>
                                            <span><?= esc($alert['message']) ?></span>
                                        </span>
                                        <svg class="notification-arrow" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                    <?php if ($opensLowStock): ?>
                                        </button>
                                    <?php else: ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="notification-empty">
                                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                                    No urgent stock or expiry alerts.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Dark mode toggle -->
                <div class="topbar-theme">
                    <button type="button" class="theme-toggle" id="themeToggle" title="Toggle dark mode" aria-label="Toggle dark mode" aria-pressed="false">
                        <!-- Sun icon (shown in light mode) -->
                        <svg class="icon-sun" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="5"/>
                            <line x1="12" y1="1" x2="12" y2="3"/>
                            <line x1="12" y1="21" x2="12" y2="23"/>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                            <line x1="1" y1="12" x2="3" y2="12"/>
                            <line x1="21" y1="12" x2="23" y2="12"/>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                        <!-- Moon icon (shown in dark mode) -->
                        <svg class="icon-moon" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                    </button>
                </div>

                <div class="account-wrap" id="accountWrap">
                    <button type="button" class="topbar-user" id="accountButton" aria-expanded="false" aria-controls="accountPanel" aria-label="<?= esc('Account options for ' . ($fullName ?: 'User'), 'attr') ?>">
                        <span class="user-avatar" aria-hidden="true"><?= esc($initials) ?></span>
                        <span class="user-info"><span class="user-name"><?= esc($fullName ?: 'User') ?></span><span class="user-role"><?= esc($role === 'admin' ? 'Administrator' : $displayRole) ?></span></span>
                        <svg class="account-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="account-panel" id="accountPanel" hidden>
                        <div class="account-panel-head"><span>Signed in as</span><strong><?= esc($fullName ?: 'User') ?></strong><span><?= esc($displayRole) ?></span><span><?= esc($role === 'admin' ? 'All branches' : ($branchName ?: 'Your branch')) ?></span></div>
                        <nav aria-label="Account options">
                            <a href="<?= site_url('settings') ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="3"/><circle cx="15" cy="17" r="3"/></svg>Interface settings</a>
                            <a class="account-signout" href="<?= site_url('logout') ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 4H4v16h5M9 12h12m-5-5 5 5-5 5"/></svg>Sign out</a>
                        </nav>
                    </div>
                </div>
            </div>
        </header>
