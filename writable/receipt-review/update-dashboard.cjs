const fs = require('fs');
const file = 'app/Views/dashboard/index.php';
let s = fs.readFileSync(file, 'utf8').replace(/\r\n/g, '\n');
fs.writeFileSync('writable/receipt-review/dashboard-before.php', s);
s = s.replace(/<style>[\s\S]*?<\/style>/, `<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>?v=20260924-1">`);
s = s.replace('$hasTopProducts = !empty($topProductLabels);', `$hasTopProducts = !empty($topProductLabels);
$hasDailySales = array_sum($dailyTxns ?? []) > 0 || array_sum($dailyRevenue ?? []) != 0;
$hasMonthlySales = array_sum($monthlyTxns ?? []) > 0 || array_sum($monthlyRevenue ?? []) != 0;`);
s = s.replace('<h1 id="dashboardGreeting"', '<span class="dash-eyebrow">Dashboard / Overview</span>\n            <h1 id="dashboardGreeting"');
s = s.replace('Business overview, sales activity, and inventory issues that need attention.', 'Today at your pharmacy. Review sales, check stock, and plan your next steps.');
s = s.replace('All active branches</span>', 'All branches</span>');
const kpiStart = s.indexOf('    <div class="kpi-grid">');
const kpiEnd = s.indexOf('    <div class="dash-grid-2">', kpiStart);
const kpis = [...s.slice(kpiStart, kpiEnd).matchAll(/        <a class="kpi [\s\S]*?<\/a>/g)].map(x => x[0]);
if(kpis.length !== 9) throw Error('Expected nine dashboard metrics');
const section = (id,title,description) => `<div class="dash-section-heading" id="${id}"><h2>${title}</h2><p>${description}</p></div>`;
const summary = `<div class="dash-inventory-summary">${kpis.slice(6).join('\n')}</div>`;
const overview = `<nav class="dash-section-nav" aria-label="Dashboard sections"><a href="#dashOverview">Today</a><a href="#dashInventory">Inventory watch</a><a href="#dashTrends">Sales trends</a><a href="#dashBranches">Branches</a><a href="#dashActivity">Recent activity</a></nav>
    ${section('dashOverview','Today’s overview','Sales totals reflect completed and partially refunded transactions.')}
    <div class="kpi-grid">${kpis.slice(0,3).join('\n')}</div>
    ${section('dashInventory','Inventory watch','Active branch inventory records. A product may appear in more than one category.')}
    <div class="dash-watch-grid">${kpis.slice(3,6).join('\n')}</div>
    <!-- INVENTORY_DETAILS -->
    ${section('dashTrends','Sales performance','See how sales are moving across the business.')}
`;
s = s.slice(0,kpiStart) + overview + s.slice(kpiEnd);
const detailStart = s.lastIndexOf('    <div class="dash-grid-2">',s.indexOf('<h2>Low-Stock Products</h2>'));
const detailEnd = s.indexOf('    <section class="card dash-card" style="margin-bottom:16px;">',detailStart);
let details = s.slice(detailStart,detailEnd);
details = details.replace(/        <section class="card dash-card">\s*<div class="card-head"><div><h2>Inventory Attention<\/h2>[\s\S]*?<\/section>\n/, '');
details = details.replace('class="card dash-card"','class="card dash-card dash-span-2"');
s = s.slice(0,detailStart) + s.slice(detailEnd);
s = s.replace('<!-- INVENTORY_DETAILS -->', `<details class="dash-stock-details"><summary><span><strong>Review stock and expiry lists</strong><small>Up to 10 records per list, ordered by stock level or expiry date</small></span><span class="dash-disclosure" aria-hidden="true">+</span></summary><div class="dash-detail-body">${details}</div></details>`);
const branchStart = s.lastIndexOf('    <div class="dash-grid-2">',s.indexOf('<h2>Branch Sales</h2>'));
s = s.slice(0,branchStart) + section('dashBranches','Across your branches','Compare the last 30 days of sales and current active inventory.') + summary + '\n' + s.slice(branchStart);
s = s.replace('    <section class="card dash-card" style="margin-bottom:16px;">',section('dashActivity','Recent activity','Latest sales and recorded team actions.') + '\n    <section class="card dash-card" style="margin-bottom:16px;">');
s = s.replace('Yesterday: ₱', 'Yesterday (full day): ₱');
s = s.replace('class="kpi green"', 'class="kpi green dash-sales-kpi"');
s = s.replace('class="kpi amber"', 'class="kpi amber"');
s = s.replace('<p>Best-performing products during the last 90 days.</p>','<p>Top 10 by net revenue over 90 days. Switch to see their units sold.</p>');
s = s.replace('<div class="chart-body">\n                <div class="chart-panel active"', `<div class="dash-trend-summary" aria-live="polite"><div><span id="dashTrendPeriod">Last 30 days · net sales</span><strong id="dashTrendRevenue">₱<?= number_format(array_sum($dailyRevenue), 2) ?></strong></div><div><span>Transactions</span><strong id="dashTrendTransactions"><?= number_format(array_sum($dailyTxns)) ?></strong></div></div>
            <div class="chart-body">
                <div class="chart-panel active"`);
for (const [id,has,period] of [['chartDaily','hasDailySales','30 days'],['chartMonthly','hasMonthlySales','12 months']]) {
 s = s.replace(new RegExp(`<canvas id="${id}"[^>]*><\\/canvas>`), `<canvas id="${id}" role="img" aria-label="Sales and transaction trend over the last ${period}" <?= !$${has} ? 'hidden' : '' ?>></canvas><?php if (!$${has}): ?><div class="chart-empty">No sales recorded in the last ${period}. Completed sales will appear here.</div><?php endif; ?>`);
}
s = s.replace(/<canvas id="(chartTopProducts|chartBranch)" /g, '<canvas id="$1" role="img" ');
s = s.replace('onclick="switchTop(\'revenue\')"','onclick="switchTop(\'revenue\')" <?= !$hasTopProducts ? \'disabled\' : \'\' ?>');
s = s.replace('onclick="switchTop(\'units\')"','onclick="switchTop(\'units\')" <?= !$hasTopProducts ? \'disabled\' : \'\' ?>');
s = s.replace("document.querySelectorAll('.chart-panel').forEach(panel => panel.hidden = true);", `document.querySelectorAll('.dash-page .chart-tab').forEach(button => button.disabled = true);
        document.querySelectorAll('.dash-page canvas').forEach(canvas => {
            if (canvas.hidden) return;
            canvas.hidden = true;
            const message = document.createElement('div');
            message.className = 'chart-empty';
            message.textContent = 'Chart unavailable. You can still review the totals and tables below.';
            canvas.parentElement.appendChild(message);
        });`);
s = s.replace("const isDark = () =>", `const reducedMotion = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('reduce-motion');
    Chart.defaults.animation = reducedMotion() ? false : {duration: 250};
    const themeValue = name => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    const isDark = () =>`);
s = s.replace("const tickColor = () => isDark() ? '#93a4ba' : '#64748b';", "const tickColor = () => themeValue('--muted');");
s = s.replace("const labelColor = () => isDark() ? '#d3dce8' : '#24324a';", "const labelColor = () => themeValue('--text');");
s = s.replace("const surfaceColor = () => isDark() ? '#161e2e' : '#ffffff';", "const surfaceColor = () => themeValue('--surface');");
s = s.replace("const primary = '#3b82f6';", "const primary = themeValue('--primary');");
s = s.replace('if (!canvas) return null;', 'if (!canvas || canvas.hidden) return null;');
s = s.replace("const dailyMode = mode === 'daily';", `const dailyMode = mode === 'daily';
        const series = dailyMode ? daily : monthly;
        document.getElementById('dashTrendPeriod').textContent = dailyMode ? 'Last 30 days · net sales' : 'Last 12 months · net sales';
        document.getElementById('dashTrendRevenue').textContent = money(series.revenue.reduce((total,value) => total + Number(value), 0));
        document.getElementById('dashTrendTransactions').textContent = series.txns.reduce((total,value) => total + Number(value), 0).toLocaleString('en-PH');`);
s = s.replace("if (!chart) return;", `if (!chart) return;
            chart.options.animation = reducedMotion() ? false : {duration: 250};
            if (chart.options.plugins?.tooltip) Object.assign(chart.options.plugins.tooltip, {
                backgroundColor: surfaceColor(), borderColor: gridColor(), titleColor: labelColor(), bodyColor: tickColor()
            });
            if (chart.config.type === 'line') chart.data.datasets[0].borderColor = themeValue('--primary');`);
s = s.replace("attributeFilter: ['class']", "attributeFilter: ['class', 'data-accent']");
fs.writeFileSync(file,s);
