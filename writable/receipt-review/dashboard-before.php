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
?>

<style>
    .dash-page { min-width: 0; }
    .dash-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }
    .dash-header h1 {
        margin: 0;
        color: var(--text);
        font-size: clamp(22px, 2.4vw, 30px);
        line-height: 1.15;
        letter-spacing: -.55px;
    }
    .dash-header p {
        margin: 6px 0 0;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.55;
    }
    .dash-header-side {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 9px;
        flex-wrap: wrap;
    }
    .dash-date,
    .dash-scope {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 36px;
        padding: 7px 11px;
        border-radius: 9px;
        border: 1px solid var(--line);
        background: var(--surface);
        color: var(--muted);
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    .dash-scope {
        color: var(--primary);
        background: var(--primary-lighter);
        border-color: color-mix(in srgb, var(--primary) 24%, var(--line));
    }

    .dash-actions {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 18px;
    }
    .dash-action {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
        min-height: 62px;
        padding: 12px 14px;
        border: 1px solid var(--line);
        border-radius: var(--radius-lg);
        background: var(--surface);
        color: var(--text);
        text-decoration: none;
        box-shadow: var(--shadow);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .dash-action:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--primary) 45%, var(--line));
        box-shadow: var(--shadow-md);
    }
    .dash-action-icon {
        display: grid;
        place-items: center;
        flex: 0 0 38px;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--primary-lighter);
        color: var(--primary);
    }
    .dash-action strong {
        display: block;
        overflow: hidden;
        color: var(--text);
        font-size: 13px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dash-action span:not(.dash-action-icon) {
        display: block;
        margin-top: 2px;
        overflow: hidden;
        color: var(--muted);
        font-size: 11px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 13px;
        margin-bottom: 18px;
    }
    .kpi {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        min-height: 132px;
        overflow: hidden;
        padding: 17px 18px 15px;
        border: 1px solid var(--line);
        border-radius: var(--radius-lg);
        background: var(--surface);
        color: var(--text);
        text-decoration: none;
        box-shadow: var(--shadow);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .kpi:hover {
        transform: translateY(-1px);
        border-color: var(--line-2);
        box-shadow: var(--shadow-md);
    }
    .kpi::before {
        content: '';
        position: absolute;
        inset: 0 0 auto;
        height: 3px;
    }
    .kpi.blue::before { background: #3b82f6; }
    .kpi.green::before { background: #22c55e; }
    .kpi.amber::before { background: #f59e0b; }
    .kpi.red::before { background: #ef4444; }
    .kpi-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: auto;
    }
    .kpi-icon {
        display: grid;
        place-items: center;
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        border-radius: 10px;
        color: var(--primary);
        background: var(--primary-lighter);
    }
    .kpi.green .kpi-icon { color: var(--success-text); background: var(--success-bg); }
    .kpi.amber .kpi-icon { color: #a16207; background: var(--warning-bg); }
    .kpi.red .kpi-icon { color: var(--danger); background: var(--danger-bg); }
    .kpi-label {
        color: var(--muted);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .7px;
        line-height: 1.25;
        text-align: right;
        text-transform: uppercase;
    }
    .kpi-value {
        margin-top: 14px;
        overflow-wrap: anywhere;
        color: var(--text);
        font-size: clamp(24px, 3vw, 31px);
        font-weight: 850;
        letter-spacing: -.7px;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }
    .kpi-value.money { font-size: clamp(20px, 2.4vw, 27px); }
    .kpi.amber .kpi-value { color: #b45309; }
    .kpi.red .kpi-value { color: var(--danger); }
    .kpi-sub {
        margin-top: 7px;
        color: var(--muted);
        font-size: 11.5px;
        line-height: 1.35;
    }

    .dash-grid-2 {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .dash-span-2 { grid-column: 1 / -1; }
    .dash-card { min-width: 0; }
    .dash-card .card-head {
        gap: 12px;
        flex-wrap: wrap;
    }
    .dash-card .card-head h2 {
        margin: 0;
        color: var(--text);
        font-size: 15px;
        line-height: 1.35;
    }
    .dash-card .card-head p {
        margin: 3px 0 0;
        color: var(--muted);
        font-size: 11.5px;
        line-height: 1.45;
    }
    .dash-card-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 32px;
        padding: 6px 10px;
        border: 1px solid var(--line);
        border-radius: 8px;
        background: var(--surface-2);
        color: var(--muted);
        font-size: 11px;
        font-weight: 750;
        text-decoration: none;
        white-space: nowrap;
    }
    .dash-card-action:hover { color: var(--primary); border-color: var(--primary); }

    .chart-switch {
        display: inline-flex;
        max-width: 100%;
        overflow: hidden;
        border: 1px solid var(--line);
        border-radius: 9px;
        background: var(--surface-2);
    }
    .chart-tab {
        min-height: 34px;
        padding: 7px 13px;
        border: 0;
        border-right: 1px solid var(--line);
        background: transparent;
        color: var(--muted);
        cursor: pointer;
        font: inherit;
        font-size: 11.5px;
        font-weight: 750;
    }
    .chart-tab:last-child { border-right: 0; }
    .chart-tab.active { background: var(--surface); color: var(--primary); }
    .chart-panel { display: none; }
    .chart-panel.active { display: block; }
    .chart-body { padding: 4px 18px 18px; }
    .chart-wrap { position: relative; height: 280px; min-width: 0; }
    .chart-wrap.small { height: 290px; }
    .chart-empty {
        display: grid;
        place-items: center;
        min-height: 220px;
        padding: 24px;
        color: var(--muted);
        font-size: 13px;
        text-align: center;
    }
    .chart-empty[hidden] { display: none !important; }

    .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .dash-table { width: 100%; }
    .dash-table th { padding: 9px 13px; font-size: 10.5px; }
    .dash-table td { padding: 11px 13px; font-size: 13px; vertical-align: middle; }
    .dash-table .sub { margin-top: 2px; color: var(--muted); font-size: 11px; }
    .dash-table .amount {
        color: var(--text);
        font-weight: 750;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .dash-empty {
        padding: 24px !important;
        color: var(--muted);
        text-align: center !important;
    }
    .status-stack { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .text-danger-strong { color: var(--danger); font-weight: 800; }
    .text-warning-strong { color: #b45309; font-weight: 800; }

    .attention-list { display: grid; gap: 10px; padding: 16px; }
    .attention-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 13px 14px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: var(--surface-2);
        color: var(--text);
        text-decoration: none;
    }
    .attention-item:hover { border-color: var(--primary); }
    .attention-item strong { display: block; font-size: 13px; }
    .attention-item small { display: block; margin-top: 2px; color: var(--muted); font-size: 11px; }
    .attention-count {
        min-width: 38px;
        color: var(--text);
        font-size: 18px;
        font-weight: 850;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    @media (max-width: 1100px) {
        .dash-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 820px) {
        .dash-grid-2 { grid-template-columns: 1fr; }
        .dash-span-2 { grid-column: auto; }
        .dash-card .card-head { align-items: flex-start; }
    }
    @media (max-width: 640px) {
        .dash-header { margin-bottom: 14px; }
        .dash-header h1 { font-size: 22px; }
        .dash-header p { font-size: 12px; }
        .dash-header-side {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
            gap: 8px;
        }
        .dash-date,
        .dash-scope {
            width: 100%;
            min-width: 0;
            justify-content: center;
            padding-inline: 8px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dash-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .dash-action {
            min-height: 64px;
            padding: 10px 11px;
            gap: 9px;
        }
        .dash-action-icon {
            width: 34px;
            height: 34px;
            flex-basis: 34px;
        }
        .dash-action > span:not(.dash-action-icon) {
            display: block;
            min-width: 0;
        }
        .dash-action > span:not(.dash-action-icon) > span { display: none; }
        .dash-action strong {
            font-size: 12px;
            line-height: 1.25;
            white-space: normal;
            overflow-wrap: anywhere;
        }
        .kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 9px;
        }
        .kpi {
            min-height: 126px;
            padding: 12px 11px 11px;
        }
        .kpi-top {
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
        }
        .kpi-icon { width: 31px; height: 31px; flex-basis: 31px; }
        .kpi-label {
            min-width: 0;
            font-size: 9px;
            letter-spacing: .35px;
            line-height: 1.2;
            text-align: left;
            overflow-wrap: anywhere;
        }
        .kpi-value {
            margin-top: 11px;
            font-size: clamp(22px, 7vw, 27px);
        }
        .kpi-value.money { font-size: clamp(19px, 5.8vw, 24px); }
        .kpi-sub {
            margin-top: 6px;
            font-size: 10.5px;
            line-height: 1.3;
        }
        .chart-body { padding: 2px 10px 12px; }
        .chart-wrap { height: 245px; }
        .chart-wrap.small { height: 270px; }
        .chart-switch { width: 100%; }
        .chart-tab { flex: 1; }

        .mobile-card-table,
        .mobile-card-table tbody,
        .mobile-card-table tr,
        .mobile-card-table td { display: block; width: 100%; }
        .mobile-card-table thead { display: none; }
        .mobile-card-table tbody { padding: 8px 10px 10px; }
        .mobile-card-table tr {
            margin: 0 0 9px;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
        }
        .mobile-card-table tr:last-child { margin-bottom: 0; }
        .mobile-card-table td {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 9px 11px;
            border-bottom: 1px solid var(--line);
            text-align: right;
            white-space: normal !important;
            overflow-wrap: anywhere;
        }
        .mobile-card-table td:last-child { border-bottom: 0; }
        .mobile-card-table td::before {
            content: attr(data-label);
            flex: 0 0 38%;
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .45px;
            text-align: left;
            text-transform: uppercase;
        }
        .mobile-card-table td > * { max-width: 60%; }
        .mobile-card-table td.dash-empty {
            display: block;
            padding: 20px !important;
            text-align: center !important;
        }
        .mobile-card-table td.dash-empty::before { display: none; }
    }
    @media (max-width: 360px) {
        .dash-header-side,
        .dash-actions,
        .kpi-grid { grid-template-columns: 1fr; }
        .dash-action > span:not(.dash-action-icon) > span {
            display: block;
            margin-top: 2px;
            font-size: 10.5px;
            white-space: normal;
        }
        .kpi { min-height: 112px; }
    }
</style>

<div class="dash-page">
    <div class="dash-header">
        <div>
            <h1 id="dashboardGreeting" data-name="<?= esc($firstName ?: 'there', 'attr') ?>">Good <?= esc($greeting) ?>, <?= esc($firstName ?: 'there') ?></h1>
            <p>Business overview, sales activity, and inventory issues that need attention.</p>
        </div>
        <div class="dash-header-side">
            <span class="dash-scope">All active branches</span>
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

    <div class="kpi-grid">
        <a class="kpi green" href="<?= site_url('admin/reports') ?>">
            <div class="kpi-top"><span class="kpi-icon"><?= view('partials/icon', ['name' => 'wallet']) ?></span><span class="kpi-label">Today's Net Sales</span></div>
            <div class="kpi-value money">₱<?= number_format((float) $todayRevenue, 2) ?></div>
            <div class="kpi-sub">Yesterday: ₱<?= number_format((float) $yesterdayRevenue, 2) ?></div>
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
        </a>
        <a class="kpi amber" href="<?= site_url('products') ?>">
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
        </a>
        <a class="kpi blue" href="<?= site_url('products') ?>">
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
        </a>
    </div>

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
            <div class="chart-body">
                <div class="chart-panel active" id="panel30d"><div class="chart-wrap"><canvas id="chartDaily" aria-label="Daily sales trend"></canvas></div></div>
                <div class="chart-panel" id="panel12m"><div class="chart-wrap"><canvas id="chartMonthly" aria-label="Monthly sales trend"></canvas></div></div>
                <div class="chart-empty" id="chartLibraryError" hidden>Charts could not load. Refresh the page when the internet connection is stable.</div>
            </div>
        </section>
    </div>

    <div class="dash-grid-2">
        <section class="card dash-card">
            <div class="card-head">
                <div>
                    <h2>Top Products</h2>
                    <p>Best-performing products during the last 90 days.</p>
                </div>
                <div class="chart-switch" role="group" aria-label="Top product measure">
                    <button type="button" class="chart-tab active" id="tabTopRev" aria-pressed="true" onclick="switchTop('revenue')">Revenue</button>
                    <button type="button" class="chart-tab" id="tabTopUnits" aria-pressed="false" onclick="switchTop('units')">Units</button>
                </div>
            </div>
            <div class="chart-body">
                <?php if ($hasTopProducts): ?>
                    <div class="chart-wrap small"><canvas id="chartTopProducts" aria-label="Top products"></canvas></div>
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
                    <div class="chart-wrap small"><canvas id="chartBranch" aria-label="Sales by branch"></canvas></div>
                <?php else: ?>
                    <div class="chart-empty">No completed sales are available for the last 30 days.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>

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

    <div class="dash-grid-2">
        <section class="card dash-card">
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

        <section class="card dash-card">
            <div class="card-head"><div><h2>Inventory Attention</h2><p>Items that may require action from the owner.</p></div></div>
            <div class="attention-list">
                <a class="attention-item" href="<?= site_url('products') ?>">
                    <span><strong>Low or out of stock</strong><small>Review replenishment needs.</small></span>
                    <span class="attention-count"><?= number_format((int) $lowStockCount) ?></span>
                </a>
                <a class="attention-item" href="<?= site_url('admin/expiry-report?status=near') ?>">
                    <span><strong>Near-expiry products</strong><small>Expiring within the next 30 days.</small></span>
                    <span class="attention-count"><?= number_format((int) $nearExpiryCount) ?></span>
                </a>
                <a class="attention-item" href="<?= site_url('admin/expiry-report?status=expired') ?>">
                    <span><strong>Expired stock</strong><small>Remove or handle according to store policy.</small></span>
                    <span class="attention-count"><?= number_format((int) $expiredCount) ?></span>
                </a>
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
                                <td data-label="Expiry"><?= esc(date('M j, Y', strtotime($item['expiration_date']))) ?></td>
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
                                <td data-label="Expiry"><?= esc(date('M j, Y', strtotime($item['expiration_date']))) ?></td>
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
                            <td data-label="Date"><?= esc(date('M j, Y g:i A', strtotime($sale['sale_date']))) ?></td>
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
                            <td data-label="Time"><?= !empty($log['log_time']) ? esc(date('M j, Y g:i A', strtotime($log['log_time']))) : '—' ?></td>
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
        document.querySelectorAll('.chart-panel').forEach(panel => panel.hidden = true);
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

    const isDark = () => document.documentElement.classList.contains('dark');
    const gridColor = () => isDark() ? 'rgba(255,255,255,.07)' : 'rgba(15,23,42,.07)';
    const tickColor = () => isDark() ? '#93a4ba' : '#64748b';
    const labelColor = () => isDark() ? '#d3dce8' : '#24324a';
    const surfaceColor = () => isDark() ? '#161e2e' : '#ffffff';
    const primary = '#3b82f6';
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
        if (!canvas) return null;
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

    new MutationObserver(reThemeCharts).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
})();
</script>

<?= $this->endSection() ?>
