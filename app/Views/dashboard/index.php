<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<?php
$philippineTimezone = new DateTimeZone('Asia/Manila');
$now = new DateTimeImmutable('now', $philippineTimezone);
$currentHour = (int) $now->format('G');
$greeting = $currentHour < 12 ? 'morning' : ($currentHour < 18 ? 'afternoon' : 'evening');
$firstName = explode(' ', trim((string) session('full_name')))[0] ?? 'there';
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$hasBranchSales = array_sum(array_map('floatval', $branchChartData ?? [])) > 0;
$hasTopProducts = !empty($topProductLabels);
$hasDailySales = array_sum($dailyTxns ?? []) > 0 || array_sum($dailyRevenue ?? []) != 0;
$hasMonthlySales = array_sum($monthlyTxns ?? []) > 0 || array_sum($monthlyRevenue ?? []) != 0;
?>

<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>?v=20260929-1">

<div class="dash-page">
    <div class="dash-header">
        <div>
            <span class="dash-eyebrow">Dashboard / Overview</span>
            <h1 id="dashboardGreeting" data-name="<?= esc($firstName ?: 'there', 'attr') ?>">Good <?= esc($greeting) ?>, <?= esc($firstName ?: 'there') ?></h1>
            <p>Today at your pharmacy. Review sales, check stock, and plan your next steps.</p>
        </div>
        <div class="dash-header-side">
            <span class="dash-scope">All branches</span>
            <time
                class="dash-date"
                id="dashboardPhilippineTime"
                datetime="<?= esc($now->format(DateTimeInterface::ATOM), 'attr') ?>"
                data-server-epoch-ms="<?= esc((string) ((int) $now->format('U') * 1000), 'attr') ?>"
                title="Philippine Standard Time (UTC+8)"
            >
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                </svg>
                <span><?= esc($now->format('F j, Y · g:i:s A')) ?> PHT</span>
            </time>
        </div>
    </div>

    <nav class="dash-actions" aria-label="Quick actions">
        <a class="dash-action" href="<?= site_url('cashier/sales') ?>">
            <span class="dash-action-icon"><?= view('partials/icon', ['name' => 'cart']) ?></span>
            <span><strong>Open POS</strong><span>Start a new transaction</span></span>
        </a>
        <a class="dash-action" href="<?= site_url('products/create') ?>">
            <span class="dash-action-icon"><?= view('partials/icon', ['name' => 'plus']) ?></span>
            <span><strong>Add Product</strong><span>Create inventory record</span></span>
        </a>
        <a class="dash-action" href="<?= site_url('admin/reports') ?>">
            <span class="dash-action-icon"><?= view('partials/icon', ['name' => 'chart']) ?></span>
            <span><strong>View Reports</strong><span>Sales and forecasting</span></span>
        </a>
        <a class="dash-action" href="<?= site_url('admin/expiry-report') ?>">
            <span class="dash-action-icon"><?= view('partials/icon', ['name' => 'clock']) ?></span>
            <span><strong>Expiry Report</strong><span>Review expiring stock</span></span>
        </a>
    </nav>

<nav class="dash-section-nav" aria-label="Dashboard sections"><a href="#dashOverview">Today</a><a href="#dashInventory">Inventory watch</a><a href="#dashTrends">Sales trends</a><a href="#dashBranches">Branches</a><a href="#dashActivity">Recent activity</a></nav>
    <div class="dash-section-heading" id="dashOverview"><h2>Today’s overview</h2><p>Sales totals reflect completed and partially refunded transactions.</p></div>
    <div class="kpi-grid">        <a class="kpi green dash-sales-kpi" href="<?= site_url('admin/reports') ?>">
            <div class="kpi-top"><span class="kpi-icon"><?= view('partials/icon', ['name' => 'wallet']) ?></span><span class="kpi-label">Today's Net Sales</span></div>
            <div class="kpi-value money">₱<?= number_format((float) $todayRevenue, 2) ?></div>
            <div class="kpi-sub">Yesterday (full day): ₱<?= number_format((float) $yesterdayRevenue, 2) ?></div>
        </a>
        <a class="kpi green" href="<?= site_url('cashier/sales/history') ?>">
            <div class="kpi-top"><span class="kpi-icon"><?= view('partials/icon', ['name' => 'receipt']) ?></span><span class="kpi-label">Today's Transactions</span></div>
            <div class="kpi-value"><?= number_format((int) $todayTransactions) ?></div>
            <div class="kpi-sub"><?= number_format((int) $totalSales) ?> completed or partially refunded sales overall</div>
        </a>
        <a class="kpi blue" href="<?= site_url('products') ?>">
            <div class="kpi-top"><span class="kpi-icon"><?= view('partials/icon', ['name' => 'box']) ?></span><span class="kpi-label">Active Products</span></div>
            <div class="kpi-value"><?= number_format((int) $totalProducts) ?></div>
            <div class="kpi-sub"><?= number_format((int) $totalBranchInventory) ?> branch inventory records</div>
        </a></div>
    <div class="dash-section-heading" id="dashInventory"><h2>Inventory watch</h2><p>Active branch inventory records. A product may appear in more than one category.</p></div>
    <div class="dash-watch-grid">        <a class="kpi amber" href="<?= site_url('products') ?>">
            <div class="kpi-top"><span class="kpi-icon"><?= view('partials/icon', ['name' => 'alert']) ?></span><span class="kpi-label">Low Stock</span></div>
            <div class="kpi-value"><?= number_format((int) $lowStockCount) ?></div>
            <div class="kpi-sub"><?= number_format((int) $outOfStockCount) ?> completely out of stock</div>
        </a>
        <a class="kpi amber" href="<?= site_url('admin/expiry-report?status=near') ?>">
            <div class="kpi-top"><span class="kpi-icon"><?= view('partials/icon', ['name' => 'clock']) ?></span><span class="kpi-label">Near Expiry</span></div>
            <div class="kpi-value"><?= number_format((int) $nearExpiryCount) ?></div>
            <div class="kpi-sub">In-stock items expiring within 30 days</div>
        </a>
        <a class="kpi red" href="<?= site_url('admin/expiry-report?status=expired') ?>">
            <div class="kpi-top"><span class="kpi-icon"><?= view('partials/icon', ['name' => 'alert']) ?></span><span class="kpi-label">Expired Stock</span></div>
            <div class="kpi-value"><?= number_format((int) $expiredCount) ?></div>
            <div class="kpi-sub">In-stock items that require immediate action</div>
        </a></div>
    <details class="dash-stock-details">
        <summary>
            <span class="dash-stock-heading"><strong>Review stock and expiry lists</strong><small>Up to 10 records per list, ordered by stock level or expiry date</small></span>
            <span class="dash-disclosure">
                <span class="dash-disclosure-show">Show lists</span>
                <span class="dash-disclosure-hide">Hide lists</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg>
            </span>
        </summary>
        <div class="dash-detail-body">    <div class="dash-grid-2">
        <section class="card dash-card dash-span-2">
            <div class="card-head">
                <div><h2>Low-Stock Products</h2><p>Lowest stock items at or below their reorder level.</p></div>
                <a class="dash-card-action" href="<?= site_url('products') ?>">View products →</a>
            </div>
            <div class="table-wrap">
                <table class="dash-table mobile-card-table">
                    <thead><tr><th>Product</th><th>Branch</th><th>Stock</th><th>Reorder Level</th></tr></thead>
                    <tbody>
                    <?php if (!empty($lowStockItems)): ?>
                        <?php foreach ($lowStockItems as $item): ?>
                            <tr>
                                <td data-label="Product"><div><strong><?= esc($item['product_name']) ?></strong><div class="sub"><?= esc($item['sku'] ?: 'No SKU') ?></div></div></td>
                                <td data-label="Branch"><?= esc($item['branch_name'] ?: '—') ?></td>
                                <td data-label="Stock"><span class="text-danger-strong"><?= number_format((int) $item['stock']) ?></span></td>
                                <td data-label="Reorder Level"><?= number_format((int) $item['reorder_level']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="dash-empty">All active inventory records are above their reorder level.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>

    <div class="dash-grid-2">
        <section class="card dash-card">
            <div class="card-head">
                <div><h2>Near-Expiry Products</h2><p>In-stock items expiring within 30 days.</p></div>
                <a class="dash-card-action" href="<?= site_url('admin/expiry-report?status=near') ?>">View report →</a>
            </div>
            <div class="table-wrap">
                <table class="dash-table mobile-card-table">
                    <thead><tr><th>Product</th><th>Branch</th><th>Expiry</th><th>Days Left</th></tr></thead>
                    <tbody>
                    <?php if (!empty($nearExpiryItems)): ?>
                        <?php foreach ($nearExpiryItems as $item): ?>
                            <?php $daysLeft = max(0, (int) floor((strtotime($item['expiration_date']) - strtotime($today)) / 86400)); ?>
                            <tr>
                                <td data-label="Product"><div><strong><?= esc($item['product_name']) ?></strong><div class="sub"><?= esc($item['sku'] ?: 'No SKU') ?></div></div></td>
                                <td data-label="Branch"><?= esc($item['branch_name'] ?: '—') ?></td>
                                <td data-label="Expiry"><?= esc(\App\Libraries\DisplayDate::date($item['expiration_date'])) ?></td>
                                <td data-label="Days Left"><span class="text-warning-strong"><?= number_format($daysLeft) ?> day<?= $daysLeft === 1 ? '' : 's' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="dash-empty">No active stock is nearing expiry.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card dash-card">
            <div class="card-head">
                <div><h2>Expired Products</h2><p>In-stock items already past their expiration date.</p></div>
                <a class="dash-card-action" href="<?= site_url('admin/expiry-report?status=expired') ?>">View report →</a>
            </div>
            <div class="table-wrap">
                <table class="dash-table mobile-card-table">
                    <thead><tr><th>Product</th><th>Branch</th><th>Expiry</th><th>Stock</th></tr></thead>
                    <tbody>
                    <?php if (!empty($expiredItems)): ?>
                        <?php foreach ($expiredItems as $item): ?>
                            <tr>
                                <td data-label="Product"><div><strong><?= esc($item['product_name']) ?></strong><div class="sub"><?= esc($item['sku'] ?: 'No SKU') ?></div></div></td>
                                <td data-label="Branch"><?= esc($item['branch_name'] ?: '—') ?></td>
                                <td data-label="Expiry"><?= esc(\App\Libraries\DisplayDate::date($item['expiration_date'])) ?></td>
                                <td data-label="Stock"><span class="text-danger-strong"><?= number_format((int) $item['stock']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="dash-empty">No expired stock requires action.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

</div></details>
    <div class="dash-section-heading" id="dashTrends"><h2>Sales performance</h2><p>See how sales are moving across the business.</p></div>
    <div class="dash-grid-2">
        <section class="card dash-card dash-span-2">
            <div class="card-head">
                <div>
                    <h2>Sales Trend</h2>
                    <p>Net sales after refunds, with transaction count for the same period.</p>
                </div>
                <div class="chart-switch" role="group" aria-label="Sales trend period">
                    <button type="button" class="chart-tab active" id="tab30d" aria-pressed="true" onclick="switchTrend('daily')">30 Days</button>
                    <button type="button" class="chart-tab" id="tab12m" aria-pressed="false" onclick="switchTrend('monthly')">12 Months</button>
                </div>
            </div>
            <div class="dash-trend-summary" aria-live="polite"><div><span id="dashTrendPeriod">Last 30 days · net sales</span><strong id="dashTrendRevenue">₱<?= number_format(array_sum($dailyRevenue), 2) ?></strong></div><div><span>Transactions</span><strong id="dashTrendTransactions"><?= number_format(array_sum($dailyTxns)) ?></strong></div></div>
            <div class="chart-body">
                <div class="chart-panel active" id="panel30d"><div class="chart-wrap"><canvas id="chartDaily" role="img" aria-label="Sales and transaction trend over the last 30 days" <?= !$hasDailySales ? 'hidden' : '' ?>></canvas><?php if (!$hasDailySales): ?><div class="chart-empty">No sales recorded in the last 30 days. Completed sales will appear here.</div><?php endif; ?></div></div>
                <div class="chart-panel" id="panel12m"><div class="chart-wrap"><canvas id="chartMonthly" role="img" aria-label="Sales and transaction trend over the last 12 months" <?= !$hasMonthlySales ? 'hidden' : '' ?>></canvas><?php if (!$hasMonthlySales): ?><div class="chart-empty">No sales recorded in the last 12 months. Completed sales will appear here.</div><?php endif; ?></div></div>
                <div class="chart-empty" id="chartLibraryError" hidden>Charts could not load. Refresh the page when the internet connection is stable.</div>
            </div>
        </section>
    </div>

    <div class="dash-grid-2">
        <section class="card dash-card">
            <div class="card-head">
                <div>
                    <h2>Top Products</h2>
                    <p>Top 10 by net revenue over 90 days. Switch to see their units sold.</p>
                </div>
                <div class="chart-switch" role="group" aria-label="Top product measure">
                    <button type="button" class="chart-tab active" id="tabTopRev" aria-pressed="true" onclick="switchTop('revenue')" <?= !$hasTopProducts ? 'disabled' : '' ?>>Revenue</button>
                    <button type="button" class="chart-tab" id="tabTopUnits" aria-pressed="false" onclick="switchTop('units')" <?= !$hasTopProducts ? 'disabled' : '' ?>>Units</button>
                </div>
            </div>
            <div class="chart-body">
                <?php if ($hasTopProducts): ?>
                    <div class="chart-wrap small"><canvas id="chartTopProducts" role="img" aria-label="Top products"></canvas></div>
                <?php else: ?>
                    <div class="chart-empty">No product sales are available for the last 90 days.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card dash-card">
            <div class="card-head">
                <div>
                    <h2>Net Sales by Branch</h2>
                    <p>Branch contribution during the last 30 days.</p>
                </div>
            </div>
            <div class="chart-body">
                <?php if ($hasBranchSales): ?>
                    <div class="chart-wrap small"><canvas id="chartBranch" role="img" aria-label="Sales by branch"></canvas></div>
                <?php else: ?>
                    <div class="chart-empty">No completed sales are available for the last 30 days.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>

<div class="dash-section-heading" id="dashBranches"><h2>Across your branches</h2><p>Compare the last 30 days of sales and current active inventory.</p></div><div class="dash-inventory-summary">        <a class="kpi blue" href="<?= site_url('products') ?>">
            <div class="kpi-top"><span class="kpi-icon"><?= view('partials/icon', ['name' => 'layers']) ?></span><span class="kpi-label">Stock Units</span></div>
            <div class="kpi-value"><?= number_format((int) $totalStock) ?></div>
            <div class="kpi-sub">Combined available units in active branches</div>
        </a>
        <a class="kpi blue" href="<?= site_url('admin/branches') ?>">
            <div class="kpi-top"><span class="kpi-icon"><?= view('partials/icon', ['name' => 'building']) ?></span><span class="kpi-label">Active Branches</span></div>
            <div class="kpi-value"><?= number_format((int) $totalBranches) ?></div>
            <div class="kpi-sub">Locations currently operating</div>
        </a>
        <a class="kpi blue" href="<?= site_url('admin/users') ?>">
            <div class="kpi-top"><span class="kpi-icon"><?= view('partials/icon', ['name' => 'users']) ?></span><span class="kpi-label">Active Users</span></div>
            <div class="kpi-value"><?= number_format((int) $totalUsers) ?></div>
            <div class="kpi-sub">Enabled administrator and cashier accounts</div>
        </a></div>
    <div class="dash-grid-2">
        <section class="card dash-card">
            <div class="card-head">
                <div><h2>Branch Sales</h2><p>Transactions and net sales during the last 30 days.</p></div>
                <a class="dash-card-action" href="<?= site_url('admin/reports') ?>">Open reports →</a>
            </div>
            <div class="table-wrap">
                <table class="dash-table mobile-card-table">
                    <thead><tr><th>Branch</th><th>Transactions</th><th>Net Sales</th></tr></thead>
                    <tbody>
                    <?php if (!empty($salesByBranch)): ?>
                        <?php foreach ($salesByBranch as $row): ?>
                            <tr>
                                <td data-label="Branch"><strong><?= esc($row['branch_name'] ?: '—') ?></strong></td>
                                <td data-label="Transactions"><?= number_format((int) $row['total_transactions']) ?></td>
                                <td data-label="Net Sales" class="amount">₱<?= number_format((float) $row['total_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="dash-empty">No active branches are available.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card dash-card">
            <div class="card-head">
                <div><h2>Branch Inventory</h2><p>Active product records and available stock.</p></div>
                <a class="dash-card-action" href="<?= site_url('products') ?>">Open products →</a>
            </div>
            <div class="table-wrap">
                <table class="dash-table mobile-card-table">
                    <thead><tr><th>Branch</th><th>Products</th><th>Stock Units</th></tr></thead>
                    <tbody>
                    <?php if (!empty($inventoryByBranch)): ?>
                        <?php foreach ($inventoryByBranch as $row): ?>
                            <tr>
                                <td data-label="Branch"><strong><?= esc($row['branch_name'] ?: '—') ?></strong></td>
                                <td data-label="Products"><?= number_format((int) $row['total_products']) ?></td>
                                <td data-label="Stock Units"><?= number_format((int) $row['total_stock']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="dash-empty">No active branch inventory is available.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

<div class="dash-section-heading" id="dashActivity"><h2>Recent activity</h2><p>Latest sales and recorded team actions.</p></div>
    <section class="card dash-card" style="margin-bottom:16px;">
        <div class="card-head">
            <div><h2>Recent Sales</h2><p>Latest completed and partially refunded transactions.</p></div>
            <a class="dash-card-action" href="<?= site_url('cashier/sales/history') ?>">View sales history →</a>
        </div>
        <div class="table-wrap">
            <table class="dash-table mobile-card-table">
                <thead><tr><th>Invoice</th><th>Branch</th><th>Cashier</th><th>Payment</th><th>Status</th><th>Net Amount</th><th>Date</th></tr></thead>
                <tbody>
                <?php if (!empty($recentSales)): ?>
                    <?php foreach ($recentSales as $sale): ?>
                        <?php $isPartial = ($sale['status'] ?? '') === 'partially_refunded'; ?>
                        <tr>
                            <td data-label="Invoice"><a href="<?= site_url('cashier/sales/view/' . (int) $sale['id']) ?>" style="font-family:monospace;font-weight:800;color:var(--primary);text-decoration:none;"><?= esc($sale['invoice_no']) ?></a></td>
                            <td data-label="Branch"><?= esc($sale['branch_name'] ?: '—') ?></td>
                            <td data-label="Cashier"><?= esc($sale['full_name'] ?: '—') ?></td>
                            <td data-label="Payment"><span class="badge badge-neutral"><?= esc(ucfirst((string) ($sale['payment_method'] ?? '—'))) ?></span></td>
                            <td data-label="Status"><span class="badge <?= $isPartial ? 'badge-warning' : 'badge-success' ?>"><?= $isPartial ? 'Partially Refunded' : 'Completed' ?></span></td>
                            <td data-label="Net Amount" class="amount">₱<?= number_format((float) ($sale['remaining_total'] ?? 0), 2) ?></td>
                            <td data-label="Date"><?= esc(\App\Libraries\DisplayDate::dateTime($sale['sale_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="dash-empty">No recent completed sales.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card dash-card">
        <div class="card-head">
            <div><h2>Recent Activity</h2><p>Latest recorded actions from system users.</p></div>
            <a class="dash-card-action" href="<?= site_url('admin/activity-logs') ?>">View all logs →</a>
        </div>
        <div class="table-wrap">
            <table class="dash-table mobile-card-table">
                <thead><tr><th>User</th><th>Activity</th><th>Time</th></tr></thead>
                <tbody>
                <?php if (!empty($recentLogs)): ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td data-label="User"><div><strong><?= esc($log['full_name'] ?? 'Unknown user') ?></strong><div class="sub"><?= esc($log['username'] ?? '—') ?></div></div></td>
                            <td data-label="Activity"><?= esc($log['activity'] ?? '—') ?></td>
                            <td data-label="Time"><?= !empty($log['log_time']) ? esc(\App\Libraries\DisplayDate::dateTime($log['log_time'])) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="dash-empty">No activity has been recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(() => {
    const greetingElement = document.getElementById('dashboardGreeting');
    const timeElement = document.getElementById('dashboardPhilippineTime');
    const timeText = timeElement?.querySelector('span');
    const serverEpochMs = Number(timeElement?.dataset.serverEpochMs || 0);
    const startedAt = performance.now();

    const philippineDateTime = new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });
    const philippineHour = new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        hour: 'numeric',
        hourCycle: 'h23'
    });

    const updatePhilippineClock = () => {
        if (!serverEpochMs) return;
        const current = new Date(serverEpochMs + (performance.now() - startedAt));
        const hour = Number(philippineHour.format(current));
        const greeting = hour < 12 ? 'morning' : (hour < 18 ? 'afternoon' : 'evening');

        if (timeText) timeText.textContent = philippineDateTime.format(current) + ' PHT';
        if (timeElement) timeElement.dateTime = current.toISOString();
        if (greetingElement) {
            const name = greetingElement.dataset.name || 'there';
            greetingElement.textContent = `Good ${greeting}, ${name}`;
        }
    };

    updatePhilippineClock();
    window.setInterval(updatePhilippineClock, 1000);

    const chartError = document.getElementById('chartLibraryError');
    if (typeof window.Chart === 'undefined') {
        document.querySelectorAll('.dash-page .chart-tab').forEach(button => button.disabled = true);
        document.querySelectorAll('.dash-page canvas').forEach(canvas => {
            if (canvas.hidden) return;
            canvas.hidden = true;
            const message = document.createElement('div');
            message.className = 'chart-empty';
            message.textContent = 'Chart unavailable. You can still review the totals and tables below.';
            canvas.parentElement.appendChild(message);
        });
        if (chartError) chartError.hidden = false;
        return;
    }
    if (chartError) chartError.hidden = true;

    const daily = {
        labels: <?= json_encode($dailyLabels, $jsonFlags) ?>,
        revenue: <?= json_encode($dailyRevenue, $jsonFlags) ?>,
        txns: <?= json_encode($dailyTxns, $jsonFlags) ?>
    };
    const monthly = {
        labels: <?= json_encode($monthlyLabels, $jsonFlags) ?>,
        revenue: <?= json_encode($monthlyRevenue, $jsonFlags) ?>,
        txns: <?= json_encode($monthlyTxns, $jsonFlags) ?>
    };
    const topProducts = {
        labels: <?= json_encode($topProductLabels, $jsonFlags) ?>,
        revenue: <?= json_encode($topProductRevenue, $jsonFlags) ?>,
        units: <?= json_encode($topProductUnits, $jsonFlags) ?>
    };
    const branch = {
        labels: <?= json_encode($branchChartLabels, $jsonFlags) ?>,
        data: <?= json_encode($branchChartData, $jsonFlags) ?>
    };

    const reducedMotion = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('reduce-motion');
    const themeValue = name => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    const isDark = () => document.documentElement.classList.contains('dark');
    const gridColor = () => isDark() ? 'rgba(255,255,255,.07)' : 'rgba(15,23,42,.07)';
    const tickColor = () => themeValue('--muted');
    const labelColor = () => themeValue('--text');
    const surfaceColor = () => themeValue('--surface');
    const primary = themeValue('--primary');
    const success = '#22c55e';
    const palette = ['#3b82f6','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#84cc16','#ec4899','#14b8a6'];
    const charts = [];

    const money = value => '₱' + Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    const compactMoney = value => {
        const n = Number(value || 0);
        return '₱' + (Math.abs(n) >= 1000 ? (n / 1000).toFixed(1) + 'k' : n.toLocaleString('en-PH'));
    };
    const shortLabel = value => {
        const text = String(value ?? '');
        const max = window.innerWidth <= 640 ? 18 : 30;
        return text.length > max ? text.slice(0, max - 1) + '…' : text;
    };

    const trendOptions = () => ({
        animation: reducedMotion() ? false : {duration: 250},
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { labels: { color: labelColor(), boxWidth: 12, padding: 15, font: { size: 11 } } },
            tooltip: {
                backgroundColor: surfaceColor(),
                borderColor: gridColor(),
                borderWidth: 1,
                titleColor: labelColor(),
                bodyColor: tickColor(),
                callbacks: {
                    label: context => context.dataset.yAxisID === 'y2'
                        ? ' Transactions: ' + context.parsed.y
                        : ' Net sales: ' + money(context.parsed.y)
                }
            }
        },
        scales: {
            x: { grid: { color: gridColor() }, ticks: { color: tickColor(), maxTicksLimit: window.innerWidth <= 640 ? 6 : 10, font: { size: 10 } } },
            y: { position: 'left', beginAtZero: true, grid: { color: gridColor() }, ticks: { color: tickColor(), callback: compactMoney, font: { size: 10 } } },
            y2: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { color: tickColor(), precision: 0, font: { size: 10 } } }
        }
    });

    const makeTrendChart = (id, labels, revenue, txns) => {
        const canvas = document.getElementById(id);
        if (!canvas || canvas.hidden) return null;
        const chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Net Sales', data: revenue, yAxisID: 'y', borderColor: primary,
                        backgroundColor: 'rgba(59,130,246,.10)', fill: true, tension: .35,
                        pointRadius: 2, pointHoverRadius: 5, borderWidth: 2
                    },
                    {
                        label: 'Transactions', data: txns, yAxisID: 'y2', borderColor: success,
                        backgroundColor: 'transparent', fill: false, tension: .35,
                        pointRadius: 2, pointHoverRadius: 5, borderWidth: 2, borderDash: [5, 4]
                    }
                ]
            },
            options: trendOptions()
        });
        charts.push(chart);
        return chart;
    };

    const chartDaily = makeTrendChart('chartDaily', daily.labels, daily.revenue, daily.txns);
    const chartMonthly = makeTrendChart('chartMonthly', monthly.labels, monthly.revenue, monthly.txns);

    const topCanvas = document.getElementById('chartTopProducts');
    let topChart = null;
    if (topCanvas) {
        topChart = new Chart(topCanvas, {
            type: 'bar',
            data: {
                labels: topProducts.labels,
                datasets: [{
                    label: 'Net Revenue',
                    data: topProducts.revenue,
                    backgroundColor: palette.map(color => color + 'cc'),
                    borderColor: palette,
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                animation: reducedMotion() ? false : {duration: 250},
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: surfaceColor(),
                        borderColor: gridColor(),
                        borderWidth: 1,
                        titleColor: labelColor(),
                        bodyColor: tickColor(),
                        callbacks: { label: context => topChart?.data.datasets[0].label === 'Units Sold' ? ' ' + context.parsed.x + ' units' : ' ' + money(context.parsed.x) }
                    }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: gridColor() }, ticks: { color: tickColor(), callback: compactMoney, font: { size: 10 } } },
                    y: { grid: { display: false }, ticks: { color: tickColor(), callback: (_, index) => shortLabel(topProducts.labels[index]), font: { size: 10 } } }
                }
            }
        });
        charts.push(topChart);
    }

    const branchCanvas = document.getElementById('chartBranch');
    if (branchCanvas) {
        const branchChart = new Chart(branchCanvas, {
            type: 'doughnut',
            data: {
                labels: branch.labels,
                datasets: [{ data: branch.data, backgroundColor: palette.map(color => color + 'cc'), borderColor: surfaceColor(), borderWidth: 3, hoverOffset: 5 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                animation: reducedMotion() ? false : {duration: 250},
                plugins: {
                    legend: { position: 'bottom', labels: { color: labelColor(), boxWidth: 11, padding: 12, font: { size: 10 } } },
                    tooltip: {
                        backgroundColor: surfaceColor(), borderColor: gridColor(), borderWidth: 1,
                        titleColor: labelColor(), bodyColor: tickColor(), callbacks: { label: context => ' ' + money(context.parsed) }
                    }
                }
            }
        });
        charts.push(branchChart);
    }

    window.switchTrend = mode => {
        const dailyMode = mode === 'daily';
        const series = dailyMode ? daily : monthly;
        document.getElementById('dashTrendPeriod').textContent = dailyMode ? 'Last 30 days · net sales' : 'Last 12 months · net sales';
        document.getElementById('dashTrendRevenue').textContent = money(series.revenue.reduce((total,value) => total + Number(value), 0));
        document.getElementById('dashTrendTransactions').textContent = series.txns.reduce((total,value) => total + Number(value), 0).toLocaleString('en-PH');
        document.getElementById('panel30d')?.classList.toggle('active', dailyMode);
        document.getElementById('panel12m')?.classList.toggle('active', !dailyMode);
        document.getElementById('tab30d')?.classList.toggle('active', dailyMode);
        document.getElementById('tab12m')?.classList.toggle('active', !dailyMode);
        document.getElementById('tab30d')?.setAttribute('aria-pressed', String(dailyMode));
        document.getElementById('tab12m')?.setAttribute('aria-pressed', String(!dailyMode));
        requestAnimationFrame(() => (dailyMode ? chartDaily : chartMonthly)?.resize());
    };

    window.switchTop = mode => {
        if (!topChart) return;
        const revenueMode = mode === 'revenue';
        topChart.data.datasets[0].data = revenueMode ? topProducts.revenue : topProducts.units;
        topChart.data.datasets[0].label = revenueMode ? 'Net Revenue' : 'Units Sold';
        topChart.options.scales.x.ticks.callback = revenueMode ? compactMoney : value => Number(value).toLocaleString('en-PH');
        topChart.update();
        document.getElementById('tabTopRev')?.classList.toggle('active', revenueMode);
        document.getElementById('tabTopUnits')?.classList.toggle('active', !revenueMode);
        document.getElementById('tabTopRev')?.setAttribute('aria-pressed', String(revenueMode));
        document.getElementById('tabTopUnits')?.setAttribute('aria-pressed', String(!revenueMode));
    };

    const reThemeCharts = () => {
        charts.forEach(chart => {
            if (!chart) return;
            chart.options.animation = reducedMotion() ? false : {duration: 250};
            if (chart.options.plugins?.tooltip) Object.assign(chart.options.plugins.tooltip, {
                backgroundColor: surfaceColor(), borderColor: gridColor(), titleColor: labelColor(), bodyColor: tickColor()
            });
            if (chart.config.type === 'line') chart.data.datasets[0].borderColor = themeValue('--primary');
            if (chart.options.scales) {
                Object.values(chart.options.scales).forEach(axis => {
                    if (axis.grid) axis.grid.color = axis.grid.drawOnChartArea === false ? undefined : gridColor();
                    if (axis.ticks) axis.ticks.color = tickColor();
                });
            }
            if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = labelColor();
            if (chart.data.datasets?.[0] && chart.config.type === 'doughnut') chart.data.datasets[0].borderColor = surfaceColor();
            chart.update('none');
        });
    };

    new MutationObserver(reThemeCharts).observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-accent'] });
})();
</script>

<?= $this->endSection() ?>
