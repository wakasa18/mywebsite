const fs = require('fs');
const path = require('path');
const changed = [];
const cls = '\\App\\Libraries\\DisplayDate::';
function edit(file, update) {
  const old = fs.readFileSync(file, 'utf8'), next = update(old);
  if (old !== next) { fs.writeFileSync(file, next); changed.push(file); }
}
function walk(dir) { return fs.readdirSync(dir,{withFileTypes:true}).flatMap(e=>e.isDirectory()?walk(path.join(dir,e.name)):[path.join(dir,e.name)]); }
for (const file of walk('app/Views').filter(f=>f.endsWith('.php')&&!f.includes(path.sep+'errors'+path.sep))) {
  edit(file, text => text.replace(/date\('(M[^']*|g:ia|h:i:s A)', strtotime\(([^()]*)\)\)/g, (all, fmt, value) =>
    cls + (fmt.includes('i') ? (fmt.startsWith('M')?'dateTime':'time') : 'date') + '(' + value + ')')
    .replace(/date\('M d, Y · h:i A', (\$saleDate|\$saleDateTimestamp|\$refundTimestamp)\)/g, (_,value)=>cls+'dateTime('+value+')')
    .replace(/date\('M (?:j|j, Y|Y)', \$periodDate\)/g, (_,offset)=>cls+'date($periodDate)'));
}
const mappings = {
 'app/Views/categories/index.php': [['$category[\'created_at\'] ?? \'—\'','dateTime']],
 'app/Views/categories/trash.php': [["$cat['deleted_at']",'dateTime']],
 'app/Views/products/trash.php': [["$p['deleted_at']",'dateTime']],
 'app/Views/admin/suppliers/trash.php': [["$s['deleted_at']",'dateTime']],
 'app/Views/stock_logs/index.php': [["$log['created_at']",'dateTime']],
 'app/Views/admin/branches/index.php': [["$branch['created_at'] ?? '-'",'dateTime']],
 'app/Views/admin/users/index.php': [["$user['created_at']",'dateTime']],
 'app/Views/admin/users/activity_logs.php': [["$log['log_time']",'dateTime']],
 'app/Views/admin/branch_inventory/index.php': [["$item['expiration_date']",'date']],
 'app/Views/cashier/sales/history.php': [["$s['sale_date']",'dateTime']],
 'app/Views/admin/sale_correction/edit.php': [["$sale['sale_date']",'dateTime'],["$log['log_time']",'dateTime']],
 'app/Views/admin/stock_transfer/index.php': [["$t['transfer_date']",'date']],
 'app/Views/admin/reports/cash_movements.php': [["$row['date']",'date']],
 'app/Views/exports/cash_movements_pdf.php': [["$row['date']",'date']],
 'app/Views/products/index.php': [['$expiry','date']],
 'app/Views/admin/reports/expiry_report.php': [["$expiry ?? '-'",'date']],
 'app/Views/admin/reports/expiry_report_pdf.php': [["$expiry ?: '-'",'date']],
 'app/Views/admin/reports/reports_pdf.php': [["$reportDateFrom ?? ''",'date'],["$reportDateTo ?? ''",'date'],["$forecastDateFrom ?? ''",'date'],["$forecastDateTo ?? ''",'date'],["$row['report_date'] ?? '-'",'period'],["$row['forecast_date'] ?? '-'",'period']],
 'app/Views/admin/reports/index.php': [["$row['report_date']",'period'],["$row['forecast_date']",'period']],
};
for(const [file, pairs] of Object.entries(mappings)) edit(file,text=>{
  for(const [value,method] of pairs) { const find='esc('+value+')'; if(!text.includes(find))throw Error(file+' missing '+find); text=text.replaceAll(find,'esc('+cls+method+'('+value+'))'); }
  return text;
});
edit('app/Views/stock_logs/pdf.php',text=>text
 .replace("esc($dateFrom ?: 'All Dates')", "$dateFrom ? esc("+cls+"date($dateFrom)) : 'All dates'")
 .replace("esc($dateTo ?: 'All Dates')", "$dateTo ? esc("+cls+"date($dateTo)) : 'All dates'")
 .replace("nl2br(esc(str_replace(' ', \"\\n\", $log['created_at'] ?? '-')))","esc("+cls+"dateTime($log['created_at'] ?? null))"));
edit('app/Views/admin/reports/reports_pdf.php',text=>text.replace("nl2br(esc(str_replace(' ', \"\\n\", $sale['sale_date'] ?? '-')))","esc("+cls+"dateTime($sale['sale_date'] ?? null))"));
edit('app/Views/admin/reports/index.php',text=>text
 .replace("esc($basis[3] . ' to ' . $basis[4])", "esc("+cls+"date($basis[3]) . ' to ' . "+cls+"date($basis[4]))")
 .replaceAll("date('o-\\WW', $periodDate) . ' · ' . "+cls+"date($periodDate) . '–' . "+cls+"date('+6 days', $periodDate)", "'Week of ' . "+cls+"date($periodDate)")
 .replace("'monthly' => "+cls+"date($periodDate)", "'monthly' => date('F Y', $periodDate)")
 .replace(": "+cls+"date($periodDate) . ' monthly equivalent'", ": date('F Y', $periodDate) . ' monthly equivalent'")
 .replace(/json_encode\(array_column\((\$reportHistory|\$forecastHistory|\$futureForecast), '(report_date|forecast_date)'\)\)/g, (_,data,key)=>"json_encode(array_map([\\App\\Libraries\\DisplayDate::class, 'period'], array_column("+data+", '"+key+"')))")
 );
edit('app/Views/layouts/staff.php',text=>text.replace("month:'short'", "month:'long'"));
// Generated PDF metadata already uses words; give it the same date/time separator.
for(const file of ['app/Controllers/Admin/ReportsController.php','app/Controllers/StockLogsController.php']) edit(file,text=>text.replaceAll("date('F d, Y h:i A')", "date('F j, Y \\a\\t g:i A')"));
fs.writeFileSync('writable/receipt-review/date-changed-files.json',JSON.stringify([...new Set(changed)].map(f=>f.replaceAll('\\','/')),null,2));
console.log([...new Set(changed)].length+' files updated.');
