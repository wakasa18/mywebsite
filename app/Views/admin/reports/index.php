<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<style>
    /* ── Page header ── */
    .rp-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }
    .rp-contexts {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }
    .rp-context {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: var(--primary-light);
        border: 1px solid rgba(43,127,255,.2);
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        color: var(--primary);
        white-space: nowrap;
        margin-top: 4px;
    }
    .rp-context.forecast {
        color: #7c3aed;
        background: rgba(124,58,237,.09);
        border-color: rgba(124,58,237,.2);
    }

    /* ── Filter toolbar ── */
    .rp-filters { margin-bottom: 20px; }
    .rp-shared-filter {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .rp-shared-filter .field { margin-bottom: 0; min-width: 220px; }
    .rp-filter-groups {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }
    .rp-filter-group {
        padding: 15px;
        border: 1.5px solid var(--line);
        border-radius: var(--radius);
        background: var(--surface-2);
    }
    .rp-filter-group.forecast {
        border-color: rgba(124,58,237,.22);
        background: rgba(124,58,237,.045);
    }
    .rp-filter-group-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    }
    .rp-filter-group-title strong {
        display: block;
        color: var(--text);
        font-size: 13px;
    }
    .rp-filter-group-title span {
        display: block;
        color: var(--muted);
        font-size: 11px;
        margin-top: 2px;
    }
    .rp-filter-fields {
        display: grid;
        grid-template-columns: minmax(120px, .8fr) repeat(2, minmax(0, 1fr));
        gap: 10px;
    }
    .rp-filter-fields .field { margin-bottom: 0; min-width: 0; }
    @media (max-width: 900px) {
        .rp-filter-groups { grid-template-columns: 1fr; }
    }
    @media (max-width: 620px) {
        .rp-filter-fields { grid-template-columns: 1fr; }
        .rp-shared-filter .field { min-width: 100%; }
    }
    .rp-actions-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1.5px solid var(--line);
    }
    .btn svg { flex-shrink: 0; }

    /* ── KPI grid (mirrors dashboard) ── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 14px;
        margin-bottom: 22px;
    }
    .kpi {
        background: var(--surface);
        border: 1.5px solid var(--line);
        border-radius: var(--radius-lg);
        padding: 16px 18px 14px;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
        transition: border-color .15s, box-shadow .15s;
    }
    .kpi:hover { border-color: var(--line-2); box-shadow: var(--shadow-md); }
    .kpi::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    }
    .kpi.accent-blue::before   { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .kpi.accent-green::before  { background: linear-gradient(90deg, #16a34a, #4ade80); }
    .kpi.accent-amber::before  { background: linear-gradient(90deg, #d97706, #fbbf24); }
    .kpi.accent-purple::before { background: linear-gradient(90deg, #7c3aed, #a78bfa); }
    .kpi-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .kpi-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
    .kpi.accent-blue   .kpi-icon { background: var(--primary-lighter); }
    .kpi.accent-green  .kpi-icon { background: var(--success-bg); }
    .kpi.accent-amber  .kpi-icon { background: var(--warning-bg); }
    .kpi.accent-purple .kpi-icon { background: rgba(124,58,237,0.12); }
    .kpi-label { font-size: 10.5px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: var(--muted); text-align: right; line-height: 1.3; }
    .kpi-value { font-size: 26px; font-weight: 800; letter-spacing: -.6px; color: var(--text); line-height: 1; }
    .kpi-value.sm { font-size: 18px; letter-spacing: -.3px; }
    .kpi-sub { font-size: 11.5px; color: var(--muted); margin-top: 5px; font-weight: 500; }

    /* ── Two-column section layout (mirrors dashboard) ── */
    .dash-two-col { display: grid; grid-template-columns: var(--grid-2col); gap: 16px; margin-bottom: 16px; }
    @media (max-width: 860px) { .dash-two-col { grid-template-columns: 1fr; } }

    .card-head-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .3px;
        white-space: nowrap;
    }
    .tag-blue   { background: var(--primary-light); color: var(--primary); }
    .tag-purple { background: rgba(124,58,237,0.12); color: #8b5cf6; }
    .tag-gray   { background: var(--surface-2); color: var(--muted); border: 1px solid var(--line); }

    /* ── Chart tabs (mirrors dashboard) ── */
    .chart-tabs { display: flex; gap: 0; background: var(--surface-2); border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
    .chart-tab { padding: 7px 14px; font-size: 12.5px; font-weight: 600; color: var(--muted); cursor: pointer; background: none; border: none; transition: color .15s, background .15s; }
    .chart-tab.active { color: #fff; background: var(--primary); }
    .chart-wrap { position: relative; height: 280px; }

    .amount { font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums; }

    .table-scroll { max-height: 360px; overflow-y: auto; }

    /* ── Restock suggestions popup ── */
    .forecast-modal-trigger {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 11px 18px; margin-bottom: 16px;
        background: var(--surface); border: 1.5px solid var(--line-2); border-radius: var(--radius);
        font-size: 13px; font-weight: 700; color: var(--text-2); cursor: pointer;
        transition: all .15s; font-family: inherit;
    }
    .forecast-modal-trigger:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary); }
    .forecast-modal-trigger .badge { margin-left: 2px; }

    .forecast-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(13,27,46,0.5);
        z-index: 500; align-items: flex-start; justify-content: center;
        padding: 40px 20px; overflow-y: auto; backdrop-filter: blur(2px);
    }
    .forecast-modal-overlay.show { display: flex; }
    .forecast-modal {
        background: var(--bg); border-radius: var(--radius-lg); box-shadow: var(--shadow-md);
        width: 100%; max-width: 980px; max-height: calc(100vh - 80px);
        display: flex; flex-direction: column; overflow: hidden;
    }
    .forecast-modal-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1.5px solid var(--line); background: var(--surface); flex-shrink: 0;
    }
    .forecast-modal-head h2 { margin: 0; font-size: 17px; }
    .forecast-modal-close {
        width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid var(--line-2);
        background: var(--surface); color: var(--muted); font-size: 15px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; transition: all .15s; flex-shrink: 0;
    }
    .forecast-modal-close:hover { background: var(--danger-bg); border-color: #fecaca; color: var(--danger); }
    .forecast-modal-body { padding: 20px; overflow-y: auto; }

    .rp-notices {
        margin: -6px 0 18px;
        padding: 12px 14px;
        border: 1px solid rgba(217,119,6,.28);
        border-radius: var(--radius);
        background: var(--warning-bg);
        color: var(--text-2);
        font-size: 12.5px;
        line-height: 1.5;
    }
    .rp-notices strong { color: var(--text); }
    .rp-notices ul { margin: 6px 0 0 18px; padding: 0; }
    .rp-filter-help { margin-top: 10px; font-size: 11.5px; color: var(--muted); line-height: 1.45; }
    .forecast-note {
        margin: 0 20px 16px;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid var(--line);
        background: var(--surface-2);
        color: var(--muted);
        font-size: 12px;
        line-height: 1.5;
    }
    .status-cell { display: inline-flex; align-items: center; gap: 6px; }
    @media (max-width: 620px) {
        .rp-actions-row > .row { width: 100%; }
        .rp-actions-row > .row .btn { flex: 1; justify-content: center; }
    }
</style>
<link rel="stylesheet" href="<?= base_url('assets/css/reports.css') ?>?v=20260927-2">

<?php
$forecastLabelText = 'Next Day';
if (($forecastType ?? 'daily') === 'weekly') $forecastLabelText = 'Next Week';
if (($forecastType ?? 'daily') === 'monthly') $forecastLabelText = 'Next Month';

$today = date('Y-m-d');
$reorderSuggestionCount = count($reorderForecast ?? []);
$reorderCriticalCount = 0;
foreach (($reorderForecast ?? []) as $reorderRow) {
    if (($reorderRow['urgency'] ?? '') === 'critical') {
        $reorderCriticalCount++;
    }
}

$exportQuery = 'branch_id=' . urlencode($branchId ?? '')
    . '&report_type=' . urlencode($reportType ?? 'daily')
    . '&report_date_from=' . urlencode($reportDateFrom)
    . '&report_date_to=' . urlencode($reportDateTo)
    . '&forecast_date_from=' . urlencode($forecastDateFrom)
    . '&forecast_date_to=' . urlencode($forecastDateTo)
    . '&forecast_type=' . urlencode($forecastType ?? 'daily');
?>

<!-- ── Header ── -->
<div class="reports-workspace">
<div class="rp-header">
    <div>
        <h1>Reports</h1>
        <p class="muted" style="font-size:13px; margin-top:4px;">Review sales, profit, best-selling products, and future sales estimates.</p>
    </div>
    <div class="rp-contexts">
        <span class="rp-context">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Reports: <?= esc(\App\Libraries\DisplayDate::date($reportDateFrom)) ?> – <?= esc(\App\Libraries\DisplayDate::date($reportDateTo)) ?>
        </span>
        <span class="rp-context forecast">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>
            </svg>
            Forecast: <?= esc(\App\Libraries\DisplayDate::date($forecastDateFrom)) ?> – <?= esc(\App\Libraries\DisplayDate::date($forecastDateTo)) ?>
        </span>
        <span class="rp-context"><?= esc($branchName) ?></span>
    </div>
</div>

<nav class="rp-section-nav" aria-label="Report sections">
    <a href="#reportFilters">Filters &amp; exports</a>
    <a href="#reportSummary">Overview</a>
    <a href="#reportTrends">Sales trends</a>
    <a href="#reportForecast">Forecast</a>
    <a href="#reportActivity">Sales details</a>
    <a href="#reportExportHistory">Export history</a>
</nav>

<?php if (!empty($filterNotices)): ?>
    <div class="rp-notices" role="status">
        <strong>Some filter values were adjusted:</strong>
        <ul>
            <?php foreach ($filterNotices as $notice): ?>
                <li><?= esc($notice) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- ── Filters ── -->
<div class="card rp-filters" id="reportFilters">
    <div class="card-body">
        <form method="get" action="<?= site_url('admin/reports') ?>" id="salesReportFilters">

            <div class="rp-shared-filter">
                <div class="field">
                    <label class="label" for="reportBranch">Branch</label>
                    <select name="branch_id" id="reportBranch" class="input">
                        <option value="">All Branches</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= esc($b['id']) ?>" <?= ($branchId == $b['id']) ? 'selected' : '' ?>>
                                <?= esc($b['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <input type="hidden" name="forecast_type" value="<?= esc($forecastType ?? 'daily') ?>">
            <input type="hidden" name="forecast_date_from" value="<?= esc($forecastDateFrom) ?>">
            <input type="hidden" name="forecast_date_to" value="<?= esc($forecastDateTo) ?>">

            <section class="rp-filter-group" aria-labelledby="standardReportsFilterTitle">
                <div class="rp-filter-group-title">
                    <div>
                        <strong id="standardReportsFilterTitle">Sales Report Filters</strong>
                        <span>Choose the branch, date range, and how the report should be grouped.</span>
                    </div>
                    <span class="card-head-tag tag-blue">Reports</span>
                </div>
                <div class="rp-filter-fields">
                    <div class="field">
                        <label class="label" for="reportGrouping">Group sales by</label>
                        <select name="report_type" id="reportGrouping" class="input">
                            <option value="daily" <?= ($reportType ?? 'daily') === 'daily' ? 'selected' : '' ?>>Daily</option>
                            <option value="weekly" <?= ($reportType ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($reportType ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        </select>
                    </div>
                    <div class="field">
                        <label class="label" for="reportDateFrom">Start date</label>
                        <input type="date" id="reportDateFrom" name="report_date_from" class="input" value="<?= esc($reportDateFrom) ?>" max="<?= esc($today) ?>" required>
                    </div>
                    <div class="field">
                        <label class="label" for="reportDateTo">End date</label>
                        <input type="date" id="reportDateTo" name="report_date_to" class="input" value="<?= esc($reportDateTo) ?>" max="<?= esc($today) ?>" required>
                    </div>
                </div>
                <div class="rp-date-presets" aria-label="Quick report date ranges" data-today="<?= esc($today) ?>">
                    <button type="button" data-report-range="today" aria-pressed="false">Today</button>
                    <button type="button" data-report-range="7" aria-pressed="false">Last 7 days</button>
                    <button type="button" data-report-range="30" aria-pressed="false">Last 30 days</button>
                    <button type="button" data-report-range="month" aria-pressed="false">This month</button>
                </div>
                <p class="rp-filter-help">These dates are used for sales totals, profit, product rankings, and transaction lists.</p>
            </section>

            <div class="rp-actions-row">
                <div class="rp-submit-actions">
                <button class="btn btn-primary" type="submit">Apply report filters</button>
                <a href="<?= site_url('admin/reports') ?>" class="btn btn-secondary">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 1 0 2.64-6.36"/><polyline points="3 3 3 9 9 9"/>
                    </svg>
                    Reset
                </a>
                </div>

                <div class="row" style="gap:8px;">
                    <a href="<?= site_url('admin/expiry-report') ?>" class="btn btn-secondary">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Expiry Report
                    </a>
                    <a href="<?= site_url('admin/reports/export-excel?' . $exportQuery) ?>" class="btn btn-secondary" data-report-export>
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Excel CSV
                    </a>
                    <a href="<?= site_url('admin/reports/pdf?' . $exportQuery) ?>" class="btn btn-secondary" data-report-export>
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        PDF
                    </a>
                </div>
            </div>
            <p class="rp-export-note">Exports use the applied report and forecast dates shown above.</p>
            <p class="rp-pending-filters" id="reportPendingFilters" role="status" hidden>Filters changed. Apply your report or forecast changes before exporting to include them.</p>
        </form>
    </div>
</div>

<!-- ── KPI Grid ── -->
<div class="rp-section-heading" id="reportSummary"><h2>Performance overview</h2><p>Results for the applied sales period</p></div>
<div class="kpi-grid">

    <div class="kpi accent-blue">
        <div class="kpi-top">
            <div class="kpi-icon"><?= view('partials/icon', ['name' => 'receipt']) ?></div>
            <div class="kpi-label">Gross Sales</div>
        </div>
        <div class="kpi-value sm">₱<?= number_format((float) ($salesSummary['gross_sales'] ?? 0), 2) ?></div>
        <div class="kpi-sub">Sales before discounts, after refunds</div>
    </div>

    <div class="kpi accent-blue">
        <div class="kpi-top">
            <div class="kpi-icon"><?= view('partials/icon', ['name' => 'wallet']) ?></div>
            <div class="kpi-label">Net Sales</div>
        </div>
        <div class="kpi-value sm">₱<?= number_format((float) ($salesSummary['net_sales'] ?? 0), 2) ?></div>
        <div class="kpi-sub">Actual sales after discounts and refunds</div>
    </div>

    <div class="kpi accent-amber">
        <div class="kpi-top">
            <div class="kpi-icon"><?= view('partials/icon', ['name' => 'box']) ?></div>
            <div class="kpi-label">Product Cost</div>
        </div>
        <div class="kpi-value sm">₱<?= number_format((float) ($profitSummary['cogs'] ?? 0), 2) ?></div>
        <div class="kpi-sub">Cost of the products that were sold</div>
    </div>

    <div class="kpi accent-green">
        <div class="kpi-top">
            <div class="kpi-icon"><?= view('partials/icon', ['name' => 'chart']) ?></div>
            <div class="kpi-label">Gross Profit</div>
        </div>
        <div class="kpi-value sm">₱<?= number_format((float) ($profitSummary['gross_profit'] ?? 0), 2) ?></div>
        <div class="kpi-sub"><?= number_format((float) ($grossProfitMargin ?? 0), 1) ?>% of net sales</div>
    </div>

    <div class="kpi accent-blue">
        <div class="kpi-top">
            <div class="kpi-icon"><?= view('partials/icon', ['name' => 'cart']) ?></div>
            <div class="kpi-label">Transactions</div>
        </div>
        <div class="kpi-value"><?= esc($salesSummary['total_transactions'] ?? 0) ?></div>
        <div class="kpi-sub">Completed sales in the selected dates</div>
    </div>

    <div class="kpi accent-blue">
        <div class="kpi-top">
            <div class="kpi-icon"><?= view('partials/icon', ['name' => 'calculator']) ?></div>
            <div class="kpi-label">Average Sale</div>
        </div>
        <div class="kpi-value sm">₱<?= number_format((float) ($averageTransactionValue ?? 0), 2) ?></div>
        <div class="kpi-sub">Average amount for each transaction</div>
    </div>

    <div class="kpi accent-amber">
        <div class="kpi-top">
            <div class="kpi-icon"><?= view('partials/icon', ['name' => 'tag']) ?></div>
            <div class="kpi-label">Discount</div>
        </div>
        <div class="kpi-value sm">₱<?= number_format((float) ($salesSummary['total_discount'] ?? 0), 2) ?></div>
        <div class="kpi-sub">Total discounts given</div>
    </div>

    <div class="kpi accent-purple">
        <div class="kpi-top">
            <div class="kpi-icon"><?= view('partials/icon', ['name' => 'chart']) ?></div>
            <div class="kpi-label"><?= esc($forecastLabelText) ?> Forecast</div>
        </div>
        <div class="kpi-value sm">₱<?= number_format((float) ($nextForecastValue ?? 0), 2) ?></div>
        <div class="kpi-sub">Estimated from the selected sales history</div>
    </div>

    <div class="kpi accent-purple">
        <div class="kpi-top">
            <div class="kpi-icon"><?= view('partials/icon', ['name' => 'layers']) ?></div>
            <div class="kpi-label">Low-Stock Products</div>
        </div>
        <div class="kpi-value"><?= esc($reorderSuggestionCount) ?></div>
        <div class="kpi-sub"><?= $reorderCriticalCount > 0 ? esc($reorderCriticalCount) . ' need attention soon' : 'Based on current stock and recent sales' ?></div>
    </div>

</div>

<?= view('admin/reports/cash_movements', ['cashMovements' => $cashMovements ?? null]) ?>

<!-- ── Composition & Top Products charts ── -->
<div class="rp-section-heading" id="reportTrends"><h2>Sales trends</h2><p>Track earnings and the products customers buy most</p></div>
<div class="dash-two-col">

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Sales and Profit</h2>
                <p>Compare sales, product cost, and profit over time</p>
            </div>
            <span class="card-head-tag tag-blue">By Period</span>
        </div>
        <div style="padding: 0 20px 20px;">
            <div class="chart-wrap"><canvas id="compositionChart" role="img" aria-label="Net sales, product costs and gross profit by period"></canvas></div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Best-Selling Products</h2>
                <p>By quantity sold in this period</p>
            </div>
        </div>
        <div style="padding: 0 20px 20px;">
            <div class="chart-wrap"><canvas id="topProductsChart" role="img" aria-label="Best-selling products by quantity; values are listed in Sales details below"></canvas></div>
        </div>
    </div>

</div>

<!-- ── Forecasting filter ── -->
<div class="rp-section-heading" id="reportForecast"><h2>Plan ahead</h2><p>Forecast sales and review restock suggestions</p></div>
<div class="card rp-filters" style="margin-bottom:16px;">
    <div class="card-body">
        <?php if ($message = session()->getFlashdata('forecast_success')): ?>
            <div class="rp-notices" role="status"><?= esc($message) ?></div>
        <?php endif; ?>
        <?php if ($message = session()->getFlashdata('forecast_error')): ?>
            <div class="rp-notices" role="alert"><?= esc($message) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= site_url('admin/reports/update-forecast') ?>" data-return-url="<?= site_url('admin/reports') ?>" id="salesForecastFilters" data-auto-filter="off">
            <?= csrf_field() ?>
            <input type="hidden" name="branch_id" value="<?= esc($branchId ?? '') ?>">
            <input type="hidden" name="report_type" value="<?= esc($reportType ?? 'daily') ?>">
            <input type="hidden" name="report_date_from" value="<?= esc($reportDateFrom) ?>">
            <input type="hidden" name="report_date_to" value="<?= esc($reportDateTo) ?>">

            <section class="rp-filter-group forecast" aria-labelledby="forecastFilterTitle">
                <div class="rp-filter-group-title">
                    <div>
                        <strong id="forecastFilterTitle">Sales Forecast Filters</strong>
                        <span>Choose the past sales period that the system should use for the estimate. Maximum: 24 months.</span>
                    </div>
                    <span class="card-head-tag tag-purple">Forecasting</span>
                </div>

                <div class="rp-filter-fields">
                    <div class="field">
                        <label class="label" for="forecastGrouping">Show forecast by</label>
                        <select name="forecast_type" id="forecastGrouping" class="input">
                            <option value="daily" <?= ($forecastType ?? 'daily') === 'daily' ? 'selected' : '' ?>>Daily</option>
                            <option value="weekly" <?= ($forecastType ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($forecastType ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        </select>
                    </div>
                    <div class="field">
                        <label class="label" for="forecastDateFrom">Use sales from</label>
                        <input type="date" id="forecastDateFrom" name="forecast_date_from" class="input" value="<?= esc($forecastDateFrom) ?>" max="<?= esc($today) ?>" required>
                    </div>
                    <div class="field">
                        <label class="label" for="forecastDateTo">Use sales to</label>
                        <input type="date" id="forecastDateTo" name="forecast_date_to" class="input" value="<?= esc($forecastDateTo) ?>" max="<?= esc($today) ?>" required>
                    </div>
                </div>
                <p class="rp-filter-help">Click Update Forecast to apply these filters and save the seven sales forecast periods shown below, plus separate low-stock product estimates. Matching records from today are refreshed.</p>
                <p class="rp-pending-filters" id="forecastPendingFilters" role="status" hidden>Forecast changes are not applied yet. Click Update Forecast to refresh the results.</p>


                <div style="display:flex; justify-content:flex-end; margin-top:14px;">
                    <button class="btn btn-primary" type="submit">Update Forecast</button>
                </div>
            </section>
        </form>
    </div>
</div>

<!-- ── Forecast chart ── -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-head">
        <div>
            <h2>Sales Forecast</h2>
            <p>Past sales compared with estimated future sales. Update Forecast saves the seven future values with their target dates.</p>
        </div>
        <span class="card-head-tag tag-purple"><?= esc(ucfirst($forecastType ?? 'daily')) ?> View</span>
    </div>
    <div style="padding: 0 20px 20px;">
        <div class="chart-wrap" style="height:340px;"><canvas id="forecastChart" role="img" aria-label="Actual and estimated sales; forecast values are listed in the tables below"></canvas></div>
    </div>
    <div class="forecast-note">This forecast is an estimate based on past sales. Use it as a guide when planning stock and sales.</div>
</div>

<button type="button" class="forecast-modal-trigger" id="forecastModalTrigger" aria-controls="forecastModalOverlay" aria-expanded="false">
    Restock Suggestions
    <?php if ($reorderCriticalCount > 0): ?>
        <span class="badge badge-danger"><?= esc($reorderCriticalCount) ?> urgent</span>
    <?php endif; ?>
</button>
<button type="button" class="btn btn-secondary" id="savedSalesForecastTrigger" aria-controls="forecastModalOverlay" aria-expanded="false">Saved sales forecasts</button>

<div class="forecast-modal-overlay" id="forecastModalOverlay" aria-hidden="true">
    <div class="forecast-modal" role="dialog" aria-modal="true" aria-labelledby="forecastModalTitle" aria-describedby="restockContext">
        <div class="forecast-modal-head">
            <div><span class="restock-eyebrow">Inventory planning</span><h2 id="forecastModalTitle">Restock Suggestions</h2>
                <p id="restockContext"><?= esc($branchName ?? 'All branches') ?> &middot; Sales from <?= esc(\App\Libraries\DisplayDate::date($forecastDateFrom)) ?> to <?= esc(\App\Libraries\DisplayDate::date($forecastDateTo)) ?></p>
            </div>
            <button type="button" class="forecast-modal-close" id="forecastModalClose" aria-label="Close">✕</button>
        </div>
        <div class="restock-controls" id="restockControls" hidden>
            <div class="restock-tabs" role="tablist" aria-label="Restock information">
                <button type="button" role="tab" id="restockCurrentTab" aria-controls="restockCurrent" aria-selected="true">Suggestions <span><?= count($reorderForecast ?? []) ?></span></button>
                <button type="button" role="tab" id="restockSavedTab" aria-controls="restockSaved" aria-selected="false" tabindex="-1">Saved records <span><?= count($forecastingDataLog ?? []) ?></span></button>
            </div>
            <div class="restock-tools">
                <label class="restock-search"><span>Search this list</span><input type="search" id="restockSearch" placeholder="Product, SKU or branch" autocomplete="off"></label>
                <label class="restock-priority" id="restockPriorityLabel"><span>Priority</span><select id="restockPriority"><option value="">All priorities</option><option value="critical">Urgent</option><option value="warning">Order soon</option><option value="watch">Monitor</option><option value="unknown">Review manually</option></select></label>
                <button type="button" class="btn" id="restockReset">Reset</button>
            </div>
            <p class="restock-result-count" id="restockResultCount" role="status" aria-live="polite"></p>
        </div>
        <div class="forecast-modal-body">

            <!-- ── Restock suggestions ── -->
            <div class="card restock-panel" id="restockCurrent">
                <div class="card-head">
                    <div>
                        <h2>Order planning</h2>
                        <p>Listed by estimated time until stock runs out</p>
                    </div>
                    <span class="card-head-tag tag-purple"><?= esc($reorderCoverageDays ?? 30) ?>-day stock guide</span>
                </div>

                <?php if (!empty($reorderForecast)): ?>
                    <div class="table-wrap table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Branch</th>
                                    <th>Stock</th>
                                    <th>Approx. Stock Left</th>
                                    <th>Suggested Order</th>
                                    <th>Priority</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reorderForecast as $row): ?>
                                    <tr data-restock-row data-priority="<?= esc($row['urgency'], 'attr') ?>">
                                        <td data-label="Product">
                                            <?= esc($row['product_name']) ?>
                                            <?php if (!empty($row['sku'])): ?>
                                                <div class="muted" style="font-size:11px; margin-top:1px;"><?= esc($row['sku']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Branch"><?= esc($row['branch_name']) ?></td>
                                        <td data-label="Stock"><?= esc($row['stock']) ?> on hand<div class="muted" style="font-size:11px; margin-top:1px;">Reorder at <?= esc($row['reorder_level']) ?></div></td>
                                        <td data-label="Approx. Stock Left">
                                            <?php if ($row['days_left'] !== null): ?>
                                                About <?= esc($row['days_left']) ?> <?= (int) $row['days_left'] === 1 ? 'day' : 'days' ?>
                                            <?php else: ?>
                                                <span class="muted">No reliable estimate</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Suggested Order" class="amount restock-order"><?php if ($row['days_left'] === null): ?><span class="muted">Review manually</span><?php else: ?><strong><?= esc($row['suggested_qty']) ?></strong> <span>units</span><?php endif; ?></td>
                                        <td data-label="Priority">
                                            <?php
                                                $urgencyBadge = [
                                                    'critical' => ['badge-danger', 'Urgent'],
                                                    'warning'  => ['badge-warning', 'Order Soon'],
                                                    'watch'    => ['badge-neutral', 'Monitor'],
                                                    'unknown'  => ['badge-neutral', 'Review manually'],
                                                ][$row['urgency']] ?? ['badge-neutral', 'Monitor'];
                                            ?>
                                            <span class="badge <?= $urgencyBadge[0] ?>"><?= $urgencyBadge[1] ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="restock-guidance">
                        Suggestions are based on product sales from <?= esc(\App\Libraries\DisplayDate::date($forecastDateFrom)) ?> to <?= esc(\App\Libraries\DisplayDate::date($forecastDateTo)) ?>.
                        The suggested order aims to cover about <?= esc($reorderCoverageDays ?? 30) ?> days. Review current stock and supplier delivery time before placing an order.
                    </p>
                <?php else: ?>
                    <div class="card-body">
                        <p class="muted" style="text-align:center; padding:20px 0;">No products are currently at or below their reorder level<?= !empty($branchId) ? ' for this branch' : '' ?>.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── Forecasting Data log ── -->
            <div class="card restock-panel" id="restockSaved">
                <div class="card-head">
                    <div>
                        <h2>Saved Forecast Records</h2>
                        <p>Latest 100 records across all branches, newest first</p>
                    </div>
                </div>

                <?php $forecastLog = $forecastingDataLog ?? []; ?>
                <?php if (!empty($forecastLog)): ?>
                    <div class="table-wrap table-scroll">
                        <table class="forecast-log-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Forecast / product</th>
                                    <th>Target period / estimate basis</th>
                                    <th>Source / sales used</th>
                                    <th>Estimated Qty</th>
                                    <th>Estimated Sales</th>
                                    <th>Generated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forecastLog as $row): ?>
                                    <?php
                                    $hasBasis = preg_match('/^(Sales|Manual) Holt b=(all|\d+) (\d{4}-\d{2}-\d{2})\.\.(\d{4}-\d{2}-\d{2}) (daily|weekly|monthly) /', (string) ($row['method_used'] ?? ''), $basis);
                                    $isSalesForecast = $hasBasis && $basis[1] === 'Sales';
                                    $periodDate = $row['forecast_month'] ?? null;
                                    $periodLabel = $isSalesForecast
                                        ? match ($basis[5]) {
                                            'weekly' => 'Week of ' . \App\Libraries\DisplayDate::date($periodDate),
                                            'monthly' => \App\Libraries\DisplayDate::month($periodDate),
                                            default => \App\Libraries\DisplayDate::date($periodDate),
                                        }
                                        : \App\Libraries\DisplayDate::month($periodDate) . ' monthly equivalent';
                                    ?>
                                    <tr data-restock-row>
                                        <td data-label="ID" class="muted"><?= esc($row['id']) ?></td>
                                        <td data-label="Forecast / product">
                                            <?php if ($isSalesForecast): ?>
                                                <strong>Sales revenue</strong><div class="muted"><?= esc(ucfirst($basis[5])) ?> forecast</div>
                                            <?php elseif (!empty($row['product_name'])): ?>
                                                <?= esc($row['product_name']) ?>
                                                <?php if (!empty($row['sku'])): ?>
                                                    <div class="muted" style="font-size:11px; margin-top:1px;"><?= esc($row['sku']) ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="muted">Store-wide</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Target period / estimate basis"><?= esc($periodLabel) ?></td>
                                        <td data-label="Source / sales used">
                                            <?php if ($hasBasis): ?>
                                                <?php
                                                $scope = 'All branches';
                                                if ($basis[2] !== 'all') {
                                                    $scope = 'Branch #' . $basis[2];
                                                    foreach ($branches as $branch) {
                                                        if ((string) $branch['id'] === $basis[2]) { $scope = $branch['branch_name']; break; }
                                                    }
                                                }
                                                ?>
                                                <strong><?= $isSalesForecast ? 'Saved sales forecast' : 'Product demand estimate' ?></strong><div class="muted"><?= esc($scope) ?></div>
                                                <div class="muted"><?= esc(\App\Libraries\DisplayDate::date($basis[3]) . ' to ' . \App\Libraries\DisplayDate::date($basis[4])) ?></div>
                                                <div class="muted"><?= $isSalesForecast ? esc(ucfirst($basis[5])) . ' revenue · α 0.3 / β 0.2' : 'Daily demand · 30-day outlook' ?></div>
                                            <?php else: ?>
                                                <span>Scheduled / historical</span><div class="muted">All branches</div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Estimated Qty" class="amount"><?= $row['predicted_quantity'] !== null ? esc($row['predicted_quantity']) . ' units' : '—' ?></td>
                                        <td data-label="Estimated Sales" class="amount"><?= $row['predicted_revenue'] !== null ? '₱' . number_format((float) $row['predicted_revenue'], 2) : '—' ?></td>
                                        <td data-label="Generated At" class="muted" style="font-size:12px; "><?= esc(\App\Libraries\DisplayDate::dateTime($row['generated_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="restock-guidance">
                        Latest 100 records across all branches. Sales revenue rows save the chart's exact future values for each target period. Product rows are separate monthly equivalents of a 30-day daily-demand outlook, not calendar-month sales forecasts. Updating the same filters today refreshes matching records; previous days and scheduled snapshots stay unchanged.
                    </p>
                <?php else: ?>
                    <div class="card-body">
                        <p class="muted" style="text-align:center; padding:20px 0;">No saved records yet. Click Update Forecast to save seven sales forecast periods and any low-stock product estimates.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="restock-empty" id="restockEmpty" hidden><strong>No matches in this list</strong><p>Try a different product, SKU or branch, or reset your filters.</p><button type="button" class="btn" id="restockEmptyReset">Reset filters</button></div>
        </div>
        <div class="restock-footer">
            <p>Estimates guide ordering. Check stock and supplier lead times.</p>
            <button type="button" class="btn" id="restockDone">Close</button>
            <button type="button" class="btn btn-primary" id="restockEditFilters">Forecast filters <span aria-hidden="true">&rarr;</span></button>
        </div>
    </div>
</div>

<!-- ── Top Products / Recent Sales tables ── -->
<div class="rp-section-heading" id="reportActivity"><h2>Sales details</h2><p>Product rankings, recent transactions and forecast values</p></div>
<div class="dash-two-col">

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Best-Selling Products</h2>
                <p>Showing up to 10 top-selling products for the selected filters.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="mobile-record-table">
                <thead>
                    <tr><th>Product</th><th>Units Sold</th><th>Sales</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($topProducts)): ?>
                        <?php foreach ($topProducts as $item): ?>
                            <tr>
                                <td><?= esc($item['product_name_snapshot']) ?></td>
                                <td><?= esc($item['total_qty_sold']) ?></td>
                                <td class="amount">₱<?= number_format((float) $item['total_sales'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="muted" style="text-align:center; padding:20px;">No sales data found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Recent Sales</h2>
                <p>Showing up to 10 most recent sales for the selected filters.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="mobile-record-table">
                <thead>
                    <tr><th>Invoice</th><th>Status</th><th>Remaining Total</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentSales)): ?>
                        <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td><?= esc($sale['invoice_no']) ?></td>
                                <td>
                                    <span class="badge <?= ($sale['status'] ?? '') === 'partially_refunded' ? 'badge-warning' : 'badge-success' ?>">
                                        <?= ($sale['status'] ?? '') === 'partially_refunded' ? 'Partial Refund' : 'Completed' ?>
                                    </span>
                                </td>
                                <td class="amount">₱<?= number_format((float) ($sale['remaining_total'] ?? $sale['final_total']), 2) ?></td>
                                <td><?= esc(\App\Libraries\DisplayDate::dateTime($sale['sale_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="muted" style="text-align:center; padding:20px;">No recent sales found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ── Forecast History / Future Forecast tables ── -->
<div class="dash-two-col">

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Forecast History</h2>
                <p>Fitted values against actual net sales</p>
            </div>
        </div>
        <div class="table-wrap table-scroll">
            <table class="mobile-record-table">
                <thead>
                    <tr><th>Period</th><th>Net Sales</th><th>Fitted Forecast</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($forecastHistory)): ?>
                        <?php foreach ($forecastHistory as $row): ?>
                            <tr>
                                <td><?= esc(\App\Libraries\DisplayDate::period($row['report_date'])) ?></td>
                                <td class="amount">₱<?= number_format((float) ($row['net_sales'] ?? 0), 2) ?></td>
                                <td>₱<?= number_format((float) ($row['forecast'] ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="muted" style="text-align:center; padding:20px;">No forecast data found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Future Forecast</h2>
                <p>
                    <?php if (($forecastType ?? 'daily') === 'weekly'): ?>Next 7 weeks
                    <?php elseif (($forecastType ?? 'daily') === 'monthly'): ?>Next 7 months
                    <?php else: ?>Next 7 days
                    <?php endif; ?>
                </p>
            </div>
            <span class="card-head-tag tag-purple">Predicted</span>
        </div>
        <div class="table-wrap table-scroll">
            <table class="mobile-record-table">
                <thead>
                    <tr><th>Forecast Period</th><th>Forecast Sales</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($futureForecast)): ?>
                        <?php foreach ($futureForecast as $row): ?>
                            <tr>
                                <td><?= esc(\App\Libraries\DisplayDate::period($row['forecast_date'])) ?></td>
                                <td class="amount">₱<?= number_format((float) ($row['forecast_value'] ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="muted" style="text-align:center; padding:20px;">No future forecast found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ── Export History ── -->
<div class="card" id="reportExportHistory">
    <div class="card-head">
        <div>
            <h2>Export History</h2>
            <p>Last 10 exports<?= !empty($branchId) ? ' for this branch' : '' ?></p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="mobile-record-table">
            <thead>
                <tr><th>Generated</th><th>User</th><th>Branch</th><th>Type</th><th>Report Period</th><th>Gross</th><th>Profit</th></tr>
            </thead>
            <tbody>
                <?php if (!empty($exportHistory)): ?>
                    <?php foreach ($exportHistory as $row): ?>
                        <tr>
                            <td><?= \App\Libraries\DisplayDate::date($row['created_at']) ?><br><span class="muted"><?= \App\Libraries\DisplayDate::time($row['created_at']) ?></span></td>
                            <td><?= esc($row['full_name'] ?? '-') ?></td>
                            <td><?= esc($row['branch_name'] ?? 'All Branches') ?></td>
                            <td>
                                <span class="badge <?= $row['export_type'] === 'pdf' ? 'badge-danger' : 'badge-success' ?>">
                                    <?= strtoupper(esc($row['export_type'])) ?>
                                </span>
                            </td>
                            <td><?= esc(\App\Libraries\DisplayDate::date($row['date_from'])) ?> – <?= esc(\App\Libraries\DisplayDate::date($row['date_to'])) ?></td>
                            <td class="amount">₱<?= number_format((float) $row['gross_sales'], 2) ?></td>
                            <td class="amount">₱<?= number_format((float) $row['gross_profit'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="muted" style="text-align:center; padding:20px;">No export history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
<script src="<?= base_url('assets/js/reports.js') ?>?v=20260927-3" defer></script>
<script src="<?= base_url('assets/js/report-exports.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') {
        document.querySelectorAll('.chart-wrap').forEach(function (wrap) {
            wrap.innerHTML = '<p class="muted" style="text-align:center; padding:80px 12px;">Charts could not be loaded. The report tables and exports are still available.</p>';
        });
        return;
    }

    const reportLabels = <?= json_encode(array_map([\App\Libraries\DisplayDate::class, 'period'], array_column($reportHistory, 'report_date'))) ?>;
    const reportNetSales = <?= json_encode(array_map('floatval', array_column($reportHistory, 'net_sales'))) ?>;
    const reportCogs = <?= json_encode(array_map('floatval', array_column($reportHistory, 'cogs'))) ?>;
    const reportGrossProfit = <?= json_encode(array_map('floatval', array_column($reportHistory, 'gross_profit'))) ?>;

    const forecastLabels = <?= json_encode(array_map([\App\Libraries\DisplayDate::class, 'period'], array_column($forecastHistory, 'report_date'))) ?>;
    const forecastActualSales = <?= json_encode(array_map('floatval', array_column($forecastHistory, 'net_sales'))) ?>;
    const fittedForecast = <?= json_encode(array_map('floatval', array_column($forecastHistory, 'forecast'))) ?>;

    const futureLabels = <?= json_encode(array_map([\App\Libraries\DisplayDate::class, 'period'], array_column($futureForecast, 'forecast_date'))) ?>;
    const futureValues = <?= json_encode(array_map('floatval', array_column($futureForecast, 'forecast_value'))) ?>;

    const topLabels = <?= json_encode(array_column($topProducts, 'product_name_snapshot')) ?>;
    const topQty = <?= json_encode(array_map('intval', array_column($topProducts, 'total_qty_sold'))) ?>;

    // ── Theming helpers (matches dashboard) ──
    const isDark = () => document.documentElement.classList.contains('dark');
    const gridColor = () => isDark() ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
    const tickColor = () => isDark() ? '#6b7e96' : '#6b82a0';
    const labelColor = () => isDark() ? '#b0bdce' : '#2c3e5a';

    const BLUE = '#3b82f6';
    const AMBER = '#f59e0b';
    const GREEN = '#22c55e';
    const PURPLE = '#8b5cf6';
    const PALETTE = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#14b8a6'];

    const pesoTick = v => '₱' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v);

    const baseOptions = (extra = {}) => ({
        responsive: true,
        maintainAspectRatio: false,
        animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('reduce-motion') ? false : undefined,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { labels: { color: labelColor(), font: { size: 12 }, boxWidth: 12, padding: 14 } },
            tooltip: {
                backgroundColor: isDark() ? '#1c2639' : '#fff',
                borderColor: isDark() ? '#2e3f58' : '#dce7f5',
                borderWidth: 1,
                titleColor: labelColor(),
                bodyColor: tickColor(),
                callbacks: {
                    label: ctx => ' ' + ctx.dataset.label + ': ₱' + ctx.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                }
            }
        },
        ...extra
    });

    const baseScales = () => ({
        x: { grid: { color: gridColor() }, ticks: { color: tickColor(), font: { size: 11 }, maxTicksLimit: 10 } },
        y: { grid: { color: gridColor() }, ticks: { color: tickColor(), font: { size: 11 }, callback: pesoTick } }
    });

    // ── Sales and profit chart ──
    const compositionChart = new Chart(document.getElementById('compositionChart'), {
        type: 'line',
        data: {
            labels: reportLabels,
            datasets: [
                { label: 'Net Sales', data: reportNetSales, borderColor: BLUE, backgroundColor: 'rgba(59,130,246,0.08)', fill: true, tension: 0.35, pointRadius: 2, pointHoverRadius: 5 },
                { label: 'Product Cost', data: reportCogs, borderColor: AMBER, backgroundColor: 'rgba(245,158,11,0.08)', fill: false, tension: 0.35, pointRadius: 2, pointHoverRadius: 5 },
                { label: 'Gross Profit', data: reportGrossProfit, borderColor: GREEN, backgroundColor: 'rgba(34,197,94,0.08)', fill: false, tension: 0.35, pointRadius: 2, pointHoverRadius: 5 }
            ]
        },
        options: { ...baseOptions(), scales: baseScales() }
    });

    // ── Top Products (horizontal bar) ──
    const topProductsChart = new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: topLabels,
            datasets: [{ label: 'Qty Sold', data: topQty, backgroundColor: PALETTE.map(c => c + 'cc'), borderColor: PALETTE, borderWidth: 1.5, borderRadius: 5 }]
        },
        options: {
            ...baseOptions(),
            indexAxis: 'y',
            plugins: { ...baseOptions().plugins, tooltip: { ...baseOptions().plugins.tooltip, callbacks: { label: ctx => ' ' + ctx.parsed.x + ' units' } } },
            scales: {
                x: { grid: { color: gridColor() }, ticks: { color: tickColor(), font: { size: 11 } } },
                y: { grid: { color: gridColor() }, ticks: { color: tickColor(), font: { size: 11 }, callback: function(value) { const label = this.getLabelForValue(value); return label.length > 25 ? label.slice(0, 24) + '…' : label; } } }
            }
        }
    });

    // ── Sales forecast ──
    const forecastChart = new Chart(document.getElementById('forecastChart'), {
        type: 'line',
        data: {
            labels: forecastLabels.concat(futureLabels),
            datasets: [
                { label: 'Actual Sales', data: forecastActualSales.concat(Array(futureLabels.length).fill(null)), borderColor: BLUE, backgroundColor: 'rgba(59,130,246,0.08)', fill: true, tension: 0.3, pointRadius: 2, pointHoverRadius: 5 },
                { label: 'Estimated Sales', data: fittedForecast.concat(futureValues), borderColor: PURPLE, borderDash: [6, 6], fill: false, tension: 0.3, pointRadius: 2, pointHoverRadius: 5 }
            ]
        },
        options: { ...baseOptions(), scales: baseScales() }
    });

    [[compositionChart, reportLabels.length], [topProductsChart, topLabels.length], [forecastChart, forecastLabels.length + futureLabels.length]].forEach(([chart, count]) => {
        if (count) return;
        chart.canvas.hidden = true;
        const message = document.createElement('p');
        message.className = 'rp-chart-empty';
        message.textContent = 'No data for this chart. Try a different date range or branch.';
        chart.canvas.parentElement.appendChild(message);
    });

    // ── Re-theme on dark mode toggle ──
    const observer = new MutationObserver(() => {
        [compositionChart, topProductsChart, forecastChart].forEach(c => {
            Object.values(c.options.scales).forEach(ax => {
                if (ax.grid) ax.grid.color = gridColor();
                if (ax.ticks) ax.ticks.color = tickColor();
            });
            c.options.plugins.legend.labels.color = labelColor();
            const tooltipTheme = baseOptions().plugins.tooltip;
            ['backgroundColor', 'borderColor', 'titleColor', 'bodyColor'].forEach(key => {
                c.options.plugins.tooltip[key] = tooltipTheme[key];
            });
            c.update('none');
        });
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
})();

(function () {
    var trigger = document.getElementById('forecastModalTrigger');
    var overlay = document.getElementById('forecastModalOverlay');
    var closeBtn = document.getElementById('forecastModalClose');
    if (!trigger || !overlay || !closeBtn) return;

    function open() {
        window.PharxmacoModal.open(overlay, {trigger: trigger, initialFocus: closeBtn});
    }
    function close() {
        window.PharxmacoModal.close(overlay);
    }

    trigger.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) close();
    });
})();

(function () {
    function connectRange(fromId, toId, maxMonths) {
        var from = document.getElementById(fromId);
        var to = document.getElementById(toId);
        if (!from || !to) return;

        function dateMinusMonths(value, months) {
            var parts = value.split('-').map(Number);
            if (parts.length !== 3) return '';
            var d = new Date(parts[0], parts[1] - 1, parts[2]);
            d.setMonth(d.getMonth() - months);
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + day;
        }

        function sync() {
            from.max = to.value || from.getAttribute('max') || '';
            to.min = from.value || '';
            if (maxMonths && to.value) {
                from.min = dateMinusMonths(to.value, maxMonths);
            }
        }

        from.addEventListener('change', sync);
        to.addEventListener('change', sync);
        sync();
    }

    connectRange('reportDateFrom', 'reportDateTo', null);
    connectRange('forecastDateFrom', 'forecastDateTo', 24);
})();
</script>

<?= $this->endSection() ?>
