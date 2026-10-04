<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Database;
use Dompdf\Dompdf;
use App\Libraries\PdfFactory;
use App\Models\ReportExportModel;
use App\Models\BranchModel;
use App\Libraries\ReorderForecastService;

class ReportsController extends BaseController
{
    protected $db;
    protected $reportExportModel;
    protected $branchModel;
    protected $reorderForecastService;
    private array $filterNotices = [];

    /**
     * Sale statuses that represent real, counted revenue. 'void' and fully
     * 'refunded' sales contribute $0 in practice and must not appear in
     * financial totals or the forecast time series. 'partially_refunded'
     * sales DO count, but only for their remaining (post-refund) amount —
     * see refundedAmountJoinSql() / refundedCogsProfitJoinSql() below.
     */
    private const REVENUE_STATUSES = ['completed', 'partially_refunded'];
    private const FORECAST_LEVEL_WEIGHT = 0.30;
    private const FORECAST_TREND_WEIGHT = 0.20;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->reportExportModel = new ReportExportModel();
        $this->branchModel = new BranchModel();
        $this->reorderForecastService = new ReorderForecastService();
    }

    /**
     * Raw subquery joined as `ri`, aggregating the net peso amount actually
     * returned to customers after item discounts. Subtracting
     * ri.refunded_amount from a sale's total/final amount yields the
     * true remaining revenue for a partially refunded sale, and is a
     * harmless no-op (COALESCE to 0) for sales with no refund rows.
     */
    private function refundedAmountJoinSql(): string
    {
        return '(SELECT sale_id, SUM(refund_subtotal) AS refunded_amount '
            . 'FROM refund_items GROUP BY sale_id) ri';
    }

    /**
     * Raw subquery joined as `rf`, aggregating refunded cost-of-goods and
     * the profit reduction caused by each refund. The profit removed is the
     * net refund amount less the cost of the returned units. Prorating the original
     * item profit was incorrect when an item had a discount.
     */
    private function refundedCogsProfitJoinSql(): string
    {
        // Only a return to sellable inventory recovers the item's inventory cost.
        $recoveredCost = "CASE WHEN COALESCE(ri.return_condition, 'resellable') = 'resellable' "
            . 'THEN ri.quantity_refunded * sit.cost_price_at_sale ELSE 0 END';
        return '(SELECT ri.sale_item_id, SUM(' . $recoveredCost . ') AS refunded_cogs, '
            . 'SUM(ri.refund_subtotal - (' . $recoveredCost . ')) AS refunded_profit '
            . 'FROM refund_items ri JOIN sale_items sit ON sit.id = ri.sale_item_id '
            . 'GROUP BY ri.sale_item_id) rf';
    }

    /**
     * Raw subquery joined as `rq`, aggregating refunded quantity and
     * peso amount per sale_item — used for the Top Products list so
     * returned units don't inflate a product's apparent sales rank.
     */
    private function refundedQtyJoinSql(): string
    {
        return '(SELECT sale_item_id, SUM(quantity_refunded) AS qty_refunded, '
            . 'SUM(refund_subtotal) AS refunded_subtotal '
            . 'FROM refund_items GROUP BY sale_item_id) rq';
    }

    /**
     * The raw grouping key for a given forecast granularity, computed
     * directly off `s.sale_date`. Used ONLY inside an inner derived-table
     * subquery, materialized there as its own plain column ("grp") —
     * never referenced live against the base table from an outer query.
     *
     * Why: under MySQL's ONLY_FULL_GROUP_BY (the MySQL 8 default — off
     * by default on MariaDB, which is why this didn't surface in earlier
     * testing), wrapping a live expression like YEARWEEK(s.sale_date, 3)
     * inside another function in the SELECT list gets rejected as "not
     * functionally dependent on GROUP BY", even when it's textually the
     * exact same expression used in GROUP BY, and even more so when that
     * same alias is then referenced in ORDER BY. A derived table's plain
     * output column doesn't carry that baggage — MySQL treats a bare
     * column from a subquery as an ordinary column, so formatting it
     * further (period_key) or grouping/ordering by it is unrestricted.
     */
    private function periodGroupExprSql(string $forecastType): string
    {
        switch ($forecastType) {
            case 'weekly':
                // ISO-8601 (Monday start, week 1 = first week with 4+ days
                // in the new year) AND correctly paired with its own
                // week-year — see periodKeyExprSql() for why this matters
                // at year boundaries.
                return 'YEARWEEK(s.sale_date, 3)';
            case 'monthly':
                return "DATE_FORMAT(s.sale_date, '%Y-%m')";
            case 'daily':
            default:
                return 'DATE(s.sale_date)';
        }
    }

    /**
     * Formats the derived table's materialized `t.grp` column (see
     * periodGroupExprSql()) into the display/lookup key. Only `weekly`
     * needs reformatting (YEARWEEK's packed integer -> "YYYY-Www"); the
     * rest are already in their final display form.
     */
    private function periodKeyExprSql(string $forecastType): string
    {
        if ($forecastType === 'weekly') {
            return "CONCAT(LEFT(t.grp, 4), '-W', LPAD(RIGHT(t.grp, 2), 2, '0'))";
        }

        return 't.grp';
    }

    /**
     * Reads a single query-string value. Array/object inputs are rejected so
     * malformed URLs cannot trigger type errors in date and filter handling.
     */
    private function queryValue(string $key): ?string
    {
        $value = $this->request->getGet($key);
        if ($value === null) {
            return null;
        }

        if (!is_scalar($value)) {
            $this->filterNotices[] = "The {$key} filter was invalid and was ignored.";
            return null;
        }

        return trim((string) $value);
    }

    /**
     * Normalizes a submitted date range. Invalid or future dates are corrected
     * safely, while human-readable notices are collected for the page so a
     * changed filter is never silently ignored.
     */
    private function normalizeDateRange(
        ?string $dateFrom,
        ?string $dateTo,
        bool $limitToTwelveMonths = false,
        string $label = 'Date range'
    ): array {
        $defaultFrom = date('Y-m-d', strtotime('-29 days'));
        $defaultTo = date('Y-m-d');
        $today = date('Y-m-d');

        if (!$this->isValidDate($dateFrom)) {
            if (!empty($dateFrom)) {
                $this->filterNotices[] = "{$label}: the start date was invalid, so the default was used.";
            }
            $dateFrom = $defaultFrom;
        }

        if (!$this->isValidDate($dateTo)) {
            if (!empty($dateTo)) {
                $this->filterNotices[] = "{$label}: the end date was invalid, so today was used.";
            }
            $dateTo = $defaultTo;
        }

        if ($dateFrom > $today) {
            $dateFrom = $today;
            $this->filterNotices[] = "{$label}: the start date was in the future and was changed to today.";
        }

        if ($dateTo > $today) {
            $dateTo = $today;
            $this->filterNotices[] = "{$label}: the end date was in the future and was changed to today.";
        }

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            $this->filterNotices[] = "{$label}: the start and end dates were reversed, so they were swapped.";
        }

        if ($limitToTwelveMonths) {
            $earliestAllowed = (new \DateTimeImmutable($dateTo))
                ->modify('-12 months')
                ->format('Y-m-d');

            if ($dateFrom < $earliestAllowed) {
                $dateFrom = $earliestAllowed;
                $this->filterNotices[] = "{$label}: the range was shortened to the latest 12 months.";
            }
        }

        return [$dateFrom, $dateTo];
    }

    private function isValidDate(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        $date = \DateTime::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    /**
     * Dates used by sales summaries, profit, top products, recent sales,
     * and the non-forecast report chart.
     */
    private function resolveReportDateRange(): array
    {
        $dateFrom = $this->queryValue('report_date_from');
        $dateTo = $this->queryValue('report_date_to');

        // Keep old bookmarked/export URLs working.
        $dateFrom = $dateFrom ?? $this->queryValue('date_from');
        $dateTo = $dateTo ?? $this->queryValue('date_to');

        return $this->normalizeDateRange($dateFrom, $dateTo, false, 'Reports date range');
    }

    /**
     * Dates used only by sales forecasting and product reorder suggestions.
     * When no separate forecast dates were submitted, use the report dates
     * as the initial values so the first page load keeps the previous behavior.
     */
    private function resolveForecastDateRange(string $fallbackFrom, string $fallbackTo): array
    {
        $dateFrom = $this->queryValue('forecast_date_from') ?? $fallbackFrom;
        $dateTo = $this->queryValue('forecast_date_to') ?? $fallbackTo;

        return $this->normalizeDateRange($dateFrom, $dateTo, true, 'Forecasting date range');
    }

    /**
     * Accepts only an active branch. Invalid or inactive IDs fall back to
     * All Branches instead of producing an empty report with a misleading
     * branch label.
     */
    private function resolveBranchId(): string
    {
        if (session('role') === 'cashier') {
            $assignedBranchId = (int) session('branch_id');
            if ($assignedBranchId < 1) {
                $this->filterNotices[] = 'No active branch is assigned to this cashier, so no branch records are available.';
                return '0';
            }

            $assignedBranch = $this->db->table('branches')
                ->select('id')
                ->where('id', $assignedBranchId)
                ->where('status', 'active')
                ->get()
                ->getRowArray();

            if (!$assignedBranch) {
                $this->filterNotices[] = 'The cashier assigned branch is unavailable, so no branch records are available.';
                return '0';
            }

            return (string) $assignedBranchId;
        }

        $branchId = (string) ($this->queryValue('branch_id') ?? '');
        if ($branchId === '') {
            return '';
        }

        if (!ctype_digit($branchId) || (int) $branchId < 1) {
            $this->filterNotices[] = 'The selected branch was invalid, so All Branches was used.';
            return '';
        }

        $branch = $this->db->table('branches')
            ->select('id')
            ->where('id', (int) $branchId)
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        if (!$branch) {
            $this->filterNotices[] = 'The selected branch is unavailable, so All Branches was used.';
            return '';
        }

        return (string) ((int) $branchId);
    }

    private function normalizePeriodType(?string $type, string $label = 'Report interval'): string
    {
        if ($type === null || $type === '') {
            return 'daily';
        }

        if (!in_array($type, ['daily', 'weekly', 'monthly'], true)) {
            $this->filterNotices[] = "{$label} was invalid, so Daily was used.";
            return 'daily';
        }

        return $type;
    }


    /**
     * Writes a CSV row while neutralizing spreadsheet formulas in text cells.
     * This prevents names, SKUs, or invoice values beginning with =, +, -, or
     * @ from being executed when the CSV is opened in spreadsheet software.
     */
    private function writeCsvRow($output, array $row): void
    {
        \App\Libraries\CsvExport::writeRow($output, $row, 8);
    }

    private function clearOutputBuffers(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    private function prepareCsvDownload(string $filename): void
    {
        $this->clearOutputBuffers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private function safeFilenamePart(string $value, string $fallback = 'all_branches'): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $value) ?? '';
        $safe = trim($safe, '_');
        return strtolower($safe !== '' ? $safe : $fallback);
    }

    private function generatedBy(): string
    {
        return (string) (session('full_name') ?: session('username') ?: 'System User');
    }

    private function addPdfPageNumbers(Dompdf $dompdf): void
    {
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(34, $canvas->get_height() - 24, 'PHARXMACO  |  Management report  |  Asia/Manila', $font, 7, [0.4, 0.43, 0.5]);
        $canvas->page_text(
            $canvas->get_width() - 115,
            $canvas->get_height() - 24,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            8,
            [0.35, 0.35, 0.35]
        );
    }

    private function expiryStatusFilterLabel(string $status): string
    {
        return [
            'all' => 'All Statuses',
            'expired' => 'Expired',
            'near' => 'Near Expiry',
            'good' => 'Good',
            'missing' => 'No Expiry Date',
        ][$status] ?? 'All Statuses';
    }

    private function expirySummary(array $products, string $today, string $nearDate): array
    {
        $summary = ['total' => count($products), 'expired' => 0, 'near' => 0, 'good' => 0, 'missing' => 0];
        foreach ($products as $product) {
            $expiry = $product['expiration_date'] ?? null;
            if (empty($expiry)) {
                $summary['missing']++;
            } elseif ($expiry < $today) {
                $summary['expired']++;
            } elseif ($expiry <= $nearDate) {
                $summary['near']++;
            } else {
                $summary['good']++;
            }
        }
        return $summary;
    }

    public function index()
    {
        [$reportDateFrom, $reportDateTo] = $this->resolveReportDateRange();
        [$forecastDateFrom, $forecastDateTo] = $this->resolveForecastDateRange($reportDateFrom, $reportDateTo);
        $branchId = $this->resolveBranchId();
        $reportType = $this->normalizePeriodType($this->queryValue('report_type'), 'Reports interval');
        $forecastType = $this->normalizePeriodType($this->queryValue('forecast_type'), 'Forecast interval');

        $data = $this->buildReportData(
            $reportDateFrom,
            $reportDateTo,
            $forecastDateFrom,
            $forecastDateTo,
            $branchId,
            $reportType,
            $forecastType
        );

        $exportHistoryBuilder = $this->db->table('report_exports re')
            ->select('re.*, u.full_name, b.branch_name')
            ->join('users u', 'u.id = re.user_id', 'left')
            ->join('branches b', 'b.id = re.branch_id', 'left');

        if (!empty($branchId)) {
            $exportHistoryBuilder->where('re.branch_id', $branchId);
        }

        $exportHistory = $exportHistoryBuilder
            ->orderBy('re.id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return view('admin/reports/index', array_merge($data, [
            'exportHistory' => $exportHistory,
            'filterNotices' => array_values(array_unique($this->filterNotices)),
        ]));
    }

    public function exportExcel()
    {
        if ($this->isExportProbe()) {
            return $this->response->setStatusCode(204);
        }

        [$reportDateFrom, $reportDateTo] = $this->resolveReportDateRange();
        [$forecastDateFrom, $forecastDateTo] = $this->resolveForecastDateRange($reportDateFrom, $reportDateTo);
        $branchId = $this->resolveBranchId();
        $reportType = $this->normalizePeriodType($this->queryValue('report_type'), 'Reports interval');
        $forecastType = $this->normalizePeriodType($this->queryValue('forecast_type'), 'Forecast interval');

        $data = $this->buildReportData(
            $reportDateFrom,
            $reportDateTo,
            $forecastDateFrom,
            $forecastDateTo,
            $branchId,
            $reportType,
            $forecastType
        );

        $this->recordReportExport('excel', $data);

        $filename = 'sales_report_'
            . $this->safeFilenamePart((string) $data['branchName']) . '_'
            . $reportDateFrom . '_to_' . $reportDateTo . '_'
            . date('Ymd_His') . '.csv';
        $this->prepareCsvDownload($filename);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            throw new \RuntimeException('Unable to open the report export stream.');
        }
        fwrite($output, "\xEF\xBB\xBF");

        $this->writeCsvRow($output, ['Pharxmaco Drugstore - Sales and Forecast Report']);
        $this->writeCsvRow($output, ['Generated By', $this->generatedBy()]);
        $this->writeCsvRow($output, ['Generated At', date('Y-m-d H:i:s')]);
        $this->writeCsvRow($output, ['Timezone', 'Asia/Manila']);
        $this->writeCsvRow($output, ['Branch', $data['branchName']]);
        $this->writeCsvRow($output, ['Reports Date Range', $reportDateFrom . ' to ' . $reportDateTo]);
        $this->writeCsvRow($output, ['Reports Grouping', ucfirst($data['reportType'])]);
        $this->writeCsvRow($output, ['Forecast History Range', $forecastDateFrom . ' to ' . $forecastDateTo]);
        $this->writeCsvRow($output, ['Forecast Grouping', ucfirst($data['forecastType'])]);

        if (!empty($this->filterNotices)) {
            $this->writeCsvRow($output, []);
            $this->writeCsvRow($output, ['FILTER NOTES']);
            foreach (array_unique($this->filterNotices) as $notice) {
                $this->writeCsvRow($output, [$notice]);
            }
        }

        $this->writeCsvRow($output, []);
        $this->writeCsvRow($output, ['SUMMARY']);
        $this->writeCsvRow($output, ['Currency', 'PHP']);
        $this->writeCsvRow($output, ['Metric', 'Value']);
        $this->writeCsvRow($output, ['Gross Sales', round((float) ($data['salesSummary']['gross_sales'] ?? 0), 2)]);
        $this->writeCsvRow($output, ['Net Sales', round((float) ($data['salesSummary']['net_sales'] ?? 0), 2)]);
        $this->writeCsvRow($output, ['Total Discount', round((float) ($data['salesSummary']['total_discount'] ?? 0), 2)]);
        $this->writeCsvRow($output, ['Product Cost', round((float) ($data['profitSummary']['cogs'] ?? 0), 2)]);
        $this->writeCsvRow($output, ['Gross Profit', round((float) ($data['profitSummary']['gross_profit'] ?? 0), 2)]);
        $this->writeCsvRow($output, ['Total Transactions', (int) ($data['salesSummary']['total_transactions'] ?? 0)]);
        $this->writeCsvRow($output, ['Average Sale', round((float) ($data['averageTransactionValue'] ?? 0), 2)]);
        $this->writeCsvRow($output, ['Gross Profit Margin (%)', round((float) ($data['grossProfitMargin'] ?? 0), 2)]);
        $this->writeCsvRow($output, ['Next Estimated Sales', round((float) ($data['nextForecastValue'] ?? 0), 2)]);
        $this->writeCsvRow($output, ['Low-Stock Products', count($data['reorderForecast'] ?? [])]);

        $this->writeCsvRow($output, []);
        $this->writeCsvRow($output, ['RECEIPTS AND REFUND PAYOUTS BY DATE']);
        $this->writeCsvRow($output, ['Sales use the sale date; payouts use the refund date. Current corrected invoice values; cash change excluded. Unknown methods are legacy refunds.']);
        $this->writeCsvRow($output, ['Date', 'Payment Method', 'Receipts (PHP)', 'Refund Payouts (PHP)', 'Net Movement (PHP)']);
        foreach ($data['cashMovements']['rows'] as $row) {
            $this->writeCsvRow($output, [$row['date'], strtoupper($row['method']), (float) $row['receipts'], (float) $row['refunds'], (float) $row['net']]);
        }
        $totals = $data['cashMovements']['totals'];
        $this->writeCsvRow($output, ['Total', '', (float) $totals['receipts'], (float) $totals['refunds'], (float) $totals['net']]);
        $this->writeCsvRow($output, ['Recorded Cash Movement (PHP)', (float) $totals['cash_net']]);
        $this->writeCsvRow($output, ['Refunds With Unknown Payout Method (PHP)', (float) $totals['unknown_refunds']]);
        $this->writeCsvRow($output, ['RETURNED UNITS BY CONDITION']);
        $this->writeCsvRow($output, ['Condition', 'Units']);
        foreach ($data['cashMovements']['returns'] as $row) {
            $this->writeCsvRow($output, [$row['return_condition'], (int) $row['quantity']]);
        }
        $this->writeCsvRow($output, []);
        $this->writeCsvRow($output, ['SALES AND PROFIT BY PERIOD']);
        $this->writeCsvRow($output, ['Period', 'Transactions', 'Gross Sales (PHP)', 'Discount (PHP)', 'Net Sales (PHP)', 'Product Cost (PHP)', 'Gross Profit (PHP)']);
        if (!empty($data['reportHistory'])) {
            foreach ($data['reportHistory'] as $row) {
                $this->writeCsvRow($output, [
                    $row['report_date'] ?? '',
                    (int) ($row['total_transactions'] ?? 0),
                    round((float) ($row['gross_sales'] ?? 0), 2),
                    round((float) ($row['total_discount'] ?? 0), 2),
                    round((float) ($row['net_sales'] ?? 0), 2),
                    round((float) ($row['cogs'] ?? 0), 2),
                    round((float) ($row['gross_profit'] ?? 0), 2),
                ]);
            }
        } else {
            $this->writeCsvRow($output, ['No report breakdown found']);
        }

        $this->writeCsvRow($output, []);
        $this->writeCsvRow($output, ['BEST-SELLING PRODUCTS']);
        $this->writeCsvRow($output, ['Product', 'Units Sold', 'Net Product Sales (PHP)']);
        if (!empty($data['topProducts'])) {
            foreach ($data['topProducts'] as $item) {
                $this->writeCsvRow($output, [
                    $item['product_name_snapshot'] ?? '-',
                    (int) ($item['total_qty_sold'] ?? 0),
                    round((float) ($item['total_sales'] ?? 0), 2),
                ]);
            }
        } else {
            $this->writeCsvRow($output, ['No sales data found']);
        }

        $this->writeCsvRow($output, []);
        $this->writeCsvRow($output, ['RECENT SALES']);
        $this->writeCsvRow($output, ['Invoice No', 'Branch', 'Cashier', 'Status', 'Remaining Total (PHP)', 'Refunded Amount (PHP)', 'Sale Date']);
        if (!empty($data['recentSales'])) {
            foreach ($data['recentSales'] as $sale) {
                $this->writeCsvRow($output, [
                    $sale['invoice_no'] ?? '-',
                    $sale['branch_name'] ?? '-',
                    $sale['full_name'] ?? '-',
                    ucwords(str_replace('_', ' ', (string) ($sale['status'] ?? '-'))),
                    round((float) ($sale['remaining_total'] ?? $sale['final_total'] ?? 0), 2),
                    round((float) ($sale['refunded_amount'] ?? 0), 2),
                    $sale['sale_date'] ?? '',
                ]);
            }
        } else {
            $this->writeCsvRow($output, ['No recent sales found']);
        }

        $this->writeCsvRow($output, []);
        $this->writeCsvRow($output, ['PAST SALES AND ESTIMATES']);
        $this->writeCsvRow($output, ['Period', 'Actual Net Sales (PHP)', 'Estimated Sales (PHP)']);
        if (!empty($data['forecastHistory'])) {
            foreach ($data['forecastHistory'] as $row) {
                $this->writeCsvRow($output, [
                    $row['report_date'] ?? '',
                    round((float) ($row['net_sales'] ?? 0), 2),
                    round((float) ($row['forecast'] ?? 0), 2),
                ]);
            }
        } else {
            $this->writeCsvRow($output, ['No sales history found']);
        }

        $this->writeCsvRow($output, []);
        $this->writeCsvRow($output, ['ESTIMATED FUTURE SALES']);
        $this->writeCsvRow($output, ['Period', 'Estimated Sales (PHP)']);
        if (!empty($data['futureForecast'])) {
            foreach ($data['futureForecast'] as $row) {
                $this->writeCsvRow($output, [
                    $row['forecast_date'] ?? '',
                    round((float) ($row['forecast_value'] ?? 0), 2),
                ]);
            }
        } else {
            $this->writeCsvRow($output, ['No future sales estimate found']);
        }

        $this->writeCsvRow($output, []);
        $this->writeCsvRow($output, ['RESTOCK SUGGESTIONS']);
        $this->writeCsvRow($output, ['Product', 'SKU', 'Branch', 'Current Stock', 'Reorder Level', 'Estimated Days Left', 'Suggested Order Quantity', 'Priority']);
        if (!empty($data['reorderForecast'])) {
            foreach ($data['reorderForecast'] as $row) {
                $this->writeCsvRow($output, [
                    $row['product_name'] ?? '-',
                    $row['sku'] ?? '-',
                    $row['branch_name'] ?? '-',
                    (int) ($row['stock'] ?? 0),
                    (int) ($row['reorder_level'] ?? 0),
                    isset($row['days_left']) ? (int) $row['days_left'] : 'Review manually',
                    (int) ($row['suggested_qty'] ?? 0),
                    ucwords(str_replace('_', ' ', (string) ($row['urgency'] ?? '-'))),
                ]);
            }
        } else {
            $this->writeCsvRow($output, ['No low-stock products found']);
        }

        fclose($output);
        exit;
    }

    public function exportPdf()
    {
        if ($this->isExportProbe()) {
            return $this->response->setStatusCode(204);
        }

        [$reportDateFrom, $reportDateTo] = $this->resolveReportDateRange();
        [$forecastDateFrom, $forecastDateTo] = $this->resolveForecastDateRange($reportDateFrom, $reportDateTo);
        $branchId = $this->resolveBranchId();
        $reportType = $this->normalizePeriodType($this->queryValue('report_type'), 'Reports interval');
        $forecastType = $this->normalizePeriodType($this->queryValue('forecast_type'), 'Forecast interval');

        $data = $this->buildReportData(
            $reportDateFrom,
            $reportDateTo,
            $forecastDateFrom,
            $forecastDateTo,
            $branchId,
            $reportType,
            $forecastType
        );

        $data['generatedBy'] = $this->generatedBy();
        $data['generatedAt'] = date('F d, Y h:i A');
        $data['filterNotices'] = array_values(array_unique($this->filterNotices));
        $html = view('admin/reports/reports_pdf', $data);

        $dompdf = PdfFactory::create();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $this->addPdfPageNumbers($dompdf);

        $this->recordReportExport('pdf', $data);

        $filename = 'sales_report_'
            . $this->safeFilenamePart((string) $data['branchName']) . '_'
            . $reportDateFrom . '_to_' . $reportDateTo . '_'
            . date('Ymd_His') . '.pdf';

        $this->clearOutputBuffers();
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    private function isExportProbe(): bool
    {
        return $this->request->is('head')
            || stripos($this->request->getHeaderLine('Sec-Purpose'), 'prefetch') !== false
            || stripos($this->request->getHeaderLine('Purpose'), 'prefetch') !== false;
    }

    private function recordReportExport(string $type, array $data): void
    {
        $requestId = $this->request->getGet('export_request');
        $hasRequestId = is_string($requestId) && preg_match('/^[a-f0-9]{32}$/D', $requestId) === 1;
        $identity = [
            'user' => session('user_id'),
            'type' => $type,
            'branch' => $data['branchId'] ?: null,
            'from' => $data['reportDateFrom'],
            'to' => $data['reportDateTo'],
            'interval' => $data['reportType'],
            'forecast_from' => $data['forecastDateFrom'],
            'forecast_to' => $data['forecastDateTo'],
            'forecast_interval' => $data['forecastType'],
            'request' => $hasRequestId ? $requestId : null,
        ];
        $key = hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR));
        $now = time();
        // File-backed sessions serialize requests from the same browser. Keep the
        // session open until the insert and receipt are both saved; do not close early.
        $receipts = session('report_export_receipts') ?? [];
        $receipts = array_filter($receipts, static fn ($at) => $at > $now - 3600);
        // Old links/no JavaScript receive a short double-request guard. A fresh
        // click token records a new intentional export, even with identical filters.
        $window = $hasRequestId ? 3600 : 5;
        if (isset($receipts[$key]) && $receipts[$key] > $now - $window) {
            return;
        }

        $result = $this->reportExportModel->insert([
            'user_id' => session('user_id'),
            'branch_id' => $data['branchId'] ?: null,
            'export_type' => $type,
            'date_from' => $data['reportDateFrom'],
            'date_to' => $data['reportDateTo'],
            'gross_sales' => (float) ($data['salesSummary']['gross_sales'] ?? 0),
            'net_sales' => (float) ($data['salesSummary']['net_sales'] ?? 0),
            'total_discount' => (float) ($data['salesSummary']['total_discount'] ?? 0),
            'cogs' => (float) ($data['profitSummary']['cogs'] ?? 0),
            'gross_profit' => (float) ($data['profitSummary']['gross_profit'] ?? 0),
            'total_transactions' => (int) ($data['salesSummary']['total_transactions'] ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($result === false) {
            throw new \RuntimeException('Unable to record the report export. Please try again.');
        }
        $receipts[$key] = $now;
        session()->set('report_export_receipts', array_slice($receipts, -100, null, true));
    }

    private function normalizeExpiryStatus(?string $status): string
    {
        if ($status === null || $status === '') {
            return 'all';
        }

        if (!in_array($status, ['all', 'expired', 'near', 'good', 'missing'], true)) {
            $this->filterNotices[] = 'The expiry status filter was invalid, so All Statuses was used.';
            return 'all';
        }

        return $status;
    }

    private function expiryProductsBuilder(string $branchId, string $status, string $today, string $nearDate)
    {
        $builder = $this->db->table('branch_products')
            ->select('
                branch_products.*,
                products.product_name,
                products.sku,
                products.manufacturer,
                branches.branch_name,
                suppliers.supplier_name
            ')
            ->join('products', 'products.id = branch_products.product_id')
            ->join('branches', 'branches.id = branch_products.branch_id')
            ->join('suppliers', 'suppliers.id = products.supplier_id', 'left')
            ->where('products.status', 'active')
            ->where('branch_products.status', 'active')
            ->where('branches.status', 'active');

        if ($branchId !== '') {
            $builder->where('branch_products.branch_id', (int) $branchId);
        }

        switch ($status) {
            case 'expired':
                $builder->where('branch_products.expiration_date IS NOT NULL', null, false)
                    ->where('branch_products.expiration_date <', $today);
                break;
            case 'near':
                $builder->where('branch_products.expiration_date >=', $today)
                    ->where('branch_products.expiration_date <=', $nearDate);
                break;
            case 'good':
                $builder->where('branch_products.expiration_date >', $nearDate);
                break;
            case 'missing':
                $builder->where('branch_products.expiration_date IS NULL', null, false);
                break;
        }

        return $builder
            ->orderBy('branch_products.expiration_date IS NULL', 'ASC', false)
            ->orderBy('branch_products.expiration_date', 'ASC')
            ->orderBy('products.product_name', 'ASC');
    }

    private function expiryStatusLabel(?string $expiry, string $today, string $nearDate): string
    {
        if (empty($expiry)) {
            return 'No Expiry Date';
        }
        if ($expiry < $today) {
            return 'Expired';
        }
        if ($expiry <= $nearDate) {
            return 'Near Expiry';
        }

        return 'Good';
    }

    public function expiryReport()
    {
        $branchId = $this->resolveBranchId();
        $status = $this->normalizeExpiryStatus($this->queryValue('status'));
        $today = date('Y-m-d');
        $nearExpiryDays = 30;
        $nearDate = date('Y-m-d', strtotime('+' . $nearExpiryDays . ' days'));
        $perPage = 10;

        $builder = $this->expiryProductsBuilder($branchId, $status, $today, $nearDate);
        $pager = service('pager');
        $page = max(1, (int) ($this->queryValue('page') ?? 1));
        $total = $builder->countAllResults(false);
        $products = $builder
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        $pager->makeLinks($page, $perPage, $total);

        $branchesBuilder = $this->db->table('branches')
            ->where('status', 'active')
            ->orderBy('branch_name', 'ASC');
        if (session('role') === 'cashier') {
            $branchesBuilder->where('id', (int) $branchId);
        }
        $branches = $branchesBuilder->get()->getResultArray();

        return view('admin/reports/expiry_report', [
            'products' => $products,
            'branches' => $branches,
            'filterBranch' => $branchId,
            'filterStatus' => $status,
            'today' => $today,
            'nearDate' => $nearDate,
            'nearExpiryDays' => $nearExpiryDays,
            'pager' => $pager,
            'filterNotices' => array_values(array_unique($this->filterNotices)),
            'isCashier' => session('role') === 'cashier',
        ]);
    }

    public function exportExpiryExcel()
    {
        $branchId = $this->resolveBranchId();
        $status = $this->normalizeExpiryStatus($this->queryValue('status'));
        $today = date('Y-m-d');
        $nearExpiryDays = 30;
        $nearDate = date('Y-m-d', strtotime('+' . $nearExpiryDays . ' days'));
        $products = $this->expiryProductsBuilder($branchId, $status, $today, $nearDate)
            ->get()
            ->getResultArray();

        $branchName = $this->expiryBranchName($branchId);
        $summary = $this->expirySummary($products, $today, $nearDate);
        $filename = 'expiry_report_'
            . $this->safeFilenamePart($branchName) . '_'
            . $this->safeFilenamePart($this->expiryStatusFilterLabel($status), 'all_statuses') . '_'
            . date('Ymd_His') . '.csv';
        $this->prepareCsvDownload($filename);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            throw new \RuntimeException('Unable to open the expiry export stream.');
        }
        fwrite($output, "\xEF\xBB\xBF");

        $this->writeCsvRow($output, ['Pharxmaco Drugstore - Expiry Report']);
        $this->writeCsvRow($output, ['Generated By', $this->generatedBy()]);
        $this->writeCsvRow($output, ['Generated At', date('Y-m-d H:i:s')]);
        $this->writeCsvRow($output, ['Timezone', 'Asia/Manila']);
        $this->writeCsvRow($output, ['Branch', $branchName]);
        $this->writeCsvRow($output, ['Status Filter', $this->expiryStatusFilterLabel($status)]);
        $this->writeCsvRow($output, ['Near-Expiry Window', $nearExpiryDays . ' days']);
        $this->writeCsvRow($output, ['Total Records', $summary['total']]);
        $this->writeCsvRow($output, ['Expired', $summary['expired']]);
        $this->writeCsvRow($output, ['Near Expiry', $summary['near']]);
        $this->writeCsvRow($output, ['Good', $summary['good']]);
        $this->writeCsvRow($output, ['No Expiry Date', $summary['missing']]);

        if (!empty($this->filterNotices)) {
            $this->writeCsvRow($output, []);
            $this->writeCsvRow($output, ['FILTER NOTES']);
            foreach (array_unique($this->filterNotices) as $notice) {
                $this->writeCsvRow($output, [$notice]);
            }
        }

        $this->writeCsvRow($output, []);
        $this->writeCsvRow($output, ['Product', 'SKU', 'Manufacturer', 'Supplier', 'Branch', 'Stock', 'Expiration Date', 'Status']);

        foreach ($products as $product) {
            $expiry = $product['expiration_date'] ?? null;
            $this->writeCsvRow($output, [
                $product['product_name'] ?? '-',
                $product['sku'] ?? '-',
                $product['manufacturer'] ?? '-',
                $product['supplier_name'] ?? '-',
                $product['branch_name'] ?? '-',
                (int) ($product['stock'] ?? 0),
                $expiry ?: '',
                $this->expiryStatusLabel($expiry, $today, $nearDate),
            ]);
        }

        fclose($output);
        exit;
    }

    public function exportExpiryPdf()
    {
        $branchId = $this->resolveBranchId();
        $status = $this->normalizeExpiryStatus($this->queryValue('status'));
        $today = date('Y-m-d');
        $nearExpiryDays = 30;
        $nearDate = date('Y-m-d', strtotime('+' . $nearExpiryDays . ' days'));
        $products = $this->expiryProductsBuilder($branchId, $status, $today, $nearDate)
            ->get()
            ->getResultArray();

        $branchName = $this->expiryBranchName($branchId);
        $summary = $this->expirySummary($products, $today, $nearDate);
        $html = view('admin/reports/expiry_report_pdf', [
            'products' => $products,
            'branchName' => $branchName,
            'filterStatusLabel' => $this->expiryStatusFilterLabel($status),
            'today' => $today,
            'nearDate' => $nearDate,
            'nearExpiryDays' => $nearExpiryDays,
            'summary' => $summary,
            'generatedBy' => $this->generatedBy(),
            'generatedAt' => date('F d, Y h:i A'),
            'filterNotices' => array_values(array_unique($this->filterNotices)),
        ]);

        $dompdf = PdfFactory::create();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $this->addPdfPageNumbers($dompdf);

        $filename = 'expiry_report_'
            . $this->safeFilenamePart($branchName) . '_'
            . $this->safeFilenamePart($this->expiryStatusFilterLabel($status), 'all_statuses') . '_'
            . date('Ymd_His') . '.pdf';
        $this->clearOutputBuffers();
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    private function expiryBranchName(string $branchId): string
    {
        if ($branchId === '') {
            return 'All Branches';
        }
        if ($branchId === '0') {
            return 'No Assigned Branch';
        }

        $branch = $this->branchModel->find((int) $branchId);
        return (string) ($branch['branch_name'] ?? 'Unavailable Branch');
    }

    private function buildReportData(
        string $reportDateFrom,
        string $reportDateTo,
        string $forecastDateFrom,
        string $forecastDateTo,
        string $branchId = '',
        string $reportType = 'daily',
        string $forecastType = 'daily'
    ): array {
        $reportStart = $reportDateFrom . ' 00:00:00';
        $reportEnd = $reportDateTo . ' 23:59:59';

        $branches = $this->branchModel
            ->where('status', 'active')
            ->orderBy('branch_name', 'ASC')
            ->findAll();

        $branchName = 'All Branches';
        if (!empty($branchId)) {
            $branch = $this->branchModel->find($branchId);
            if ($branch) {
                $branchName = $branch['branch_name'];
            }
        }

        // Standard reports use only the separate report date range.
        $salesSummaryBuilder = $this->db->table('sales s')
            ->select("
                COUNT(s.id) as total_transactions,
                COALESCE(SUM(s.total_amount - COALESCE(ri.refunded_amount, 0)), 0) as gross_sales,
                COALESCE(SUM(s.discount_amount), 0) as total_discount,
                COALESCE(SUM(s.final_total - COALESCE(ri.refunded_amount, 0)), 0) as net_sales
            ")
            ->join($this->refundedAmountJoinSql(), 'ri.sale_id = s.id', 'left', false)
            ->where('s.sale_date >=', $reportStart)
            ->where('s.sale_date <=', $reportEnd)
            ->whereIn('s.status', self::REVENUE_STATUSES);

        if (!empty($branchId)) {
            $salesSummaryBuilder->where('s.branch_id', $branchId);
        }

        $salesSummary = $salesSummaryBuilder->get()->getRowArray();

        $profitSummaryBuilder = $this->db->table('sale_items si')
            ->select("
                COALESCE(SUM(si.cost_price_at_sale * si.quantity - COALESCE(rf.refunded_cogs, 0)), 0) as cogs,
                COALESCE(SUM(si.profit - COALESCE(rf.refunded_profit, 0)), 0) as gross_profit
            ")
            ->join('sales s', 's.id = si.sale_id')
            ->join($this->refundedCogsProfitJoinSql(), 'rf.sale_item_id = si.id', 'left', false)
            ->where('s.sale_date >=', $reportStart)
            ->where('s.sale_date <=', $reportEnd)
            ->whereIn('s.status', array_merge(self::REVENUE_STATUSES, ['refunded']));

        if (!empty($branchId)) {
            $profitSummaryBuilder->where('s.branch_id', $branchId);
        }

        $profitSummary = $profitSummaryBuilder->get()->getRowArray();

        $topProductsBuilder = $this->db->table('sale_items si')
            ->select("
                si.product_id,
                MAX(si.product_name_snapshot) as product_name_snapshot,
                SUM(si.quantity - COALESCE(rq.qty_refunded, 0)) as total_qty_sold,
                SUM((si.subtotal - COALESCE(si.discount_applied, 0)) - COALESCE(rq.refunded_subtotal, 0)) as total_sales
            ")
            ->join('sales s', 's.id = si.sale_id')
            ->join($this->refundedQtyJoinSql(), 'rq.sale_item_id = si.id', 'left', false)
            ->where('s.sale_date >=', $reportStart)
            ->where('s.sale_date <=', $reportEnd)
            ->whereIn('s.status', self::REVENUE_STATUSES);

        if (!empty($branchId)) {
            $topProductsBuilder->where('s.branch_id', $branchId);
        }

        $topProducts = $topProductsBuilder
            ->groupBy('si.product_id')
            ->having('SUM(si.quantity - COALESCE(rq.qty_refunded, 0)) > 0', null, false)
            ->orderBy('total_qty_sold', 'DESC')
            ->orderBy('total_sales', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $recentSalesBuilder = $this->db->table('sales s')
            ->select('s.*, u.full_name, b.branch_name, '
                . 'COALESCE(ri.refunded_amount, 0) AS refunded_amount, '
                . '(s.final_total - COALESCE(ri.refunded_amount, 0)) AS remaining_total', false)
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('branches b', 'b.id = s.branch_id', 'left')
            ->join($this->refundedAmountJoinSql(), 'ri.sale_id = s.id', 'left', false)
            ->where('s.sale_date >=', $reportStart)
            ->where('s.sale_date <=', $reportEnd)
            ->whereIn('s.status', self::REVENUE_STATUSES);

        if (!empty($branchId)) {
            $recentSalesBuilder->where('s.branch_id', $branchId);
        }

        $recentSales = $recentSalesBuilder
            ->orderBy('s.id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        // The sales charts follow the report range, while forecasting and
        // reorder suggestions use the independent forecasting range.
        $reportHistory = $this->buildPeriodHistory(
            $reportDateFrom,
            $reportDateTo,
            $branchId,
            $reportType
        );

        $forecastBaseHistory = $this->buildPeriodHistory(
            $forecastDateFrom,
            $forecastDateTo,
            $branchId,
            $forecastType
        );

        $holt = $this->computeHoltForecast($forecastBaseHistory, $forecastType);
        $reorderForecast = $this->reorderForecastService->generate(
            $forecastDateFrom,
            $forecastDateTo,
            $branchId,
            self::FORECAST_LEVEL_WEIGHT,
            self::FORECAST_TREND_WEIGHT,
            false
        );

        $netSales = (float) ($salesSummary['net_sales'] ?? 0);
        $transactionCount = (int) ($salesSummary['total_transactions'] ?? 0);
        $grossProfit = (float) ($profitSummary['gross_profit'] ?? 0);

        return [
            // New explicit names.
            'reportDateFrom' => $reportDateFrom,
            'reportDateTo' => $reportDateTo,
            'forecastDateFrom' => $forecastDateFrom,
            'forecastDateTo' => $forecastDateTo,

            // Backward-compatible aliases for any custom view code that may
            // still expect the old report-only variable names.
            'dateFrom' => $reportDateFrom,
            'dateTo' => $reportDateTo,

            'branchId' => $branchId,
            'branchName' => $branchName,
            'branches' => $branches,
            'reportType' => $reportType,
            'forecastType' => $forecastType,
            'cashMovements' => (new \App\Libraries\CashMovementReport($this->db))->generate($reportDateFrom, $reportDateTo, $branchId),
            'salesSummary' => $salesSummary,
            'profitSummary' => $profitSummary,
            'averageTransactionValue' => $transactionCount > 0 ? $netSales / $transactionCount : 0.0,
            'grossProfitMargin' => $netSales > 0 ? ($grossProfit / $netSales) * 100 : 0.0,
            'topProducts' => $topProducts,
            'recentSales' => $recentSales,
            'reportHistory' => $reportHistory,
            'forecastHistory' => $holt['reportHistory'],
            'futureForecast' => $holt['futureForecast'],
            'nextForecastValue' => $holt['nextForecastValue'],
            'reorderForecast' => $reorderForecast,
            'reorderCoverageDays' => ReorderForecastService::REORDER_COVERAGE_DAYS,
            'forecastingDataLog' => $this->getForecastingDataLog(),
        ];
    }

    /**
     * Builds a complete, evenly spaced report series for the requested
     * date range. Empty periods are included as zero values so charts and
     * forecasting does not compress gaps in the sales timeline.
     */
    private function buildPeriodHistory(
        string $dateFrom,
        string $dateTo,
        string $branchId,
        string $forecastType
    ): array {
        $start = $dateFrom . ' 00:00:00';
        $end = $dateTo . ' 23:59:59';
        $salesPeriodRows = $this->getSalesPeriodRows($start, $end, $branchId, $forecastType);
        $cogsPeriodRows = $this->getCogsPeriodRows($start, $end, $branchId, $forecastType);
        $periodMap = [];

        foreach ($this->generatePeriodSequence($dateFrom, $dateTo, $forecastType) as $key => $seed) {
            $periodMap[$key] = $seed;
        }

        foreach ($salesPeriodRows as $row) {
            $key = (string) $row['period_key'];

            if (!isset($periodMap[$key])) {
                $periodMap[$key] = $this->emptyPeriodRow($key, $row['period_date'], $row['report_date']);
            }

            $periodMap[$key]['period_date'] = $row['period_date'];
            $periodMap[$key]['report_date'] = $row['report_date'];
            $periodMap[$key]['total_transactions'] = (int) ($row['total_transactions'] ?? 0);
            $periodMap[$key]['gross_sales'] = (float) ($row['gross_sales'] ?? 0);
            $periodMap[$key]['total_discount'] = (float) ($row['total_discount'] ?? 0);
            $periodMap[$key]['net_sales'] = (float) ($row['net_sales'] ?? 0);
        }

        foreach ($cogsPeriodRows as $row) {
            $key = (string) $row['period_key'];

            if (!isset($periodMap[$key])) {
                $periodMap[$key] = $this->emptyPeriodRow($key, $row['period_date'], $row['report_date']);
            }

            $periodMap[$key]['cogs'] = (float) ($row['cogs'] ?? 0);
            $periodMap[$key]['gross_profit'] = (float) ($row['gross_profit'] ?? 0);
        }

        ksort($periodMap);
        return array_values($periodMap);
    }

    /**
     * Raw contents of forecasting_data, most recent first — not a
     * comparison or an accuracy metric, just what's actually sitting in
     * the table right now. LEFT JOIN rather than an inner join since
     * product_id is nullable in the schema (kept defensive even though
     * every row currently written is per-product, via the reorder
     * forecast's same-day cache + history).
     */
    private function getForecastingDataLog(): array
    {
        return $this->db->query("
            SELECT
                fd.id, fd.product_id, fd.forecast_month, fd.predicted_quantity,
                fd.predicted_revenue, fd.method_used, fd.generated_at,
                p.product_name, p.sku
            FROM forecasting_data fd
            LEFT JOIN products p ON p.id = fd.product_id
            ORDER BY fd.generated_at DESC, fd.id DESC
            LIMIT 100
        ")->getResultArray();
    }

    private function getSalesPeriodRows(string $start, string $end, string $branchId = '', string $forecastType = 'daily'): array
    {
        $groupExpr = $this->periodGroupExprSql($forecastType);
        $keyExpr = $this->periodKeyExprSql($forecastType);
        $statusList = "'" . implode("','", self::REVENUE_STATUSES) . "'";

        $binds = [$start, $end];
        $branchFilter = '';
        if (!empty($branchId)) {
            $branchFilter = 'AND s.branch_id = ?';
            $binds[] = $branchId;
        }

        $sql = "
            SELECT
                {$keyExpr} as period_key,
                MIN(t.pd) as period_date,
                {$keyExpr} as report_date,
                SUM(t.txn) as total_transactions,
                SUM(t.gross) as gross_sales,
                SUM(t.disc) as total_discount,
                SUM(t.net) as net_sales
            FROM (
                SELECT
                    {$groupExpr} as grp,
                    DATE(s.sale_date) as pd,
                    1 as txn,
                    (s.total_amount - COALESCE(ri.refunded_amount, 0)) as gross,
                    s.discount_amount as disc,
                    (s.final_total - COALESCE(ri.refunded_amount, 0)) as net
                FROM sales s
                LEFT JOIN {$this->refundedAmountJoinSql()} ON ri.sale_id = s.id
                WHERE s.sale_date >= ? AND s.sale_date <= ?
                    AND s.status IN ({$statusList})
                    {$branchFilter}
            ) t
            GROUP BY t.grp
            ORDER BY period_date ASC
        ";

        return $this->db->query($sql, $binds)->getResultArray();
    }

    private function getCogsPeriodRows(string $start, string $end, string $branchId = '', string $forecastType = 'daily'): array
    {
        $groupExpr = $this->periodGroupExprSql($forecastType);
        $keyExpr = $this->periodKeyExprSql($forecastType);
        $statusList = "'" . implode("','", array_merge(self::REVENUE_STATUSES, ['refunded'])) . "'";

        $binds = [$start, $end];
        $branchFilter = '';
        if (!empty($branchId)) {
            $branchFilter = 'AND s.branch_id = ?';
            $binds[] = $branchId;
        }

        $sql = "
            SELECT
                {$keyExpr} as period_key,
                MIN(t.pd) as period_date,
                {$keyExpr} as report_date,
                SUM(t.cogs) as cogs,
                SUM(t.gross_profit) as gross_profit
            FROM (
                SELECT
                    {$groupExpr} as grp,
                    DATE(s.sale_date) as pd,
                    (si.cost_price_at_sale * si.quantity - COALESCE(rf.refunded_cogs, 0)) as cogs,
                    (si.profit - COALESCE(rf.refunded_profit, 0)) as gross_profit
                FROM sale_items si
                JOIN sales s ON s.id = si.sale_id
                LEFT JOIN {$this->refundedCogsProfitJoinSql()} ON rf.sale_item_id = si.id
                WHERE s.sale_date >= ? AND s.sale_date <= ?
                    AND s.status IN ({$statusList})
                    {$branchFilter}
            ) t
            GROUP BY t.grp
            ORDER BY period_date ASC
        ";

        return $this->db->query($sql, $binds)->getResultArray();
    }

    /**
     * Builds a zero-value row keyed by period_key, matching the shape used
     * by buildReportData()'s $periodMap. Used both to seed the full date
     * range up front and as a fallback if a cogs row shows up for a period
     * the sales query somehow didn't produce.
     */
    private function emptyPeriodRow(string $key, string $periodDate, string $reportDate): array
    {
        return [
            'period_key' => $key,
            'period_date' => $periodDate,
            'report_date' => $reportDate,
            'total_transactions' => 0,
            'gross_sales' => 0.0,
            'total_discount' => 0.0,
            'net_sales' => 0.0,
            'cogs' => 0.0,
            'gross_profit' => 0.0,
        ];
    }

    /**
     * Generates every period_key in [$dateFrom, $dateTo] for the given
     * granularity, even ones with zero sales activity. Holt's method
     * assumes evenly-spaced observations, so without this, a day/week/
     * month with no sales just vanishes from the series instead of
     * counting as 0 — which skews the trend estimate.
     *
     * Keys are formatted to match the SQL grouping in getSalesPeriodRows()/
     * getCogsPeriodRows() exactly, so real data rows merge onto these seeds
     * without creating duplicate buckets.
     */
    private function generatePeriodSequence(string $dateFrom, string $dateTo, string $forecastType): array
    {
        $periods = [];
        $start = new \DateTime($dateFrom);
        $end = new \DateTime($dateTo);

        switch ($forecastType) {
            case 'weekly':
                $cursor = clone $start;
                $cursor->modify('monday this week');
                while ($cursor <= $end) {
                    // 'o' = ISO-8601 week-numbering year, not the calendar
                    // year ('Y') — required so late-Dec/early-Jan dates land
                    // in the same bucket as the matching SQL-side YEARWEEK(,3)
                    // query instead of a mismatched, made-up "Y-W53"/"Y-W01".
                    $key = $cursor->format('o') . '-W' . $cursor->format('W');
                    $periods[$key] = $this->emptyPeriodRow($key, $cursor->format('Y-m-d'), $key);
                    $cursor->modify('+1 week');
                }
                break;

            case 'monthly':
                $cursor = new \DateTime($start->format('Y-m-01'));
                while ($cursor <= $end) {
                    $key = $cursor->format('Y-m');
                    $periods[$key] = $this->emptyPeriodRow($key, $cursor->format('Y-m-d'), $key);
                    $cursor->modify('+1 month');
                }
                break;

            case 'daily':
            default:
                $cursor = clone $start;
                while ($cursor <= $end) {
                    $key = $cursor->format('Y-m-d');
                    $periods[$key] = $this->emptyPeriodRow($key, $key, $key);
                    $cursor->modify('+1 day');
                }
                break;
        }

        return $periods;
    }

    /**
     * The actual Holt's Linear Trend math, kept separate from
     * computeHoltForecast() so it's clear this is reusable against any
     * evenly-spaced numeric series — not just the revenue forecast's
     * net_sales column. (ReorderForecastService has its own copy of this
     * same routine for the per-product reorder forecast — see that
     * class's docblock for why it's duplicated rather than shared.)
     *
     * Returns the fitted (one-step-ahead) value for every input index,
     * plus the final level/trend so the caller can project as many future
     * periods as it needs.
     */
    private function holtSmooth(array $values, float $levelWeight, float $trendWeight): array
    {
        $fitted = [];
        $level = 0.0;
        $trend = 0.0;

        if (!empty($values)) {
            $level = $values[0];
            $trend = count($values) > 1 ? ($values[1] - $values[0]) : 0;

            $fitted[0] = $values[0];

            for ($i = 1; $i < count($values); $i++) {
                $fitted[$i] = $level + $trend;

                $previousLevel = $level;
                $level = ($levelWeight * $values[$i]) + ((1 - $levelWeight) * ($level + $trend));
                $trend = ($trendWeight * ($level - $previousLevel)) + ((1 - $trendWeight) * $trend);
            }
        }

        return ['fitted' => $fitted, 'level' => $level, 'trend' => $trend];
    }

    private function computeHoltForecast(array $reportHistory, string $forecastType = 'daily'): array
    {
        $actualSales = array_map(function ($row) {
            return (float) ($row['net_sales'] ?? 0);
        }, $reportHistory);

        $smoothed = $this->holtSmooth(
            $actualSales,
            self::FORECAST_LEVEL_WEIGHT,
            self::FORECAST_TREND_WEIGHT
        );
        $holtForecast = $smoothed['fitted'];
        $level = $smoothed['level'];
        $trend = $smoothed['trend'];
        $futureForecast = [];

        foreach ($reportHistory as $i => $row) {
            $reportHistory[$i]['forecast'] = round(max(0.0, (float) ($holtForecast[$i] ?? 0)), 2);
        }

        $futurePeriods = 7;
        $lastPeriodDate = !empty($reportHistory) ? (end($reportHistory)['period_date'] ?? date('Y-m-d')) : date('Y-m-d');

        for ($m = 1; $m <= $futurePeriods; $m++) {
            switch ($forecastType) {
                case 'weekly':
                    $forecastLabel = date('o-\WW', strtotime($lastPeriodDate . ' +' . $m . ' week'));
                    break;

                case 'monthly':
                    $forecastLabel = date('Y-m', strtotime(date('Y-m-01', strtotime($lastPeriodDate)) . ' +' . $m . ' month'));
                    break;

                case 'daily':
                default:
                    $forecastLabel = date('Y-m-d', strtotime($lastPeriodDate . " +{$m} day"));
                    break;
            }

            $futureForecast[] = [
                'forecast_date' => $forecastLabel,
                // Clamped at 0 — an unclamped linear trend can otherwise
                // extrapolate to negative "sales" during a declining stretch,
                // which is meaningless for a peso figure.
                'forecast_value' => round(max(0.0, $level + ($m * $trend)), 2)
            ];
        }

        return [
            'reportHistory' => $reportHistory,
            'futureForecast' => $futureForecast,
            'nextForecastValue' => !empty($futureForecast) ? (float) $futureForecast[0]['forecast_value'] : 0,
        ];
    }
}
