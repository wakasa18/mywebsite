const fs = require('fs');
const layoutPath = 'app/Views/layouts/staff.php';
let layout = fs.readFileSync(layoutPath, 'utf8');
const start = layout.indexOf("<?php if ((int) ($notificationData['low_stock_total'] ?? 0) > 0): ?>");
const end = layout.indexOf('\n<script>', start);
if (start < 0 || end < 0) throw Error('Modal boundaries missing');
let partial = layout.slice(start, end).trim() + '\n';
partial = partial.replace('aria-labelledby="lowStockModalTitle"', 'aria-labelledby="lowStockModalTitle" aria-describedby="lowStockModalDescription"')
 .replace('class="inventory-modal-subtitle"', 'class="inventory-modal-subtitle" id="lowStockModalDescription"')
 .replace('class="inventory-modal-count"', 'class="inventory-modal-count" role="status" aria-live="polite"');
fs.writeFileSync('app/Views/layouts/low_stock_modal.php', partial);
layout = layout.slice(0,start) + "<?= view('layouts/low_stock_modal', ['notificationData' => $notificationData]) ?>\n" + layout.slice(end);
fs.writeFileSync(layoutPath, layout);
const reportsPath = 'app/Views/admin/reports/index.php';
let reports = fs.readFileSync(reportsPath, 'utf8');
const modalStart = reports.indexOf('<div class="forecast-modal-overlay"');
const modalEnd = reports.indexOf('<!--', reports.indexOf('\n</div>',modalStart));
let modal = reports.slice(modalStart, modalEnd);
let tableIndex=0;
modal = modal.replace(/<table>[\s\S]*?<\/table>/g, table => {
 const headings = [...table.matchAll(/<th>(.*?)<\/th>/g)].map(match => match[1]);
 let col=0;
 table = table.replace(/<td(\s[^>]*|)>/g, (tag, attrs) => `<td data-label="${headings[col++ % headings.length]}"${attrs}>`);
 if (tableIndex++ === 1) table = table.replace('<table>', '<table class="forecast-log-table">').replace('white-space:nowrap;', '');
 return table;
});
reports=reports.slice(0,modalStart)+modal+reports.slice(modalEnd);
fs.writeFileSync(reportsPath,reports);
