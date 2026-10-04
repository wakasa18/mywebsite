const fs = require('fs');
function edit(path, fn) {const s=fs.readFileSync(path,'utf8').replace(/\r\n/g,'\n');fs.writeFileSync(path,fn(s));}
function replace(s,a,b) {if(!s.includes(a))throw new Error('Missing '+a.slice(0,70));return s.replace(a,b);}
edit('app/Controllers/Admin/ReportsController.php', s => {
 s = replace(s, "            'salesSummary' => $salesSummary,", "            'cashMovements' => (new \\App\\Libraries\\CashMovementReport($this->db))->generate($reportDateFrom, $reportDateTo, $branchId),\n            'salesSummary' => $salesSummary,");
 const a=s.indexOf('    private function refundedCogsProfitJoinSql()');
 const b=s.indexOf('\n    /**',a);
 s=s.slice(0,a)+`    private function refundedCogsProfitJoinSql(): string
    {
        // Only a return to sellable inventory recovers the item's inventory cost.
        $recoveredCost = "CASE WHEN COALESCE(ri.return_condition, 'resellable') = 'resellable' "
            . 'THEN ri.quantity_refunded * sit.cost_price_at_sale ELSE 0 END';
        return '(SELECT ri.sale_item_id, SUM(' . $recoveredCost . ') AS refunded_cogs, '
            . 'SUM(ri.refund_subtotal - (' . $recoveredCost . ')) AS refunded_profit '
            . 'FROM refund_items ri JOIN sale_items sit ON sit.id = ri.sale_item_id '
            . 'GROUP BY ri.sale_item_id) rf';
    }
`+s.slice(b);
 const profitStart=s.indexOf('        $profitSummaryBuilder =');
 const profitEnd=s.indexOf('        $topProductsBuilder =',profitStart);
 let p=s.slice(profitStart,profitEnd).replace("->whereIn('s.status', self::REVENUE_STATUSES)", "->whereIn('s.status', array_merge(self::REVENUE_STATUSES, ['refunded']))");
 s=s.slice(0,profitStart)+p+s.slice(profitEnd);
 const cogsStart=s.indexOf('    private function getCogsPeriodRows(');
 const cogsEnd=s.indexOf('\n    /**',cogsStart);
 p=s.slice(cogsStart,cogsEnd).replace('implode("\',\'", self::REVENUE_STATUSES)', 'implode("\',\'", array_merge(self::REVENUE_STATUSES, [\'refunded\']))');
 s=s.slice(0,cogsStart)+p+s.slice(cogsEnd);
 const marker="        $this->writeCsvRow($output, ['SALES AND PROFIT BY PERIOD']);";
 s=replace(s,marker,`        $this->writeCsvRow($output, ['RECEIPTS AND REFUND PAYOUTS BY DATE']);
        $this->writeCsvRow($output, ['Sales use the sale date; payouts use the refund date. Current corrected invoice values; cash change excluded. Unknown methods are legacy refunds.']);
        $this->writeCsvRow($output, ['Date', 'Payment Method', 'Receipts', 'Refund Payouts', 'Net Movement']);
        foreach ($data['cashMovements']['rows'] as $row) {
            $this->writeCsvRow($output, [$row['date'], $row['method'], $row['receipts'], $row['refunds'], $row['net']]);
        }
        $totals = $data['cashMovements']['totals'];
        $this->writeCsvRow($output, ['Total', '', $totals['receipts'], $totals['refunds'], $totals['net']]);
        $this->writeCsvRow($output, ['Recorded Cash Movement', $totals['cash_net']]);
        $this->writeCsvRow($output, ['Refunds With Unknown Payout Method', $totals['unknown_refunds']]);
        $this->writeCsvRow($output, ['Returned Units by Condition']);
        foreach ($data['cashMovements']['returns'] as $row) {
            $this->writeCsvRow($output, [$row['return_condition'], (int) $row['quantity']]);
        }
        $this->writeCsvRow($output, []);
`+marker);
 return s;
});
edit('app/Views/admin/reports/index.php', s=>replace(s,'<!-- ── Composition & Top Products charts ── -->',"<?= view('admin/reports/cash_movements', ['cashMovements' => $cashMovements ?? null]) ?>\n\n<!-- ── Composition & Top Products charts ── -->"));
edit('app/Views/admin/reports/reports_pdf.php', s=>replace(s,'    <div class="section-title">Sales and Profit by Period</div>',"    <?= view('admin/reports/cash_movements', ['cashMovements' => $cashMovements ?? null]) ?>\n\n    <div class=\"section-title\">Sales and Profit by Period</div>"));
