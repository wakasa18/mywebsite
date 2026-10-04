const fs = require('node:fs');
for (const file of ['app/Views/admin/reports/reports_pdf.php', 'app/Views/admin/reports/expiry_report_pdf.php', 'app/Views/stock_logs/pdf.php']) {
    let text = fs.readFileSync(file, 'utf8');
    const widths = file.includes('stock_logs') ? '\n<style>.w-id{width:4%}.w-product{width:17%}.w-branch{width:9%}.w-user{width:10%}.w-type{width:9%}.w-qty{width:5%}.w-stock{width:6%}.w-remarks{width:22%}.w-date{width:12%} table.data{font-size:9px}</style>' : '';
    text = text.replace(/    <style>[\s\S]*?<\/style>/, "    <?= view('exports/pdf_styles') ?>" + widths);
    text = text.replace("view('admin/reports/cash_movements'", "view('exports/cash_movements_pdf'");
    text = text.replace('<td class="nowrap"><?= esc($sale[\'sale_date\'] ?? \'-\') ?></td>', '<td><?= nl2br(esc(str_replace(\' \', "\\n", $sale[\'sale_date\'] ?? \'-\'))) ?></td>');
    text = text.replace('<td class="nowrap"><?= esc($log[\'created_at\'] ?? \'-\') ?></td>', '<td><?= nl2br(esc(str_replace(\' \', "\\n", $log[\'created_at\'] ?? \'-\'))) ?></td>');
    if (file.includes('reports_pdf')) text = text.replace('<div class="section-title">Business Summary</div>', '<div class="section-title">Business Summary</div>\n    <p class="section-note">Currency: Philippine peso (PHP). Dates and times: Asia/Manila.</p>');
    if (file.includes('expiry')) text = text.replace('    <table class="data">', '    <div class="section-title">Inventory Expiry Details</div>\n    <p class="section-note">Status is evaluated as of the report date. Stock quantities are in each product\'s recorded unit.</p>\n    <table class="data">');
    fs.writeFileSync(file, text);
}
for (const file of ['app/Controllers/Admin/ReportsController.php','app/Controllers/StockLogsController.php']) {
    let text = fs.readFileSync(file, 'utf8');
    const start = text.indexOf('        $safeRow = array_map', text.indexOf('private function writeCsvRow'));
    const end = text.indexOf('        fputcsv($output, $safeRow);', start) + '        fputcsv($output, $safeRow);'.length;
    if (start < 0 || end < start) throw Error('CSV helper not found');
    text = text.slice(0,start) + '        \\App\\Libraries\\CsvExport::writeRow($output, $row, ' + (file.includes('StockLogs') ? '10' : '8') + ');' + text.slice(end);
    text = text.replace(/(\$this->writeCsvRow\(\$output, \['Generated At', [^\r\n]+\);)/g, "$1\n        $this->writeCsvRow($output, ['Timezone', 'Asia/Manila']);");
    if (file.includes('ReportsController')) {
        text = text.replace("$this->writeCsvRow($output, ['SUMMARY']);", "$this->writeCsvRow($output, ['SUMMARY']);\n        $this->writeCsvRow($output, ['Currency', 'PHP']);\n        $this->writeCsvRow($output, ['Metric', 'Value']);");
        text = text.replace("['Date', 'Payment Method', 'Receipts', 'Refund Payouts', 'Net Movement']", "['Date', 'Payment Method', 'Receipts (PHP)', 'Refund Payouts (PHP)', 'Net Movement (PHP)']");
        text = text.replace("[$row['date'], $row['method'], $row['receipts'], $row['refunds'], $row['net']]", "[$row['date'], strtoupper($row['method']), (float) $row['receipts'], (float) $row['refunds'], (float) $row['net']]");
        text = text.replace("['Total', '', $totals['receipts'], $totals['refunds'], $totals['net']]", "['Total', '', (float) $totals['receipts'], (float) $totals['refunds'], (float) $totals['net']]");
        text = text.replace("['Recorded Cash Movement', $totals['cash_net']]", "['Recorded Cash Movement (PHP)', (float) $totals['cash_net']]");
        text = text.replace("['Refunds With Unknown Payout Method', $totals['unknown_refunds']]", "['Refunds With Unknown Payout Method (PHP)', (float) $totals['unknown_refunds']]");
        text = text.replace("['Returned Units by Condition']);", "['RETURNED UNITS BY CONDITION']);\n        $this->writeCsvRow($output, ['Condition', 'Units']);");
        text = text.replace("['Period', 'Transactions', 'Gross Sales', 'Discount', 'Net Sales', 'Product Cost', 'Gross Profit']", "['Period', 'Transactions', 'Gross Sales (PHP)', 'Discount (PHP)', 'Net Sales (PHP)', 'Product Cost (PHP)', 'Gross Profit (PHP)']");
        text = text.replace("['Product', 'Units Sold', 'Net Product Sales']", "['Product', 'Units Sold', 'Net Product Sales (PHP)']");
        text = text.replace("'Remaining Total', 'Refunded Amount'", "'Remaining Total (PHP)', 'Refunded Amount (PHP)'");
        text = text.replace("['Period', 'Actual Net Sales', 'Estimated Sales']", "['Period', 'Actual Net Sales (PHP)', 'Estimated Sales (PHP)']");
        text = text.replace("['Period', 'Estimated Sales']", "['Period', 'Estimated Sales (PHP)']");
    }
    text = text.replace("        $canvas->page_text(\n", "        $canvas->page_text(\n"); // Preserve the existing page counter.
    const footer = "        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');";
    text = text.replace(footer, footer + "\n        $canvas->page_text(34, $canvas->get_height() - 24, 'PHARXMACO  |  " + (file.includes('StockLogs') ? 'Stock movement report' : 'Management report') + "  |  Asia/Manila', $font, 7, [0.4, 0.43, 0.5]);");
    fs.writeFileSync(file, text);
}
